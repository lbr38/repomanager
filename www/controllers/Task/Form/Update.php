<?php

namespace Controllers\Task\Form;

use Exception;
use Controllers\Repo\Repo;
use Controllers\Repo\Snapshot\Snapshot;
use Controllers\History\Save as History;
use Controllers\Utils\Generate\Html\Label;

class Update
{
    public function validate(array $formParams): void
    {
        $repoController = new Repo();

        // Check that the snapshot id is valid
        Param\Snapshot::checkId($formParams['snap-id']);

        // Check architecture
        Param\Arch::check($formParams['arch']);

        // Check gpg sign
        Param\GpgSign::check($formParams['gpg-sign']);

        // Check scheduling parameters
        Param\Schedule::check($formParams['schedule']);

        // Retrieve all repo data from the Id
        // $repoController->setSnapId($formParams['snap-id']);
        $repoController->getAllById('', $formParams['snap-id'], '');

        // Check env
        if (!empty($formParams['env'])) {
            Param\Environment::check($formParams['env']);
        }

        // Case of a mirror repository, check additional parameters
        if ($repoController->getType() == 'mirror') {
            // Check keep latest versions of packages
            Param\KeepLatest::check($formParams['advanced-params']['packages']['keep-latest']);

            // Check package(s) to include
            Param\PackageInclude::check($formParams['advanced-params']['packages']['include']);

            // Check package(s) to exclude
            Param\PackageExclude::check($formParams['advanced-params']['packages']['exclude']);

            // Check gpg check
            Param\GpgCheck::check($formParams['gpg-check']);

            if ($repoController->getPackageType() == 'rpm') {
                // Check additional metadata files sync
                Param\Metadata::checkSync($formParams['advanced-params']['metadata-sync']['comps']);
                Param\Metadata::checkSync($formParams['advanced-params']['metadata-sync']['modules']);
                Param\Metadata::checkSync($formParams['advanced-params']['metadata-sync']['updateinfo']);
            }

            if ($repoController->getPackageType() == 'deb') {
                // Check metadata custom fields
                Param\Metadata::checkOrigin($formParams['advanced-params']['metadata-custom-fields']['origin']);
                Param\Metadata::checkLabel($formParams['advanced-params']['metadata-custom-fields']['label']);
                Param\Metadata::checkDescription($formParams['advanced-params']['metadata-custom-fields']['description']);
            }
        }

        // Add history
        if ($repoController->getPackageType() == 'rpm') {
            History::set('Running task: update ' . $repoController->getType() . ' repository ' . Label::white($repoController->getName()));
        }
        if ($repoController->getPackageType() == 'deb') {
            History::set('Running task: update ' . $repoController->getType() . ' repository ' . Label::white($repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection()));
        }

        unset($repoController);
    }
}
