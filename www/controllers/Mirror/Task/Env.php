<?php

namespace Controllers\Mirror\Task;

use Controllers\Mirror\Mirror;
use Exception;

/**
 *  Point environments to an existing snapshot of a mirror
 */
class Env extends Execution
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
        $this->taskLogStepController->new('env', 'POINTING ENVIRONMENT');
        $this->taskLogSubStepController->new('point', 'POINTING ENVIRONMENT');

        $mirror = $this->getMirror();
        $snapshot = $this->getMirrorSnapshot((int) $mirror['Id']);
        $snapDirName = Mirror::getSnapshotDirName($snapshot['Date'], $snapshot['Time']);

        if (empty($this->params['env'])) {
            throw new Exception('Environment must be specified');
        }

        $envs = $this->checkEnvs($this->params['env']);
        $currentEnvs = $this->mirrorEnvController->getByMirrorId((int) $mirror['Id']);
        $protectedEnvs = (new \Controllers\Environment())->getProtected();

        // A protected environment cannot be moved from a snapshot to another
        foreach ($envs as $env) {
            if (in_array($env, $protectedEnvs) and isset($currentEnvs[$env]) and $currentEnvs[$env] != $snapshot['Id']) {
                throw new Exception('Environment ' . htmlspecialchars($env) . ' is protected and cannot be moved');
            }
        }

        $this->pointEnvs((int) $mirror['Id'], Mirror::getDirOf($mirror), (int) $snapshot['Id'], $snapDirName, $envs);

        $this->taskLogSubStepController->completed();
        $this->taskLogStepController->completed();
    }
}
