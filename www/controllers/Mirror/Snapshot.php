<?php

namespace Controllers\Mirror;

use Controllers\Filesystem\Directory;
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
     *  Return all active snapshots of a mirror, most recent first
     */
    public function getByMirrorId(int $mirrorId): array
    {
        return $this->model->getByMirrorId($mirrorId);
    }

    /**
     *  Delete snapshots that are not used by any environment, except the most recent ones (RETENTION setting)
     *  At least one unused snapshot is always kept: the one an environment has just been switched from,
     *  as clients may still be reading it
     *  Return the deleted snapshots directory names
     */
    public function clean(int $mirrorId, string $mirrorDir): array
    {
        $deleted = [];

        foreach ($this->model->getUnused($mirrorId, max(RETENTION, 1)) as $snapshot) {
            $dirName = Mirror::getSnapshotDirName($snapshot['Date'], $snapshot['Time']);
            $path = Mirror::getPath($mirrorDir) . '/' . $dirName;

            if (is_dir($path) and !Directory::deleteRecursive($path)) {
                throw new Exception('Could not delete snapshot directory ' . $path);
            }

            $this->model->updateStatus($snapshot['Id'], 'deleted');
            $deleted[] = $dirName;
        }

        return $deleted;
    }

    /**
     *  Delete a snapshot directory and mark it as deleted in database
     *  Environments pointing to the snapshot must have been removed before
     */
    public function delete(array $snapshot, string $mirrorDir): void
    {
        $path = Mirror::getSnapshotPath($mirrorDir, $snapshot['Date'], $snapshot['Time']);

        if (is_dir($path) and !Directory::deleteRecursive($path)) {
            throw new Exception('Could not delete snapshot directory ' . $path);
        }

        $this->model->updateStatus($snapshot['Id'], 'deleted');
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
     *  Update snapshot status
     */
    public function updateStatus(int $id, string $status): void
    {
        $this->model->updateStatus($id, $status);
    }

    /**
     *  Update snapshot size (in bytes)
     */
    public function updateSize(int $id, int $size): void
    {
        $this->model->updateSize($id, $size);
    }
}
