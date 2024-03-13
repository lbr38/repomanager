<?php

namespace Controllers\Task;

use Exception;

class Task
{
    private $id;
    private $pid;
    private $model;
    private $logfile;
    private $action;
    private $status;
    private $error;
    private $type;
    private $date;
    private $time;
    private $repoName;
    // private $planId; // Si une opération est lancée par une planification alors on peut stocker l'ID de cette planification dans cette variable
    private $gpgCheck;
    private $gpgResign;
    private $timeStart;
    private $timeEnd;
    private $poolId;

    public function __construct(bool $generatePid = true)
    {
        $this->model = new \Models\Task\Task();
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

    // public function setPlanId(string $planId)
    // {
    //     $this->planId = $planId;
    // }

    public function setAction(string $action)
    {
        $this->action = $action;
    }

    public function setType(string $type)
    {
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

    // public function getPlanId()
    // {
    //     return $this->planId;
    // }

    public function getPoolId()
    {
        return $this->poolId;
    }

    public function getDuration()
    {
        return microtime(true) - $this->timeStart;
    }

    /**
     *  Get task details by Id
     */
    public function getById(int $id)
    {
        return $this->model->getById($id);
    }

    /**
     *  Update plan Id in database
     */
    // public function updatePlanId(int $planId)
    // {
    //     $this->model->updatePlanId($this->id, $planId);
    // }

    /**
     *  Update repo name in database
     */
    public function updateTargetRepo(string $repoName)
    {
        $this->model->updateTargetRepo($this->id, $repoName);
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
    public function updateGpgSign(string $gpgResign)
    {
        $this->model->updateGpgSign($this->id, $gpgResign);
    }

    /**
     *  Execute one or more tasks
     */
    public function execute(array $tasksParams)
    {
        $myTaskPool = new \Controllers\Task\Pool\Pool();

        /**
         *  $tasksParams can contain one or more tasks
         *  Each task is an array containing all the parameters needed to execute the operation
         */
        foreach ($tasksParams as $taskParams) {
            /**
             *  If the task is a new repo, we need to loop through all the releasever (rpm) or dist/section (deb) and create a dedicated task for each of them
             */
            if ($taskParams['action'] == 'new') {
                if ($taskParams['packageType'] == 'rpm') {
                    foreach ($taskParams['releasever'] as $releasever) {
                        /**
                         *  Create a new array with the same parameters as the original array, but with only one dist and one section
                         */
                        $params = $taskParams;

                        /**
                         *  Replace the releasever array with a single releasever
                         */
                        $params['releasever'] = $releasever;

                        /**
                         *  Generate a pool file containing all the parameters needed to execute the operation then retrieve the pool Id
                         */
                        $poolId = $myTaskPool->new($params);

                        /**
                         *  Execute the task
                         */
                        $this->executeId($poolId);
                    }
                }

                if ($taskParams['packageType'] == 'deb') {
                    foreach ($taskParams['dist'] as $dist) {
                        foreach ($taskParams['section'] as $section) {
                            /**
                             *  Create a new array with the same parameters as the original array, but with only one dist and one section
                             */
                            $params = $taskParams;

                            /**
                             *  Replace the dist and section arrays with a single dist and a single section
                             */
                            $params['dist'] = $dist;
                            $params['section'] = $section;

                            /**
                             *  Generate a pool file containing all the parameters needed to execute the task then retrieve the pool Id
                             */
                            $poolId = $myTaskPool->new($params);


                            /**
                             *  Execute the task
                             */
                            $this->executeId($poolId);
                        }
                    }
                }

            /**
             *  Every other task can be executed directly
             */
            } else {
                /**
                 *  Generate a pool file containing all the parameters needed to execute the operation then retrieve the pool Id
                 */
                $poolId = $myTaskPool->new($taskParams);

                /**
                 *  Execute the task
                 */
                $this->executeId($poolId);
            }
        }
    }

    /**
     *  Execute a task in background from its pool Id
     */
    public function executeId(int $id)
    {
        // $myprocess = new \Controllers\Process('/usr/bin/php ' . ROOT . '/operations/execute.php --id="' . $id . '" >/dev/null 2>/dev/null &');
        // $myprocess->execute();
        // $myprocess->close();
    }

    /**
     *  Start task
     */
    public function start()
    {
        $this->date = date('Y-m-d');
        $this->time = date('H:i:s');
        $this->timeStart = microtime(true);
        $this->status = 'running';

        /**
         *  Add task in database
         */
        $this->model->add($this->date, $this->time, $this->action, $this->type, $this->pid, $this->poolId, $this->logfile, $this->status);

        /**
         *  Get task id in database
         */
        $this->id = $this->model->getLastInsertRowID();

        /**
         *  Update task informations in database
         */
        // if (!empty($this->planId)) {
        //     $this->updatePlanId($this->planId);
        // }

        if (!empty($this->repoName)) {
            $this->updateTargetRepo($this->repoName);
        }

        if (!empty($this->sourceSnapId)) {
            $this->updateSourceSnap($this->sourceSnapId);
        }

        if (!empty($this->targetSnapId)) {
            $this->updateTargetSnap($this->targetSnapId);
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
            $this->updateGpgSign($this->gpgResign);
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
        if (!file_put_contents(PID_DIR . '/' . $this->pid . '.pid', 'PID="' . $this->pid . '"' . PHP_EOL . 'LOG="' . $this->logfile . '"' . PHP_EOL)) {
            throw new Exception('Could not create PID file ' . PID_DIR . '/' . $this->pid . '.pid');
        }

        /**
         *  Add current PHP execution PID to the PID file to make sure it can be killed with the stop button
         */
        $this->addsubpid(getmypid());
    }

    /**
     *  Stop and close task
     */
    public function close()
    {
        /**
         *  Generate a 'completed' file in the task steps temporary directory, so that logbuilder.php stops
         */
        if (!touch(TEMP_DIR . '/' . $this->pid . '/completed')) {
            throw new Exception('Could not create file ' . TEMP_DIR . '/' . $this->pid . '/completed');
        }

        /**
         *  Delete pid file
         */
        if (file_exists(PID_DIR . '/' . $this->pid . '.pid')) {
            if (!unlink(PID_DIR . '/' . $this->pid . '.pid')) {
                throw new Exception('Could not delete PID file ' . PID_DIR . '/' . $this->pid . '.pid');
            }
        }

        /**
         *  Close task in database
         */
        $this->model->close($this->id, $this->status, $this->getDuration());

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
     *  Stop a task based on the specified PID
     */
    public function kill(string $pid)
    {
        if (!file_exists(PID_DIR . '/' . $pid . '.pid')) {
            throw new Exception('Specified task PID does not exist.');
        }

        /**
         *  Getting task Id from its PID
         */
        $taskId = $this->model->getIdByPid($pid);

        /**
         *  If the task Id is empty, we throw an exception
         */
        if (empty($taskId)) {
            throw new Exception('Could not find task Id from PID ' . $pid);
        }

        /**
         *  Getting PID file content
         */
        $content = file_get_contents(PID_DIR . '/' . $pid . '.pid');

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
                $myprocess = new \Controllers\Process('/usr/bin/ps --pid ' . $subpid);
                $myprocess->execute();
                $content = $myprocess->getOutput();
                $myprocess->close();

                if ($myprocess->getExitCode() != 0) {
                    continue;
                }

                /**
                 *  Kill the process
                 */
                $myprocess = new \Controllers\Process('/usr/bin/kill -9 ' . $subpid);
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
         *  If this task was started by a planification, we need to update the planification in database
         *  First we need to get the planification Id
         */
        // $planId = $this->model->getPlanIdByPid($pid);

        /**
         *  Update task in database, set status to 'stopped'
         */
        $this->model->setStatus($taskId, 'stopped');

        /**
         *  Update planification in database
         */
        // if (!empty($planId)) {
        //     $myplan = new \Controllers\Planification();
        //     $myplan->stop($planId);
        // }

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
}
