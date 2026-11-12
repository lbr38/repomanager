<?php

namespace Controllers\Api\V3\Repository\Type;

use Controllers\Api\HttpException;
use Controllers\Api\V3\Request;
use Controllers\Mirror\Mirror as MirrorController;
use Controllers\Mirror\Task\Params;
use Controllers\User\Permission\Repo as RepoPermission;

/**
 *  Mirror: exact copy of a source repository
 */
class Mirror extends Type
{
    private int $id;
    private array $mirror;

    public function __construct(int $id)
    {
        $mirror = (new MirrorController())->getById($id);

        if (empty($mirror) or !RepoPermission::allowedToViewMirror($id)) {
            throw new HttpException(404, 'Mirror #' . $id . ' not found');
        }

        $this->id = $id;
        $this->mirror = $mirror;
    }

    public function getType(): string
    {
        return 'mirror';
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     *  Return all the mirrors the user is allowed to view
     */
    public static function list(): array
    {
        $mirrors = [];
        $groupNames = self::groupNames();
        $groupsIds = (new MirrorController())->getGroupsIds();

        foreach ((new MirrorController())->get() as $mirror) {
            if (!RepoPermission::allowedToViewMirror((int) $mirror['Id'])) {
                continue;
            }

            $mirrors[] = self::formatRow($mirror, $groupNames, $groupsIds[$mirror['Id']] ?? null);
        }

        return $mirrors;
    }

    public function format(): array
    {
        $mirror = self::formatRow($this->mirror, self::groupNames(), (new MirrorController())->getGroupsIds()[$this->id] ?? null);
        $mirror['snapshots'] = $this->formatSnapshots();

        return $mirror;
    }

    public function formatSnapshots(): array
    {
        $snapshots = [];

        foreach ((new \Controllers\Mirror\Snapshot())->getByMirrorId($this->id) as $snapshot) {
            $snapshots[] = $this->formatSnapshotRow($snapshot);
        }

        return $snapshots;
    }

    public function formatSnapshot(int $snapId): array
    {
        return $this->formatSnapshotRow($this->getSnapshot($snapId));
    }

    /**
     *  Return the task parameters to create a mirror from the request
     */
    public static function createParams(Request $request, array $schedule): array
    {
        $packageType = $request->string('package_type');

        if (!in_array($packageType, MirrorController::PACKAGE_TYPES, true)) {
            throw HttpException::invalidParameter('Invalid package type ' . $packageType . ' for a mirror (' . implode(', ', MirrorController::PACKAGE_TYPES) . ')', '#/package_type');
        }

        $keys = ['package_type', 'name', 'source', 'arch', 'gpg_check', 'environments', 'description', 'tags', 'group', 'retention', 'retention_keep_all', 'schedule'];
        $keys = array_merge($keys, ['rpm' => ['releasever'], 'deb' => ['dists', 'components'], 'arch' => ['repos']][$packageType]);

        $request->allow($keys);

        $values = [
            'package-type' => $packageType,
            'name'         => $request->string('name', ''),
            'source'       => $request->string('source'),
            'arch'         => $request->list('arch'),
            'gpg-check'    => $request->boolString('gpg_check', true),
            'env'          => $request->list('environments', []),
            'description'  => $request->string('description', ''),
            'tags'         => $request->list('tags', []),
            // Mirrors groups are specified by Id in the forms
            'group'        => self::groupId($request->string('group', ''))
        ];

        if ($packageType == 'rpm') {
            $values['releasever'] = $request->string('releasever');
        }

        // Flat repositories have no distribution nor component
        if ($packageType == 'deb') {
            $values['dist'] = $request->list('dists', []);
            $values['components'] = $request->list('components', []);
        }

        if ($packageType == 'arch') {
            $values['repos'] = $request->list('repos');
        }

        return Params::create(array_merge($values, self::retentionParams($request)), $schedule);
    }

    public function edit(Request $request): void
    {
        $editController = new \Controllers\Mirror\Edit();

        if (!\Controllers\User\Permission\Mirror::allowedAction('edit')) {
            throw new HttpException(403, 'You are not allowed to edit mirrors');
        }

        $request->allow(self::EDIT_KEYS);

        $params = self::editParams($request);

        self::checkNotEmpty($params);

        $params['mirror-id'] = strval($this->id);

        $editController->validate($params);
        $editController->edit($params);
    }

    public function deleteParams(array $schedule): array
    {
        return [Params::delete($this->id, null, $schedule)];
    }

    public function newSnapshotParams(Request $request, array $schedule): array
    {
        $request->allow(['environments', 'schedule']);

        return Params::update($this->id, $request->list('environments', []), $schedule);
    }

    public function deleteSnapshotParams(int $snapId, array $schedule): array
    {
        $this->getSnapshot($snapId);

        return Params::delete($this->id, $snapId, $schedule);
    }

    public function envParams(int $snapId, array $envs, array $schedule): array
    {
        $this->getSnapshot($snapId);

        return Params::env($this->id, $snapId, $envs, $schedule);
    }

    public function removeEnvParams(int $snapId, string $env): array
    {
        $this->getSnapshot($snapId);

        // An environment points to one snapshot of the mirror
        $envs = (new \Controllers\Mirror\Environment())->getByMirrorId($this->id);

        if (!isset($envs[$env]) or (int) $envs[$env] != $snapId) {
            throw new HttpException(404, 'Environment ' . $env . ' does not point to snapshot #' . $snapId . ' of ' . $this->getLabel());
        }

        return Params::removeEnv($this->id, $env);
    }

    /**
     *  Return an active snapshot of the mirror, throw a 404 error if it is not an active snapshot of the mirror
     */
    private function getSnapshot(int $snapId): array
    {
        $snapshot = (new \Controllers\Mirror\Snapshot())->getById($snapId);

        if (empty($snapshot) or (int) $snapshot['Id_mirror'] != $this->id or $snapshot['Status'] != 'active') {
            throw new HttpException(404, 'Snapshot #' . $snapId . ' of ' . $this->getLabel() . ' not found');
        }

        return $snapshot;
    }

    /**
     *  Return the mirror details from its database row
     */
    private static function formatRow(array $mirror, array $groupNames, mixed $groupId): array
    {
        $packageType = $mirror['Package_type'];
        $params = MirrorController::getParams($mirror);

        return array_merge([
            'id'           => (int) $mirror['Id'],
            'type'         => 'mirror',
            'package_type' => $packageType,
            'name'         => $mirror['Name'],
            'releasever'   => $packageType == 'rpm' ? $mirror['Releasever'] : null,
            'dists'        => $packageType == 'deb' ? array_values($params['dists'] ?? []) : null,
            'components'   => $packageType == 'deb' ? array_values($params['components'] ?? []) : null,
            'repos'        => $packageType == 'arch' ? array_values($params['repos'] ?? []) : null,
            'arch'         => array_values($params['arch'] ?? []),
            'gpg_check'    => ($params['gpg-check'] ?? 'true') == 'true',
            'source'       => $mirror['Source'],
            'description'  => (string) ($mirror['Description'] ?? ''),
            'tags'         => self::formatTags($mirror['Tags']),
            // A mirror is member of one group at most
            'groups'       => ($groupId !== null && isset($groupNames[$groupId])) ? [$groupNames[$groupId]] : []
        ], self::formatRetention($mirror['Retention']));
    }

    /**
     *  Return a snapshot details from its database row, with the environments pointing to it and their URL
     */
    private function formatSnapshotRow(array $snapshot): array
    {
        $environments = [];
        $url = WWW_MIRRORS_DIR_URL . '/' . MirrorController::getDirOf($this->mirror);

        foreach ((new \Controllers\Mirror\Environment())->getBySnapId((int) $snapshot['Id']) as $env) {
            $environments[] = [
                'name' => $env,
                'url'  => $url . '/' . $env
            ];
        }

        return [
            'id'           => (int) $snapshot['Id'],
            'date'         => $snapshot['Date'],
            'time'         => $snapshot['Time'],
            'size'         => $snapshot['Size'] === null ? null : (int) $snapshot['Size'],
            'environments' => $environments
        ];
    }
}
