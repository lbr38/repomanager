<?php

namespace Controllers\Mirror\Task;

use Controllers\Mirror\Mirror;
use Controllers\Repo\Snapshot\Retention;
use Exception;

/**
 *  Sync an existing mirror into a new snapshot
 *  Unchanged files are hard linked from the latest snapshot, old unused snapshots are deleted (RETENTION setting)
 */
class Update extends Execution
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
     *  Sync the mirror into a new snapshot, then delete old unused snapshots
     */
    private function execute(): void
    {
        $this->taskLogStepController->new('sync', 'SYNCING MIRROR');
        $this->taskLogSubStepController->new('initializing', 'INITIALIZING');

        $mirror = $this->getMirror();
        $mirrorId = (int) $mirror['Id'];
        $dir = Mirror::getDirOf($mirror);
        $envs = $this->checkEnvs($this->params['env'] ?? []);

        $sync = $this->prepareSync($mirror['Package_type'], $dir, $mirror['Source'], Mirror::getParams($mirror));

        // Unchanged files are hard linked from the latest snapshot
        $snapshots = $this->mirrorSnapshotController->getByMirrorId($mirrorId);

        if (!empty($snapshots)) {
            $previousSnapshotPath = Mirror::getSnapshotPath($dir, $snapshots[0]['Date'], $snapshots[0]['Time']);

            if (is_dir($previousSnapshotPath)) {
                $sync->setPreviousSnapshotDir($previousSnapshotPath);
                $this->taskLogSubStepController->output('Unchanged files will be reused from the latest snapshot (' . Mirror::getSnapshotLabel(basename($previousSnapshotPath)) . '), only new files are downloaded');
            }
        }

        $this->taskLogSubStepController->completed();

        $sync->mirror();

        $this->taskLogStepController->completed();

        // Publish the snapshot
        $this->taskLogStepController->new('create-snapshot', 'CREATING SNAPSHOT');
        $this->taskLogSubStepController->new('publish', 'PUBLISHING SNAPSHOT');

        $this->addSnapshot($mirrorId, $dir, $this->publishSnapshot($dir), $sync, $envs);

        // Delete old unused snapshots
        $retention = Retention::get($mirror['Retention'] ?? null);
        $this->taskLogSubStepController->new('clean-snapshots', 'CLEANING OLD SNAPSHOTS', 'Snapshots without environment are kept according to the retention setting (' . ($retention === null ? 'keep all' : max($retention, 1)) . ')');

        $deleted = $this->mirrorSnapshotController->clean($mirrorId, $dir, $mirror['Retention'] ?? null);

        foreach ($deleted as $dirName) {
            $this->taskLogSubStepController->output('Snapshot ' . Mirror::getSnapshotLabel($dirName) . ' deleted');
        }

        $this->taskLogSubStepController->completed(empty($deleted) ? 'No snapshot to delete' : count($deleted) . ' snapshot(s) deleted');
        $this->taskLogStepController->completed();
    }
}
