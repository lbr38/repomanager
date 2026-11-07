<?php

namespace Controllers\Task\Form;

use Exception;
use Controllers\Repo\Repo;
use Controllers\Repo\Snapshot\Snapshot;
use Controllers\History\Save as History;
use Controllers\Utils\Generate\Html\Label;

class Delete
{
    public function validate(array $formParams): void
    {
        $repoController = new Repo();
        $repoSnaphotController = new Snapshot();

        // Check that the snapshot id is valid
        Param\Snapshot::checkId($formParams['snap-id']);

        // Check that the snapshot exists
        if (!$repoSnaphotController->exists($formParams['snap-id'])) {
            throw new Exception('Snapshot Id #' . $formParams['snap-id'] . ' does not exist');
        }

        // Retrieve all repo data from the Id
        $repoController->getAllById('', $formParams['snap-id'], '');

        // A snapshot used by a protected environment cannot be deleted
        $protectedEnvs = $repoSnaphotController->getProtectedEnvs((int) $formParams['snap-id']);

        if (!empty($protectedEnvs)) {
            throw new Exception('Snapshot ' . Label::white(htmlspecialchars($repoController->getLabel()) . ' ⸺ ' . $repoController->getDateFormatted()) . ' is used by ' . \Controllers\Environment::nameList($protectedEnvs, 'protected') . ' and cannot be deleted');
        }

        // Check scheduling parameters
        Param\Schedule::check($formParams['schedule']);

        // Add history
        if ($repoController->getPackageType() == 'rpm') {
            History::set('Running task: delete repository snapshot ' . Label::white($repoController->getName()) . '⸺' . Label::white($repoController->getDateFormatted()));
        }
        if ($repoController->getPackageType() == 'deb') {
            History::set('Running task: delete repository snapshot ' . Label::white($repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection()) . '⸺' . Label::white($repoController->getDateFormatted()));
        }

        unset($repoController, $repoSnaphotController);
    }
}
