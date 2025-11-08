<?php

namespace Controllers\Task;

use \Controllers\Utils\Convert;
use Exception;
use JsonException;

class Execution
{
    protected object $repoController;
    protected object $sourceRepoController;
    protected object $repoSnapshotController;
    protected object $repoEnvController;
    protected object $rpmRepoController;
    protected object $debRepoController;
    protected object $taskController;
    protected object $taskLogStepController;
    protected object $taskLogSubStepController;
    protected object $taskNotifyController;
    protected object $profileController;
    protected object $layoutContainerReloadController;
    protected int $taskId;
    protected string $action;
    // Default task execution status is 'done', it will be changed to 'error' if an error occurs
    protected string $status = 'done';
    protected string $error = '';
    protected string $date;
    protected string $time;
    protected string $timeStart;
    protected array $task;
    protected array $params;

    public function __construct(int $taskId, string $action)
    {
        $this->repoController = new \Controllers\Repo\Repo();
        $this->sourceRepoController = new \Controllers\Repo\Repo();
        $this->repoSnapshotController = new \Controllers\Repo\Snapshot();
        $this->repoEnvController = new \Controllers\Repo\Environment();
        $this->rpmRepoController = new \Controllers\Repo\Rpm();
        $this->debRepoController = new \Controllers\Repo\Deb();
        $this->taskController = new \Controllers\Task\Task();
        $this->taskLogStepController = new \Controllers\Task\Log\Step($taskId);
        $this->taskLogSubStepController = new \Controllers\Task\Log\SubStep($taskId);
        $this->taskNotifyController = new \Controllers\Task\Notify();
        $this->profileController = new \Controllers\Profile();
        $this->layoutContainerReloadController = new \Controllers\Layout\ContainerReload();

        try {
            $requiredParams = [];
            $optionalParams = [];
            $conditionalRequiredParams = [];
            $conditionalOptionalParams = [];

            // Prepare task and task log
            $date = date('Y-m-d');
            $time = date('H:i:s');

            // Set task properties
            $this->taskId = $taskId;
            $this->action = $action;
            $this->date   = $date;
            $this->time   = $time;

            // Retrieve tasks parameters definition
            include_once ROOT . '/config/tasks/' . $action . '.php';

            // Set task Id for taskController
            $this->taskController->setId($taskId);

            // Retrieve task params
            $this->task = $this->taskController->getById($taskId);

            try {
                $this->params = json_decode($this->task['Raw_params'], true);
            } catch (JsonException $e) {
                throw new Exception('could not decode task #' . $taskId . ' parameters JSON: ' . $e->getMessage());
            }

            // Retrieve the latest snapshot Id for the repository, if needed
            // This requires 'repo-id' to be defined in params and to be numeric
            if (isset($taskConfig['use-latest-snapshot']) and $taskConfig['use-latest-snapshot']) {
                if (!isset($this->params['repo-id'])) {
                    throw new Exception('parameter repo-id is undefined, cannot retrieve latest snapshot Id for the repository');
                }

                if (!is_numeric($this->params['repo-id'])) {
                    throw new Exception('parameter repo-id is not numeric, cannot retrieve latest snapshot Id for the repository');
                }

                // Get the latest snapshot Id for the repository, this can return null if no snapshot was found
                $latestSnapId = $this->repoController->getLatestSnapId($this->params['repo-id']);

                // If latest snapshot Id was not found, throw an exception
                if (empty($latestSnapId)) {
                    throw new Exception('could not find the latest snapshot Id repository #' . $this->params['repo-id']);
                }

                // Update snap-id param to latest snapshot Id
                $this->params['snap-id'] = strval($latestSnapId);

                // Update Raw_params in the database
                try {
                    $this->taskController->updateRawParams($taskId, json_encode($this->params));
                } catch (JsonException $e) {
                    throw new Exception('could not update task #' . $taskId . ' parameters in database (JSON encode failed): ' . $e->getMessage());
                }
            }

            // Retrieve repository details from repo id, snap id and env id, if needed
            if (isset($taskConfig['retrieve-repo-from-all-id']) and $taskConfig['retrieve-repo-from-all-id']) {
                $this->repoController->getAllById($this->params['repo-id'], $this->params['snap-id'], $this->params['env-id']);
            }

            // Retrieve repository details from snap Id, if needed
            if (isset($taskConfig['retrieve-repo-from-snap-id']) and $taskConfig['retrieve-repo-from-snap-id']) {
                $this->repoController->getAllById(null, $this->params['snap-id'], null);
            }

            // Retrieve required params for the specified action, if any
            if (isset($taskConfig['required-params'])) {
                array_push($requiredParams, ...$taskConfig['required-params']);
            }

            // Retrieve optional params for the specified action, if any
            if (isset($taskConfig['optional-params'])) {
                array_push($optionalParams, ...$taskConfig['optional-params']);
            }

            // If there are conditional required parameters, retrieve them too
            if (isset($taskConfig['conditional-required-params'])) {
                // $basedOnParam must be set in params, for example 'package-type' must exists in params if there are conditional required params based on 'package-type'
                foreach ($taskConfig['conditional-required-params'] as $basedOnParam => $conditions) {
                    // If based on user form input values
                    if ($taskConfig['conditional-compare-with'] == 'form') {
                        if (isset($this->params[$basedOnParam]) and isset($conditions[$this->params[$basedOnParam]])) {
                            array_push($requiredParams, ...$conditions[$this->params[$basedOnParam]]);
                        }
                    }

                    // If based on current repository values
                    if ($taskConfig['conditional-compare-with'] == 'current-repo') {
                        // Retrieve current repository details
                        if ($basedOnParam == 'repo-type') {
                            $value = $this->repoController->getType();
                        }
                        if ($basedOnParam == 'package-type') {
                            $value = $this->repoController->getPackageType();
                        }

                        if (isset($conditions[$value])) {
                            array_push($requiredParams, ...$conditions[$value]);
                        }
                    }
                }
            }

            // If there are conditional optional parameters, retrieve them too
            if (isset($taskConfig['conditional-optional-params'])) {
                // $basedOnParam must be set in params, for example 'package-type' must exists in params if there are conditional optional params based on 'package-type'
                foreach ($taskConfig['conditional-optional-params'] as $basedOnParam => $conditions) {
                    // If based on user form input values
                    if ($taskConfig['conditional-compare-with'] == 'form') {
                        if (isset($this->params[$basedOnParam]) and isset($conditions[$this->params[$basedOnParam]])) {
                            array_push($optionalParams, ...$conditions[$this->params[$basedOnParam]]);
                        }
                    }

                    // If based on current repository values
                    if ($taskConfig['conditional-compare-with'] == 'current-repo') {
                        // Retrieve current repository details
                        if ($basedOnParam == 'repo-type') {
                            $value = $this->repoController->getType();
                        }
                        if ($basedOnParam == 'package-type') {
                            $value = $this->repoController->getPackageType();
                        }

                        if (isset($conditions[$value])) {
                            array_push($optionalParams, ...$conditions[$value]);
                        }
                    }
                }
            }

            // Check required parameters
            try {
                $this->paramsCheck($requiredParams);
            } catch (Exception $e) {
                throw new Exception('parameters check error: ' . $e->getMessage());
            }

            // Set required and optional params
            try {
                $this->paramsSet($requiredParams, $optionalParams);
            } catch (Exception $e) {
                throw new Exception('parameters set error: ' . $e->getMessage());
            }

            // Update date and time in database
            $this->taskController->updateDate($taskId, $date);
            $this->taskController->updateTime($taskId, $time);

            // Start task
            $this->start();
        } catch (Exception $e) {
            throw new Exception('Task initialization error: ' . $e->getMessage());
        }
    }

    /**
     *  Check that required parameters are defined and not empty
     */
    public function paramsCheck($requiredParams)
    {
        foreach ($requiredParams as $param) {
            if (!isset($this->params[$param])) {
                throw new Exception('parameter ' . $param . ' is undefined');
            }

            if (empty($this->params[$param])) {
                throw new Exception('parameter ' . $param . ' is empty');
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
            'repo-id' => 'setRepoId',
            'snap-id' => 'setSnapId',
            'env-id' => 'setEnvId',
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
     *  Start task execution
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
        $this->taskController->addsubpid(getmypid());
    }

    /**
     *  End task execution
     */
    public function end() : void
    {
        /**
         *  Set task status
         */
        $this->taskController->setStatus($this->status);
        $this->taskController->updateStatus($this->taskId, $this->status);

        if ($this->status == 'error') {
            $this->taskController->setError('Failed');

            // Set latest sub step error message and mark step as error
            $this->taskLogSubStepController->error($this->error);
            $this->taskLogStepController->error();
        }

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
            throw new Exception('Error while ending task: ' . $e->getMessage());
        }
    }
}
