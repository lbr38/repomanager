<?php

namespace Controllers\Task;

use Exception;

trait Param
{
    /**
     *  Check required parameters for a task
     */
    public function taskParamsCheck($taskType, $taskParams, $requiredParams)
    {
        /**
         *  Check required parameters
         */
        foreach ($requiredParams as $param) {
            if (empty($taskParams[$param])) {
                throw new Exception($taskType . ': parameter ' . $param . ' is not defined.');
            }
        }
    }

    /**
     *  Set repo parameters for a task
     */
    public function taskParamsSet($taskParams, $requiredParams = null, $optionalParams = null)
    {
        /**
         *  Repo controller setter functions depending on parameters
         */
        $setters = array(
            'package-type' => 'setPackageType',
            'repo-type' => 'setType',
            'name' => 'setName',
            'dist' => 'setDist',
            'section' => 'setSection',
            'source' => 'setSource',
            'arch' => 'setArch',
            'date' => 'setDate',
            'releasever' => 'setReleasever',
            'gpg-check' => 'setGpgCheck',
            'gpg-sign' => 'setGpgSign',
            'env' => 'setEnv',
            'description' => 'setDescription',
            'group' => 'setGroup',
            'package-include' => 'setPackagesToInclude',
            'package-exclude' => 'setPackagesToExclude'
        );

        /**
         *  Set required parameters, using the appropriate setter function
         */
        if (!empty($requiredParams)) {
            foreach ($requiredParams as $param) {
                $setterFunction = $setters[$param];
                $this->repo->$setterFunction($taskParams[$param]);
            }
        }

        /**
         *  Set optional parameters if defined, using the appropriate setter function
         */
        if (!empty($optionalParams)) {
            foreach ($optionalParams as $param) {
                // if (!empty($taskParams[$param])) {
                if (isset($taskParams[$param])) {
                    $setterFunction = $setters[$param];
                    $this->repo->$setterFunction($taskParams[$param]);
                }
            }
        }
    }

    /**
     *  Generate a summary table for the task
     *  Valid for:
     *   - a new repo/section
     *   - an update of repo/section
     *   - a rebuild of repo/section metadata
     */
    public function printDetails(string $title)
    {
        $this->taskLog->step();

        ob_start();

        /**
         *  Affichage du tableau récapitulatif de l'opération
         */
        include(ROOT . '/views/templates/tasks/new-update-rebuild.inc.php');

        $this->taskLog->steplogWrite(ob_get_clean());
    }
}
