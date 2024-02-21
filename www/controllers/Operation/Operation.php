<?php

namespace Controllers\Operation;

use Exception;

class Operation
{
    private $id;
    private $pid;
    private $model;
    private $logfile;
    private $action;
    private $validActions = array('new', 'create', 'update', 'env', 'duplicate', 'delete', 'removeEnv', 'rebuild');
    private $status;
    private $error;
    private $type;
    private $date;
    private $time;
    private $repoName;
    private $planId; // Si une opération est lancée par une planification alors on peut stocker l'ID de cette planification dans cette variable
    private $gpgCheck;
    private $gpgResign;
    private $timeStart;
    private $timeEnd;
    private $poolId;

    private $profileController;
    private $layoutContainerStateController;

    public function __construct(bool $generatePid = true)
    {
        $this->model = new \Models\Operation\Operation();
        $this->profileController = new \Controllers\Profile();
        $this->layoutContainerStateController = new \Controllers\Layout\ContainerState();

        if ($generatePid) {
            /**
             *  Generate a random PID
             */
            $this->pid = mt_rand(10001, 99999);

            /**
             *  If the PID already exists, generate a new one
             */
            while (file_exists(PID_DIR . '/' . $this->pid . '.pid')) {
                $this->pid = mt_rand(10001, 99999);
            }
        }
    }

    public function setPid(int $pid)
    {
        $this->pid = $pid;
    }

    public function setPlanId(string $planId)
    {
        $this->planId = $planId;
    }

    public function setAction(string $action)
    {
        if (!in_array($action, $this->validActions)) {
            throw new Exception('Operation action is invalid');
        }

        $this->action = $action;
    }

    public function setType(string $type)
    {
        if ($type !== 'manual' and $type !== 'plan') {
            throw new Exception("Operation type is invalid");
        }

        $this->type = $type;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    public function setError(string $error)
    {
        $this->error = $error;
    }

    public function setRepoId(string $id)
    {
        $this->repoId = $id;
    }

    public function setRepoName(string $name)
    {
        $this->repoName = $name;
    }

    public function setGpgCheck(string $gpgCheck)
    {
        $this->gpgCheck = $gpgCheck;
    }

    public function setGpgResign(string $gpgResign)
    {
        $this->gpgResign = $gpgResign;
    }

    public function setPoolId(string $poolId)
    {
        $this->poolId = $poolId;
    }

    public function setLogfile(string $logfile)
    {
        $this->logfile = $logfile;
    }

    public function setSourceSnapId(string $snapId)
    {
        $this->sourceSnapId = $snapId;
    }

    public function setTargetSnapId(string $snapId)
    {
        $this->targetSnapId = $snapId;
    }

    public function setTargetEnvId(string $envId)
    {
        $this->targetEnvId = $envId;
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getPlanId()
    {
        return $this->planId;
    }

    public function getPoolId()
    {
        return $this->poolId;
    }

    public function getDuration()
    {
        return microtime(true) - $this->timeStart;
    }

    /**
     *  Retourne les opérations exécutées ou en cours d'exécution par une planification à partir de son Id
     */
    public function getOperationsByPlanId(string $planId, string $status)
    {
        return $this->model-> getOperationsByPlanId($planId, $status);
    }

    /**
     *  List all running operations
     *  It is possible to filter the type of operation ('manual' or 'plan')
     *  It is possible to add an offset to the request
     */
    public function listRunning(string $type = '', bool $withOffset = false, int $offset = 0)
    {
        return $this->model->listRunning($type, $withOffset, $offset);
    }

    /**
     *  List all done operations (with or without errors)
     *  It is possible to filter the type of operation ('manual' or 'plan')
     *  It is possible to filter the type of planification that launched this operation ('plan' or 'regular' (unique planification or recurrent planification))
     *  It is possible to add an offset to the request
     */
    public function listDone(string $type = '', string $planType = '', bool $withOffset = false, int $offset = 0)
    {
        return $this->model->listDone($type, $planType, $withOffset, $offset);
    }

    /**
     *  Return true if an operation is running
     */
    public function somethingRunning()
    {
        return $this->model->somethingRunning();
    }

    /**
     *  Stop the operation based on the specified PID
     */
    public function kill(string $pid)
    {
        if (!file_exists(PID_DIR . '/' . $pid . '.pid')) {
            throw new Exception('Specified operation PID does not exist.');
        }

        /**
         *  Getting PID file content
         */
        $content = file_get_contents(PID_DIR . '/' . $pid . ".pid");

        /**
         *  Getting logfile name
         */
        preg_match('/(?<=LOG=).*/', $content, $logfile);
        $logfile = str_replace('"', '', $logfile[0]);

        /**
         *  Getting sub PIDs
         */
        preg_match_all('/(?<=SUBPID=).*/', $content, $subpids);

        /**
         *  Killing sub PIDs
         */
        if (!empty($subpids[0])) {
            $killError = '';

            foreach ($subpids[0] as $subpid) {
                $subpid = trim(str_replace('"', '', $subpid));

                /**
                 *  Check if the PID is still running
                 */
                $myprocess = new \Controllers\Process('ps --pid ' . $subpid);
                $myprocess->execute();
                $content = $myprocess->getOutput();
                $myprocess->close();

                if ($myprocess->getExitCode() != 0) {
                    continue;
                }

                /**
                 *  Kill the process
                 */
                $myprocess = new \Controllers\Process('kill -9 ' . $subpid);
                $myprocess->execute();
                $content = $myprocess->getOutput();
                $myprocess->close();

                if ($myprocess->getExitCode() != 0) {
                    $killError .= 'Could not kill PID ' . $subpid . ': ' . $content. '<br>';
                }
            }
        }

        /**
         *  Delete PID file
         */
        if (!unlink(PID_DIR . '/' . $pid . '.pid')) {
            throw new Exception('Error while deleting PID file');
        }

        /**
         *  If this operation was started by a planification, we need to update the planification in database
         *  First we need to get the planification Id
         */
        $planId = $this->model->getPlanIdByPid($pid);

        /**
         *  Update operation in database, set status to 'stopped'
         */
        $this->model->stopRunningOp($pid);

        /**
         *  Update planification in database
         */
        if (!empty($planId)) {
            $myplan = new \Controllers\Planification();
            $myplan->stop($planId);
        }

        \Controllers\App\Cache::clear();

        /**
         *  Update layout containers states
         */
        $this->layoutContainerStateController->update('header/menu');
        $this->layoutContainerStateController->update('repos/list');
        $this->layoutContainerStateController->update('planifications/queued-running');
        $this->layoutContainerStateController->update('operations/list');

        unset($myplan);

        if (!empty($killError)) {
            throw new Exception($killError);
        }
    }

    /**
     *  Start operation
     */
    public function start()
    {
        $this->date = date('Y-m-d');
        $this->time = date('H:i:s');
        $this->timeStart = microtime(true);
        $this->status = 'running';

        /**
         *  Add operation in database
         */
        $this->model->add($this->date, $this->time, $this->action, $this->type, $this->pid, $this->poolId, $this->logfile, $this->status);

        /**
         *  Get operation id in database
         */
        $this->id = $this->model->getLastInsertRowID();

        /**
         *  Update operation informations in database
         */
        if (!empty($this->planId)) {
            $this->updatePlanId($this->planId);
        }

        if (!empty($this->repoName)) {
            $this->updateTargetRepo($this->repoName);
        }

        if (!empty($this->sourceRepo)) {
            $this->updateSourceRepo($this->sourceRepo);
        }

        if (!empty($this->sourceSnapId)) {
            $this->updateSourceSnap($this->sourceSnapId);
        }

        if (!empty($this->targetSnapId)) {
            $this->updateTargetSnap($this->targetSnapId);
        }

        if (!empty($this->sourceEnvId)) {
            $this->updateSourceEnv($this->sourceEnvId);
        }

        if (!empty($this->targetEnvId)) {
            $this->updateTargetEnv($this->targetEnvId);
        }

        if (!empty($this->groupId)) {
            $this->updateGroupId($this->groupId);
        }

        if (!empty($this->gpgCheck)) {
            $this->updateGpgCheck($this->gpgCheck);
        }

        if (!empty($this->gpgResign)) {
            $this->updateGpgResign($this->gpgResign);
        }

        \Controllers\App\Cache::clear();

        /**
         *  Update layout containers states
         */
        $this->layoutContainerStateController->update('header/menu');
        $this->layoutContainerStateController->update('repos/list');
        $this->layoutContainerStateController->update('planifications/queued-running');
        $this->layoutContainerStateController->update('operations/list');
        $this->layoutContainerStateController->update('browse/list');
        $this->layoutContainerStateController->update('browse/actions');

        /**
         *  Create the PID file
         */
        file_put_contents(PID_DIR . '/' . $this->pid . '.pid', 'PID="' . $this->pid . '"' . PHP_EOL . 'LOG="' . $this->logfile . '"' . PHP_EOL);

        /**
         *  Add current PHP execution PID to the PID file to make sure it can be killed with the stop button
         */
        $this->addsubpid(getmypid());
    }

    /**
     *  Stop and close operation
     */
    public function close()
    {
        /**
         *  Generate a 'completed' file in the operation steps temporary directory, so that logbuilder.php stops
         */
        touch(TEMP_DIR . '/' . $this->pid . '/completed');

        /**
         *  Delete pid file
         */
        if (file_exists(PID_DIR . '/' . $this->pid . '.pid')) {
            unlink(PID_DIR . '/' . $this->pid . '.pid');
        }

        /**
         *  Close operation in database
         */
        $this->model->closeOperation($this->id, $this->status, $this->getDuration());

        /**
         *  Clear cache
         */
        \Controllers\App\Cache::clear();

        /**
         *  Update layout containers states
         */
        $this->layoutContainerStateController->update('header/menu');
        $this->layoutContainerStateController->update('repos/list');
        $this->layoutContainerStateController->update('repos/properties');
        $this->layoutContainerStateController->update('planifications/queued-running');
        $this->layoutContainerStateController->update('planifications/history');
        $this->layoutContainerStateController->update('operations/list');
        $this->layoutContainerStateController->update('browse/list');
        $this->layoutContainerStateController->update('browse/actions');

        /**
         *  Clean unused repos from profiles
         */
        $this->profileController->cleanProfiles();

        unset($this->myprofileController);
    }

    /**
     *  Add subpid to main PID file
     */
    public function addsubpid(int $pid)
    {
        /**
         *  Add specified PID to the main PID file
         */
        file_put_contents(PID_DIR . '/' . $this->pid . '.pid', 'SUBPID="' . $pid . '"' . PHP_EOL, FILE_APPEND);

        /**
         *  Also add children PID to the main PID file
         */
        $childrenPid = $this->getChildrenPid($pid);

        /**
         *  If no children PID, exit the loop
         */
        if ($childrenPid !== false) {
            /**
             *  Add children PID to the main PID file
             */
            foreach ($childrenPid as $childPid) {
                if (is_numeric($childPid)) {
                    file_put_contents(PID_DIR . '/' . $this->pid . '.pid', 'SUBPID="' . $childPid . '"' . PHP_EOL, FILE_APPEND);
                }

                /**
                 *  If the child PID has children PID, then add them too
                 */
                $grandChildrenPid = $this->getChildrenPid($childPid);

                if ($grandChildrenPid !== false) {
                    foreach ($grandChildrenPid as $grandChildPid) {
                        if (is_numeric($grandChildPid)) {
                            file_put_contents(PID_DIR . '/' . $this->pid . '.pid', 'SUBPID="' . $grandChildPid . '"' . PHP_EOL, FILE_APPEND);
                        }
                    }
                }
            }
        }
    }

    /**
     *  Return an array with all children PID of the specified PID or false if no children PID
     */
    public function getChildrenPid(int $pid)
    {
        /**
         *  Specified PID could have children PID, we need to get them all
         */
        $myprocess = new \Controllers\Process('pgrep -P ' . $pid);
        $myprocess->execute();

        /**
         *  If exit code is 0, then the PID has children
         */
        if ($myprocess->getExitCode() == 0) {
            /**
             *  Get children PID from output
             */
            $childrenPid = $myprocess->getOutput();
            $myprocess->close();

            $childrenPid = explode(PHP_EOL, $childrenPid);

            /**
             *  Return children PID
             */
            return $childrenPid;
        }

        return false;
    }

    /**
     *  Retourne le nom du repo ou du groupe en cours de traitement
     */
    public function printRepoOrGroup(string $id)
    {
        /**
         *  Récupération de toutes les informations concernant l'opération en base de données
         */
        $opInfo = $this->model->getAll($id);

        $myrepo = new \Controllers\Repo\Repo();
        $mygroup = new \Controllers\Group('repo');

        if (!empty($opInfo['Id_group'])) {
            $group = $mygroup->getNameById($opInfo['Id_group']);
        }

        if (!empty($opInfo['Id_repo_source'])) {
            if (is_numeric($opInfo['Id_repo_source'])) {
                $myrepo->getAllById($opInfo['Id_repo_source'], '', '');
                $repoName = $myrepo->getName();
                $repoDist = $myrepo->getDist();
                $repoSection = $myrepo->getSection();
            } else {
                $repo = explode('|', $opInfo['Id_repo_source']);
                $repoName = $repo[0];
                if (!empty($repo[1]) and !empty($repo[2])) {
                    $repoDist = $repo[1];
                    $repoSection = $repo[2];
                }
            }
        } else if (!empty($opInfo['Id_snap_source'])) {
            if (is_numeric($opInfo['Id_snap_source'])) {
                $myrepo->getAllById('', $opInfo['Id_snap_source'], '');
                $repoName = $myrepo->getName();
                $repoDist = $myrepo->getDist();
                $repoSection = $myrepo->getSection();
            }
        } else if (!empty($opInfo['Id_repo_target'])) {
            if (is_numeric($opInfo['Id_repo_target'])) {
                $myrepo->getAllById($opInfo['Id_repo_target'], '', '');
                $repoName = $myrepo->getName();
                $repoDist = $myrepo->getDist();
                $repoSection = $myrepo->getSection();
            } else {
                $repo = explode('|', $opInfo['Id_repo_target']);
                $repoName = $repo[0];
                if (!empty($repo[1]) and !empty($repo[2])) {
                    $repoDist = $repo[1];
                    $repoSection = $repo[2];
                }
            }
        } else if (!empty($opInfo['Id_snap_target'])) {
            if (is_numeric($opInfo['Id_snap_target'])) {
                $myrepo->getAllById('', $opInfo['Id_snap_target'], '');
                $repoName = $myrepo->getName();
                $repoDist = $myrepo->getDist();
                $repoSection = $myrepo->getSection();
            }
        }
        if (!empty($opInfo['Id_env_target'])) {
            $repoEnv = $opInfo['Id_env_target'];
        }

        unset($mygroup, $myrepo);

        /**
         *  Affichage du groupe ou du repo concerné par l'opération
         */
        if (!empty($group)) {
            echo '<span class="label-white">Groupe ' . $group . '</span>';
        }

        if (!empty($repoDist) and !empty($repoSection)) {
            echo '<span class="label-white">' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</span>';
        }

        if (!empty($repoName) and empty($repoDist) and empty($repoSection)) {
            echo '<span class="label-white">' . $repoName . '</span>';
        }

        if (!empty($repoEnv)) {
            echo ' ' . \Controllers\Common::envtag($repoEnv);
        }

        return;
    }

    /**
     *  Update plan Id in database
     */
    public function updatePlanId(int $planId)
    {
        $this->model->updatePlanId($this->id, $planId);
    }

    /**
     *  Update repo name in database
     */
    public function updateTargetRepo(string $repoName)
    {
        $this->model->updateTargetRepo($this->id, $repoName);
    }

    /**
     *  Update source repo in database
     */
    public function updateSourceRepo(string $sourceRepo)
    {
        $this->model->updateSourceRepo($this->id, $sourceRepo);
    }

    /**
     *  Update source snap Id in database
     */
    public function updateSourceSnap(string $snapId)
    {
        $this->model->updateSourceSnap($this->id, $snapId);
    }

    /**
     *  Update target snap Id in database
     */
    public function updateTargetSnap(string $snapId)
    {
        $this->model->updateTargetSnap($this->id, $snapId);
    }

    /**
     *  Update source env in database
     */
    public function updateSourceEnv(string $sourceEnv)
    {
        $this->model->updateSourceEnv($this->id, $sourceEnv);
    }

    /**
     *  Update target env Id in database
     */
    public function updateTargetEnv(string $targetEnvId)
    {
        $this->model->updateTargetEnv($this->id, $targetEnvId);
    }

    /**
     *  Update group Id in database
     */
    public function updateGroup(string $groupId)
    {
        $this->model->updateGroup($this->id, $groupId);
    }

    /**
     *  Update GPG check in database
     */
    public function updateGpgCheck(string $gpgCheck)
    {
        $this->model->updateGpgCheck($this->id, $gpgCheck);
    }

    /**
     *  Update GPG resign in database
     */
    public function updateGpgResign(string $gpgResign)
    {
        $this->model->updateGpgResign($this->id, $gpgResign);
    }

    /**
     *  Exécution d'une opération dont les paramètres ont été validés par validateForm()
     */
    public function execute(array $operationsParams)
    {
        /**
         *  $operationsParams can contain one or more operations
         *  Each operation is an array containing all the parameters needed to execute the operation
         */
        foreach ($operationsParams as $operationParams) {
            /**
             *  If the operation is a new repo, we need to loop through all the releasever (rpm) or dist/section (deb) and create a dedicated operation for each of them
             */
            if ($operationParams['action'] == 'new') {
                if ($operationParams['packageType'] == 'rpm') {
                    foreach ($operationParams['releasever'] as $releasever) {
                        /**
                         *  Create a new array with the same parameters as the original array, but with only one dist and one section
                         */
                        $params = $operationParams;

                        /**
                         *  Replace the releasever array with a single releasever
                         */
                        $params['releasever'] = $releasever;

                        /**
                         *  Generate a pool file containing all the parameters needed to execute the operation then retrieve the pool Id
                         */
                        $poolId = $this->generatePoolFile($params);

                        /**
                         *  Execute the operation
                         */
                        $this->executeId($poolId);
                    }
                }

                if ($operationParams['packageType'] == 'deb') {
                    foreach ($operationParams['dist'] as $dist) {
                        foreach ($operationParams['section'] as $section) {
                            /**
                             *  Create a new array with the same parameters as the original array, but with only one dist and one section
                             */
                            $params = $operationParams;

                            /**
                             *  Replace the dist and section arrays with a single dist and a single section
                             */
                            $params['dist'] = $dist;
                            $params['section'] = $section;

                            /**
                             *  Generate a pool file containing all the parameters needed to execute the operation then retrieve the pool Id
                             */
                            $poolId = $this->generatePoolFile($params);

                            /**
                             *  Execute the operation
                             */
                            $this->executeId($poolId);
                        }
                    }
                }

            /**
             *  Every other operation can be executed directly
             */
            } else {
                /**
                 *  Generate a pool file containing all the parameters needed to execute the operation then retrieve the pool Id
                 */
                $poolId = $this->generatePoolFile($operationParams);

                /**
                 *  Execute the operation
                 */
                $this->executeId($poolId);
            }
        }
    }

    /**
     *  Execute an operation (in background) from its pool Id
     */
    public function executeId(int $poolId)
    {
        if (!file_exists(POOL . '/' . $poolId . '.json')) {
            throw new Exception('Error: specified pool Id does not exist.');
        }

        $myprocess = new \Controllers\Process('/usr/bin/php ' . ROOT . "/operations/execute.php --id='$poolId' >/dev/null 2>/dev/null &");
        $myprocess->execute();
        $myprocess->close();
    }

    /**
     *  Generate a JSON file containing all the parameters needed to execute the operation then return the pool Id
     */
    private function generatePoolFile(array $params)
    {
        /**
         *  Create a poolId to identify the asynchronous operation (it is a mix of Unix timestamp and a random number)
         */
        while (true) {
            $poolId = time() . \Controllers\Common::generateRandom();
            /**
             *  Create the JSON file and exit the loop if the number is available
             */
            if (!file_exists(POOL . '/' . $poolId . '.json')) {
                touch(POOL . '/' . $poolId . '.json');
                break;
            }
        }

        /**
         *  Add the content of the array in the JSON file
         */
        if (!file_put_contents(POOL . '/' . $poolId . '.json', json_encode($params, JSON_PRETTY_PRINT))) {
            throw new Exception('Error: error while generating operation JSON file, operation cannot be run.');
        }

        return $poolId;
    }
}
