<?php

namespace Controllers\Task\Form;

use Exception;
use \Controllers\History\Save as History;

class Update
{
    public function validate(array $formParams)
    {
        $myrepo = new \Controllers\Repo\Repo();

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
         *  Case of a mirror repository, check additional parameters
         */
        if ($myrepo->getType() == 'mirror') {
            /**
             *  Check package(s) to include
             */
            Param\PackageInclude::check($formParams['package-include']);

            /**
             *  Check package(s) to exclude
             */
            Param\PackageExclude::check($formParams['package-exclude']);

            /**
             *  Check gpg check
             */
            Param\GpgCheck::check($formParams['gpg-check']);
        }

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
            History::set('Running task: update ' . $myrepo->getType() . ' repository <span class="label-white">' . $myrepo->getName() . '</span>');
        }
        if ($myrepo->getPackageType() == 'deb') {
            History::set('Running task: update ' . $myrepo->getType() . ' repository <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>');
        }

        unset($myrepo);
    }
}
