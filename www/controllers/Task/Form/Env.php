<?php

namespace Controllers\Task\Form;

use Controllers\Repo\Repo;
use Controllers\Utils\Generate\Html\Label;
use Controllers\History\Save as History;

class Env
{
    public function validate(array $formParams): void
    {
        $repoController = new Repo();

        // Check that the snapshot id is valid
        Param\Snapshot::checkId($formParams['snap-id']);

        // Retrieve all repo data from the Id
        $repoController->setSnapId($formParams['snap-id']);
        $repoController->getAllById('', $formParams['snap-id'], '');

        // Check environment
        Param\Environment::check($formParams['env']);

        // Check description
        Param\Description::check($formParams['description']);

        // Check scheduling parameters
        Param\Schedule::check($formParams['schedule']);

        // Add history
        $content = '';
        foreach ($formParams['env'] as $env) {
            $content .= Label::envtag($env) . ' ';
        }

        if ($repoController->getPackageType() == 'rpm') {
            History::set('Running task: point environment(s) <span>' . trim($content) . '</span> to repository <span class="label-white">' . $repoController->getName() . '</span>⸺<span class="label-black">' . $repoController->getDateFormatted() . '</span>');
        }
        if ($repoController->getPackageType() == 'deb') {
            History::set('Running task: point environment(s) <span>' . trim($content) . '</span> to repository <span class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection() . '</span>⸺<span class="label-black">' . $repoController->getDateFormatted() . '</span>');
        }

        unset($repoController);
    }
}
