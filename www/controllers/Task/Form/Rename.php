<?php

namespace Controllers\Task\Form;

use Exception;
use \Controllers\History\Save as History;
use Controllers\Utils\Generate\Html\Label;

class Rename
{
    public function validate(array $formParams)
    {
        $repoController = new \Controllers\Repo\Repo();
        $rpmRepoController = new \Controllers\Repo\Rpm();
        $debRepoController = new \Controllers\Repo\Deb();

        // Check that the snapshot id is valid
        Param\Snapshot::checkId($formParams['snap-id']);

        // Retrieve all repo data from the Id
        $repoController->getAllById('', $formParams['snap-id'], '');

        // Check old name
        Param\Name::check($formParams['old-name']);

        // Old name must match the current name of the repository being renamed, otherwise someone is trying to doing something bad
        if ($formParams['old-name'] != $repoController->getName()) {
            throw new Exception('The old repository name does not match the name of the repository being renamed');
        }

        // Check name
        Param\Name::check($formParams['name']);

        // Check that a repo with the same name does not already exist
        if ($repoController->getPackageType() == 'rpm') {
            if ($rpmRepoController->isActive($formParams['name'], $repoController->getReleasever())) {
                throw new Exception(Label::white($formParams['name'] . ' ❯ ' . $repoController->getReleasever()) . ' repository already exists');
            }
        }
        if ($repoController->getPackageType() == 'deb') {
            if ($debRepoController->isActive($formParams['name'], $repoController->getDist(), $repoController->getSection())) {
                throw new Exception(Label::white($formParams['name'] . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection()) . ' repository already exists');
            }
        }

        // Check scheduling parameters
        Param\Schedule::check($formParams['schedule']);

        // Add history
        if ($repoController->getPackageType() == 'rpm') {
            History::set('Running task: renaming repository ' . Label::white($repoController->getName()) . '⸺' . Label::black($repoController->getDateFormatted()) . ' ➡ ' . Label::white($formParams['name']));
        }
        if ($repoController->getPackageType() == 'deb') {
            History::set('Running task: renaming repository ' . Label::white($repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection()) . '⸺' . Label::black($repoController->getDateFormatted()) . ' ➡ ' . Label::white($formParams['name'] . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection()));
        }

        unset($repoController, $rpmRepoController, $debRepoController);
    }
}
