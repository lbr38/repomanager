<?php

namespace Controllers\Task\Form;

use Controllers\Repo\Repo;
use Controllers\History\Save as History;

class Update
{
    public function validate(array $formParams): void
    {
        $repoController = new Repo();

        // Check that the snapshot id is valid
        Param\Snapshot::checkId($formParams['snap-id']);

        // Retrieve all repo data from the Id
        $repoController->setSnapId($formParams['snap-id']);
        $repoController->getAllById('', $formParams['snap-id'], '');

        // Check env
        if (!empty($formParams['env'])) {
            Param\Environment::check($formParams['env']);
        }

        // Check architecture
        Param\Arch::check($formParams['arch']);

        // Case of a mirror repository, check additional parameters
        if ($repoController->getType() == 'mirror') {
            // Check package(s) to include
            Param\PackageInclude::check($formParams['advanced-params']['packages']['include']);

            // Check package(s) to exclude
            Param\PackageExclude::check($formParams['advanced-params']['packages']['exclude']);

            // Check gpg check
            Param\GpgCheck::check($formParams['gpg-check']);

            if ($repoController->getPackageType() == 'deb') {
                // Check metadata custom fields
                Param\Metadata::checkOrigin($formParams['advanced-params']['metadata-custom-fields']['origin']);
                Param\Metadata::checkLabel($formParams['advanced-params']['metadata-custom-fields']['label']);
                Param\Metadata::checkDescription($formParams['advanced-params']['metadata-custom-fields']['description']);
            }
        }

        // Check gpg sign
        Param\GpgSign::check($formParams['gpg-sign']);

        // Check scheduling parameters
        Param\Schedule::check($formParams['schedule']);

        // Add history
        if ($repoController->getPackageType() == 'rpm') {
            History::set('Running task: update ' . $repoController->getType() . ' repository <span class="label-white">' . $repoController->getName() . '</span>');
        }
        if ($repoController->getPackageType() == 'deb') {
            History::set('Running task: update ' . $repoController->getType() . ' repository <span class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection() . '</span>');
        }

        unset($repoController);
    }
}
