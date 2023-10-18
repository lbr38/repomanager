<?php

namespace Controllers\Repo\Operation;

use Exception;

class Create extends Operation
{
    use Package\Sync;
    use Package\Sign;
    use Metadata\Create;
    use Finalize;

    public function __construct(string $poolId, array $operationParams)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->operation = new \Controllers\Operation\Operation();
        $this->log = new \Controllers\Log\OperationLog('repomanager', $this->operation->getPid());

        /**
         *  Check and set operation parameters
         */
        $requiredParams = array('packageType', 'type', 'targetArch');
        $optionnalParams = array('targetEnv', 'targetPackageTranslation', 'targetGroup', 'targetDescription');

        /**
         *  Required parameters in case the repo type is 'rpm'
         */
        if ($operationParams['packageType'] == 'rpm') {
            $requiredParams[] = 'releasever';
        }

        /**
         *  Required parameters in case the repo type is 'deb'
         */
        if ($operationParams['packageType'] == 'deb') {
            $requiredParams[] = 'dist';
            $requiredParams[] = 'section';
        }

        /**
         *  Required parameters in case the operation is a mirror
         */
        if ($operationParams['type'] == 'mirror') {
            $requiredParams[] = 'source';
            $requiredParams[] = 'targetGpgCheck';
            $requiredParams[] = 'targetGpgResign';
            $requiredParams[] = 'targetSourcePackage';
        }

        /**
         *  Required parameters in case the operation is a local repo
         */
        if ($operationParams['type'] == 'local') {
            $this->repo->setName($operationParams['alias']);
        }

        $this->operationParamsCheck('Create repo', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams, $optionnalParams);

        if ($operationParams['type'] == 'mirror') {
            /**
             *  Alias parameter can be empty, if it's the case, the value will be 'source'
             */
            if (!empty($operationParams['alias'])) {
                $this->repo->setName($operationParams['alias']);
            } else {
                $this->repo->setName($this->repo->getSource());
            }
        }

        /**
         *  Set operation details
         */
        $this->operation->setAction('new');
        $this->operation->setType('manual');
        $this->operation->setPoolId($poolId);
        $this->operation->setRepoName($this->repo->getName());
        if ($this->repo->getPackageType() == 'deb') {
            $this->operation->setRepoName($this->repo->getName() . '|' . $this->repo->getDist() . '|' . $this->repo->getSection());
        }
        if ($operationParams['type'] == 'mirror') {
            $this->operation->setGpgCheck($this->repo->getTargetGpgCheck());
            $this->operation->setGpgResign($this->repo->getTargetGpgResign());
        }
        $this->operation->setLogfile($this->log->getName());
        $this->operation->start();

        /**
         *  Run the operation
         */
        if ($operationParams['type'] == 'mirror') {
            $this->mirror();
        }
        if ($operationParams['type'] == 'local') {
            $this->local();
        }
    }

    /**
     *  Create a new mirror
     */
    private function mirror()
    {
        /**
         *  Define the date and time of the new mirror snapshot
         */
        $this->repo->setTargetDate(date('Y-m-d'));
        $this->repo->setTargetTime(date('H:i'));

        /**
         *  Clear cache
         */
        \Controllers\App\Cache::clear();

        /**
         *  Run the external script that will build the main log file from the small log files of each step
         */
        $this->log->runLogBuilder($this->operation->getPid(), $this->log->getLocation());

        try {
            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->printDetails('CREATE A NEW ' . strtoupper($this->repo->getPackageType()) . ' REPOSITORY MIRROR');

            /**
             *   Etape 2 : récupération des paquets
             */
            $this->syncPackage();

            /**
             *   Etape 3 : signature des paquets/du repo
             */
            $this->signPackage();

            /**
             *   Etape 4 : Création du repo et liens symboliques
             */
            $this->createMetadata();

            /**
             *   Etape 5 : Finalisation du repo (ajout en BDD et application des droits)
             */
            $this->finalize();

            /**
             *  Passage du status de l'opération en done
             */
            $this->operation->setStatus('done');
        } catch (\Exception $e) {
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

    /**
     *  Création d'un nouveau repo / section local
     */
    private function local()
    {
        /**
         *  On défini la date du jour et l'environnement par défaut sur lesquels sera basé le nouveau miroir
         */
        $this->repo->setTargetDate(date('Y-m-d'));
        $this->repo->setTargetTime(date("H:i"));

        /**
         *  Clear cache
         */
        \Controllers\App\Cache::clear();

        /**
         *  Run the external script that will build the main log file from the small log files of each step
         */
        $this->log->runLogBuilder($this->operation->getPid(), $this->log->getLocation());

        try {
            ob_start();

            /**
             *  1. Génération du tableau récapitulatif de l'opération
             */
            include(ROOT . '/templates/tables/op-new-local.inc.php');

            $this->log->step('CREATING REPO');

            /**
             *  3. Création du répertoire avec le nom du repo, et les sous-répertoires permettant d'acceuillir les futurs paquets
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if (!is_dir(REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName() . '/packages')) {
                    if (!mkdir(REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName() . '/packages', 0770, true)) {
                        throw new Exception('Could not create directory ' . REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName() . '/packages');
                    }
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if (!is_dir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getSection() . '/pool/' . $this->repo->getSection())) {
                    if (!mkdir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getSection() . '/pool/' . $this->repo->getSection(), 0770, true)) {
                        throw new Exception('Could not create directory ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getSection() . '/pool/' . $this->repo->getSection());
                    }
                }
            }

            /**
             *   4. Création du lien symbolique, si un environnement a été spécifié par l'utilisateur
             */
            if (!empty($this->repo->getTargetEnv())) {
                if ($this->repo->getPackageType() == 'rpm') {
                    exec('cd ' . REPOS_DIR . '/ && ln -sfn ' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName() . ' ' . $this->repo->getName() . '_' . $this->repo->getTargetEnv(), $output, $result);
                }
                if ($this->repo->getPackageType() == 'deb') {
                    exec('cd ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/ && ln -sfn ' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getSection() . ' ' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv(), $output, $result);
                }
                if ($result != 0) {
                    throw new Exception('Could not point environment to the repository');
                }
            }

            /**
             *  Vérification de l'existance du repo en base de données
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $exists = $this->repo->exists($this->repo->getName());
            }
            if ($this->repo->getPackageType() == 'deb') {
                $exists = $this->repo->exists($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection());
            }

            /**
             *  Si actuellement aucun repo de ce nom n'existe en base de données alors on l'ajoute
             *  Note : ici on renseigne la source comme étant $this->repo->getName()
             */
            if ($exists === false) {
                if ($this->repo->getPackageType() == 'rpm') {
                    $this->repo->add($this->repo->getName(), 'rpm', $this->repo->getName());
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $this->repo->add($this->repo->getName(), 'deb', $this->repo->getName());
                }

                /**
                 *  Retrieve repo Id from the last insert row
                 */
                $this->repo->setRepoId($this->repo->getLastInsertRowID());

                /**
                 *  Set repo releasever
                 */
                if ($this->repo->getPackageType() == 'rpm') {
                    $this->repo->updateReleasever($this->repo->getRepoId(), $this->repo->getReleasever());
                }

                /**
                 *  Set repo dist and section
                 */
                if ($this->repo->getPackageType() == 'deb') {
                    $this->repo->updateDist($this->repo->getRepoId(), $this->repo->getDist());
                    $this->repo->updateSection($this->repo->getRepoId(), $this->repo->getSection());
                }

            /**
             *  Sinon si un repo de même nom existe, on rattache ce nouveau snapshot et ce nouvel env à ce repo
             */
            } else {
                /**
                 *  D'abord on récupère l'Id en base de données du repo
                 */
                if ($this->repo->getPackageType() == 'rpm') {
                    $this->repo->setRepoId($this->repo->getIdByName($this->repo->getName(), '', ''));
                }

                if ($this->repo->getPackageType() == 'deb') {
                    $this->repo->setRepoId($this->repo->getIdByName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection()));
                }
            }

            unset($exists);

            /**
             *  Ajout du snapshot en base de données
             */
            $this->repo->addSnap($this->repo->getTargetDate(), $this->repo->getTargetTime(), 'no', $this->repo->getTargetArch(), $this->repo->getTargetSourcePackage(), $this->repo->getTargetPackageTranslation(), $this->repo->getType(), 'active', $this->repo->getRepoId());

            /**
             *  Récupération de l'Id du snapshot ajouté précédemment
             */
            $this->repo->setSnapId($this->repo->getLastInsertRowID());

            /**
             *  Ajout de l'env en base de données si un env a été spécifié par l'utilisateur
             */
            if (!empty($this->repo->getTargetEnv())) {
                $this->repo->addEnv($this->repo->getTargetEnv(), $this->repo->getTargetDescription(), $this->repo->getSnapId());
            }

            /**
             *  6. Application des droits sur le nouveau repo créé
             */
            if ($this->repo->getPackageType() == 'rpm') {
                exec('find ' . REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName() . '/ -type f -exec chmod 0660 {} \;');
                exec('find ' . REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName() . '/ -type d -exec chmod 0770 {} \;');
                exec('chown -R ' . WWW_USER . ':repomanager ' . REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName());
            }
            if ($this->repo->getPackageType() == 'deb') {
                exec('find ' . REPOS_DIR . '/' . $this->repo->getName() . '/ -type f -exec chmod 0660 {} \;');
                exec('find ' . REPOS_DIR . '/' . $this->repo->getName() . '/ -type d -exec chmod 0770 {} \;');
                exec('chown -R ' . WWW_USER . ':repomanager ' . REPOS_DIR . '/' . $this->repo->getName());
            }

            $this->log->stepOK();

            /**
             *  7. Ajout de la section à un groupe si un groupe a été renseigné
             */
            if (!empty($this->repo->getTargetGroup())) {
                $this->log->step('ADDING TO GROUP');
                $this->repo->addRepoIdToGroup($this->repo->getRepoId(), $this->repo->getTargetGroup());
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
