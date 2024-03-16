<?php

namespace Controllers\Task\Form;

use Exception;

class Duplicate
{
    public function validate(array $formParams)
    {
        $myrepo = new \Controllers\Repo\Repo();
        $myhistory = new \Controllers\History();

        /**
         *  Check that the snapshot id is valid
         */
        Param\Snapshot::checkId($formParams['snapId']);

        /**
         *  Retrieve all repo data from the Ids,
         */
        $myrepo->setSnapId($formParams['snapId']);
        $myrepo->getAllById('', $formParams['snapId'], '');

        /**
         *  Check name
         */
        Param\Name::check($formParams['target-name']);

        /**
         *  Check env
         */
        if (!empty($formParams['target-env'])) {
            Param\Environment::check($formParams['target-env']);
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
            if ($myrepo->isActive($formParams['target-name']) === true) {
                throw new Exception('<span class="label-white">' . $formParams['target-name'] . '</span> repo already exists');
            }
        }
        if ($myrepo->getPackageType() == 'deb') {
            if ($myrepo->isActive($formParams['target-name'], $myrepo->getDist(), $myrepo->getSection()) === true) {
                throw new Exception('<span class="label-white">' . $formParams['target-name'] . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span> repo already exists');
            }
        }

        /**
         *  Add history
         */
        if ($myrepo->getPackageType() == 'rpm') {
            $myhistory->set($_SESSION['username'], 'Run operation: duplicate repo <span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span> ➡ <span class="label-white">' . $formParams['target-name'] . '</span>', 'success');
        }
        if ($myrepo->getPackageType() == 'deb') {
            $myhistory->set($_SESSION['username'], 'Run operation: duplicate repo  <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span> ➡ <span class="label-white">' . $formParams['target-name'] . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>', 'success');
        }
    }
}
