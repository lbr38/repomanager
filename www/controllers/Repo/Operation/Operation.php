<?php

namespace Controllers\Repo\Operation;

use Exception;

class Operation
{
    protected $repo;
    protected $operation;
    protected $log;

    protected $poolId;

    /**
     *  Check required parameters for an operation
     */
    protected function operationParamsCheck($operationType, $operationParams, $requiredParams)
    {
        /**
         *  Check required parameters
         */
        foreach ($requiredParams as $param) {
            if (empty($operationParams[$param])) {
                throw new Exception($operationType . ": parameter '$param' is not defined.");
            }
        }
    }

    /**
     *  Set repo parameters for an operation
     */
    protected function operationParamsSet($operationParams, $requiredParams = null, $optionnalParams = null)
    {
        /**
         *  Set required parameters, using the appropriate setter function
         */
        if (!empty($requiredParams)) {
            foreach ($requiredParams as $param) {
                $setterFunction = 'set' . ucfirst($param);
                $this->repo->$setterFunction($operationParams[$param]);
            }
        }

        /**
         *  Set optionnal parameters if defined, using the appropriate setter function
         */
        if (!empty($optionnalParams)) {
            foreach ($optionnalParams as $param) {
                if (!empty($operationParams[$param])) {
                    $setterFunction = 'set' . ucfirst($param);
                    $this->repo->$setterFunction($operationParams[$param]);
                }
            }
        }
    }

    /**
    *   Génération d'un tableau récapitulatif de l'opération
    *   Valide pour :
    *    - un nouveau repo/section
    *    - une mise à jour de repo/section
    *    - une reconstruction des métadonnées d'un repo/section
    */
    protected function printDetails(string $title)
    {
        $this->log->step();

        ob_start();

        /**
         *  Affichage du tableau récapitulatif de l'opération
         */
        include(ROOT . '/templates/tables/op-new-update-reconstruct.inc.php');

        $this->log->steplogWrite(ob_get_clean());
    }
}
