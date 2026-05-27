<?php

namespace Controllers\Task\Form;

use Exception;
use Controllers\Repo\Repo;
use Controllers\Environment;
use Controllers\History\Save as History;
use Controllers\Utils\Generate\Html\Label;

class RemoveEnv
{
    public function validate(array $formParams): void
    {
        $repoController = new Repo();
        $envController = new Environment();

        // Check that the snapshot id is valid
        Param\Snapshot::checkId($formParams['snap-id']);

        // Retrieve all repo data from the Id
        $repoController->getAllById($formParams['repo-id'], $formParams['snap-id'], $formParams['env-id']);

        // Check if the env is protected
        if ($envController->isProtected($repoController->getEnv())) {
            throw new Exception('Environment ' . $repoController->getEnv() . ' is protected and cannot be removed');
        }

        // Add history
        if ($repoController->getPackageType() == 'rpm') {
            History::set('Running task: remove ' . $repoController->getEnv() . ' environment from ' . Label::white($repoController->getName()) . '⸺' . Label::white($repoController->getDateFormatted()));
        }
        if ($repoController->getPackageType() == 'deb') {
            History::set('Running task: remove ' . $repoController->getEnv() . ' environment from ' . Label::white($repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection()) . '⸺' . Label::white($repoController->getDateFormatted()));
        }

        unset($repoController);
    }
}
