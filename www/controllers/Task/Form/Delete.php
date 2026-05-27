<?php

namespace Controllers\Task\Form;

use Exception;
use Controllers\Repo\Repo;
use Controllers\History\Save as History;

class Delete
{
    public function validate(array $formParams): void
    {
        $repoController = new Repo();

        // Check that the snapshot id is valid
        Param\Snapshot::checkId($formParams['snap-id']);

        // Retrieve all repo data from the Id
        $repoController->setSnapId($formParams['snap-id']);
        $repoController->getAllById('', $formParams['snap-id'], '');

        // Check that the repo exists
        if ($repoController->existsSnapId($formParams['snap-id']) === false) {
            throw new Exception('Snapshot Id ' . $formParams['snap-id'] . ' does not exist');
        }

        // Check scheduling parameters
        Param\Schedule::check($formParams['schedule']);

        // Add history
        if ($repoController->getPackageType() == 'rpm') {
            History::set('Running task: delete repository snapshot <span class="label-white">' . $repoController->getName() . '</span>⸺<span class="label-black">' . $repoController->getDateFormatted() . '</span>');
        }
        if ($repoController->getPackageType() == 'deb') {
            History::set('Running task: delete repository snapshot <span class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection() . '</span>⸺<span class="label-black">' . $repoController->getDateFormatted() . '</span>');
        }

        unset($repoController);
    }
}
