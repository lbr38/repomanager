<?php

namespace Controllers\Mirror\Task;

use Controllers\Mirror\Mirror;
use Controllers\Repo\Snapshot\Retention;
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

    /**
     *  Sync the source repository into the first snapshot of the new mirror
     */
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

        // rpm mirrors are unique by name and release version, deb and arch mirrors by name
        $releasever = $mirrorParams['releasever'] ?? '';
        $dir = Mirror::getDir($packageType, $name, $releasever);

        $this->mirrorController->checkAvailable($name, $releasever, $packageType);
        $envs = $this->checkEnvs($this->params['env'] ?? []);

        $sync = $this->prepareSync($packageType, $dir, $source, $mirrorParams);

        $this->taskLogSubStepController->completed();

        $sync->mirror();

        $this->taskLogStepController->completed();

        // Publish the snapshot
        $this->taskLogStepController->new('create-snapshot', 'CREATING SNAPSHOT');
        $this->taskLogSubStepController->new('publish', 'PUBLISHING SNAPSHOT');

        // The mirror is only added in database once its first snapshot is published
        $published = $this->publishSnapshot($dir);

        $mirrorId = $this->mirrorController->add($name, $releasever, $packageType, $source, $mirrorParams, $description);
        $this->mirrorController->updateTags($mirrorId, (array) ($this->params['tags'] ?? []));
        $this->mirrorController->updateRetention($mirrorId, Retention::fromInput($this->params['retention'] ?? null, $this->params['retention-keep-all'] ?? 'false'));

        $this->addSnapshot($mirrorId, $dir, $published, $sync, $envs);

        $this->taskLogStepController->completed();

        if (!empty($this->params['group']) and is_numeric($this->params['group'])) {
            $this->taskLogStepController->new('add-to-group', 'ADDING MIRROR TO GROUP');
            $this->mirrorController->addToGroup($mirrorId, (int) $this->params['group']);
            $this->taskLogStepController->completed();
        }
    }
}
