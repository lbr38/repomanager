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
        ob_start();

        $this->taskLog->step('FINALIZING');

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
                $this->repo->addEnv($this->repo->getEnv(), $this->repo->getDescription(), $this->repo->getSnapId());
            }
        }

        if ($this->task->getAction() == 'update') {
            /**
             *  Dans le cas où la nouvelle date du snapshot est la même que l'ancienne
             *  (cas où on remet à jour le même snapshot le même jour) alors on met seulement à jour quelques
             *  informations de base du repo en base de données et rien d'autre.
             */
            if ($this->sourceRepo->getDate() == $this->repo->getDate()) {
                /**
                 *  Mise à jour de l'état de la signature GPG
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
                 *  Mise à jour de la date
                 */
                $this->repo->snapSetDate($this->repo->getSnapId(), date('Y-m-d'));

                /**
                 *  Mise à jour de l'heure
                 */
                $this->repo->snapSetTime($this->repo->getSnapId(), date('H:i'));

            /**
             *  Sinon on ajoute un nouveau snapshot en base de données à la date du jour
             */
            } else {
                /**
                 *  Cas où un nouveau snapshot a été créé, on l'ajoute en base de données
                 */
                $this->repo->addSnap($this->repo->getDate(), $this->repo->getTime(), $this->repo->getGpgSign(), $this->repo->getArch(), array(), $this->repo->getPackagesToInclude(), $this->repo->getPackagesToExclude(), 'mirror', 'active', $this->repo->getRepoId());

                /**
                 *  On récupère l'Id du snapshot précédemment créé
                 *  Et on peut du coup définir que snapId = cet Id
                 */
                $this->repo->setSnapId($this->repo->getLastInsertRowID());
            }
        }

        /**
         *  Si l'utilisateur a renseigné un environnement à faire pointer sur le snapshot créé
         */
        if (!empty($this->repo->getEnv())) {
            /**
             *  Si l'utilisateur n'a précisé aucune description alors on récupère celle actuellement en place sur l'environnement de même nom (si l'environnement existe et si il possède une description)
             */
            if (empty($this->repo->getDescription())) {
                if ($this->repo->getPackageType() == 'rpm') {
                    $actualDescription = $this->repo->getDescriptionByName($this->repo->getName(), '', '', $this->repo->getEnv());
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $actualDescription = $this->repo->getDescriptionByName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getEnv());
                }

                /**
                 *  Si la description récupérée est vide alors la description restera vide
                 */
                if (!empty($actualDescription)) {
                    $this->repo->setDescription($actualDescription);
                } else {
                    $this->repo->setDescription('');
                }
            }

            /**
             *  On récupère l'Id de l'environnement actuellement an place (si il y en a un)
             */
            $actualEnvIds = $this->repo->getEnvIdFromRepoName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getEnv());

            /**
             *  On supprime l'éventuel environnement de même nom pointant déjà vers un snapshot de ce repo (si il y en a un)
             */
            if (!empty($actualEnvIds)) {
                foreach ($actualEnvIds as $actualEnvId) {
                    $this->repo->removeEnv($actualEnvId['Id']);
                }
            }

            /**
             *  Puis on déclare le nouvel environnement et on le fait pointer vers le snapshot précédemment créé
             */
            $this->repo->addEnv($this->repo->getEnv(), $this->repo->getDescription(), $this->repo->getSnapId());
        }

        /**
         *  3. Application des droits sur le snapshot créé
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

        $this->taskLog->stepOK();

        /**
         *  Ajout du repo à un groupe si un groupe a été renseigné.
         *  Uniquement si il s'agit d'un nouveau repo/section ($this->task->getAction() = new)
         */
        if ($this->task->getAction() == 'create' and !empty($this->repo->getGroup())) {
            $this->taskLog->step('ADDING TO GROUP');
            $this->repo->addRepoIdToGroup($this->repo->getRepoId(), $this->repo->getGroup());
            $this->taskLog->stepOK();
        }

        /**
         *  Nettoyage automatique des snapshots inutilisés
         */
        $snapshotsRemoved = $this->repo->cleanSnapshots();

        if (!empty($snapshotsRemoved)) {
            $this->taskLog->step('CLEANING');
            $this->taskLog->stepOK($snapshotsRemoved);
        }

        /**
         *  Nettoyage des repos inutilisés dans les groupes
         */
        $this->repo->cleanGroups();
    }
}
