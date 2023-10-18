<?php

namespace Controllers\Repo\Operation;

use Exception;

class Update extends Operation
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
         *  Check and set snapId parameter
         */
        $requiredParams = array('snapId');
        $this->operationParamsCheck('Update repo', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $this->repo->getSnapId(), null);

        /**
         *  Check and set others operation parameters
         */
        $requiredParams = array('targetGpgCheck', 'targetGpgResign', 'targetArch', 'onlySyncDifference');
        $optionnalParams = array('targetEnv', 'targetSourcePackage', 'targetPackageTranslation');
        $this->operationParamsCheck('Update repo', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams, $optionnalParams);

        /**
         *  Set operation details
         */
        $this->operation->setAction('update');
        $this->operation->setType('manual');
        $this->operation->setPoolId($poolId);
        $this->operation->setTargetSnapId($this->repo->getSnapId());
        $this->operation->setGpgCheck($this->repo->getTargetGpgCheck());
        $this->operation->setGpgResign($this->repo->getTargetGpgResign());
        $this->operation->setLogfile($this->log->getName());

        /**
         *  Si un Id de planification a été spécifié alors ça signifie que l'action a été initialisée par une planification
         */
        if (!empty($operationParams['planId'])) {
            $this->operation->setType('plan');
            $this->operation->setPlanId($operationParams['planId']);
        }

        $this->operation->start();

        /**
         *  Run the operation
         */
        $this->update();
    }

    /**
     *  Mise à jour d'un miroir de repo / section
     */
    private function update()
    {
        /**
         *  On défini la date du jour et l'environnement par défaut sur lesquels sera basé le nouveau miroir
         */
        $this->repo->setTargetDate(date('Y-m-d'));
        $this->repo->setTargetTime(date('H:i'));

        /**
         *  Nettoyage du cache
         */
        \Controllers\App\Cache::clear();

        /**
         *  Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
         */
        $this->log->runLogBuilder($this->operation->getPid(), $this->log->getLocation());

        try {
            /**
             *  Etape 1 : Afficher les détails de l'opération
             */
            $this->printDetails('UPDATE REPO');

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
             *   Etape 6 : Finalisation du repo (ajout en BDD et application des droits)
             */
            $this->finalize();

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
             *  Get total duration
             */
            $duration = $this->operation->getDuration();

            /**
             *  Close operation
             */
            $this->log->stepDuration($duration);
            $this->operation->close();

            /**
             *  Cas où cette fonction est lancée par une planification : la planif attend un retour, on lui renvoie false pour lui indiquer qu'il y a eu une erreur
             */
            // return false;
            throw new Exception($e->getMessage());
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
