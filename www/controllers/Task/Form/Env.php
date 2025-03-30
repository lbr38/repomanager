<?php

namespace Controllers\Task\Form;

use Exception;

class Env
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
         *  Check environment
         */
        Param\Environment::check($formParams['env']);

        /**
         *  Check description
         */
        Param\Description::check($formParams['description']);

        /**
         *  Check scheduling parameters
         */
        Param\Schedule::check($formParams['schedule']);

        /**
         *  Add history
         */
        if ($myrepo->getPackageType() == 'rpm') {
            $myhistory->set('Running task: point environment to repository <span class="label-white">' . $myrepo->getName() . '</span>⸺<span class="label-black">' . $myrepo->getDateFormatted() . '</span>⸺' . \Controllers\Common::envtag($formParams['env']), 'success');
        }
        if ($myrepo->getPackageType() == 'deb') {
            $myhistory->set('Running task: point environment to repository <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⸺<span class="label-black">' . $myrepo->getDateFormatted() . '</span>⸺' . \Controllers\Common::envtag($formParams['env']), 'success');
        }
    }
}
