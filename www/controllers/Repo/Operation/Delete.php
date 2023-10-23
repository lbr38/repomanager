<?php

namespace Controllers\Repo\Operation;

use Exception;

class Delete extends Operation
{
    public function __construct(string $poolId, array $operationParams)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->operation = new \Controllers\Operation\Operation();
        $this->log = new \Controllers\Log\OperationLog('repomanager', $this->operation->getPid());

        /**
         *  Check and set operation parameters
         */
        $requiredParams = array('snapId');
        $this->operationParamsCheck('Delete repo snapshot', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $this->repo->getSnapId(), null);

        /**
         *  Set operation details
         */
        $this->operation->setAction('delete');
        $this->operation->setType('manual');
        $this->operation->setPoolId($poolId);
        $this->operation->setTargetSnapId($this->repo->getSnapId());
        $this->operation->setLogfile($this->log->getName());
        $this->operation->start();

        /**
         *  Run the operation
         */
        $this->delete();
    }

    /**
     *  Delete a repo snapshot
     */
    private function delete()
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
            include(ROOT . '/templates/tables/op-delete.inc.php');

            $this->log->step('DELETING');

            /**
             *  2. Suppression du snapshot
             */
            $result = 0;

            if ($this->repo->getPackageType() == "rpm") {
                if (is_dir(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName())) {
                    exec('rm ' . REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName() . ' -rf', $output, $result);
                }
            }
            if ($this->repo->getPackageType() == "deb") {
                if (is_dir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection())) {
                    exec('rm ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . ' -rf', $output, $result);
                }
            }

            if ($result != 0) {
                throw new Exception('cannot delete snapshot of the <span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
            }

            $this->log->stepOK();

            /**
             *  Passage du snapshot en état 'deleted' en base de données
             */
            $this->repo->snapSetStatus($this->repo->getSnapId(), 'deleted');

            /**
             *  Récupération des Id d'environnements qui pointaient vers ce snapshot
             */
            $envIds = $this->repo->getEnvIdBySnapId($this->repo->getSnapId());

            /**
             *  On traite chaque Id d'environnement qui pointait vers ce snapshot
             */
            if (!empty($envIds)) {
                foreach ($envIds as $envId) {
                    /**
                     *  Suppression des environnements pointant vers ce snapshot en base de données
                     */
                    $myrepo = new \Controllers\Repo\Repo();
                    $myrepo->getAllById('', '', $envId);

                    /**
                     *  Si un lien symbolique de cet environnement pointait vers le snapshot supprimé alors on peut supprimer le lien symbolique.
                     */
                    if ($myrepo->getPackageType() == 'rpm') {
                        if (is_link(REPOS_DIR . '/' . $myrepo->getName() . '_' . $myrepo->getEnv())) {
                            if (readlink(REPOS_DIR . '/' . $myrepo->getName() . '_' . $myrepo->getEnv()) == $myrepo->getDateFormatted() . '_' . $myrepo->getName()) {
                                unlink(REPOS_DIR . '/' . $myrepo->getName() . '_' . $myrepo->getEnv());
                            }
                        }
                    }
                    if ($myrepo->getPackageType() == 'deb') {
                        if (is_link(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getSection() . '_' . $myrepo->getEnv())) {
                            if (readlink(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getSection() . '_' . $myrepo->getEnv()) == $myrepo->getDateFormatted() . '_' . $myrepo->getSection()) {
                                unlink(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getSection() . '_' . $myrepo->getEnv());
                            }
                        }
                    }
                    unset($myrepo);
                }
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
