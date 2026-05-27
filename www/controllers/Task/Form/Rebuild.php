<?php

namespace Controllers\Task\Form;

use Exception;
use Controllers\Repo\Repo;
use Controllers\Repo\Snapshot\Snapshot;
use Controllers\History\Save as History;
use Controllers\Utils\Generate\Html\Label;

class Rebuild
{
    public function validate(array $formParams): void
    {
        $repoController = new Repo();
        $repoSnaphotController = new Snapshot();

        // Check that the snapshot id is valid
        Param\Snapshot::checkId($formParams['snap-id']);

        // Check gpg sign
        Param\GpgSign::check($formParams['gpg-sign']);

        // Check scheduling parameters
        Param\Schedule::check($formParams['schedule']);

        // Check that the snapshot exists
        if (!$repoSnaphotController->exists($formParams['snap-id'])) {
            throw new Exception('Snapshot Id #' . $formParams['snap-id'] . ' does not exist');
        }

        // Check if the snapshot has a protected environment
        if ($repoSnaphotController->hasProtectedEnv($formParams['snap-id'])) {
            throw new Exception('Snapshot has a protected environment and cannot be deleted');
        }

        // Retrieve all repo data from the Id
        $repoController->getAllById('', $formParams['snap-id'], '');

        // Add history
        if ($repoController->getPackageType() == 'rpm') {
            History::set('Running task: rebuild repository metadata files of ' . Label::white($repoController->getName()) . '⸺' . Label::white($repoController->getDateFormatted()));
        }
        if ($repoController->getPackageType() == 'deb') {
            History::set('Running task: rebuild repository metadata files of ' . Label::white($repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection()) . '⸺' . Label::white($repoController->getDateFormatted()));
        }

        unset($repoController);
    }
}
