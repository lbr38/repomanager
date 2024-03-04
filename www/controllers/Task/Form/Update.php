<?php

namespace Controllers\Task\Form;

use Exception;

class Update
{
    public function validate(array $formParams)
    {
        $myrepo = new \Controllers\Repo\Repo();
        $myhistory = new \Controllers\History();

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
         *  Check only sync difference
         */
        Param\OnlySyncDifference::check($formParams['only-sync-difference']);

        /**
         *  Check env
         */
        if (!empty($formParams['env'])) {
            Param\Environment::check($formParams['env']);
        }

        /**
         *  Check architecture
         */
        Param\Arch::check($formParams['arch']);

        /**
         *  Check gpg check
         */
        Param\GpgCheck::check($formParams['gpg-check']);

        /**
         *  Check gpg sign
         */
        Param\GpgSign::check($formParams['gpg-sign']);

        /**
         *  Check scheduling parameters
         */
        Param\Schedule::check($formParams['schedule']);

        /**
         *  Add history
         */
        if ($myrepo->getPackageType() == 'rpm') {
            $myhistory->set($_SESSION['username'], 'Running task: update repository <span class="label-white">' . $myrepo->getName() . '</span> (' . $myrepo->getType() . ')', 'success');
        }
        if ($myrepo->getPackageType() == 'deb') {
            $myhistory->set($_SESSION['username'], 'Running task: update repository <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span> (' . $myrepo->getType() . ')', 'success');
        }
    }
}
