<?php

namespace Controllers\Task\Repo;

use Exception;

trait Finalize
{
    /**
    *   Finalisation du repo : ajout en base de données et application des droits
    */
    protected function finalize()
    {
        $this->taskLogStepController->new('finalizing', 'FINALIZING');

        /**
         *  Le type d'opération doit être renseigné pour cette fonction (soit 'create' soit 'update')
         */
        if (empty($this->task->getAction())) {
            throw new Exception('task action unknown (empty)');
        }
        if ($this->task->getAction() != 'create' and $this->task->getAction() != 'update') {
            throw new Exception('task action is invalid');
        }

        /**
         *  1. Mise à jour de la BDD
         *  - Si il s'agit d'un nouveau repo alors on l'ajoute en base de données
         */
        if ($this->task->getAction() == 'create') {
            /**
             *  Si actuellement aucun repo rpm de ce nom n'existe en base de données alors on l'ajoute
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if ($this->repo->exists($this->repo->getName()) === false) {
                    $this->repo->add($this->repo->getSource(), 'rpm', $this->repo->getName());

                    /**
                     *  L'Id du repo devient alors l'Id de la dernière ligne insérée en base de données
                     */
                    $this->repo->setRepoId($this->repo->getLastInsertRowID());

                    /**
                     *  Set repo releasever
                     */
                    $this->repo->updateReleasever($this->repo->getRepoId(), $this->repo->getReleasever());

                /**
                 *  Sinon si un repo de même nom existe, on récupère son Id en base de données
                 */
                } else {
                    $this->repo->setRepoId($this->repo->getIdByName($this->repo->getName(), '', ''));
                }
            }

            /**
             *  Si actuellement aucun repo deb de ce nom n'existe en base de données alors on l'ajoute
             */
            if ($this->repo->getPackageType() == 'deb') {
                if ($this->repo->exists($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection()) === false) {
                    $this->repo->add($this->repo->getSource(), 'deb', $this->repo->getName());

                    /**
                     *  L'Id du repo devient alors l'Id de la dernière ligne insérée en base de données
                     */
                    $this->repo->setRepoId($this->repo->getLastInsertRowID());

                    /**
                     *  Set repo dist and section
                     */
                    $this->repo->updateDist($this->repo->getRepoId(), $this->repo->getDist());
                    $this->repo->updateSection($this->repo->getRepoId(), $this->repo->getSection());

                /**
                 *  Sinon si un repo de même nom existe, on récupère son Id en base de données
                 */
                } else {
                    $this->repo->setRepoId($this->repo->getIdByName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection()));
                }
            }

            /**
             *  Add snapshot in database
             *  Empty array() for package translation because it's not used for the moment
             */
            $this->repo->addSnap($this->repo->getDate(), $this->repo->getTime(), $this->repo->getGpgSign(), $this->repo->getArch(), array(), $this->repo->getPackagesToInclude(), $this->repo->getPackagesToExclude(), $this->repo->getType(), 'active', $this->repo->getRepoId());

            /**
             *  Retrieve the last insert row ID
             */
            $this->repo->setSnapId($this->repo->getLastInsertRowID());

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
                $this->repo->snapSetDate($this->repo->getSnapId(), date('Y-m-d'));

                /**
                 *  Update time
                 */
                $this->repo->snapSetTime($this->repo->getSnapId(), date('H:i'));

            /**
             *  Otherwise we add a new snapshot in the database with today's date
             */
            } else {
                /**
                 *  Add snapshot in database
                 */
                $this->repo->addSnap($this->repo->getDate(), $this->repo->getTime(), $this->repo->getGpgSign(), $this->repo->getArch(), array(), $this->repo->getPackagesToInclude(), $this->repo->getPackagesToExclude(), $this->repo->getType(), 'active', $this->repo->getRepoId());

                /**
                 *  Retrieve the last insert row Id
                 *  And we can set snapId = this Id
                 */
                $this->repo->setSnapId($this->repo->getLastInsertRowID());
            }
        }

        /**
         *  If the user has specified an environment to point to the created snapshot
         */
        if (!empty($this->repo->getEnv())) {
            foreach ($this->repo->getEnv() as $env) {
                /**
                 *  If the user has not specified any description, then we retrieve the one currently in place on the environment of the same name (if the environment exists and if it has a description)
                 */
                if (empty($this->repo->getDescription())) {
                    if ($this->repo->getPackageType() == 'rpm') {
                        $actualDescription = $this->repo->getDescriptionByName($this->repo->getName(), '', '', $env);
                    }
                    if ($this->repo->getPackageType() == 'deb') {
                        $actualDescription = $this->repo->getDescriptionByName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $env);
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
                $actualEnvIds = $this->repo->getEnvIdFromRepoName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $env);

                /**
                 *  Delete the possible environment of the same name pointing to a snapshot of this repo (if there is one)
                 */
                if (!empty($actualEnvIds)) {
                    foreach ($actualEnvIds as $actualEnvId) {
                        $this->repo->removeEnv($actualEnvId['Id']);
                    }
                }

                /**
                 *  Then we declare the new environment and make it point to the previously created snapshot
                 */
                $this->repoEnvController->add($env, $this->repo->getDescription(), $this->repo->getSnapId());
            }
        }

        /**
         *  Apply permissions on the created snapshot
         */
        if ($this->repo->getPackageType() == 'rpm') {
            \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName(), 'file', 660);
            \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName(), 'dir', 770);
            \Controllers\Filesystem\File::recursiveChown(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName(), WWW_USER, 'repomanager');
        }
        if ($this->repo->getPackageType() == 'deb') {
            \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection(), 'file', 660);
            \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection(), 'dir', 770);
            \Controllers\Filesystem\File::recursiveChown(REPOS_DIR . '/' . $this->repo->getName(), WWW_USER, 'repomanager');
        }

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
        try {
            $snapshotsRemoved = $this->repoSnapshotController->clean();
            $this->taskLogStepController->completed($snapshotsRemoved);
        } catch (Exception $e) {
            $this->taskLogStepController->error($e->getMessage());
        }
    }
}
