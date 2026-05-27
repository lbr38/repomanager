<?php

namespace Controllers\Task\Form;

use Exception;
use Controllers\Repo\Repo;
use Controllers\History\Save as History;

class Duplicate
{
    public function validate(array $formParams): void
    {
        $repoController = new Repo();
        $rpmRepoController = new \Controllers\Repo\Rpm();
        $debRepoController = new \Controllers\Repo\Deb();

        // Check that the snapshot id is valid
        Param\Snapshot::checkId($formParams['snap-id']);

        // Retrieve all repo data from the Id
        $repoController->setSnapId($formParams['snap-id']);
        $repoController->getAllById('', $formParams['snap-id'], '');

        // Check name
        Param\Name::check($formParams['name']);

        // Check env
        if (!empty($formParams['env'])) {
            Param\Environment::check($formParams['env']);
        }

        // Check description
        if (!empty($formParams['description'])) {
            Param\Description::check($formParams['description']);
        }

        // Check group
        if (!empty($formParams['group'])) {
            Param\Group::check($formParams['group']);
        }

        // Check that a repo with the same name does not already exist
        if ($repoController->getPackageType() == 'rpm') {
            if ($rpmRepoController->isActive($formParams['name'], $repoController->getReleasever())) {
                throw new Exception('<span class="label-white">' . $formParams['name'] . ' ❯ ' . $repoController->getReleasever() . '</span> repository already exists');
            }
        }
        if ($repoController->getPackageType() == 'deb') {
            if ($debRepoController->isActive($formParams['name'], $repoController->getDist(), $repoController->getSection())) {
                throw new Exception('<span class="label-white">' . $formParams['name'] . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection() . '</span> repo already exists');
            }
        }

        // Check scheduling parameters
        Param\Schedule::check($formParams['schedule']);

        // Add history
        if ($repoController->getPackageType() == 'rpm') {
            History::set('Running task: duplicate repository <span class="label-white">' . $repoController->getName() . '</span>⸺<span class="label-black">' . $repoController->getDateFormatted() . '</span> ➡ <span class="label-white">' . $formParams['name'] . '</span>');
        }
        if ($repoController->getPackageType() == 'deb') {
            History::set('Running task: duplicate repository <span class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection() . '</span>⸺<span class="label-black">' . $repoController->getDateFormatted() . '</span> ➡ <span class="label-white">' . $formParams['name'] . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection() . '</span>');
        }

        unset($repoController, $rpmRepoController, $debRepoController);
    }
}
