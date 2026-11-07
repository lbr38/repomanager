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

        /**
         *  Only one snapshot per day: a same day update replaces the snapshot of the current day
         *  Scheduled tasks are checked when executed, as their execution date is not known yet
         */
        if ($repoController->getType() == 'filtered' and ($formParams['schedule']['scheduled'] ?? 'false') != 'true') {
            $repoSnapshotController = new Snapshot();

            // An update applies to the latest snapshot: updating an older snapshot would replace the snapshot of the current day
            if (!empty($repoSnapshotController->getOtherTodaySnapId($repoController->getRepoId(), (int) $formParams['snap-id']))) {
                throw new Exception('Snapshot ' . Label::white(htmlspecialchars($repoController->getLabel()) . ' ⸺ ' . $repoController->getDateFormatted()) . ' cannot be updated today: the snapshot of the ' . Label::white(date('d-m-Y')) . ' would be replaced (only one snapshot per day). Update the latest snapshot instead');
            }

            // The snapshot of the current day cannot be replaced if it is used by a protected environment
            if ($repoController->getDate() == date('Y-m-d')) {
                $protectedEnvs = $repoSnapshotController->getProtectedEnvs((int) $formParams['snap-id']);

                if (!empty($protectedEnvs)) {
                    throw new Exception('Snapshot ' . Label::white(htmlspecialchars($repoController->getLabel()) . ' ⸺ ' . $repoController->getDateFormatted()) . ' is used by ' . \Controllers\Environment::nameList($protectedEnvs, 'protected') . ' and cannot be replaced by an update on the same day');
                }
            }
        }

        // Case of a mirror repository, check additional parameters
        if ($repoController->getType() == 'filtered') {
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
