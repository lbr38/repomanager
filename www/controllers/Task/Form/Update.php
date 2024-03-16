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
        Param\Snapshot::checkId($formParams['snapId']);

        /**
         *  Retrieve all repo data from the Ids,
         */
        $myrepo->setSnapId($formParams['snapId']);
        $myrepo->getAllById('', $formParams['snapId'], '');

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
         *  Add history
         */
        if ($myrepo->getPackageType() == 'rpm') {
            $myhistory->set($_SESSION['username'], 'Run operation: update repo <span class="label-white">' . $myrepo->getName() . '</span> (' . $myrepo->getType() . ')', 'success');
        }
        if ($myrepo->getPackageType() == 'deb') {
            $myhistory->set($_SESSION['username'], 'Run operation: update repo <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span> (' . $myrepo->getType() . ')', 'success');
        }
    }
}
