<?php

namespace Controllers\Task\Repo;

use Exception;

trait Finalize
{
    /**
     *  Finalize the repository: add to the database and apply permissions
     */
    protected function finalize()
    {
        $this->taskLogStepController->new('finalizing', 'FINALIZING');
        $this->taskLogSubStepController->new('updating-database', 'UPDATING DATABASE');

        /**
         *  Update the database
         *  If the task is a 'create' then we add the repository to the database.
         */
        if ($this->task->getAction() == 'create') {
            /**
             *  If currently no rpm repo of this name exists in the database then we add it
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if (!$this->rpmRepoController->exists($this->repo->getName(), $this->repo->getReleasever())) {
                    $this->rpmRepoController->add($this->repo->getName(), $this->repo->getReleasever(), $this->repo->getSource());

                    /**
                     *  Repository Id becomes the Id of the last inserted row in the database
                     */
                    $this->repo->setRepoId($this->rpmRepoController->getLastInsertRowID());

                /**
                 *  Otherwise, if a repo of the same name exists, we retrieve its Id from the database
                 */
                } else {
                    $this->repo->setRepoId($this->rpmRepoController->getIdByNameReleasever($this->repo->getName(), $this->repo->getReleasever()));
                }
            }

            /**
             *  If currently no deb repo of this name exists in the database then we add it
             */
            if ($this->repo->getPackageType() == 'deb') {
                if (!$this->debRepoController->exists($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection())) {
                    $this->debRepoController->add($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getSource());

                    /**
                     *  Repository Id becomes the Id of the last inserted row in the database
                     */
                    $this->repo->setRepoId($this->debRepoController->getLastInsertRowID());

                /**
                 *  Otherwise, if a repo of the same name exists, we retrieve its Id from the database
                 */
                } else {
                    $this->repo->setRepoId($this->debRepoController->getIdByNameDistComponent($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection()));
                }
            }

            /**
             *  Add snapshot in database
             *  Empty array() for package translation because it's not used for the moment
             */
            $this->repoSnapshotController->add($this->repo->getDate(), $this->repo->getTime(), $this->repo->getGpgSign(), $this->repo->getArch(), array(), $this->repo->getPackagesToInclude(), $this->repo->getPackagesToExclude(), $this->repo->getType(), 'active', $this->repo->getRepoId());

            /**
             *  Retrieve the last insert row ID
             */
            $this->repo->setSnapId($this->repoSnapshotController->getLastInsertRowID());

            /**
             *  Add env in database if an env has been specified by the user
             */
            if (!empty($this->repo->getEnv())) {
                foreach ($this->repo->getEnv() as $env) {
                    $this->repoEnvController->add($env, $this->repo->getDescription(), $this->repo->getSnapId());
                }
            }
        }

        if ($this->task->getAction() == 'update') {
            /**
             *  Case where the new snapshot date is the same as the old one,
             *  We only update the repository information in the database and nothing else.
             */
            if ($this->sourceRepo->getDate() == $this->repo->getDate()) {
                /**
                 *  Update GPG signature state
                 */
                $this->repo->snapSetSigned($this->repo->getSnapId(), $this->repo->getGpgSign());

                /**
                 *  Update architecture (it could be different from the previous one)
                 */
                $this->repo->snapSetArch($this->repo->getSnapId(), $this->repo->getArch());

                /**
                 *  Update packages to include (it could be different from the previous one)
                 */
                $this->repo->snapSetPackagesIncluded($this->repo->getSnapId(), $this->repo->getPackagesToInclude());

                /**
                 *  Update packages to exclude (it could be different from the previous one)
                 */
                $this->repo->snapSetPackagesExcluded($this->repo->getSnapId(), $this->repo->getPackagesToExclude());

                /**
                 *  Update date
                 */
                $this->repo->snapSetDate($this->repo->getSnapId(), $this->repo->getDate());

                /**
                 *  Update time
                 */
                $this->repo->snapSetTime($this->repo->getSnapId(), $this->repo->getTime());

            /**
             *  Otherwise we add a new snapshot in the database with today's date
             */
            } else {
                /**
                 *  Add snapshot in database
                 */
                $this->repoSnapshotController->add($this->repo->getDate(), $this->repo->getTime(), $this->repo->getGpgSign(), $this->repo->getArch(), array(), $this->repo->getPackagesToInclude(), $this->repo->getPackagesToExclude(), $this->repo->getType(), 'active', $this->repo->getRepoId());

                /**
                 *  Retrieve the last insert row Id
                 *  And we can set snapId = this Id
                 */
                $this->repo->setSnapId($this->repoSnapshotController->getLastInsertRowID());
            }
        }

        $this->taskLogSubStepController->completed();

        /**
         *  If the user has specified an environment to point to the created snapshot
         */
        if (!empty($this->repo->getEnv())) {
            $this->taskLogSubStepController->new('adding-env', 'ADDING ENVIRONMENT');

            foreach ($this->repo->getEnv() as $env) {
                /**
                 *  If the user has not specified any description, then we retrieve the one currently in place on the environment of the same name (if the environment exists and if it has a description)
                 */
                if (empty($this->repo->getDescription())) {
                    if ($this->repo->getPackageType() == 'rpm') {
                        $actualDescription = $this->rpmRepoController->getDescriptionByName($this->repo->getName(), $this->repo->getReleasever(), $env);
                    }
                    if ($this->repo->getPackageType() == 'deb') {
                        $actualDescription = $this->debRepoController->getDescriptionByName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $env);
                    }

                    /**
                     *  If the retrieved description is empty then the description will remain empty
                     */
                    if (!empty($actualDescription)) {
                        $this->repo->setDescription(htmlspecialchars_decode($actualDescription));
                    } else {
                        $this->repo->setDescription('');
                    }
                }

                /**
                 *  Retrieve the Id of the environment currently in place (if there is one)
                 */
                if ($this->repo->getPackageType() == 'rpm') {
                    $actualEnvIds = $this->rpmRepoController->getEnvIdFromRepoName($this->repo->getName(), $this->repo->getReleasever(), $env);
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $actualEnvIds = $this->debRepoController->getEnvIdFromRepoName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $env);
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
                $this->repoEnvController->add($env, $this->repo->getDescription(), $this->repo->getSnapId());
            }

            $this->taskLogSubStepController->completed();
        }

        /**
         *  Apply permissions on the created snapshot
         */
        $this->taskLogSubStepController->new('applying-permissions', 'APPLYING PERMISSIONS');

        if ($this->repo->getPackageType() == 'rpm') {
            \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever() . '/' . $this->repo->getDate(), 'file', 660);
            \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever() . '/' . $this->repo->getDate(), 'dir', 770);
            \Controllers\Filesystem\File::recursiveChown(REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever() . '/' . $this->repo->getDate(), WWW_USER, 'repomanager');
        }
        if ($this->repo->getPackageType() == 'deb') {
            \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '/' . $this->repo->getDate(), 'file', 660);
            \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '/' . $this->repo->getDate(), 'dir', 770);
            \Controllers\Filesystem\File::recursiveChown(REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '/' . $this->repo->getDate(), WWW_USER, 'repomanager');
        }

        $this->taskLogSubStepController->completed();
        $this->taskLogStepController->completed();

        /**
         *  Add repository to a group if a group has been specified.
         *  Only if it is a new repo/section (create)
         */
        if ($this->task->getAction() == 'create' and !empty($this->repo->getGroup())) {
            $this->taskLogStepController->new('adding-to-group', 'ADDING REPOSITORY TO GROUP');
            $this->repo->addRepoIdToGroup($this->repo->getRepoId(), $this->repo->getGroup());
            $this->taskLogStepController->completed();
        }

        $this->taskLogStepController->new('cleaning', 'CLEANING');

        /**
         *  Clean unused repos in groups
         */
        $this->repo->cleanGroups();

        /**
         *  Clean unused snapshots
         */
        $this->taskLogSubStepController->new('cleaning-snapshots', 'CLEANING SNAPSHOTS');
        $this->taskLogSubStepController->completed($this->repoSnapshotController->clean());

        $this->taskLogStepController->completed();
    }
}
