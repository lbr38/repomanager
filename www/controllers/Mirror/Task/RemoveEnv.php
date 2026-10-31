<?php

namespace Controllers\Mirror\Task;

use Controllers\Mirror\Mirror;
use Exception;

/**
 *  Remove an environment from a mirror
 */
class RemoveEnv extends Execution
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
        $this->taskLogStepController->new('remove-env', 'REMOVING ENVIRONMENT');
        $this->taskLogSubStepController->new('remove', 'REMOVING ENVIRONMENT');

        $mirror = $this->getMirror();
        $env = $this->params['env'] ?? '';

        if (empty($env) or !is_string($env)) {
            throw new Exception('Environment must be specified');
        }

        $currentEnvs = $this->mirrorEnvController->getByMirrorId((int) $mirror['Id']);

        if (!isset($currentEnvs[$env])) {
            throw new Exception('Environment ' . htmlspecialchars($env) . ' is not used by this mirror');
        }

        if (in_array($env, (new \Controllers\Environment())->getProtected())) {
            throw new Exception('Environment ' . htmlspecialchars($env) . ' is protected and cannot be removed');
        }

        $this->mirrorEnvController->remove((int) $mirror['Id'], Mirror::getDirOf($mirror), $env);

        $this->taskLogSubStepController->completed('Environment ' . htmlspecialchars($env) . ' removed, its URL is no longer available to clients');
        $this->taskLogStepController->completed();
    }
}
