<?php

namespace Controllers\Mirror\Task;

use Controllers\Mirror\Mirror;
use Controllers\Filesystem\Directory;
use Controllers\Utils\Generate\Html\Label;
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

    /**
     *  Delete a snapshot, or the whole mirror if no snapshot is specified
     */
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
     *  Delete a single snapshot, which must not be used by a protected environment
     *  Other environments pointing to the snapshot are removed with it
     */
    private function deleteSnapshot(array $mirror): void
    {
        $this->taskLogStepController->new('delete', 'DELETING SNAPSHOT');
        $this->taskLogSubStepController->new('delete-snapshot', 'DELETING SNAPSHOT');

        $snapshot = $this->getMirrorSnapshot((int) $mirror['Id']);
        $label = Mirror::getSnapshotLabel(Mirror::getSnapshotDirName($snapshot['Date'], $snapshot['Time']));
        $envs = $this->mirrorEnvController->getBySnapId((int) $snapshot['Id']);
        $protectedEnvs = array_intersect($envs, (new \Controllers\Environment())->getProtected());

        if (!empty($protectedEnvs)) {
            throw new Exception('Snapshot ' . Label::white(htmlspecialchars(Mirror::getLabel($mirror['Name'], $mirror['Releasever'])) . ' ⸺ ' . $label) . ' is used by ' . \Controllers\Environment::nameList($protectedEnvs, 'protected') . ' and cannot be deleted');
        }

        // Remove the environments symlinks, their database entries are deleted with the snapshot
        foreach ($envs as $env) {
            $this->mirrorEnvController->remove((int) $mirror['Id'], Mirror::getDirOf($mirror), $env);
        }

        $this->mirrorSnapshotController->delete($snapshot, Mirror::getDirOf($mirror));

        if (!empty($envs)) {
            $this->taskLogSubStepController->output(ucfirst(strip_tags(\Controllers\Environment::nameList($envs))) . (count($envs) > 1 ? ' removed, their URLs are' : ' removed, its URL is') . ' no longer available to clients');
        }

        $this->taskLogSubStepController->completed('Snapshot ' . $label . ' deleted');
        $this->taskLogStepController->completed();

        // A mirror without snapshot cannot be used anymore, it is deleted with its last snapshot
        $this->deleteMirrorIfEmpty($mirror);
    }

    /**
     *  Delete the whole mirror if it has no snapshot left
     *  Several snapshots of the same mirror can be deleted at once by parallel tasks: the check is done after
     *  the deletion, so that the last task to end deletes the mirror
     */
    private function deleteMirrorIfEmpty(array $mirror): void
    {
        $dir = Mirror::getDirOf($mirror);

        if (!empty($this->mirrorSnapshotController->getByMirrorId((int) $mirror['Id'])) or !is_dir(Mirror::getPath($dir))) {
            return;
        }

        // Another task holds the lock: either a sync, which will add a new snapshot, or another task already deleting the mirror
        if (!$this->tryLock($dir)) {
            return;
        }

        // Check again once locked, the mirror may have been deleted or synced in the meantime
        if (empty($this->mirrorController->getById((int) $mirror['Id'])) or !empty($this->mirrorSnapshotController->getByMirrorId((int) $mirror['Id']))) {
            return;
        }

        $this->taskLogStepController->new('delete-mirror', 'DELETING MIRROR');
        $this->taskLogSubStepController->new('delete-mirror', 'DELETING MIRROR');

        $this->removeMirror($mirror);

        $this->taskLogSubStepController->completed('No snapshot left, mirror ' . htmlspecialchars(Mirror::getLabel($mirror['Name'], $mirror['Releasever'])) . ' deleted, with its environments and scheduled tasks');
        $this->taskLogStepController->completed();
    }

    /**
     *  Delete the whole mirror: snapshots, environments, scheduled tasks and directory
     */
    private function deleteMirror(array $mirror): void
    {
        $this->taskLogStepController->new('delete', 'DELETING MIRROR');
        $this->taskLogSubStepController->new('delete-mirror', 'DELETING MIRROR');

        $label = Mirror::getLabel($mirror['Name'], $mirror['Releasever']);
        $protectedEnvs = array_intersect(array_keys($this->mirrorEnvController->getByMirrorId((int) $mirror['Id'])), (new \Controllers\Environment())->getProtected());

        if (!empty($protectedEnvs)) {
            throw new Exception('Mirror ' . Label::white(htmlspecialchars($label)) . ' is used by ' . \Controllers\Environment::nameList($protectedEnvs, 'protected') . ' and cannot be deleted');
        }

        // Make sure no sync is running on the mirror
        $this->lock(Mirror::getDirOf($mirror));

        $this->removeMirror($mirror);

        $this->taskLogSubStepController->completed('Mirror ' . htmlspecialchars($label) . ' deleted, with its snapshots, environments and scheduled tasks');
        $this->taskLogStepController->completed();
    }

    /**
     *  Delete snapshots, environments, scheduled tasks and directory of a mirror, then the mirror itself
     *  The mirror must be locked by the caller
     */
    private function removeMirror(array $mirror): void
    {
        $dir = Mirror::getDirOf($mirror);

        // Delete each snapshot with its environments, the mirror directory is deleted afterwards
        foreach ($this->mirrorSnapshotController->getByMirrorId((int) $mirror['Id']) as $snapshot) {
            $this->mirrorSnapshotController->delete($snapshot, $dir);
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
    }
}
