<?php

namespace Controllers\Task\Form;

use Exception;
use Controllers\Repo\Rpm;
use Controllers\Repo\Deb;
use Controllers\Repo\Repo;
use Controllers\History\Save as History;
use Controllers\Utils\Generate\Html\Label;

class Rename
{
    public function validate(array $formParams): void
    {
        $repoController = new Repo();
        $rpmRepoController = new Rpm();
        $debRepoController = new Deb();

        // Check that the repo id is valid
        Param\Repo::checkId($formParams['repo-id']);

        // Retrieve all repo data from the Id
        $repoController->getAllById($formParams['repo-id']);

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
            History::set('Running task: renaming repository ' . Label::white($repoController->getName()) . ' ➡ ' . Label::white($formParams['name']));
        }
        if ($repoController->getPackageType() == 'deb') {
            History::set('Running task: renaming repository ' . Label::white($repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection()) . ' ➡ ' . Label::white($formParams['name'] . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection()));
        }

        unset($repoController, $rpmRepoController, $debRepoController);
    }
}
