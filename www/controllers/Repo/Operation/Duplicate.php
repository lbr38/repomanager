<?php

namespace Controllers\Repo\Operation;

use Exception;

class Duplicate extends Operation
{
    use Metadata\Create;

    public function __construct(string $poolId, array $operationParams)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->operation = new \Controllers\Operation\Operation();
        $this->log = new \Controllers\Log\OperationLog('repomanager', $this->operation->getPid());

        /**
         *  Check and set snapId parameter
         */
        $requiredParams = array('snapId');
        $this->operationParamsCheck('Duplicate repo', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $this->repo->getSnapId(), null);

        /**
         *  Set additionnal params from the actual repo to duplicate
         */
        $operationParams['targetGpgResign'] = $this->repo->getSigned();
        $operationParams['targetArch'] = $this->repo->getArch();
        $operationParams['targetPackageTranslation'] = $this->repo->getPackageTranslation();

        /**
         *  Check and set others operation parameters
         */
        $requiredParams = array('snapId', 'targetName', 'targetGpgResign', 'targetArch');
        $optionnalParams = array('targetGroup', 'targetDescription', 'targetEnv', 'targetPackageTranslation');
        $this->operationParamsCheck('Duplicate repo', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams, $optionnalParams);

        /**
         *  Set operation details
         */
        $this->operation->setAction('duplicate');
        $this->operation->setType('manual');
        $this->operation->setPoolId($poolId);
        $this->operation->setSourceSnapId($this->repo->getSnapId());
        $this->operation->setRepoName($this->repo->getTargetName());
        if ($this->repo->getPackageType() == 'deb') {
            $this->operation->setRepoName($this->repo->getTargetName() . '|' . $this->repo->getDist() . '|' . $this->repo->getSection());
        }
        $this->operation->setLogfile($this->log->getName());
        $this->operation->start();

        /**
         *  Run the operation
         */
        $this->duplicate();
    }

    /**
     *  Duplicate repo
     */
    private function duplicate()
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
            include(ROOT . '/templates/tables/op-duplicate.inc.php');

            $this->log->step('DUPLICATING');

            /**
             *  On vérifie que le snapshot source existe
             */
            if ($this->repo->existsSnapId($this->repo->getSnapId()) === false) {
                throw new Exception("Source repo snapshot does not exist");
            }

            /**
             *  On vérifie qu'un repo de même nom cible n'existe pas déjà
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if ($this->repo->isActive($this->repo->getTargetName()) === true) {
                    throw new Exception('a repo <span class="label-black">' . $this->repo->getTargetName() . '</span> already exists');
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if ($this->repo->isActive($this->repo->getTargetName(), $this->repo->getDist(), $this->repo->getSection()) === true) {
                    throw new Exception('a repo <span class="label-black">' . $this->repo->getTargetName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection() . '</span> already exists');
                }
            }

            /**
             *  Création du nouveau répertoire avec le nouveau nom du repo :
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if (!file_exists(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getTargetName())) {
                    if (!mkdir(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getTargetName(), 0770, true)) {
                        throw new Exception("cannot create directory for the new repo <b>" . $this->repo->getTargetName() . "</b>");
                    }
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if (!file_exists(REPOS_DIR . '/' . $this->repo->getTargetName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection())) {
                    if (!mkdir(REPOS_DIR . '/' . $this->repo->getTargetName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection(), 0770, true)) {
                        throw new Exception("cannot create directory for the new repo <b>" . $this->repo->getTargetName() . "</b>");
                    }
                }
            }

            /**
             *  Copie du contenu du repo/de la section
             *  Anti-slash devant la commande cp pour forcer l'écrasement si un répertoire de même nom trainait par là
             */
            if ($this->repo->getPackageType() == 'rpm') {
                exec('\cp -r ' . REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName() . '/* ' . REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getTargetName() . '/', $output, $result);
            }
            if ($this->repo->getPackageType() == 'deb') {
                exec('\cp -r ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . '/* ' . REPOS_DIR . '/' . $this->repo->getTargetName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . '/', $output, $result);
            }
            if ($result != 0) {
                throw new Exception('cannot copy data from the source repo to the new repo');
            }

            $this->log->stepOK();

            /**
             *  On a deb repo, the duplicated repo metadata must be rebuilded
             */
            if ($this->repo->getPackageType() == 'deb') {
                /**
                 *  For the needs of the createMetadata function, name of the repo to create must be in $name
                 *  Temporary backuping the actual name then replace it with $this->repo->getTargetName()
                 */
                $backupName = $this->repo->getName();
                $this->repo->setName($this->repo->getTargetName());
                $this->repo->setTargetDate($this->repo->getDate());

                $this->createMetadata();

                /**
                 *  Set back the backuped name
                 */
                $this->repo->setName($backupName);
            }

            $this->log->step('FINALIZING');

            /**
             *  Création du lien symbolique
             *  Seulement si l'utilisateur a spécifié un environnement
             */
            if (!empty($this->repo->getTargetEnv())) {
                if ($this->repo->getPackageType() == 'rpm') {
                    exec('cd ' . REPOS_DIR . '/ && ln -sfn ' . $this->repo->getDateFormatted() . '_' . $this->repo->getTargetName() . ' ' .  $this->repo->getTargetName() . '_' . $this->repo->getTargetEnv(), $output, $result);
                }
                if ($this->repo->getPackageType() == 'deb') {
                    exec('cd ' . REPOS_DIR . '/' . $this->repo->getTargetName() . '/' . $this->repo->getDist() . '/ && ln -sfn ' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . ' ' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv(), $output, $result);
                }
                if ($result != 0) {
                    throw new Exception('Cannot set repo environment');
                }
            }

            /**
             *  8. Insertion du nouveau repo en base de données
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $this->repo->add($this->repo->getSource(), 'rpm', $this->repo->getTargetName());
            }
            if ($this->repo->getPackageType() == 'deb') {
                $this->repo->add($this->repo->getSource(), 'deb', $this->repo->getTargetName());
            }

            /**
             *  On récupère l'Id du repo créé en base de données
             */
            $targetRepoId = $this->repo->getLastInsertRowID();

            if ($this->repo->getPackageType() == 'rpm') {
                /**
                 *  Set repo releasever
                 */
                $this->repo->updateReleasever($targetRepoId, $this->repo->getReleasever());
            }

            if ($this->repo->getPackageType() == 'deb') {
                /**
                 *  Set repo dist and section
                 */
                $this->repo->updateDist($targetRepoId, $this->repo->getDist());
                $this->repo->updateSection($targetRepoId, $this->repo->getSection());
            }

            /**
             *  On ajoute le snapshot copié en base de données
             */
            $this->repo->addSnap($this->repo->getDate(), $this->repo->getTime(), $this->repo->getSigned(), $this->repo->getTargetArch(), $this->repo->getTargetSourcePackage(), $this->repo->getTargetPackageTranslation(), $this->repo->getType(), $this->repo->getStatus(), $targetRepoId);

            /**
             *  On récupère l'Id du snapshot créé en base de données
             */
            $targetSnapId = $this->repo->getLastInsertRowID();

            /**
             *  On ajoute l'environnement créé
             *  Seulement si l'utilisateur a spécifié un environnement
             */
            if (!empty($this->repo->getTargetEnv())) {
                $this->repo->addEnv($this->repo->getTargetEnv(), $this->repo->getTargetDescription(), $targetSnapId);
            }

            /**
             *  9. Application des droits sur le nouveau repo créé
             */
            if ($this->repo->getPackageType() == 'rpm') {
                exec('find ' . REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getTargetName() . '/ -type f -exec chmod 0660 {} \;');
                exec('find ' . REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getTargetName() . '/ -type d -exec chmod 0770 {} \;');
                exec('chown -R ' . WWW_USER . ':repomanager ' . REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getTargetName());
            }
            if ($this->repo->getPackageType() == 'deb') {
                exec('find ' . REPOS_DIR . '/' . $this->repo->getTargetName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . '/ -type f -exec chmod 0660 {} \;');
                exec('find ' . REPOS_DIR . '/' . $this->repo->getTargetName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . '/ -type d -exec chmod 0770 {} \;');
                exec('chown -R ' . WWW_USER . ':repomanager ' . REPOS_DIR . '/' . $this->repo->getTargetName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection());
            }

            $this->log->stepOK();

            /**
             *  10. Ajout de la section à un groupe si un groupe a été renseigné
             */
            if (!empty($this->repo->getTargetGroup())) {
                $this->log->step('ADDING TO GROUP');

                /**
                 *  Ajout du repo créé au groupe spécifié
                 */
                $this->repo->addRepoIdToGroup($targetRepoId, $this->repo->getTargetGroup());

                $this->log->stepOK();
            }

            /**
             *  Nettoyage des repos inutilisés dans les groupes
             */
            $this->repo->cleanGroups();

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
