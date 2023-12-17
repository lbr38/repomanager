<?php

namespace Controllers\Repo\Operation;

use Exception;

class Env extends Operation
{
    public function __construct(string $poolId, array $operationParams)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->operation = new \Controllers\Operation\Operation();
        $this->log = new \Controllers\Log\OperationLog('repomanager', $this->operation->getPid());

        /**
         *  Check and set snapId parameter
         */
        $requiredParams = array('snapId');
        $this->operationParamsCheck('Repo environment', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $this->repo->getSnapId(), null);

        /**
         *  Check and set others operation parameters
         */
        $requiredParams = array('targetEnv');
        $optionnalParams = array('targetDescription');
        $this->operationParamsCheck('Repo environment', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams, $optionnalParams);

        /**
         *  Set operation details
         */
        $this->operation->setAction('env');
        $this->operation->setType('manual');
        $this->operation->setPoolId($poolId);
        $this->operation->setTargetSnapId($this->repo->getSnapId());
        $this->operation->setTargetEnvId($this->repo->getTargetEnv());
        $this->operation->setLogfile($this->log->getName());
        $this->operation->start();

        /**
         *  Run the operation
         */
        $this->env();
    }

    /**
     *  Point an environment to a snapshot
     */
    private function env()
    {
        /**
         *  Nettoyage du cache
         */
        \Controllers\App\Cache::clear();

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $this->log->runLogBuilder($this->operation->getPid(), $this->log->getLocation());

        try {
            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT . '/templates/tables/op-env.inc.php');

            $this->log->step('ADDING NEW ENVIRONMENT ' . \Controllers\Common::envtag($this->repo->getTargetEnv()));

            /**
             *  2. On vérifie si le snapshot source existe
             */
            if ($this->repo->existsSnapId($this->repo->getSnapId()) === false) {
                throw new Exception('Target snapshot does not exist');
            }

            /**
             *  3. On vérifie qu'un même environnement pointant vers le snapshot cible n'existe pas déjà
             */
            if ($this->repo->existsSnapIdEnv($this->repo->getSnapId(), $this->repo->getTargetEnv()) === true) {
                if ($this->repo->getPackageType() == 'rpm') {
                    throw new Exception('A ' . \Controllers\Common::envtag($this->repo->getTargetEnv()) . ' environment already exists on <span class="label-white">' . $this->repo->getName() . '</span>⟶<span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
                }

                if ($this->repo->getPackageType() == 'deb') {
                    throw new Exception('A ' . \Controllers\Common::envtag($this->repo->getTargetEnv()) . ' environment already exists on <span class="label-white">' . $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection() . '</span>⟶<span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
                }
            }

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
             *  4. Traitement
             *  Deux cas possibles :
             *   1. Ce repo/section n'avait pas d'environnement pointant vers le snapshot cible, on crée simplement un lien symbo et on crée le nouvel environnement en base de données.
             *   2. Ce repo/section avait déjà un environnement pointant vers un snapshot, on le supprime et on fait pointer l'environnement vers le nouveau snapshot.
             */
            if ($this->repo->getPackageType() == 'rpm') {
                /**
                 *  Cas 1 : pas d'environnement de même nom existant sur ce snapshot
                 */
                if ($this->repo->existsEnv($this->repo->getName(), null, null, $this->repo->getTargetEnv()) === false) {
                    /**
                     *  Suppression du lien symbolique (on sait ne jamais si il existe)
                     */
                    if (is_link(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getTargetEnv())) {
                        unlink(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getTargetEnv());
                    }

                    /**
                     *  Création du lien symbolique
                     */
                    exec('cd ' . REPOS_DIR . '/ && ln -sfn ' . $this->repo->getDateFormatted() . '_' . $this->repo->getName() . ' ' . $this->repo->getName() . '_' . $this->repo->getTargetEnv());

                    /**
                     *  Ajout de l'environnement en BDD
                     */
                    $this->repo->addEnv($this->repo->getTargetEnv(), $this->repo->getTargetDescription(), $this->repo->getSnapId());

                    /**
                     *  Clôture de l'étape en cours
                     */
                    $this->log->stepOK();

                /**
                 *  Cas 2 : Il y a déjà un environnement de repo du même nom pointant vers un snapshot.
                 */
                } else {
                    /**
                     *  On récupère l'Id de l'environnement déjà existant
                     */
                    $actualEnvIds = $this->repo->getEnvIdFromRepoName($this->repo->getName(), null, null, $this->repo->getTargetEnv());

                    /**
                     *  On supprime l'éventuel environnement de même nom pointant déjà vers un snapshot de ce repo (si il y en a un)
                     */
                    if (!empty($actualEnvIds)) {
                        foreach ($actualEnvIds as $actualEnvId) {
                            $this->repo->removeEnv($actualEnvId['Id']);
                        }
                    }

                    /**
                     *  Suppression du lien symbolique
                     */
                    if (is_link(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getTargetEnv())) {
                        unlink(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getTargetEnv());
                    }

                    /**
                     *  Création du nouveau lien symbolique, pointant vers le snapshot cible
                     */
                    exec('cd ' . REPOS_DIR . '/ && ln -sfn ' . $this->repo->getDateFormatted() . '_' . $this->repo->getName() . ' ' . $this->repo->getName() . '_' . $this->repo->getTargetEnv());

                    /**
                     *  Puis on déclare le nouvel environnement et on le fait pointer vers le snapshot précédemment créé
                     */
                    $this->repo->addEnv($this->repo->getTargetEnv(), $this->repo->getTargetDescription(), $this->repo->getSnapId());

                    /**
                     *  Clôture de l'étape en cours
                     */
                    $this->log->stepOK();
                }
            }

            if ($this->repo->getPackageType() == 'deb') {
                /**
                 *  Cas 1 : pas d'environnement de même nom existant sur ce snapshot
                 */
                if ($this->repo->existsEnv($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getTargetEnv()) === false) {
                    /**
                     *  Suppression du lien symbolique (on ne sait jamais si il existe)
                     */
                    if (is_link(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv())) {
                        unlink(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv());
                    }

                    /**
                     *  Création du lien symbolique
                     */
                    exec('cd ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . ' && ln -sfn ' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . ' ' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv());

                    /**
                     *  Ajout de l'environnement en BDD
                     */
                    $this->repo->addEnv($this->repo->getTargetEnv(), $this->repo->getTargetDescription(), $this->repo->getSnapId());

                    /**
                     *  Clôture de l'étape en cours
                     */
                    $this->log->stepOK();

                /**
                 *  Cas 2 : Il y a déjà un environnement de repo du même nom pointant vers un snapshot.
                 */
                } else {
                    /**
                     *  D'abord on récupère l'Id de l'environnement déjà existant car on en aura besoin pour modifier son snapshot lié en base de données.
                     */
                    $actualEnvIds = $this->repo->getEnvIdFromRepoName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getTargetEnv());

                    /**
                     *  On supprime l'éventuel environnement de même nom pointant déjà vers un snapshot de ce repo (si il y en a un)
                     */
                    if (!empty($actualEnvIds)) {
                        foreach ($actualEnvIds as $actualEnvId) {
                            $this->repo->removeEnv($actualEnvId['Id']);
                        }
                    }

                    /**
                     *  Suppression du lien symbolique
                     */
                    if (is_link(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv())) {
                        unlink(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv());
                    }

                    /**
                     *  Création du nouveau lien symbolique, pointant vers le snapshot cible
                     */
                    exec('cd ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . ' && ln -sfn ' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . ' ' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv());

                    /**
                     *  Puis on déclare le nouvel environnement et on le fait pointer vers le snapshot précédemment créé
                     */
                    $this->repo->addEnv($this->repo->getTargetEnv(), $this->repo->getTargetDescription(), $this->repo->getSnapId());

                    /**
                     *  Clôture de l'étape en cours
                     */
                    $this->log->stepOK();
                }
            }

            $this->log->step('FINALIZING');

            /**
             *  8. Application des droits sur le repo/la section modifié
             */
            if ($this->repo->getPackageType() == 'rpm') {
                exec('find ' . REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName() . '/ -type f -exec chmod 0660 {} \;');
                exec('find ' . REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName() . '/ -type d -exec chmod 0770 {} \;');
            }

            if ($this->repo->getPackageType() == 'deb') {
                exec('find ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . '/ -type f -exec chmod 0660 {} \;');
                exec('find ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . '/ -type d -exec chmod 0770 {} \;');
            }

            /**
             *  Clôture de l'étape en cours
             */
            $this->log->stepOK();

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

            /**
             *  Nettoyage du cache
             */
            \Controllers\App\Cache::clear();

            /**
             *  Passage du status de l'opération en done
             */
            $this->operation->setStatus('done');
        } catch (\Exception $e) {
            /**
             *  On transmets l'erreur à $this->log->stepError() qui va se charger de l'afficher en rouge dans le fichier de log
             */
            $this->log->stepError($e->getMessage());

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->operation->setStatus('error');
            $this->operation->setError($e->getMessage());
        }

        /**
         *  Get total duration
         */
        $duration = $this->operation->getDuration();

        /**
         *  Close operation
         */
        $this->log->stepDuration($duration);
        $this->operation->close();
    }
}
