<?php

namespace Controllers\Task\Form;

use Exception;
use Controllers\Repo\Repo;
use Controllers\History\Save as History;
use Controllers\Utils\Generate\Html\Label;

class Duplicate
{
    public function validate(array $formParams): void
    {
        $repoController = new Repo();
        $rpmRepoController = new \Controllers\Repo\Rpm();
        $debRepoController = new \Controllers\Repo\Deb();

        // Check that the snapshot id is valid
        Param\Snapshot::checkId($formParams['snap-id']);

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

        // Check tags
        Param\Tags::check($formParams['tags']);

        // Check group
        if (!empty($formParams['group'])) {
            Param\Group::check($formParams['group']);
        }

        // Retrieve all repo data from the Id
        // $repoController->setSnapId($formParams['snap-id']);
        $repoController->getAllById('', $formParams['snap-id'], '');

        /**
         *  The new repository can have another release version (rpm) or distribution (deb) than the source snapshot,
         *  the same as the source snapshot if not specified. The component (deb) cannot be changed, as the packages
         *  of a deb snapshot are stored in a directory named after it
         */
        if ($repoController->getPackageType() == 'rpm') {
            $releasever = $repoController->getReleasever();
        }
        if ($repoController->getPackageType() == 'deb') {
            $dist = $repoController->getDist();
        }

        if (isset($formParams['releasever'])) {
            if ($repoController->getPackageType() != 'rpm') {
                throw new Exception('Release version can only be specified for a rpm repository');
            }

            Param\Releasever::check($formParams['releasever'] === '' ? [] : [$formParams['releasever']]);
            $releasever = $formParams['releasever'];
        }

        if (isset($formParams['dist'])) {
            if ($repoController->getPackageType() != 'deb') {
                throw new Exception('Distribution can only be specified for a deb repository');
            }

            Param\Dist::check($formParams['dist'] === '' ? [] : [$formParams['dist']]);
            $dist = $formParams['dist'];
        }

        // Check that a repo with the same name does not already exist
        if ($repoController->getPackageType() == 'rpm') {
            if ($rpmRepoController->isActive($formParams['name'], $releasever)) {
                throw new Exception(Label::white($formParams['name'] . ' ❯ ' . $releasever) . ' repository already exists');
            }
        }
        if ($repoController->getPackageType() == 'deb') {
            if ($debRepoController->isActive($formParams['name'], $dist, $repoController->getSection())) {
                throw new Exception(Label::white($formParams['name'] . ' ❯ ' . $dist . ' ❯ ' . $repoController->getSection()) . ' repository already exists');
            }
        }

        // Check scheduling parameters
        Param\Schedule::check($formParams['schedule']);

        // Add history
        if ($repoController->getPackageType() == 'rpm') {
            History::task('duplicate repository ' . Label::white($repoController->getName() . ' ❯ ' . $repoController->getReleasever()) . '⸺' . Label::white($repoController->getDateFormatted()) . ' ➡ ' . Label::white($formParams['name'] . ' ❯ ' . $releasever), $formParams);
        }
        if ($repoController->getPackageType() == 'deb') {
            History::task('duplicate repository ' . Label::white($repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection()) . '⸺' . Label::white($repoController->getDateFormatted()) . ' ➡ ' . Label::white($formParams['name'] . ' ❯ ' . $dist . ' ❯ ' . $repoController->getSection()), $formParams);
        }

        unset($repoController, $rpmRepoController, $debRepoController);
    }
}
