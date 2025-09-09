<?php

namespace Controllers\Task\Form;

use Exception;

class RemoveEnv
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
        $myrepo->getAllById($formParams['repo-id'], $formParams['snap-id'], $formParams['env-id']);

        /**
         *  Add history
         */
        if ($myrepo->getPackageType() == 'rpm') {
            $myhistory->set('Running task: remove ' . $myrepo->getEnv() . ' environment from <span class="label-white">' . $myrepo->getName() . '</span>⸺<span class="label-black">' . $myrepo->getDateFormatted() . '</span>', 'success');
        }
        if ($myrepo->getPackageType() == 'deb') {
            $myhistory->set('Running task: remove ' . $myrepo->getEnv() . ' environment from <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⸺<span class="label-black">' . $myrepo->getDateFormatted() . '</span>', 'success');
        }

        unset($myrepo, $myhistory);
    }
}
