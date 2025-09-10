<?php

namespace Controllers\Task\Form;

use Exception;
use \Controllers\History\Save as History;

class Rebuild
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
            History::set('Running task: rebuild repository metadata files of <span class="label-white">' . $myrepo->getName() . '</span>⸺<span class="label-black">' . $myrepo->getDateFormatted() . '</span>');
        }
        if ($myrepo->getPackageType() == 'deb') {
            History::set('Running task: rebuild repository metadata files of <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⸺<span class="label-black">' . $myrepo->getDateFormatted() . '</span>');
        }

        unset($myrepo);
    }
}
