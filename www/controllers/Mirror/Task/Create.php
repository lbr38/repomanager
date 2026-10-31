<?php

namespace Controllers\Mirror\Task;

use Controllers\Mirror\Mirror;
use Exception;

/**
 *  Create a new mirror and its first snapshot
 */
class Create extends Execution
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

        $name = $this->params['name'] ?? '';
        $packageType = $this->params['package-type'] ?? '';
        $source = $this->params['source'] ?? '';
        $description = $this->params['description'] ?? '';

        Mirror::checkName($name);

        if (!in_array($packageType, Mirror::PACKAGE_TYPES)) {
            throw new Exception('Unsupported package type: ' . htmlspecialchars($packageType));
        }

        $mirrorParams = $this->getMirrorParams($packageType);

        // rpm mirrors are unique by name and release version, deb mirrors by name
        $releasever = $mirrorParams['releasever'] ?? '';
        $dir = Mirror::getDir($packageType, $name, $releasever);

        $this->mirrorController->checkAvailable($name, $releasever, $packageType);
        $envs = $this->checkEnvs($this->params['env'] ?? []);
        $sourceDefinition = $this->getSourceDefinition($packageType, $source);

        $this->taskLogSubStepController->output('Source repository: ' . htmlspecialchars($source) . ' (<span class="copy wordbreakall">' . htmlspecialchars($sourceDefinition['url'] ?? '') . '</span>)');

        $this->lock($dir);

        // A working directory may remain from a previous failed attempt, its files will be reused
        if (is_dir(Mirror::getWorkingDir($dir))) {
            $this->taskLogSubStepController->output('Resuming from a previous sync attempt', 'note');
        }

        $sync = $this->getSyncController($packageType, $dir, $sourceDefinition, $mirrorParams);

        $this->taskLogSubStepController->completed();

        $sync->mirror();

        $this->taskLogStepController->completed();

        // Publish the snapshot
        $this->taskLogStepController->new('create-snapshot', 'CREATING SNAPSHOT');
        $this->taskLogSubStepController->new('publish', 'PUBLISHING SNAPSHOT');

        [$date, $time, $snapDirName] = $this->publishSnapshot($dir);

        $mirrorId = $this->mirrorController->add($name, $releasever, $packageType, $source, $mirrorParams, $description);
        $this->mirrorController->updateTags($mirrorId, (array) ($this->params['tags'] ?? []));

        if (!empty($this->params['group']) and is_numeric($this->params['group'])) {
            $this->mirrorController->addToGroup($mirrorId, (int) $this->params['group']);
        }
        $snapId = $this->mirrorSnapshotController->add($date, $time, 'active', $mirrorId);
        $this->mirrorSnapshotController->updateSize($snapId, $sync->getTotalSize());

        $this->pointEnvs($mirrorId, $dir, $snapId, $snapDirName, $envs);

        $this->taskLogSubStepController->completed('Snapshot ' . Mirror::getSnapshotLabel($snapDirName) . ' published');
        $this->taskLogStepController->completed();
    }
}
