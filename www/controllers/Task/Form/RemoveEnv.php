<?php

namespace Controllers\Task\Form;

use Controllers\Repo\Repo;
use Controllers\History\Save as History;

class RemoveEnv
{
    public function validate(array $formParams): void
    {
        $repoController = new Repo();

        // Check that the snapshot id is valid
        Param\Snapshot::checkId($formParams['snap-id']);

        // Retrieve all repo data from the Id
        $repoController->getAllById($formParams['repo-id'], $formParams['snap-id'], $formParams['env-id']);

        // Add history
        if ($repoController->getPackageType() == 'rpm') {
            History::set('Running task: remove ' . $repoController->getEnv() . ' environment from <span class="label-white">' . $repoController->getName() . '</span>⸺<span class="label-black">' . $repoController->getDateFormatted() . '</span>');
        }
        if ($repoController->getPackageType() == 'deb') {
            History::set('Running task: remove ' . $repoController->getEnv() . ' environment from <span class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection() . '</span>⸺<span class="label-black">' . $repoController->getDateFormatted() . '</span>');
        }

        unset($repoController);
    }
}
