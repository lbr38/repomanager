<?php

namespace Controllers\Task\Form;

use Exception;

class Delete
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
         *  Check that the repo exists
         */
        if ($myrepo->existsSnapId($formParams['snapId']) === false) {
            throw new Exception('Snapshot Id ' . $formParams['snapId'] . ' does not exist');
        }

        /**
         *  Add history
         */
        if ($myrepo->getPackageType() == 'rpm') {
            $myhistory->set($_SESSION['username'], 'Run operation: delete repo snapshot <span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>', 'success');
        }
        if ($myrepo->getPackageType() == 'deb') {
            $myhistory->set($_SESSION['username'], 'Run operation: delete repo snapshot <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>', 'success');
        }
    }
}
