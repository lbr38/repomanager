<?php

namespace Controllers\Mirror\Task;

use Controllers\Mirror\Mirror;
use Controllers\Filesystem\Directory;
use Exception;

/**
 *  Delete a snapshot of a mirror (mirror-snap-id parameter), or the whole mirror
 */
class Delete extends Execution
{
    public function __construct(int $taskId)
    {
        parent::__construct($taskId);

        try {
            $this->execute();
        } catch (Exception $e) {
            $this->status = 'error';
            $this->error = $e->getMessage();

            // Throw back the exception to be caught by the main script
            throw new Exception($e->getMessage());
        } finally {
            $this->end();
        }
    }

    private function execute(): void
    {
        $mirror = $this->getMirror();

        if (!empty($this->params['mirror-snap-id'])) {
            $this->deleteSnapshot($mirror);
            return;
        }

        $this->deleteMirror($mirror);
    }

    /**
     *  Delete a single snapshot, which must not be used by any environment
     */
    private function deleteSnapshot(array $mirror): void
    {
        $this->taskLogStepController->new('delete', 'DELETING SNAPSHOT');
        $this->taskLogSubStepController->new('delete-snapshot', 'DELETING SNAPSHOT');

        $snapshot = $this->getMirrorSnapshot((int) $mirror['Id']);
        $label = Mirror::getSnapshotLabel(Mirror::getSnapshotDirName($snapshot['Date'], $snapshot['Time']));
        $envs = $this->mirrorEnvController->getBySnapId((int) $snapshot['Id']);

        // Deleting a snapshot used by an environment would make the mirror unavailable to clients
        if (!empty($envs)) {
            throw new Exception('Snapshot ' . $label . ' is used by environment(s) ' . htmlspecialchars(implode(', ', $envs)) . ', remove or move them first');
        }

        $this->mirrorSnapshotController->delete($snapshot, Mirror::getDirOf($mirror));

        $this->taskLogSubStepController->completed('Snapshot ' . $label . ' deleted');
        $this->taskLogStepController->completed();
    }

    /**
     *  Delete the whole mirror: snapshots, environments, scheduled tasks and directory
     */
    private function deleteMirror(array $mirror): void
    {
        $this->taskLogStepController->new('delete', 'DELETING MIRROR');
        $this->taskLogSubStepController->new('delete-mirror', 'DELETING MIRROR');

        $dir = Mirror::getDirOf($mirror);
        $label = Mirror::getLabel($mirror['Name'], $mirror['Releasever']);

        // Make sure no sync is running on the mirror
        $this->lock($dir);

        foreach ($this->mirrorSnapshotController->getByMirrorId((int) $mirror['Id']) as $snapshot) {
            $this->mirrorEnvController->removeBySnapId((int) $snapshot['Id']);
            $this->mirrorSnapshotController->updateStatus((int) $snapshot['Id'], 'deleted');
        }

        (new \Controllers\Task\Scheduled())->deleteByMirrorId((int) $mirror['Id']);

        if (!Directory::deleteRecursive(Mirror::getPath($dir))) {
            throw new Exception('Could not delete mirror directory ' . Mirror::getPath($dir));
        }

        // rpm mirrors are stored in rpm/<name>/<releasever>, delete rpm/<name> if no other release version remains
        $parentDir = dirname(Mirror::getPath($dir));

        if (!empty($mirror['Releasever']) and is_dir($parentDir) and Directory::isEmpty($parentDir)) {
            rmdir($parentDir);
        }

        $this->mirrorController->delete((int) $mirror['Id']);

        $this->taskLogSubStepController->completed('Mirror ' . htmlspecialchars($label) . ' deleted, with its snapshots, environments and scheduled tasks');
        $this->taskLogStepController->completed();
    }
}
