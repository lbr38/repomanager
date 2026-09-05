<?php

namespace Controllers\Task\Form;

use Exception;
use Controllers\Repo\Repo;
use Controllers\Environment;
use Controllers\Utils\Generate\Html\Label;
use Controllers\History\Save as History;

class Env
{
    public function validate(array $formParams): void
    {
        $repoController = new Repo();
        $envController = new Environment();

        // Check that the snapshot id is valid
        Param\Snapshot::checkId($formParams['snap-id']);

        // Check environment
        Param\Environment::check($formParams['env']);

        // Check scheduling parameters
        Param\Schedule::check($formParams['schedule']);

        // Check if the env is protected
        foreach ($formParams['env'] as $env) {
            if ($envController->isProtected($env)) {
                throw new Exception('Environment ' . $env . ' is protected and cannot be moved');
            }
        }

        // Retrieve all repo data from the Id
        // $repoController->setSnapId($formParams['snap-id']);
        $repoController->getAllById('', $formParams['snap-id'], '');

        // Add history
        $content = '';
        foreach ($formParams['env'] as $env) {
            $content .= Label::envtag($env) . ' ';
        }

        if ($repoController->getPackageType() == 'rpm') {
            History::set('Running task: point environment(s) <span>' . trim($content) . '</span> to repository ' . Label::white($repoController->getName()) . '⸺' . Label::white($repoController->getDateFormatted()));
        }
        if ($repoController->getPackageType() == 'deb') {
            History::set('Running task: point environment(s) <span>' . trim($content) . '</span> to repository ' . Label::white($repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection()) . '⸺' . Label::white($repoController->getDateFormatted()));
        }

        unset($repoController);
    }
}
