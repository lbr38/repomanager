<?php

namespace Controllers\Mirror;

use Controllers\Filesystem\Directory;
use Controllers\Repo\Snapshot\Retention;
use Exception;

class Snapshot
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Mirror\Snapshot();
    }

    /**
     *  Return snapshot details by Id
     */
    public function getById(int $id): array
    {
        return $this->model->getById($id);
    }

    /**
     *  Return the mirrors snapshots selected in the repositories list (JSON list of mirror-id / mirror-snap-id), with their mirror
     *  Throw an exception if a snapshot does not exist or if the user is not allowed to view its mirror
     */
    public function getSelection(string $json): array
    {
        try {
            $selection = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new Exception('Could not decode selected snapshots');
        }

        if (empty($selection) or !is_array($selection)) {
            throw new Exception('No snapshot selected');
        }

        $mirrorController = new Mirror();
        $snapshots = [];

        foreach ($selection as $selected) {
            if (empty($selected['mirror-id']) or !is_numeric($selected['mirror-id']) or empty($selected['mirror-snap-id']) or !is_numeric($selected['mirror-snap-id'])) {
                throw new Exception('Mirror or snapshot Id is invalid');
            }

            $mirror = $mirrorController->getById((int) $selected['mirror-id']);
            $snapshot = $this->getById((int) $selected['mirror-snap-id']);

            if (empty($mirror) or empty($snapshot) or $snapshot['Id_mirror'] != $mirror['Id'] or $snapshot['Status'] != 'active' or !\Controllers\User\Permission\Repo::allowedToViewMirror((int) $mirror['Id'])) {
                throw new Exception('Mirror or snapshot does not exist');
            }

            $snapshots[] = ['mirror' => $mirror, 'snapshot' => $snapshot];
        }

        return $snapshots;
    }

    /**
     *  Return the details of a mirror snapshot to browse its content (snapshot page)
     */
    public function getBrowseInfo(string $snapId): array
    {
        if (!is_numeric($snapId)) {
            throw new Exception('invalid mirror snapshot ID.');
        }

        $snapshot = $this->getById((int) $snapId);

        if (empty($snapshot) or $snapshot['Status'] != 'active') {
            throw new Exception('mirror snapshot #' . htmlspecialchars($snapId) . ' does not exist.');
        }

        $mirror = (new Mirror())->getForPage((string) $snapshot['Id_mirror']);

        $path = Mirror::getSnapshotPath(Mirror::getDirOf($mirror), $snapshot['Date'], $snapshot['Time']);

        if (!is_dir($path)) {
            throw new Exception('snapshot directory ' . $path . ' does not exist.');
        }

        // Packages files extensions, Arch Linux packages are .pkg.tar.zst (or .pkg.tar.xz for older ones)
        $extensions = ['deb' => ['deb'], 'rpm' => ['rpm'], 'arch' => ['zst', 'xz']][$mirror['Package_type']] ?? [];

        return [
            'mirror'        => $mirror,
            'snapshot'      => $snapshot,
            'path'          => $path,
            'label'         => $mirror['label'],
            'accent'        => $mirror['accent'],
            'date'          => \DateTime::createFromFormat('Y-m-d', $snapshot['Date'])->format('d-m-Y') . ' ' . substr($snapshot['Time'], 0, 5),
            'size'          => (int) $snapshot['Size'],
            'packagesCount' => count(\Controllers\Filesystem\File::findRecursive($path, [], $extensions))
        ];
    }

    /**
     *  Return all active snapshots of a mirror, most recent first
     */
    public function getByMirrorId(int $mirrorId): array
    {
        return $this->model->getByMirrorId($mirrorId);
    }

    /**
     *  Delete snapshots that are not used by any environment, except the most recent ones (mirror retention, or RETENTION setting if not set)
     *  At least one unused snapshot is always kept: the one an environment has just been switched from,
     *  as clients may still be reading it
     *  Return the deleted snapshots directory names
     */
    public function clean(int $mirrorId, string $mirrorDir, int|null $retention): array
    {
        $deleted = [];
        $retention = Retention::get($retention);

        // All unused snapshots must be kept
        if ($retention === null) {
            return $deleted;
        }

        foreach ($this->model->getUnused($mirrorId, max($retention, 1)) as $snapshot) {
            $dirName = Mirror::getSnapshotDirName($snapshot['Date'], $snapshot['Time']);
            $path = Mirror::getPath($mirrorDir) . '/' . $dirName;

            if (is_dir($path) and !Directory::deleteRecursive($path)) {
                throw new Exception('Could not delete snapshot directory ' . $path);
            }

            $this->model->delete((int) $snapshot['Id']);
            $deleted[] = $dirName;
        }

        return $deleted;
    }

    /**
     *  Delete a snapshot directory, and the snapshot from the database
     */
    public function delete(array $snapshot, string $mirrorDir): void
    {
        $path = Mirror::getSnapshotPath($mirrorDir, $snapshot['Date'], $snapshot['Time']);

        if (is_dir($path) and !Directory::deleteRecursive($path)) {
            throw new Exception('Could not delete snapshot directory ' . $path);
        }

        $this->model->delete((int) $snapshot['Id']);
    }

    /**
     *  Add a snapshot in database and return its Id
     */
    public function add(string $date, string $time, string $status, int $mirrorId): int
    {
        $this->model->add($date, $time, $status, $mirrorId);

        return $this->model->getLastInsertRowID();
    }

    /**
     *  Update snapshot size (in bytes)
     */
    public function updateSize(int $id, int $size): void
    {
        $this->model->updateSize($id, $size);
    }
}
