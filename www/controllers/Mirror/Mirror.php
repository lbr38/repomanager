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
    public const PACKAGE_TYPES = ['deb', 'rpm'];

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
     *  Return mirror Id by package type, name and release version (rpm only, empty for deb), or null if it does not exist
     */
    public function getId(string $packageType, string $name, string $releasever = ''): int|null
    {
        return $this->model->getId($packageType, $name, $releasever);
    }

    /**
     *  Return decoded mirror params (dist, components, releasever, arch...)
     */
    public function getParams(array $mirror): array
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
        if (str_contains($description, "'") || str_contains($description, "\\") || str_contains($description, '<?') || str_contains($description, '?>')) {
            throw new Exception('Description contains invalid characters');
        }

        $this->model->updateDescription($id, Validate::string($description));
    }

    /**
     *  Update mirror tags, same rules as repositories tags
     */
    public function updateTags(int $id, array $tags): void
    {
        $validTags = [];

        foreach ($tags as $tag) {
            $tag = trim($tag);

            if (empty($tag)) {
                continue;
            }

            // Tags must be alphanumeric (hyphen and underscore allowed)
            if (!Validate::alphaNumericHyphen($tag)) {
                throw new Exception('Tag ' . htmlspecialchars($tag) . ' contains invalid characters');
            }

            if (!in_array($tag, $validTags)) {
                $validTags[] = $tag;
            }
        }

        $this->model->updateTags($id, implode(',', $validTags));
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
     *  Check that a release version is valid and safe to use as a directory name
     */
    public static function checkReleasever(string $releasever): void
    {
        if (empty($releasever) or !Validate::alphaNumericHyphen($releasever, ['.']) or str_starts_with($releasever, '.')) {
            throw new Exception('Release version must be specified and contain only alphanumeric characters');
        }
    }

    /**
     *  Return mirror directory, relative to the mirrors root directory
     *  Mirrors are separated by package type, as repositories
     *  rpm mirrors are published per release version (e.g. rpm/nginx/10), so that clients can use $releasever in their URL
     *  deb mirrors contain all their distributions in the same tree (e.g. deb/debian), as upstream
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
