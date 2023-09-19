<?php

namespace Controllers\Repo\Operation;

use Exception;

class Reconstruct extends Operation
{
    use Package\Sign;
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
        $this->operationParamsCheck('Reconstruct repo metadata', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $this->repo->getSnapId(), null);

        /**
         *  Set additionnal params from the actual repo to reconstruct
         */
        $operationParams['targetDate'] = $this->repo->getDate();
        $operationParams['targetArch'] = $this->repo->getArch();

        /**
         *  Check and set others operation parameters
         */
        $requiredParams = array('targetGpgResign', 'targetDate', 'targetArch');
        $this->operationParamsCheck('Reconstruct repo', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams, null);

        /**
         *  Set operation details
         */
        $this->operation->setAction('reconstruct');
        $this->operation->setType('manual');
        $this->operation->setPoolId($poolId);
        $this->operation->setTargetSnapId($this->repo->getSnapId());
        $this->operation->setGpgResign($this->repo->getTargetGpgResign());
        $this->operation->setLogfile($this->log->getName());
        $this->operation->start();

        /**
         *  Run the operation
         */
        $this->reconstruct();
    }

    /**
     *  Reconstruct repo metadata
     */
    private function reconstruct()
    {
        /**
         *  Nettoyage du cache
         */
        \Controllers\App\Cache::clear();

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $this->log->runLogBuilder($this->operation->getPid(), $this->log->getLocation());

        /**
         *  Modification de l'état de reconstruction des métadonnées du snapshot en base de données
         */
        $this->repo->snapSetReconstruct($this->repo->getSnapId(), 'running');

        try {
            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->printDetails('REBUILD REPO METADATA');

            /**
            *   Etape 2 : signature des paquets/du repo
            */
            $this->signPackage();

            /**
            *   Etape 3 : Création du repo et liens symboliques
            */
            $this->createMetadata();

            /**
             *  Etape 4 : on modifie l'état de la signature du repo en BDD
             *  Comme on a reconstruit les fichiers du repo, il est possible qu'on soit passé d'un repo signé à un repo non-signé, ou inversement
             *  Il faut donc modifier l'état en BDD
             */
            $this->repo->snapSetSigned($this->repo->getSnapId(), $this->repo->getTargetGpgResign());

            /**
             *  Modification de l'état de reconstruction des métadonnées du snapshot en base de données
             */
            $this->repo->snapSetReconstruct($this->repo->getSnapId(), '');

            /**
             *  Passage du status de l'opération en done
             */
            $this->operation->setStatus('done');
        } catch (\Exception $e) {
            $this->log->stepError($e->getMessage()); // On transmets l'erreur à $this->log->stepError() qui va se charger de l'afficher en rouge dans le fichier de log

            /**
             *  Passage du status de l'opération en erreur
             */
            $this->operation->setStatus('error');
            $this->operation->setError($e->getMessage());

            /**
             *  Modification de l'état de reconstruction des métadonnées du snapshot en base de données
             */
            $this->repo->snapSetReconstruct($this->repo->getSnapId(), 'failed');
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
