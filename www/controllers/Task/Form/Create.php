<?php

namespace Controllers\Task\Form;

use Exception;

class Create
{
    public function validate(array $formParams)
    {
        $rpmRepoController = new \Controllers\Repo\Rpm();
        $debRepoController = new \Controllers\Repo\Deb();
        $myhistory = new \Controllers\History();

        /**
         *  Check package type
         */
        Param\PackageType::check($formParams['package-type']);

        /**
         *  Check repo type
         */
        Param\RepoType::check($formParams['repo-type']);

        /**
         *  Case package type is 'rpm'
         */
        if ($formParams['package-type'] == 'rpm') {
            /**
             *  Check releasever
             */
            Param\Releasever::check($formParams['releasever']);
        }

        /**
         *  Case package type is 'deb'
         */
        if ($formParams['package-type'] == 'deb') {
            /**
             *  Check distribution
             */
            Param\Dist::check($formParams['dist']);

            /**
             *  Check section
             */
            Param\Section::check($formParams['section']);
        }

        /**
         *  Check env
         */
        if (!empty($formParams['env'])) {
            Param\Environment::check($formParams['env']);
        }

        /**
         *  Check description
         */
        Param\Description::check($formParams['description']);

        /**
         *  Check group
         */
        if (!empty($formParams['group'])) {
            Param\Group::check($formParams['group']);
        }

        /**
         *  If the selected repo type is 'local' we will have to check that a name has been provided (can be left empty in the case of a mirror)
         */
        if ($formParams['repo-type'] == 'local') {
            $targetName = $formParams['alias'];
        }

        /**
         *  If the selected repo type is 'mirror' then we check additional parameters
         */
        if ($formParams['repo-type'] == 'mirror') {
            /**
             *  Check source
             */
            Param\Source::check($formParams['source'], $formParams['package-type']);

            /**
             *  If no alias has been given, we use the source name as alias
             */
            if (!empty($formParams['alias'])) {
                $targetName = $formParams['alias'];
            } else {
                $targetName = $formParams['source'];
            }

            /**
             *  Check gpg check
             */
            Param\GpgCheck::check($formParams['gpg-check']);

            /**
             *  Check gpg sign
             */
            Param\GpgSign::check($formParams['gpg-sign']);

            /**
             *  Check package(s) to include
             */
            Param\PackageInclude::check($formParams['package-include']);

            /**
             *  Check package(s) to exclude
             */
            Param\PackageExclude::check($formParams['package-exclude']);
        }

        /**
         *  Check name
         */
        Param\Name::check($targetName);

        /**
         *  Check architecture
         */
        Param\Arch::check($formParams['arch']);

        /**
         *  Check if a repository with the same name is already active with snapshots
         */
        if ($formParams['package-type'] == 'rpm') {
            foreach ($formParams['releasever'] as $releasever) {
                if ($rpmRepoController->isActive($targetName, $releasever)) {
                    throw new Exception('<span class="label-white">' . $targetName . ' ❯ ' . $releasever . '</span> repository already exists');
                }
            }
        }

        if ($formParams['package-type'] == 'deb') {
            /**
             *  For deb repo, we check that no repo/dist/section with the same name is already active
             */
            foreach ($formParams['dist'] as $distribution) {
                foreach ($formParams['section'] as $section) {
                    if ($debRepoController->isActive($targetName, $distribution, $section)) {
                        throw new Exception('<span class="label-white">' . $targetName . ' ❯ ' . $distribution . ' ❯ ' . $section . '</span> repository already exists');
                    }
                }
            }
        }

        /**
         *  Check scheduling parameters
         */
        Param\Schedule::check($formParams['schedule']);

        /**
         *  Add history
         */
        if ($formParams['package-type'] == 'rpm') {
            $myhistory->set('Running task: New repository <span class="label-white">' . $targetName . '</span> (' . $formParams['repo-type'] . ')', 'success');
        }

        if ($formParams['package-type'] == 'deb') {
            foreach ($formParams['dist'] as $distribution) {
                foreach ($formParams['section'] as $section) {
                    $myhistory->set('Running task: New repository <span class="label-white">' . $targetName . ' ❯ ' . $distribution . ' ❯ ' . $section . '</span> (' . $formParams['repo-type'] . ')', 'success');
                }
            }
        }

        unset($rpmRepoController, $debRepoController, $myhistory);
    }
}
