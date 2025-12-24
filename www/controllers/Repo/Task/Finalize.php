<?php

namespace Controllers\Repo\Task;

use \Controllers\Filesystem\File;
use Exception;

trait Finalize
{
    /**
     *  Finalize the repository: add to the database and apply permissions
     */
    protected function finalize()
    {
        $this->taskLogStepController->new('finalizing', 'FINALIZING');

        // Define snapshot path
        if ($this->repoController->getPackageType() == 'rpm') {
            $snapshotPath = REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $this->repoController->getDate();
        }
        if ($this->repoController->getPackageType() == 'deb') {
            $snapshotPath = REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $this->repoController->getDate();
        }

        $this->taskLogSubStepController->new('updating-database', 'UPDATING DATABASE');

        /**
         *  Update the database
         *  If the task is a 'create' then we add the repository to the database.
         */
        if ($this->action == 'create') {
            /**
             *  If currently no rpm repo of this name exists in the database then we add it
             */
            if ($this->repoController->getPackageType() == 'rpm') {
                if (!$this->rpmRepoController->exists($this->repoController->getName(), $this->repoController->getReleasever())) {
                    $this->rpmRepoController->add($this->repoController->getName(), $this->repoController->getReleasever(), $this->repoController->getSource());

                    /**
                     *  Repository Id becomes the Id of the last inserted row in the database
                     */
                    $this->repoController->setRepoId($this->rpmRepoController->getLastInsertRowID());

                /**
                 *  Otherwise, if a repo of the same name exists, we retrieve its Id from the database
                 */
                } else {
                    $this->repoController->setRepoId($this->rpmRepoController->getIdByNameReleasever($this->repoController->getName(), $this->repoController->getReleasever()));
                }
            }

            /**
             *  If currently no deb repo of this name exists in the database then we add it
             */
            if ($this->repoController->getPackageType() == 'deb') {
                if (!$this->debRepoController->exists($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection())) {
                    $this->debRepoController->add($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection(), $this->repoController->getSource());

                    /**
                     *  Repository Id becomes the Id of the last inserted row in the database
                     */
                    $this->repoController->setRepoId($this->debRepoController->getLastInsertRowID());

                /**
                 *  Otherwise, if a repo of the same name exists, we retrieve its Id from the database
                 */
                } else {
                    $this->repoController->setRepoId($this->debRepoController->getIdByNameDistComponent($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection()));
                }
            }

            /**
             *  Add snapshot in database
             *  Empty array() for package translation because it's not used for the moment
             */
            $this->repoSnapshotController->add($this->repoController->getDate(), $this->repoController->getTime(), $this->repoController->getGpgSign(), $this->repoController->getArch(), array(), $this->repoController->getPackagesToInclude(), $this->repoController->getPackagesToExclude(), $this->repoController->getType(), 'active', $this->repoController->getRepoId());

            /**
             *  Retrieve the last insert row ID
             */
            $this->repoController->setSnapId($this->repoSnapshotController->getLastInsertRowID());

            /**
             *  Add env in database if an env has been specified by the user
             */
            if (!empty($this->repoController->getEnv())) {
                foreach ($this->repoController->getEnv() as $env) {
                    $this->repoEnvController->add($env, $this->repoController->getDescription(), $this->repoController->getSnapId());
                }
            }
        }

        if ($this->action == 'update') {
            /**
             *  Case where the new snapshot date is the same as the old one,
             *  We only update the repository information in the database and nothing else.
             */
            if ($this->sourceRepoController->getDate() == $this->repoController->getDate()) {
                /**
                 *  Update GPG signature state
                 */
                $this->repoController->snapSetSigned($this->repoController->getSnapId(), $this->repoController->getGpgSign());

                /**
                 *  Update architecture (it could be different from the previous one)
                 */
                $this->repoController->snapSetArch($this->repoController->getSnapId(), $this->repoController->getArch());

                /**
                 *  Update packages to include (it could be different from the previous one)
                 */
                $this->repoController->snapSetPackagesIncluded($this->repoController->getSnapId(), $this->repoController->getPackagesToInclude());

                /**
                 *  Update packages to exclude (it could be different from the previous one)
                 */
                $this->repoController->snapSetPackagesExcluded($this->repoController->getSnapId(), $this->repoController->getPackagesToExclude());

                /**
                 *  Update date
                 */
                $this->repoController->snapSetDate($this->repoController->getSnapId(), $this->repoController->getDate());

                /**
                 *  Update time
                 */
                $this->repoController->snapSetTime($this->repoController->getSnapId(), $this->repoController->getTime());

            /**
             *  Otherwise we add a new snapshot in the database with today's date
             */
            } else {
                /**
                 *  Add snapshot in database
                 */
                $this->repoSnapshotController->add($this->repoController->getDate(), $this->repoController->getTime(), $this->repoController->getGpgSign(), $this->repoController->getArch(), array(), $this->repoController->getPackagesToInclude(), $this->repoController->getPackagesToExclude(), $this->repoController->getType(), 'active', $this->repoController->getRepoId());

                /**
                 *  Retrieve the last insert row Id
                 *  And we can set snapId = this Id
                 */
                $this->repoController->setSnapId($this->repoSnapshotController->getLastInsertRowID());
            }
        }

        $this->taskLogSubStepController->completed();

        /**
         *  If the user has specified an environment to point to the created snapshot
         */
        if (!empty($this->repoController->getEnv())) {
            $this->taskLogSubStepController->new('adding-env', 'ADDING ENVIRONMENT');

            foreach ($this->repoController->getEnv() as $env) {
                /**
                 *  If the user has not specified any description, then we retrieve the one currently in place on the environment of the same name (if the environment exists and if it has a description)
                 */
                if (empty($this->repoController->getDescription())) {
                    if ($this->repoController->getPackageType() == 'rpm') {
                        $actualDescription = $this->rpmRepoController->getDescriptionByName($this->repoController->getName(), $this->repoController->getReleasever(), $env);
                    }
                    if ($this->repoController->getPackageType() == 'deb') {
                        $actualDescription = $this->debRepoController->getDescriptionByName($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection(), $env);
                    }

                    /**
                     *  If the retrieved description is empty then the description will remain empty
                     */
                    if (!empty($actualDescription)) {
                        $this->repoController->setDescription(htmlspecialchars_decode($actualDescription));
                    } else {
                        $this->repoController->setDescription('');
                    }
                }

                /**
                 *  Retrieve the Id of the environment currently in place (if there is one)
                 */
                if ($this->repoController->getPackageType() == 'rpm') {
                    $actualEnvIds = $this->rpmRepoController->getEnvIdFromRepoName($this->repoController->getName(), $this->repoController->getReleasever(), $env);
                }
                if ($this->repoController->getPackageType() == 'deb') {
                    $actualEnvIds = $this->debRepoController->getEnvIdFromRepoName($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection(), $env);
                }

                /**
                 *  Delete the possible environment of the same name pointing to a snapshot of this repo (if there is one)
                 */
                if (!empty($actualEnvIds)) {
                    foreach ($actualEnvIds as $actualEnvId) {
                        $this->repoEnvController->remove($actualEnvId);
                    }
                }

                /**
                 *  Then we declare the new environment and make it point to the previously created snapshot
                 */
                $this->repoEnvController->add($env, $this->repoController->getDescription(), $this->repoController->getSnapId());
            }

            $this->taskLogSubStepController->completed();
        }

        /**
         *  Apply permissions on the created snapshot
         */
        $this->taskLogSubStepController->new('applying-permissions', 'APPLYING PERMISSIONS');

        if ($this->repoController->getPackageType() == 'rpm') {
            File::recursiveChmod(REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $this->repoController->getDate(), 'dir', 770);
            File::recursiveChmod(REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $this->repoController->getDate(), 'file', 660);
        }
        if ($this->repoController->getPackageType() == 'deb') {
            File::recursiveChmod(REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $this->repoController->getDate(), 'dir', 770);
            File::recursiveChmod(REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $this->repoController->getDate(), 'file', 660);
        }

        $this->taskLogSubStepController->completed();
        $this->taskLogStepController->completed();

        /**
         *  Add repository to a group if a group has been specified.
         *  Only if it is a new repo/section (create)
         */
        if ($this->action == 'create' and !empty($this->repoController->getGroup())) {
            $this->taskLogStepController->new('adding-to-group', 'ADDING REPOSITORY TO GROUP');
            $this->repoController->addRepoIdToGroup($this->repoController->getRepoId(), $this->repoController->getGroup());
            $this->taskLogStepController->completed();
        }

        $this->taskLogStepController->new('cleaning', 'CLEANING');

        // Clean .completed and .signed files left
        $this->taskLogSubStepController->new('cleaning-temp-files', 'CLEANING TEMPORARY FILES');

        try {
            $completedFiles = File::findRecursive($snapshotPath, ['completed'], true);
            $signedFiles = File::findRecursive($snapshotPath, ['signed'], true);

            foreach (array_merge($completedFiles, $signedFiles) as $file) {
                if (!unlink($file)) {
                    throw new Exception('cannot remove file ' . $file);
                }
            }
        } catch (Exception $e) {
            throw new Exception('Error while cleaning temporary files: ' . $e->getMessage());
        }

        $this->taskLogSubStepController->completed();

        // Clean unused repos in groups
        $this->taskLogSubStepController->new('cleaning-groups', 'CLEANING GROUPS');

        try {
            $this->repoController->cleanGroups();
        } catch (Exception $e) {
            throw new Exception('Error while cleaning groups: ' . $e->getMessage());
        }

        $this->taskLogSubStepController->completed();

        // Clean unused snapshots
        $this->taskLogSubStepController->new('cleaning-snapshots', 'CLEANING SNAPSHOTS');
        $this->taskLogSubStepController->completed($this->repoSnapshotController->clean());

        $this->taskLogStepController->completed();
    }
}
