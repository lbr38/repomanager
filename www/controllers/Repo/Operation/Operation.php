<?php

namespace Controllers\Repo\Operation;

use Exception;

class Operation
{
    protected $repo;
    protected $operation;
    public $log;
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
     *  Generate a summary table for the operation
     *  Valid for:
     *   - a new repo/section
     *   - an update of repo/section
     *   - a rebuild of repo/section metadata
     */
    protected function printDetails(string $title)
    {
        $this->log->step();

        ob_start();

        /**
         *  Affichage du tableau récapitulatif de l'opération
         */
        include(ROOT . '/templates/tables/op-new-update-rebuild.inc.php');

        $this->log->steplogWrite(ob_get_clean());
    }
}
