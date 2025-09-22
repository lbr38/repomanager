<?php

namespace Controllers\Task\Form;

use Exception;
use \Controllers\History\Save as History;

class RemoveEnv
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
        $myrepo->getAllById($formParams['repo-id'], $formParams['snap-id'], $formParams['env-id']);

        /**
         *  Add history
         */
        if ($myrepo->getPackageType() == 'rpm') {
            History::set('Running task: remove ' . $myrepo->getEnv() . ' environment from <span class="label-white">' . $myrepo->getName() . '</span>⸺<span class="label-black">' . $myrepo->getDateFormatted() . '</span>');
        }
        if ($myrepo->getPackageType() == 'deb') {
            History::set('Running task: remove ' . $myrepo->getEnv() . ' environment from <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⸺<span class="label-black">' . $myrepo->getDateFormatted() . '</span>');
        }

        unset($myrepo);
    }
}
