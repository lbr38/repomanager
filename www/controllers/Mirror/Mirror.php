<?php

namespace Controllers\Mirror;

use Controllers\Utils\Validate;
use JsonException;
use Exception;

class Mirror
{
    private $model;

    /**
     *  Package types supported by mirrors
     */
    public const PACKAGE_TYPES = ['deb', 'rpm', 'arch', 'alpine', 'openbsd'];

    /**
     *  Package types whose mirrors are qualified by a release version, stored in the Releasever column:
     *  the rpm release version ($releasever) or the alpine branch (e.g. v3.24, edge)
     */
    public const RELEASEVER_TYPES = ['rpm', 'alpine'];

    /**
     *  URL path under which mirrors are published (see nginx configuration)
     */
    public const URL_PATH = '/mirror';

    public function __construct()
    {
        $this->model = new \Models\Mirror\Mirror();
    }

    /**
     *  Return all mirrors
     */
    public function get(): array
    {
        return $this->model->get();
    }

    /**
     *  Return mirror details by Id
     */
    public function getById(int $id): array
    {
        return $this->model->getById($id);
    }

    /**
     *  Return the mirror displayed by a page (e.g. /repository/mirror/<id>), with its label and accent color
     *  Throw an exception if the Id is invalid or if the user is not allowed to view the mirror
     */
    public function getForPage(string $id): array
    {
        if (!is_numeric($id)) {
            throw new Exception('invalid mirror ID specified.');
        }

        $mirror = $this->getById((int) $id);

        if (empty($mirror) or !\Controllers\User\Permission\Repo::allowedToViewMirror((int) $mirror['Id'])) {
            throw new Exception('mirror #' . htmlspecialchars($id) . ' does not exist.');
        }

        $mirror['label'] = self::getFullLabel($mirror);
        $mirror['accent'] = self::getAccent($mirror['Package_type']);

        return $mirror;
    }

    /**
     *  Return mirror Id by package type, name and release version (rpm release version or alpine branch, empty otherwise), or null if it does not exist
     */
    public function getId(string $packageType, string $name, string $releasever = ''): int|null
    {
        return $this->model->getId($packageType, $name, $releasever);
    }

    /**
     *  Return decoded mirror params (dist, components, releasever, arch...)
     */
    public static function getParams(array $mirror): array
    {
        if (empty($mirror['Params'])) {
            return [];
        }

        try {
            return json_decode($mirror['Params'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Could not decode mirror parameters: ' . $e->getMessage());
        }
    }

    /**
     *  Return the release version of a mirror from its task or mirror parameters, stored in the Releasever column:
     *  'releasever' parameter of a rpm mirror, 'branch' parameter of an alpine mirror, empty for other package types
     */
    public static function getReleaseverParam(string $packageType, array $params): string
    {
        if ($packageType == 'rpm') {
            return (string) ($params['releasever'] ?? '');
        }

        if ($packageType == 'alpine') {
            return (string) ($params['branch'] ?? '');
        }

        return '';
    }

    /**
     *  Add a new mirror in database and return its Id
     */
    public function add(string $name, string $releasever, string $packageType, string $source, array $params, string $description = ''): int
    {
        self::checkName($name);

        if (!in_array($packageType, self::PACKAGE_TYPES)) {
            throw new Exception('Unsupported package type: ' . $packageType);
        }

        $this->checkAvailable($name, $releasever, $packageType);

        try {
            $params = json_encode($params, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Could not encode mirror parameters: ' . $e->getMessage());
        }

        $this->model->add($name, $releasever, $packageType, $source, $params, $description);

        return $this->model->getLastInsertRowID();
    }

    /**
     *  Check that a new mirror can be created with this name and release version
     */
    public function checkAvailable(string $name, string $releasever, string $packageType): void
    {
        if (!is_null($this->getId($packageType, $name, $releasever))) {
            throw new Exception(ucfirst($packageType) . ' mirror ' . htmlspecialchars(self::getLabel($name, $releasever)) . ' already exists');
        }
    }

    /**
     *  Update mirror description, same rules as repositories descriptions
     */
    public function updateDescription(int $id, string $description): void
    {
        \Controllers\Repo\Repo::checkDescription($description);

        $this->model->updateDescription($id, Validate::string($description));
    }

    /**
     *  Update mirror snapshots retention (null: global setting)
     */
    public function updateRetention(int $id, int|null $retention): void
    {
        $this->model->updateRetention($id, $retention);
    }

    /**
     *  Update mirror parameters, used by the next syncs (existing snapshots are not modified)
     */
    public function updateParams(int $id, array $params): void
    {
        try {
            $params = json_encode($params, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Could not encode mirror parameters: ' . $e->getMessage());
        }

        $this->model->updateParams($id, $params);
    }

    /**
     *  Update mirror tags, same rules as repositories tags
     */
    public function updateTags(int $id, array $tags): void
    {
        $this->model->updateTags($id, implode(',', \Controllers\Repo\Repo::cleanTags($tags)));
    }

    /**
     *  Delete a mirror from database
     */
    public function delete(int $id): void
    {
        $this->model->removeFromGroup($id);
        $this->model->delete($id);
    }

    /**
     *  Return the group Id of each mirror member of a group, as an array mirror Id => group Id
     *  Mirrors that are not member of any group are displayed in the 'Default' group, as repositories
     */
    public function getGroupsIds(): array
    {
        return $this->model->getGroupsIds();
    }

    /**
     *  Set the mirrors members of a group, a mirror can only be member of one group
     */
    public function setGroupMembers(int $groupId, array $mirrorsId): void
    {
        foreach ($mirrorsId as $mirrorId) {
            if (empty($this->getById((int) $mirrorId))) {
                throw new Exception('Specified mirror Id ' . (int) $mirrorId . ' does not exist');
            }
        }

        $this->model->removeGroupMembers($groupId);

        foreach ($mirrorsId as $mirrorId) {
            $this->model->removeFromGroup((int) $mirrorId);
            $this->model->addToGroup((int) $mirrorId, $groupId);
        }
    }

    /**
     *  Add a mirror to a group (a mirror can only be member of one group)
     */
    public function addToGroup(int $mirrorId, int $groupId): void
    {
        $this->model->removeFromGroup($mirrorId);
        $this->model->addToGroup($mirrorId, $groupId);
    }

    /**
     *  Remove all mirrors from a group (when the group is deleted)
     */
    public function removeGroupMembers(int $groupId): void
    {
        $this->model->removeGroupMembers($groupId);
    }

    /**
     *  Check that a mirror name is valid and safe to use as a directory name
     */
    public static function checkName(string $name): void
    {
        if (empty($name)) {
            throw new Exception('Mirror name must be specified');
        }

        if (!Validate::alphaNumericHyphen($name, ['.']) or str_starts_with($name, '.')) {
            throw new Exception('Mirror name contains invalid characters');
        }
    }

    /**
     *  Check that a release version (or an alpine branch) is valid and safe to use as a directory name
     */
    public static function checkReleasever(string $releasever, string $label = 'Release version'): void
    {
        if (empty($releasever) or !Validate::alphaNumericHyphen($releasever, ['.']) or str_starts_with($releasever, '.')) {
            throw new Exception($label . ' must be specified and contain only alphanumeric characters');
        }
    }

    /**
     *  Check the branch and repositories of an alpine mirror
     *  They are required if the source repository URL contains the $branch and $repo variables, otherwise they are optional:
     *  a source repository without branch is mirrored in alpine/<name>, without repositories it is mirrored at the root of the snapshot
     */
    public static function checkAlpineParams(string $sourceUrl, string $branch, array $repos): void
    {
        if ($branch !== '' or str_contains($sourceUrl, '$branch')) {
            self::checkReleasever($branch, 'Branch');
        }

        if (!empty($repos) or str_contains($sourceUrl, '$repo')) {
            self::checkRepos($repos);
        }
    }

    /**
     *  Check the versions and contents of an openbsd mirror
     *  Versions are releases (e.g. 8.0), snapshots are not supported: their packages are rebuilt with the same file names
     */
    public static function checkOpenbsdParams(mixed $versions, mixed $contents): void
    {
        if (empty($versions) or !is_array($versions)) {
            throw new Exception('At least one version must be specified');
        }

        foreach ($versions as $version) {
            if (!is_string($version) or !preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
                throw new Exception('Version ' . htmlspecialchars((string) $version) . ' is invalid, a release version is expected (e.g. 8.0). Snapshots are not supported');
            }
        }

        if (empty($contents) or !is_array($contents)) {
            throw new Exception('At least one content must be specified');
        }

        foreach ($contents as $content) {
            if (!in_array($content, \Controllers\Mirror\Sync\Openbsd::CONTENTS, true)) {
                throw new Exception('Content ' . htmlspecialchars((string) $content) . ' is invalid (' . implode(', ', \Controllers\Mirror\Sync\Openbsd::CONTENTS) . ')');
            }
        }
    }

    /**
     *  Check the versions and contents of an openbsd mirror, return them normalized: versions without duplicate,
     *  contents in the order of Sync\Openbsd::CONTENTS
     */
    public static function normalizeOpenbsdParams(mixed $versions, mixed $contents): array
    {
        $versions = is_array($versions) ? array_values(array_filter($versions, fn ($version) => $version !== '')) : $versions;

        self::checkOpenbsdParams($versions, $contents);

        return [array_values(array_unique($versions)), array_values(array_intersect(\Controllers\Mirror\Sync\Openbsd::CONTENTS, $contents))];
    }

    /**
     *  Check that architectures are specified and valid
     */
    public static function checkArchs(mixed $archs): void
    {
        if (empty($archs) or !is_array($archs)) {
            throw new Exception('At least one architecture must be specified');
        }

        foreach ($archs as $arch) {
            if (!is_string($arch) or !Validate::alphaNumericHyphen($arch)) {
                throw new Exception('Architecture ' . htmlspecialchars((string) $arch) . ' contains invalid characters');
            }
        }
    }

    /**
     *  Check that deb distributions and components are valid and safe to use as paths (e.g. bookworm/updates)
     */
    public static function checkDebValues(array $values): void
    {
        foreach ($values as $value) {
            if (!is_string($value) or !Validate::alphaNumericHyphen($value, ['.', '/']) or in_array('..', explode('/', $value))) {
                throw new Exception('Distribution or component ' . htmlspecialchars((string) $value) . ' contains invalid characters');
            }
        }
    }

    /**
     *  Check that Arch Linux (core, extra...) or Alpine Linux (main, community...) repositories names are valid and safe to use as directory names
     */
    public static function checkRepos(array $repos): void
    {
        if (empty($repos)) {
            throw new Exception('At least one repository must be specified');
        }

        foreach ($repos as $repo) {
            if (!is_string($repo) or !Validate::alphaNumericHyphen($repo, ['.']) or str_starts_with($repo, '.')) {
                throw new Exception('Repository ' . htmlspecialchars((string) $repo) . ' contains invalid characters');
            }
        }
    }

    /**
     *  Return mirror directory, relative to the mirrors root directory
     *  Mirrors are separated by package type, as repositories
     *  rpm mirrors are published per release version (e.g. rpm/nginx/10), so that clients can use $releasever in their URL
     *  deb mirrors contain all their distributions in the same tree (e.g. deb/debian), as upstream
     *  arch mirrors contain all their repositories in the same tree (e.g. arch/archlinux/<env>/core/os/x86_64)
     *  alpine mirrors are published per branch, with all their repositories (e.g. alpine/alpine/v3.24/<env>/main/x86_64)
     *  openbsd mirrors contain all their versions in the same tree, as upstream (e.g. openbsd/openbsd/<env>/8.0/packages/amd64)
     */
    public static function getDir(string $packageType, string $name, string $releasever = ''): string
    {
        return $packageType . '/' . $name . ($releasever === '' ? '' : '/' . $releasever);
    }

    /**
     *  Return mirror directory from a mirror database row
     */
    public static function getDirOf(array $mirror): string
    {
        return self::getDir($mirror['Package_type'], $mirror['Name'], $mirror['Releasever'] ?? '');
    }

    /**
     *  Return mirror label to display (e.g. 'nginx ❯ 10')
     */
    public static function getLabel(string $name, string $releasever = ''): string
    {
        return $releasever === '' ? $name : $name . ' ❯ ' . $releasever;
    }

    /**
     *  Return mirror label with its content: name, release version (rpm) or branch (alpine), then distributions (deb), repositories (arch, alpine)
     *  or versions (openbsd), e.g. 'debian ❯ bookworm, trixie', 'nginx ❯ 10', 'archlinux ❯ core, extra', 'alpine ❯ v3.24 ❯ main, community',
     *  'openbsd ❯ 7.9, 8.0'
     */
    public static function getFullLabel(array $mirror): string
    {
        $params = self::getParams($mirror);
        $label = self::getLabel($mirror['Name'], $mirror['Releasever'] ?? '');
        $content = !empty($params['dists']) ? $params['dists'] : (!empty($params['repos']) ? $params['repos'] : ($params['versions'] ?? []));

        return empty($content) ? $label : $label . ' ❯ ' . implode(', ', $content);
    }

    /**
     *  Return the accent color of a package type, as used by the repositories cards and forms
     */
    public static function getAccent(string $packageType): string
    {
        return ['deb' => 'red', 'rpm' => 'blue', 'arch' => 'cyan', 'alpine' => 'violet', 'openbsd' => 'yellow'][$packageType] ?? 'blue';
    }

    /**
     *  Return the type of the upstream signature verified by the mirrors of a package type: RSA keys for Alpine Linux indexes,
     *  signify keys for OpenBSD files lists, GPG keys for the other repositories
     */
    public static function getSignatureType(string $packageType): string
    {
        return ['alpine' => 'RSA', 'openbsd' => 'signify'][$packageType] ?? 'GPG';
    }

    /**
     *  Return all the tags of the repositories and the mirrors, sorted, to suggest them in the forms
     */
    public function listAllTags(): array
    {
        $tags = (new \Controllers\Repo\Listing())->listTags();

        foreach ($this->get() as $mirror) {
            $tags = array_merge($tags, array_filter(explode(',', $mirror['Tags'] ?? '')));
        }

        $tags = array_values(array_unique($tags));
        sort($tags);

        return $tags;
    }

    /**
     *  Return mirror directory path
     */
    public static function getPath(string $dir): string
    {
        return MIRRORS_DIR . '/' . $dir;
    }

    /**
     *  Return snapshot directory name from its date and time (e.g. 2026-10-30_140000)
     */
    public static function getSnapshotDirName(string $date, string $time): string
    {
        return $date . '_' . str_replace(':', '', $time);
    }

    /**
     *  Return a human readable snapshot label from its directory name (e.g. 2026-10-30_140000 => 30-10-2026 14:00:00)
     */
    public static function getSnapshotLabel(string $dirName): string
    {
        $date = \DateTime::createFromFormat('Y-m-d_His', $dirName);

        return $date === false ? $dirName : $date->format('d-m-Y H:i:s');
    }

    /**
     *  Return snapshot directory path
     */
    public static function getSnapshotPath(string $dir, string $date, string $time): string
    {
        return self::getPath($dir) . '/' . self::getSnapshotDirName($date, $time);
    }

    /**
     *  Return sync working directory path
     *  It is located inside the mirror directory so that rename() and hard links work (same filesystem)
     *  It does not depend on the task Id so that a failed sync can be resumed by the next one
     *  Paths matching /.tmp- are never served by nginx
     */
    public static function getWorkingDir(string $dir): string
    {
        return self::getPath($dir) . '/.tmp-sync';
    }

    /**
     *  Return sync lock file path (only one sync at a time per mirror)
     */
    public static function getLockFile(string $dir): string
    {
        return self::getPath($dir) . '/.tmp-lock';
    }
}
