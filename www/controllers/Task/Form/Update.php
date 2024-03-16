<?php

namespace Controllers\Task\Form;

use Exception;

class Update
{
    public function validate(array $formParams)
    {
        $myrepo = new \Controllers\Repo\Repo();
        $mysource = new \Controllers\Source();
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
         *  Retrieve the package type of the repo
         */
        $packageType = $myrepo->getPackageType();

        /**
         *  Check gpg check
         */
        Param\GpgCheck::check($formParams['gpg-check']);

        /**
         *  Check gpg sign
         */
        Param\GpgSign::check($formParams['gpg-sign']);

        /**
         *  Check architecture
         */
        Param\Arch::check($formParams['arch']);

        /**
         *  Add history
         */
        if ($packageType == 'rpm') {
            $myhistory->set($_SESSION['username'], 'Run operation: update repo <span class="label-white">' . $myrepo->getName() . '</span> (' . $myrepo->getType() . ')', 'success');
        }
        if ($packageType == 'deb') {
            $myhistory->set($_SESSION['username'], 'Run operation: update repo <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span> (' . $myrepo->getType() . ')', 'success');
        }
    }
}
