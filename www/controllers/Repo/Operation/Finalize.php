<?php

namespace Controllers\Repo\Operation;

use Exception;

trait Finalize
{
    /**
    *   Finalisation du repo : ajout en base de données et application des droits
    */
    protected function finalize()
    {
        ob_start();

        $this->log->step('FINALIZING');

        /**
         *  Le type d'opération doit être renseigné pour cette fonction (soit 'new' soit 'update')
         */
        if (empty($this->operation->getAction())) {
            throw new Exception('operation type unknown (empty)');
        }
        if ($this->operation->getAction() != 'new' and $this->operation->getAction() != 'update') {
            throw new Exception('operation type is invalid');
        }

        /**
         *  1. Mise à jour de la BDD
         *  - Si il s'agit d'un nouveau repo alors on l'ajoute en base de données
         */
        if ($this->operation->getAction() == 'new') {
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
             *  Ajout du snapshot en base de données
             */
            $this->repo->addSnap($this->repo->getTargetDate(), $this->repo->getTargetTime(), $this->repo->getTargetGpgResign(), $this->repo->getTargetArch(), $this->repo->getTargetPackageTranslation(), $this->repo->getType(), 'active', $this->repo->getRepoId());

            /**
             *  Récupération de l'Id du snapshot ajouté précédemment
             */
            $this->repo->setSnapId($this->repo->getLastInsertRowID());

            /**
             *  Ajout de l'env en base de données, si un environnement a été spécifié par l'utilisateur
             */
            if (!empty($this->repo->getTargetEnv())) {
                $this->repo->addEnv($this->repo->getTargetEnv(), $this->repo->getTargetDescription(), $this->repo->getSnapId());
            }
        }

        if ($this->operation->getAction() == 'update') {
            /**
             *  Dans le cas où la nouvelle date du snapshot est la même que l'ancienne
             *  (cas où on remet à jour le même snapshot le même jour) alors on met seulement à jour quelques
             *  informations de base du repo en base de données et rien d'autre.
             */
            if ($this->repo->getTargetDate() == $this->repo->getDate()) {
                /**
                 *  Mise à jour de l'état de la signature GPG
                 */
                $this->repo->snapSetSigned($this->repo->getSnapId(), $this->repo->getTargetGpgResign());

                /**
                 *  Update architecture (it could be different from the previous one)
                 */
                $this->repo->snapSetArch($this->repo->getSnapId(), $this->repo->getTargetArch());

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
                $this->repo->addSnap($this->repo->getTargetDate(), $this->repo->getTargetTime(), $this->repo->getTargetGpgResign(), $this->repo->getTargetArch(), $this->repo->getTargetPackageTranslation(), 'mirror', 'active', $this->repo->getRepoId());

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
        if (!empty($this->repo->getTargetEnv())) {

            /**
             *  Si l'utilisateur n'a précisé aucune description alors on récupère celle actuellement en place sur l'environnement de même nom (si l'environnement existe et si il possède une description)
             */
            if (empty($this->repo->getTargetDescription())) {
                if ($this->repo->getPackageType() == 'rpm') {
                    $actualDescription = $this->repo->getDescriptionByName($this->repo->getName(), '', '', $this->repo->getTargetEnv());
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $actualDescription = $this->repo->getDescriptionByName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getTargetEnv());
                }

                /**
                 *  Si la description récupérée est vide alors la description restera vide
                 */
                if (!empty($actualDescription)) {
                    $this->repo->setTargetDescription($actualDescription);
                } else {
                    $this->repo->setTargetDescription('');
                }
            }

            /**
             *  On récupère l'Id de l'environnement actuellement an place (si il y en a un)
             */
            $actualEnvIds = $this->repo->getEnvIdFromRepoName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getTargetEnv());

            /**
             *  On supprime l'éventuel environnement de même nom pointant déjà vers un snapshot de ce repo (si il y en a un)
             */
            if (!empty($actualEnvIds)) {
                foreach ($actualEnvIds as $actualEnvId) {
                    $this->repo->removeEnv($actualEnvId);
                }
            }

            /**
             *  Puis on déclare le nouvel environnement et on le fait pointer vers le snapshot précédemment créé
             */
            $this->repo->addEnv($this->repo->getTargetEnv(), $this->repo->getTargetDescription(), $this->repo->getSnapId());
        }

        /**
         *  3. Application des droits sur le snapshot créé
         */
        if ($this->repo->getPackageType() == 'rpm') {
            exec('find ' . REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName() . '/ -type f -exec chmod 0660 {} \;');
            exec('find ' . REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName() . '/ -type d -exec chmod 0770 {} \;');
            exec('chown -R ' . WWW_USER . ':repomanager ' . REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName());
            /*if [ $? -ne "0" ];then
                echo "<br><span class=\"redtext\">Erreur :</span>l'application des permissions sur le repo <b>$this->repo->getName()</b> a échoué"
            fi*/
        }
        if ($this->repo->getPackageType() == 'deb') {
            exec('find ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getSection() . '/ -type f -exec chmod 0660 {} \;');
            exec('find ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getSection() . '/ -type d -exec chmod 0770 {} \;');
            exec('chown -R ' . WWW_USER . ':repomanager ' . REPOS_DIR . '/' . $this->repo->getName());
            /*if [ $? -ne "0" ];then
                echo "<br><span class=\"redtext\">Erreur :</span>l'application des permissions sur la section <b>$this->repo->getSection()</b> a échoué"
            fi*/
        }

        $this->log->stepOK();

        /**
         *  Ajout du repo à un groupe si un groupe a été renseigné.
         *  Uniquement si il s'agit d'un nouveau repo/section ($this->operation->getAction() = new)
         */
        if ($this->operation->getAction() == 'new' and !empty($this->repo->getTargetGroup())) {
            $this->log->step('ADDING TO GROUP');
            $this->repo->addRepoIdToGroup($this->repo->getRepoId(), $this->repo->getTargetGroup());
            $this->log->stepOK();
        }

        /**
         *  Nettoyage automatique des snapshots inutilisés
         */
        $snapshotsRemoved = $this->repo->cleanSnapshots();

        if (!empty($snapshotsRemoved)) {
            $this->log->step('CLEANING');
            $this->log->stepOK($snapshotsRemoved);
        }

        /**
         *  Nettoyage des repos inutilisés dans les groupes
         */
        $this->repo->cleanGroups();

        // return true;
    }
}
