<?php

namespace Controllers\Repo\Operation;

use Exception;

class RemoveEnv extends Operation
{
    public function __construct(string $poolId = '00000', array $operationParams)
    {
        /**
         *  Only admin can remove repo snapshot environment
         */
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        $this->repo = new \Controllers\Repo\Repo();
        $this->operation = new \Controllers\Operation\Operation();
        $this->log = new \Controllers\Log\OperationLog('repomanager', $this->operation->getPid());

        /**
         *  Check and set snapId parameter
         */
        $requiredParams = array('repoId', 'snapId', 'envId');
        $this->operationParamsCheck('Remove repo snapshot environment', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById($this->repo->getRepoId(), $this->repo->getSnapId(), $this->repo->getEnvId());

        /**
         *  Set operation details
         */
        $this->operation->setAction('removeEnv');
        $this->operation->setType('manual');

        /**
         *  Ce type d'opération ne comporte pas de réel poolId car elle est exécutée en dehors du process habituel
         */
        $this->operation->setPoolId('00000');
        $this->operation->setTargetSnapId($this->repo->getSnapId());
        $this->operation->setTargetEnvId($this->repo->getEnv());
        $this->operation->setLogfile($this->log->getName());
        $this->operation->start();

        /**
         *  Run the operation
         */
        $this->remove();
    }

    /**
     *  Remove snapshot environment
     */
    private function remove()
    {
        /**
         *  Ajout du PID de ce processus dans le fichier PID
         */
        // $this->operation->addsubpid(getmypid());

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
            include(ROOT . '/templates/tables/op-remove-env.inc.php');

            $this->log->step('DELETING');

            /**
             *  2. Suppression du lien symbolique de l'environnement
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if (file_exists(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv())) {
                    unlink(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv());
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if (file_exists(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv())) {
                    unlink(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv());
                }
            }

            /**
             *  3. Suppression de l'environnement en base de données
             */
            $this->repo->removeEnv($this->repo->getEnvId());

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
