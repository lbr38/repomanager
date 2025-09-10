<?php

namespace Controllers\Task\Form;

use Exception;
use \Controllers\History\Save as History;

class Duplicate
{
    public function validate(array $formParams)
    {
        $myrepo = new \Controllers\Repo\Repo();
        $rpmRepoController = new \Controllers\Repo\Rpm();
        $debRepoController = new \Controllers\Repo\Deb();

        /**
         *  Check that the snapshot id is valid
         */
        Param\Snapshot::checkId($formParams['snap-id']);

        /**
         *  Retrieve all repo data from the Ids,
         */
        $myrepo->setSnapId($formParams['snap-id']);
        $myrepo->getAllById('', $formParams['snap-id'], '');

        /**
         *  Check name
         */
        Param\Name::check($formParams['name']);

        /**
         *  Check env
         */
        if (!empty($formParams['env'])) {
            Param\Environment::check($formParams['env']);
        }

        /**
         *  Check description
         */
        if (!empty($formParams['description'])) {
            Param\Description::check($formParams['description']);
        }

        /**
         *  Check group
         */
        if (!empty($formParams['group'])) {
            Param\Group::check($formParams['group']);
        }

        /**
         *  Check that a repo with the same name does not already exist
         */
        if ($myrepo->getPackageType() == 'rpm') {
            if ($rpmRepoController->isActive($formParams['name'], $myrepo->getReleasever())) {
                throw new Exception('<span class="label-white">' . $formParams['name'] . ' ❯ ' . $myrepo->getReleasever() . '</span> repository already exists');
            }
        }
        if ($myrepo->getPackageType() == 'deb') {
            if ($debRepoController->isActive($formParams['name'], $myrepo->getDist(), $myrepo->getSection())) {
                throw new Exception('<span class="label-white">' . $formParams['name'] . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span> repo already exists');
            }
        }

        /**
         *  Check scheduling parameters
         */
        Param\Schedule::check($formParams['schedule']);

        /**
         *  Add history
         */
        if ($myrepo->getPackageType() == 'rpm') {
            History::set('Running task: duplicate repository <span class="label-white">' . $myrepo->getName() . '</span>⸺<span class="label-black">' . $myrepo->getDateFormatted() . '</span> ➡ <span class="label-white">' . $formParams['name'] . '</span>');
        }
        if ($myrepo->getPackageType() == 'deb') {
            History::set('Running task: duplicate repository <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⸺<span class="label-black">' . $myrepo->getDateFormatted() . '</span> ➡ <span class="label-white">' . $formParams['name'] . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>');
        }

        unset($myrepo, $rpmRepoController, $debRepoController);
    }
}
