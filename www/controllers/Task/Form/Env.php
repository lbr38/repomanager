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
        Param\Snapshot::checkId($formParams['snapId']);

        /**
         *  Retrieve all repo data from the Ids,
         */
        $myrepo->setSnapId($formParams['snapId']);
        $myrepo->getAllById('', $formParams['snapId'], '');

        /**
         *  Check environment
         */
        Param\Environment::check($formParams['env']);

        /**
         *  Check description
         */
        Param\Description::check($formParams['description']);

        /**
         *  Add history
         */
        if ($myrepo->getPackageType() == 'rpm') {
            $myhistory->set($_SESSION['username'], 'Run operation: new repo environment <span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>⟵' . \Controllers\Common::envtag($formParams['env']), 'success');
        }
        if ($myrepo->getPackageType() == 'deb') {
            $myhistory->set($_SESSION['username'], 'Run operation: new repo environment <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>⟵' . \Controllers\Common::envtag($formParams['env']), 'success');
        }
    }
}
