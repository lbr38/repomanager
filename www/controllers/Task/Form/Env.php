<?php

namespace Controllers\Task\Form;

use \Controllers\Utils\Generate\Html\Label;
use \Controllers\History\Save as History;

class Env
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
        $content = '';
        foreach ($formParams['env'] as $env) {
            $content .= Label::envtag($env) . ' ';
        }

        if ($myrepo->getPackageType() == 'rpm') {
            History::set('Running task: point environment(s) <span>' . trim($content) . '</span> to repository <span class="label-white">' . $myrepo->getName() . '</span>⸺<span class="label-black">' . $myrepo->getDateFormatted() . '</span>');
        }
        if ($myrepo->getPackageType() == 'deb') {
            History::set('Running task: point environment(s) <span>' . trim($content) . '</span> to repository <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⸺<span class="label-black">' . $myrepo->getDateFormatted() . '</span>');
        }

        unset($myrepo);
    }
}
