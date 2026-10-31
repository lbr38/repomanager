<?php

namespace Controllers\Mirror\Task;

use Controllers\Mirror\Mirror;
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

    private function execute(): void
    {
        $this->taskLogStepController->new('sync', 'SYNCING MIRROR');
        $this->taskLogSubStepController->new('initializing', 'INITIALIZING');

        if (empty($this->params['mirror-id']) or !is_numeric($this->params['mirror-id'])) {
            throw new Exception('Mirror Id must be specified');
        }

        $mirror = $this->mirrorController->getById((int) $this->params['mirror-id']);

        if (empty($mirror)) {
            throw new Exception('Mirror #' . (int) $this->params['mirror-id'] . ' does not exist');
        }

        $mirrorId = (int) $mirror['Id'];
        $dir = Mirror::getDirOf($mirror);
        $mirrorParams = $this->mirrorController->getParams($mirror);
        $envs = $this->checkEnvs($this->params['env'] ?? []);
        $sourceDefinition = $this->getSourceDefinition($mirror['Package_type'], $mirror['Source']);

        $this->taskLogSubStepController->output('Source repository: ' . htmlspecialchars($mirror['Source']) . ' (<span class="copy wordbreakall">' . htmlspecialchars($sourceDefinition['url'] ?? '') . '</span>)');

        $this->lock($dir);

        if (is_dir(Mirror::getWorkingDir($dir))) {
            $this->taskLogSubStepController->output('Resuming from a previous sync attempt', 'note');
        }

        $sync = $this->getSyncController($mirror['Package_type'], $dir, $sourceDefinition, $mirrorParams);

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

        [$date, $time, $snapDirName] = $this->publishSnapshot($dir);

        $snapId = $this->mirrorSnapshotController->add($date, $time, 'active', $mirrorId);
        $this->mirrorSnapshotController->updateSize($snapId, $sync->getTotalSize());

        $this->pointEnvs($mirrorId, $dir, $snapId, $snapDirName, $envs);

        $this->taskLogSubStepController->completed('Snapshot ' . Mirror::getSnapshotLabel($snapDirName) . ' published');

        // Delete old unused snapshots
        $this->taskLogSubStepController->new('clean-snapshots', 'CLEANING OLD SNAPSHOTS', 'Snapshots without environment are kept according to the retention setting (' . max(RETENTION, 1) . ')');

        $deleted = $this->mirrorSnapshotController->clean($mirrorId, $dir);

        foreach ($deleted as $dirName) {
            $this->taskLogSubStepController->output('Snapshot ' . Mirror::getSnapshotLabel($dirName) . ' deleted');
        }

        $this->taskLogSubStepController->completed(empty($deleted) ? 'No snapshot to delete' : count($deleted) . ' snapshot(s) deleted');
        $this->taskLogStepController->completed();
    }
}
