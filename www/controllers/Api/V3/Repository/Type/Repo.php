<?php

namespace Controllers\Api\V3\Repository\Type;

use JsonException;
use Controllers\Api\HttpException;
use Controllers\Api\V3\Request;
use Controllers\Repo\Repo as RepoController;
use Controllers\Repo\Listing;
use Controllers\Repo\Task\Params;
use Controllers\User\Permission\Repo as RepoPermission;

/**
 *  Curated or local repository
 */
class Repo extends Type
{
    private const TYPES = ['curated', 'local'];
    private const PACKAGE_TYPES = ['rpm', 'deb'];

    private string $type;
    private int $id;
    private array $row;

    public function __construct(string $type, int $id)
    {
        $rows = (new Listing())->listRepositories($id);

        // A repository without active snapshot does not exist anymore (it is deleted with its last snapshot)
        if (empty($rows) or !RepoPermission::allowedToView($id)) {
            throw new HttpException(404, ucfirst($type) . ' repository #' . $id . ' not found');
        }

        if ($rows[0]['Type'] != $type) {
            throw new HttpException(404, 'Repository #' . $id . ' is not a ' . $type . ' repository but a ' . $rows[0]['Type'] . ' repository');
        }

        $this->type = $type;
        $this->id = $id;
        $this->row = $rows[0];
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     *  Return all the curated and / or local repositories the user is allowed to view
     */
    public static function list(string|null $type = null): array
    {
        $repositories = [];
        $groupNames = self::groupNames();
        $groupsIds = (new \Controllers\Group\Repo())->getReposGroupsIds();

        foreach ((new Listing())->listRepositories() as $row) {
            if ($type !== null and $row['Type'] != $type) {
                continue;
            }

            if (!RepoPermission::allowedToView((int) $row['Id'])) {
                continue;
            }

            $repositories[] = self::formatRow($row, $groupNames, $groupsIds[$row['Id']] ?? []);
        }

        return $repositories;
    }

    public function format(): array
    {
        $groupsIds = (new \Controllers\Group\Repo())->getRepoGroupsIds($this->id);

        $repository = self::formatRow($this->row, self::groupNames(), $groupsIds);
        $repository['snapshots'] = $this->formatSnapshots();

        return $repository;
    }

    public function formatSnapshots(): array
    {
        $snapshots = [];

        foreach ((new Listing())->listSnapshots($this->id) as $snapshot) {
            $snapshots[] = $this->formatSnapshotRow($snapshot);
        }

        return $snapshots;
    }

    public function formatSnapshot(int $snapId): array
    {
        foreach ((new Listing())->listSnapshots($this->id) as $snapshot) {
            if ((int) $snapshot['Id'] == $snapId) {
                return $this->formatSnapshotRow($snapshot);
            }
        }

        throw new HttpException(404, 'Snapshot #' . $snapId . ' of ' . $this->getLabel() . ' not found');
    }

    /**
     *  Return the repository controller loaded with a snapshot of the repository (its latest snapshot by default)
     *  Throw a 404 error if the snapshot is not an active snapshot of the repository
     */
    public function getController(int|null $snapId = null, int|null $envId = null): RepoController
    {
        $repoController = new RepoController();

        if ($snapId === null) {
            $snapId = $repoController->getLatestSnapId($this->id);
        }

        if (empty($snapId) or !in_array($snapId, array_map('intval', array_column((new Listing())->listSnapshots($this->id), 'Id')), true)) {
            throw new HttpException(404, 'Snapshot #' . $snapId . ' of ' . $this->getLabel() . ' not found');
        }

        $repoController->getAllById($this->id, $snapId, $envId);

        return $repoController;
    }

    /**
     *  Return the task parameters to create curated or local repositories from the request
     *  A repository is created for each release version (rpm) or for each distribution and component (deb)
     */
    public static function createParams(string $type, Request $request, array $schedule): array
    {
        $packageType = $request->string('package_type');

        if (!in_array($packageType, self::PACKAGE_TYPES, true)) {
            throw HttpException::invalidParameter('Invalid package type ' . $packageType . ' for a ' . $type . ' repository (' . implode(' or ', self::PACKAGE_TYPES) . ')', '#/package_type');
        }

        $keys = ['package_type', 'name', 'arch', 'environments', 'description', 'tags', 'group', 'gpg_sign', 'retention', 'retention_keep_all', 'schedule'];
        $keys = array_merge($keys, $packageType == 'rpm' ? ['releasever'] : ['dist', 'section']);

        if ($type == 'curated') {
            $keys = array_merge($keys, ['source', 'gpg_check', 'advanced_params']);
        }

        $request->allow($keys);

        $values = [
            'repo-type'    => $type,
            'package-type' => $packageType,
            // The name of a local repository is required, a curated repository is named after its source repository by default
            'name'         => $type == 'local' ? $request->string('name') : $request->string('name', ''),
            'env'          => $request->list('environments', []),
            'description'  => $request->string('description', ''),
            'tags'         => $request->list('tags', []),
            'group'        => $request->string('group', '')
        ];

        if ($packageType == 'rpm') {
            $values['releasever'] = $request->list('releasever');
        }

        if ($packageType == 'deb') {
            $values['dist'] = $request->list('dist');
            $values['section'] = $request->list('section');
        }

        if ($request->has('arch')) {
            $values['arch'] = $request->list('arch');
        }

        if ($request->has('gpg_sign')) {
            $values['gpg-sign'] = $request->boolString('gpg_sign');
        }

        // The source repository of a curated repository is required, its name is used as repository name if not specified
        if ($type == 'curated') {
            $values['source'] = $request->string('source');
            $values['gpg-check'] = $request->boolString('gpg_check', true);
            $values['advanced-params'] = self::advancedParams($request, $packageType);
        }

        return Params::create(array_merge($values, self::retentionParams($request)), $schedule);
    }

    public function edit(Request $request): void
    {
        $editForm = new \Controllers\Repo\Edit\Form();

        if (!RepoPermission::allowedAction('edit')) {
            throw new HttpException(403, 'You are not allowed to edit repositories');
        }

        $request->allow(array_merge(self::EDIT_KEYS, $this->type == 'curated' ? ['source'] : []));

        $params = self::editParams($request);

        if ($request->has('source')) {
            $params['source'] = $request->string('source');
        }

        self::checkNotEmpty($params);

        $params['repo-id'] = strval($this->id);

        $editForm->validate($params);
        $editForm->edit($params);
    }

    public function deleteParams(array $schedule): array
    {
        $tasksParams = [];

        // The repository is deleted with its last snapshot
        foreach ((new Listing())->listSnapshots($this->id) as $snapshot) {
            $tasksParams[] = Params::delete($this->getController((int) $snapshot['Id']), $schedule);
        }

        return $tasksParams;
    }

    public function newSnapshotParams(Request $request, array $schedule): array
    {
        $values = [];
        $repoController = $this->getController();

        $request->allow(array_merge(['arch', 'gpg_sign', 'environments', 'schedule'], $this->type == 'curated' ? ['gpg_check', 'advanced_params'] : []));

        if ($request->has('arch')) {
            $values['arch'] = $request->list('arch');
        }

        if ($request->has('gpg_sign')) {
            $values['gpg-sign'] = $request->boolString('gpg_sign');
        }

        if ($request->has('gpg_check')) {
            $values['gpg-check'] = $request->boolString('gpg_check');
        }

        if ($request->has('environments')) {
            $values['env'] = $request->list('environments');
        }

        if ($this->type == 'curated') {
            $values['advanced-params'] = self::advancedParams($request, $repoController->getPackageType());
        }

        return Params::update($repoController, $values, $schedule);
    }

    public function deleteSnapshotParams(int $snapId, array $schedule): array
    {
        return Params::delete($this->getController($snapId), $schedule);
    }

    public function envParams(int $snapId, array $envs, array $schedule): array
    {
        return Params::env($this->getController($snapId), $envs, $schedule);
    }

    public function removeEnvParams(int $snapId, string $env): array
    {
        $this->getController($snapId);

        $envIds = (new \Controllers\Repo\Environment())->getIdsBySnapId($snapId);

        if (!isset($envIds[$env])) {
            throw new HttpException(404, 'Environment ' . $env . ' does not point to snapshot #' . $snapId . ' of ' . $this->getLabel());
        }

        return Params::removeEnv($this->getController($snapId, $envIds[$env]));
    }

    /**
     *  Return the task parameters to rebuild the metadata of a snapshot
     */
    public function rebuildParams(int $snapId, Request $request, array $schedule): array
    {
        $request->allow(['gpg_sign', 'schedule']);

        $gpgSign = $request->has('gpg_sign') ? $request->boolString('gpg_sign') : null;

        return Params::rebuild($this->getController($snapId), $gpgSign, $schedule);
    }

    /**
     *  Return the task parameters to duplicate a snapshot into a new repository
     */
    public function duplicateParams(int $snapId, Request $request, array $schedule): array
    {
        $request->allow(['name', 'environments', 'description', 'tags', 'group', 'schedule']);

        return Params::duplicate($this->getController($snapId), [
            'name'        => $request->string('name'),
            'env'         => $request->list('environments', []),
            'description' => $request->string('description', ''),
            'tags'        => $request->list('tags', []),
            'group'       => $request->string('group', '')
        ], $schedule);
    }

    /**
     *  Return the task parameters to rename the repository
     */
    public function renameParams(Request $request, array $schedule): array
    {
        $request->allow(['name', 'schedule']);

        return Params::rename($this->getController(), $request->string('name'), $schedule);
    }

    /**
     *  Return the repository details from its database row
     */
    private static function formatRow(array $row, array $groupNames, array $groupsIds): array
    {
        $packageType = $row['Package_type'];

        $groups = [];

        foreach ($groupsIds as $groupId) {
            if (isset($groupNames[$groupId])) {
                $groups[] = $groupNames[$groupId];
            }
        }

        return array_merge([
            'id'           => (int) $row['Id'],
            'type'         => $row['Type'],
            'package_type' => $packageType,
            'name'         => $row['Name'],
            'releasever'   => $packageType == 'rpm' ? $row['Releasever'] : null,
            'dist'         => $packageType == 'deb' ? $row['Dist'] : null,
            'section'      => $packageType == 'deb' ? $row['Section'] : null,
            'source'       => $row['Type'] == 'curated' ? $row['Source'] : null,
            'description'  => (string) ($row['Description'] ?? ''),
            'tags'         => self::formatTags($row['Tags']),
            'groups'       => $groups
        ], self::formatRetention($row['Retention']));
    }

    /**
     *  Return a snapshot details from its database row (see Listing::listSnapshots())
     */
    private function formatSnapshotRow(array $snapshot): array
    {
        $packageType = $this->row['Package_type'];
        $environments = [];

        try {
            $advancedParams = empty($snapshot['Advanced_params']) ? [] : json_decode($snapshot['Advanced_params'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $advancedParams = [];
        }

        foreach (array_keys((new \Controllers\Repo\Environment())->getIdsBySnapId((int) $snapshot['Id'])) as $env) {
            $environments[] = [
                'name' => $env,
                'url'  => $this->getUrl($env)
            ];
        }

        return [
            'id'              => (int) $snapshot['Id'],
            'date'            => $snapshot['Date'],
            'time'            => $snapshot['Time'],
            'signed'          => $snapshot['Signed'] == 'true',
            'arch'            => self::formatTags($snapshot['Arch']),
            'size'            => $snapshot['Size'] === null ? null : (int) $snapshot['Size'],
            // Metadata rebuild status: null, needed, running or failed
            'rebuild'         => empty($snapshot['Reconstruct']) ? null : $snapshot['Reconstruct'],
            'advanced_params' => self::formatAdvancedParams($packageType, (array) $advancedParams),
            'environments'    => $environments
        ];
    }

    /**
     *  Return the URL of the repository through an environment (or a snapshot directory)
     */
    private function getUrl(string $env): string
    {
        if ($this->row['Package_type'] == 'deb') {
            return WWW_REPOS_DIR_URL . '/deb/' . $this->row['Name'] . '/' . $this->row['Dist'] . '/' . $this->row['Section'] . '/' . $env;
        }

        return WWW_REPOS_DIR_URL . '/rpm/' . $this->row['Name'] . '/' . $this->row['Releasever'] . '/' . $env;
    }

    /**
     *  Return the advanced parameters of a snapshot, in the API format
     */
    private static function formatAdvancedParams(string $packageType, array $params): array
    {
        $params = Params::advancedParams($packageType, [], $params);

        $result = [
            'packages' => [
                'keep_latest' => $params['packages']['keep-latest'] === '' ? null : (int) $params['packages']['keep-latest'],
                'include'     => $params['packages']['include'],
                'exclude'     => $params['packages']['exclude']
            ]
        ];

        if ($packageType == 'rpm') {
            foreach ($params['metadata-sync'] as $file => $sync) {
                $result['metadata_sync'][$file] = $sync == 'true';
            }
        }

        if ($packageType == 'deb') {
            $result['metadata_custom_fields'] = $params['metadata-custom-fields'];
        }

        return $result;
    }

    /**
     *  Return the advanced parameters from the request, in the format of the tasks parameters
     *  Only the specified values are returned, the others get the default or current values (see Params::advancedParams())
     */
    private static function advancedParams(Request $request, string $packageType): array
    {
        $values = [];

        if (!$request->has('advanced_params')) {
            return $values;
        }

        $advanced = $request->sub('advanced_params');
        $advanced->allow(['packages', $packageType == 'rpm' ? 'metadata_sync' : 'metadata_custom_fields']);

        if ($advanced->has('packages')) {
            $packages = $advanced->sub('packages');
            $packages->allow(['keep_latest', 'include', 'exclude']);

            if ($packages->has('keep_latest')) {
                $values['packages']['keep-latest'] = strval($packages->int('keep_latest', null, true) ?? '');
            }

            foreach (['include', 'exclude'] as $key) {
                if ($packages->has($key)) {
                    $values['packages'][$key] = $packages->list($key, []);
                }
            }
        }

        if ($packageType == 'rpm' and $advanced->has('metadata_sync')) {
            $metadataSync = $advanced->sub('metadata_sync');
            $metadataSync->allow(['comps', 'modules', 'updateinfo']);

            foreach (['comps', 'modules', 'updateinfo'] as $file) {
                if ($metadataSync->has($file)) {
                    $values['metadata-sync'][$file] = $metadataSync->boolString($file);
                }
            }
        }

        if ($packageType == 'deb' and $advanced->has('metadata_custom_fields')) {
            $customFields = $advanced->sub('metadata_custom_fields');
            $customFields->allow(['origin', 'label', 'description']);

            foreach (['origin', 'label', 'description'] as $field) {
                if ($customFields->has($field)) {
                    $values['metadata-custom-fields'][$field] = $customFields->string($field, '');
                }
            }
        }

        return $values;
    }
}
