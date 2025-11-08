<?php

namespace Controllers\Task;

use \Controllers\Utils\Convert;
use Exception;
use JsonException;

class Execution
{
    private $model;
    protected $repoController;
    protected $repoSnapshotController;
    protected $repoEnvController;
    protected $taskController;
    protected $taskLogStepController;
    protected $taskLogSubStepController;
    protected $taskNotifyController;
    protected $profileController;
    protected $layoutContainerReloadController;
    protected $taskId;
    protected $action;
    protected $status;
    protected $error;
    protected $date;
    protected $time;
    protected $task;
    protected $params;
    private $timeStart;

    public function __construct(int $taskId, string $action)
    {
        $this->model = new \Models\Task\Execution();
        $this->repoController = new \Controllers\Repo\Repo();
        $this->repoSnapshotController = new \Controllers\Repo\Snapshot();
        $this->repoEnvController = new \Controllers\Repo\Environment();
        $this->taskController = new \Controllers\Task\Task();
        $this->taskLogStepController = new \Controllers\Task\Log\Step($taskId);
        $this->taskLogSubStepController = new \Controllers\Task\Log\SubStep($taskId);
        $this->taskNotifyController = new \Controllers\Task\Notify();
        $this->profileController = new \Controllers\Profile();
        $this->layoutContainerReloadController = new \Controllers\Layout\ContainerReload();

        try {
            $requiredParams = [];
            $optionalParams = [];

            // Prepare task and task log
            $date = date('Y-m-d');
            $time = date('H:i:s');

            // Set task properties
            $this->taskId = $taskId;
            $this->action = $action;
            $this->date   = $date;
            $this->time   = $time;

            // Retrieve tasks parameters
            include_once ROOT . '/config/tasks.php';

            // Retrieve task params
            $this->task = $this->taskController->getById($taskId);

            try {
                $this->params = json_decode($this->task['Raw_params'], true);
            } catch (JsonException $e) {
                throw new Exception('could not decode task #' . $taskId . ' parameters JSON: ' . $e->getMessage());
            }

            // Retrieve required and optional params for the specified action, if any
            if (isset($tasksDefinitions[$action]['required-params'])) {
                $requiredParams = $tasksDefinitions[$action]['required-params'];
            }

            if (isset($tasksDefinitions[$action]['optional-params'])) {
                $optionalParams = $tasksDefinitions[$action]['optional-params'];
            }

            // Check required parameters
            try {
                if (!empty($requiredParams)) {
                    $this->paramsCheck($requiredParams);
                }
            } catch (Exception $e) {
                throw new Exception('required parameter check error: ' . $e->getMessage());
            }

            // If there are conditional required parameters, check them too
            if (isset($tasksDefinitions[$action]['conditional-required-params'])) {
                // $basedOnParam must be set in params, for example 'package-type' must exists in params if there are conditional required params based on 'package-type'
                foreach ($tasksDefinitions[$action]['conditional-required-params'] as $basedOnParam => $conditions) {
                    if (isset($this->params[$basedOnParam]) and isset($conditions[$this->params[$basedOnParam]])) {
                        $conditionalRequiredParams = $conditions[$this->params[$basedOnParam]]['required-params'];

                        try {
                            if (!empty($conditionalRequiredParams)) {
                                $this->paramsCheck($conditionalRequiredParams);
                            }
                        } catch (Exception $e) {
                            throw new Exception('conditional required parameter check error: ' . $e->getMessage());
                        }
                    }
                }
            }

            // Retrieve repository details from snap Id, if needed
            if (isset($tasksDefinitions[$action]['retrieve-repo-from-snap-id']) and $tasksDefinitions[$action]['retrieve-repo-from-snap-id'] === true) {
                $this->repoController->getAllById(null, $this->params['snap-id'], null);
            }

            // Set required and optional params
            $this->paramsSet($requiredParams, $optionalParams);

            // If there are conditional required parameters, set them too
            if (isset($tasksDefinitions[$action]['conditional-required-params'])) {
                // $basedOnParam must be set in params, for example 'package-type' must exists in params if there are conditional required params based on 'package-type'
                foreach ($tasksDefinitions[$action]['conditional-required-params'] as $basedOnParam => $conditions) {
                    if (isset($this->params[$basedOnParam]) and isset($conditions[$this->params[$basedOnParam]])) {
                        $conditionalRequiredParams = $conditions[$this->params[$basedOnParam]]['required-params'];
                        $conditionalOptionalParams = [];

                        if (isset($conditions[$this->params[$basedOnParam]]['optional-params'])) {
                            $conditionalOptionalParams = $conditions[$this->params[$basedOnParam]]['optional-params'];
                        }

                        $this->paramsSet($conditionalRequiredParams, $conditionalOptionalParams);
                    }
                }
            }

            // TODO ?: Prepare other things before executing the task





            // Update date and time in database
            $this->taskController->updateDate($taskId, $date);
            $this->taskController->updateTime($taskId, $time);

            // Start task
            $this->start();
        } catch (Exception $e) {
            throw new Exception('task initialization error: ' . $e->getMessage());
        }
    }

    /**
     *  Destructor
     */
    public function __destruct()
    {
        // End task when object is destroyed
        $this->end();
    }

    /**
     *  Check that required parameters are defined and not empty
     */
    public function paramsCheck($requiredParams)
    {
        // Check required parameters
        foreach ($requiredParams as $param) {
            if (!isset($this->params[$param])) {
                throw new Exception('parameter ' . $param . ' is undefined.');
            }

            if (empty($this->params[$param])) {
                throw new Exception('parameter ' . $param . ' is empty.');
            }
        }
    }

    /**
     *  Set repository parameters for a task
     */
    public function paramsSet($requiredParams = [], $optionalParams = [])
    {
        /**
         *  Repo controller setter functions depending on parameters
         */
        $setters = [
            'snap-id' => 'setSnapId',
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
        ];

        /**
         *  Set required parameters, using the appropriate setter function
         */
        if (!empty($requiredParams)) {
            foreach ($requiredParams as $param) {
                $setterFunction = $setters[$param];
                $this->repoController->$setterFunction($this->params[$param]);
            }
        }

        /**
         *  Set optional parameters if defined, using the appropriate setter function
         */
        if (!empty($optionalParams)) {
            foreach ($optionalParams as $param) {
                if (isset($this->params[$param])) {
                    $setterFunction = $setters[$param];
                    $this->repoController->$setterFunction($this->params[$param]);
                }
            }
        }
    }

    /**
     *  Start task
     */
    public function start() : void
    {
        // Generate time start
        $this->timeStart = microtime(true);

        // Set status as 'running' in database
        $this->taskController->updateStatus($this->taskId, 'running');

        // Update layout containers states
        $this->layoutContainerReloadController->reload('header/menu');
        $this->layoutContainerReloadController->reload('repos/list');
        $this->layoutContainerReloadController->reload('tasks/list');
        $this->layoutContainerReloadController->reload('browse/list');
        $this->layoutContainerReloadController->reload('browse/actions');

        // Add current PHP execution PID to the PID file to make sure it can be killed with the stop button
        $this->addsubpid(getmypid());
    }

    /**
     *  End task
     */
    public function end() : void
    {
        try {
            // Calculate total duration
            $duration = Convert::microtimeToHuman(microtime(true) - $this->timeStart);

            // Create one last step for total duration
            $this->taskLogStepController->new('duration', 'DURATION');
            $this->taskLogStepController->none('Total duration: ' . $duration);

            // Update duration
            $this->taskController->updateDuration($this->taskId, $duration);

            // Delete PID file
            if (file_exists(PID_DIR . '/' . $this->taskId . '.pid')) {
                if (!unlink(PID_DIR . '/' . $this->taskId . '.pid')) {
                    throw new Exception('could not delete PID file ' . PID_DIR . '/' . $this->taskId . '.pid');
                }
            }

            // If task was a scheduled task
            if ($this->task['Type'] == 'scheduled') {
                // Send notifications if needed
                // If the task has a notification on error, send it
                if ($this->params['schedule']['schedule-notify-error'] == 'true' and $this->status == 'error') {
                    $this->taskNotifyController->error($this->task, $this->error);
                }

                // If the task has a notification on success, send it
                if ($this->params['schedule']['schedule-notify-success'] == 'true' and $this->status == 'done') {
                    $this->taskNotifyController->success($this->task);
                }

                // If it is a recurring task, duplicate the task in database and reschedule it
                if ($this->params['schedule']['schedule-type'] == 'recurring') {
                    $newTaskId = $this->taskController->duplicate($this->taskId);

                    // Reset real execution date and time
                    $this->taskController->updateDate($newTaskId, '');
                    $this->taskController->updateTime($newTaskId, '');
                    $this->taskController->updateStatus($newTaskId, 'scheduled');
                }

                unset($myTaskNotify);
            }

            // Clean unused repos from profiles
            $this->profileController->cleanProfiles();

            // Update layout containers states
            $this->layoutContainerReloadController->reload('header/menu');
            $this->layoutContainerReloadController->reload('repos/list');
            $this->layoutContainerReloadController->reload('repos/properties');
            $this->layoutContainerReloadController->reload('tasks/list');
            $this->layoutContainerReloadController->reload('browse/list');
            $this->layoutContainerReloadController->reload('browse/actions');
        } catch (Exception $e) {
            throw new Exception('error while ending task: ' . $e->getMessage());
        }
    }

    // /**
    //  *  Relaunch a task
    //  */
    // public function relaunch(int $id) : void
    // {
    //     if (!IS_ADMIN and !in_array('relaunch', USER_PERMISSIONS['tasks']['allowed-actions'])) {
    //         throw new Exception('You are not allowed to relaunch a task');
    //     }

    //     /**
    //      *  First, duplicate task in database
    //      */
    //     $newTaskId = $this->duplicate($id);

    //     /**
    //      *  If a temporary directory was used for the previous task, then rename it to be used for the new task
    //      */
    //     if (file_exists(REPOS_DIR . '/temporary-task-' . $id) and is_dir(REPOS_DIR . '/temporary-task-' . $id)) {
    //         if (!rename(REPOS_DIR . '/temporary-task-' . $id, REPOS_DIR . '/temporary-task-' . $newTaskId)) {
    //             throw new Exception('Could not rename temporary directory ' . REPOS_DIR . '/temporary-task-' . $id . ' to ' . REPOS_DIR . '/temporary-task-' . $newTaskId);
    //         }
    //     }

    //     /**
    //      *  Execute task
    //      */
    //     $this->executeId($newTaskId);

    //     $this->layoutContainerReloadController->reload('tasks/logs');
    //     $this->layoutContainerReloadController->reload('tasks/list');
    // }

    // /**
    //  *  Duplicate a task in database from its Id and return the new task Id
    //  */
    // private function duplicate(int $id) : int
    // {
    //     return $this->model->duplicate($id);
    // }

    // /**
    //  *  Stop a task based on the specified PID
    //  */
    // public function kill(string $taskId) : void
    // {
    //     if (!IS_ADMIN and !in_array('stop', USER_PERMISSIONS['tasks']['allowed-actions'])) {
    //         throw new Exception('You are not allowed to stop a task');
    //     }

    //     if (file_exists(PID_DIR . '/' . $taskId . '.pid')) {
    //         /**
    //          *  Getting PID file content
    //          */
    //         $content = file_get_contents(PID_DIR . '/' . $taskId . '.pid');

    //         /**
    //          *  Getting sub PIDs
    //          */
    //         preg_match_all('/(?<=SUBPID=).*/', $content, $subpids);

    //         /**
    //          *  Killing sub PIDs
    //          */
    //         if (!empty($subpids[0])) {
    //             $killError = '';

    //             foreach ($subpids[0] as $subpid) {
    //                 $subpid = trim(str_replace('"', '', $subpid));

    //                 /**
    //                  *  Check if the PID is still running
    //                  */
    //                 $myprocess = new \Controllers\Process('/usr/bin/ps --pid ' . $subpid);
    //                 $myprocess->execute();
    //                 $content = $myprocess->getOutput();
    //                 $myprocess->close();

    //                 if ($myprocess->getExitCode() != 0) {
    //                     continue;
    //                 }

    //                 /**
    //                  *  Kill the process
    //                  */
    //                 $myprocess = new \Controllers\Process('/usr/bin/kill -9 ' . $subpid);
    //                 $myprocess->execute();
    //                 $content = $myprocess->getOutput();
    //                 $myprocess->close();

    //                 if ($myprocess->getExitCode() != 0) {
    //                     $killError .= 'Could not kill PID ' . $subpid . ': ' . $content. '<br>';
    //                 }
    //             }
    //         }

    //         /**
    //          *  Delete PID file
    //          */
    //         if (!unlink(PID_DIR . '/' . $taskId . '.pid')) {
    //             throw new Exception('Error while deleting PID file');
    //         }
    //     }

    //     /**
    //      *  Update task in database, set status to 'stopped'
    //      */
    //     $this->updateStatus($taskId, 'stopped');

    //     $taskLogStepController = new \Controllers\Task\Log\Step($taskId);
    //     $taskLogSubStepController = new \Controllers\Task\Log\SubStep($taskId);

    //     /**
    //      *  Set latest step and substep as stopped
    //      */
    //     $taskLogStepController->stopped();
    //     $taskLogSubStepController->stopped();

    //     /**
    //      *  Update layout containers states
    //      */
    //     $this->layoutContainerReloadController->reload('header/menu');
    //     $this->layoutContainerReloadController->reload('repos/list');
    //     $this->layoutContainerReloadController->reload('tasks/list');

    //     if (!empty($killError)) {
    //         throw new Exception($killError);
    //     }
    // }

    /**
     *  Add subpid to main PID file
     */
    public function addsubpid(int $pid) : void
    {
        // Add specified PID to the main PID file
        if (!file_put_contents(PID_DIR . '/' . $this->taskId . '.pid', 'SUBPID="' . $pid . '"' . PHP_EOL, FILE_APPEND)) {
            throw new Exception('could not add sub PID to ' . PID_DIR . '/' . $this->taskId . '.pid file');
        }

        // Also add children PID to the main PID file
        $childrenPid = self::getChildrenPid($pid);

        if ($childrenPid !== false) {
            // Add children PID to the main PID file
            foreach ($childrenPid as $childPid) {
                if (is_numeric($childPid)) {
                    if (!file_put_contents(PID_DIR . '/' . $this->taskId . '.pid', 'SUBPID="' . $childPid . '"' . PHP_EOL, FILE_APPEND)) {
                        throw new Exception('could not add sub PID to ' . PID_DIR . '/' . $this->taskId . '.pid file');
                    }
                }

                // If the child PID has children PID, then add them too
                $grandChildrenPid = self::getChildrenPid($childPid);

                if ($grandChildrenPid !== false) {
                    foreach ($grandChildrenPid as $grandChildPid) {
                        if (is_numeric($grandChildPid)) {
                            if (!file_put_contents(PID_DIR . '/' . $this->taskId . '.pid', 'SUBPID="' . $grandChildPid . '"' . PHP_EOL, FILE_APPEND)) {
                                throw new Exception('could not add sub PID to ' . PID_DIR . '/' . $this->taskId . '.pid file');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     *  Return an array with all children PID of the specified PID or false if no children PID
     */
    public static function getChildrenPid(int $pid) : array|bool
    {
        // Specified PID could have children PID, we need to get them all
        $processController = new \Controllers\Process('/usr/bin/pgrep -P ' . $pid);
        $processController->execute();

        // If exit code is 0, then the PID has children
        if ($processController->getExitCode() == 0) {
            // Get children PID from output
            $childrenPid = $processController->getOutput();
            $processController->close();

            $childrenPid = explode(PHP_EOL, $childrenPid);

            // Return children PID
            return $childrenPid;
        }

        return false;
    }
}
