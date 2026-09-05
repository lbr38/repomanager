<?php

namespace Controllers\Task;

use Controllers\User\Permission\Task as TaskPermission;
use Controllers\Task\Form\Param\Schedule;
use Controllers\History\Save as History;
use Controllers\Task\Log\SubStep;
use Controllers\Task\Log\Step;
use Controllers\Utils\Convert;
use Controllers\Utils\Cron;
use Controllers\Repo\Repo;
use Controllers\Process;
use JsonException;
use Exception;
use DateTime;

class Task
{
    private $id;
    private $status;
    private $error;
    private $type;
    private $date;
    private $time;
    private $timeStart;
    protected $model;
    private $profileController;
    private $layoutContainerReloadController;

    public function __construct()
    {
        $this->model = new \Models\Task\Task();
        $this->profileController = new \Controllers\Profile();
        $this->layoutContainerReloadController = new \Controllers\Layout\ContainerReload();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getTime()
    {
        return $this->time;
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

    public function getDuration()
    {
        return microtime(true) - $this->timeStart;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    public function setDate(string $date)
    {
        $this->date = $date;
    }

    public function setTime(string $time)
    {
        $this->time = $time;
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

    /**
     *  Get task details by Id
     */
    public function getById(int $id): array
    {
        return $this->model->getById($id);
    }

    /**
     *  Update date in database
     */
    public function updateDate(int $id, string $date) : void
    {
        $this->model->updateDate($id, $date);
    }

    /**
     *  Update time in database
     */
    public function updateTime(int $id, string $time) : void
    {
        $this->model->updateTime($id, $time);
    }

    /**
     *  Update raw_params in database
     */
    public function updateRawParams(int $id, string $rawParams) : void
    {
        $this->model->updateRawParams($id, $rawParams);
    }

    /**
     *  Update status in database
     */
    public function updateStatus(int $id, string $status) : void
    {
        $this->model->updateStatus($id, $status);
    }

    /**
     *  Update duration in database
     */
    public function updateDuration(int $id, string $duration) : void
    {
        $this->model->updateDuration($id, $duration);
    }

    /**
     *  Return last done task Id
     *  Can return null if no task is found (e.g. brand new installation with no task)
     */
    public function getLastTaskId(string $status = '') : int|null
    {
        return $this->model->getLastTaskId($status);
    }

    /**
     *  Get last scheduled task (last 7 days)
     */
    public function getLastScheduledTask()
    {
        return $this->model->getLastScheduledTask();
    }

    /**
     *  Get next scheduled task
     */
    public function getNextScheduledTask()
    {
        return $this->model->getNextScheduledTask();
    }

    /**
     *  Return true if a task is running
     */
    public function somethingRunning()
    {
        return $this->model->somethingRunning();
    }

    /**
     *  Return repository from task Id
     *  Return string if $string is true, otherwise return an array with the repository details
     */
    public function getRepo(int $id, $string = true): string|array
    {
        try {
            // Retrieve task informations
            $taskInfo = $this->getById($id);

            try {
                $taskRawParams = json_decode($taskInfo['Raw_params'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new Exception('could not decode task parameters: ' . $e->getMessage());
            }
        } catch (Exception $e) {
            return 'Error retrieving repository: ' . strtolower($e->getMessage());
        }

        return $this->getRepoFromParams($taskRawParams, $string);
    }

    /**
     *  Return repository from task parameters
     *  Return string if $string is true, otherwise return an array with the repository details
     */
    public function getRepoFromParams(array $taskRawParams, $string = true): string|array
    {
        $repoController = new Repo();

        try {
            // Case where the task groups sub-tasks, the repositories are the ones of its sub-tasks
            if (!empty($taskRawParams['tasks'])) {
                unset($repoController);

                $description = count($taskRawParams['tasks']) . ' repositories';

                if ($string) {
                    return $description;
                }

                return [
                    'type' => 'group',
                    'name' => $description
                ];
            }

            // Case where the task targets a dynamic set of repositories, there is no fixed repository
            if (Target::isDynamic($taskRawParams)) {
                unset($repoController);

                $description = Target::describe($taskRawParams['target']);

                if ($string) {
                    return $description;
                }

                return [
                    'type' => 'target',
                    'name' => $description
                ];
            }

            if (!empty($taskRawParams['source-snap-id'])) {
                if (is_numeric($taskRawParams['source-snap-id'])) {
                    $repoController->getAllById('', $taskRawParams['source-snap-id'], '');
                    $name = $repoController->getName();

                    if ($repoController->getPackageType() == 'deb') {
                        $dist       = $repoController->getDist();
                        $component  = $repoController->getSection();
                    }
                    if ($repoController->getPackageType() == 'rpm') {
                        $releasever = $repoController->getReleasever();
                    }
                }
            } else if (!empty($taskRawParams['repo-id'])) {
                if (is_numeric($taskRawParams['repo-id'])) {
                    $repoController->getAllById($taskRawParams['repo-id'], '', '');
                    $name = $repoController->getName();

                    if ($repoController->getPackageType() == 'deb') {
                        $dist       = $repoController->getDist();
                        $component  = $repoController->getSection();
                    }
                    if ($repoController->getPackageType() == 'rpm') {
                        $releasever = $repoController->getReleasever();
                    }
                } else {
                    if ($taskRawParams['package-type'] == 'rpm') {
                        $name = $taskRawParams['repo-id'];
                        $releasever = $taskRawParams['releasever'];
                    }
                    if ($taskRawParams['package-type'] == 'deb') {
                        $repo = explode('|', $taskRawParams['repo-id']);
                        $name = $repo[0];
                        if (!empty($repo[1]) and !empty($repo[2])) {
                            $dist      = $repo[1];
                            $component = $repo[2];
                        }
                    }
                }
            } else if (!empty($taskRawParams['snap-id'])) {
                if (is_numeric($taskRawParams['snap-id'])) {
                    $repoController->getAllById('', $taskRawParams['snap-id'], '');
                    $name = $repoController->getName();

                    if ($repoController->getPackageType() == 'deb') {
                        $dist       = $repoController->getDist();
                        $component  = $repoController->getSection();
                    }
                    if ($repoController->getPackageType() == 'rpm') {
                        $releasever = $repoController->getReleasever();
                    }
                }
            }

            unset($repoController);

            if (!empty($dist) and !empty($component)) {
                $repo = [
                    'type' => 'deb',
                    'name' => $name,
                    'dist' => $dist,
                    'component' => $component
                ];

                if ($string) {
                    return $name . ' ❯ ' . $dist . ' ❯ ' . $component;
                }
            }

            if (!empty($releasever)) {
                $repo = [
                    'type' => 'rpm',
                    'name' => $name,
                    'releasever' => $releasever
                ];

                if ($string) {
                    return $name . ' ❯ ' . $releasever;
                }
            }

            if (empty($repo)) {
                throw new Exception('unknown repository');
            }

            return $repo;
        } catch (Exception $e) {
            return 'Error retrieving repository: ' . strtolower($e->getMessage());
        }
    }

    /**
     *  Add a new task in database
     *  @param array $params
     *  @param int|null $parentTaskId Id of the task that generated this task, if any (e.g. a scheduled task targeting a group of repositories)
     */
    private function new(array $params, int|null $parentTaskId = null) : int
    {
        /**
         *  Default values
         *  By default the task is immediate and is queued
         */
        $type = 'immediate';
        $status = 'queued';

        /**
         *  If task is scheduled then overwrite the type and status
         *  Task is not queued immediately, it will be queued at the scheduled time (when the service will launch the task)
         */
        if ($params['schedule']['scheduled'] == 'true') {
            $type = 'scheduled';
            $status = 'scheduled';
        }

        // Clean the schedule parameters (remove unnecessary parameters depending on the schedule type)
        $params = Schedule::clean($params);

        /**
         *  If task is 'create' then inject the name / dist / section into the repo-id field
         *  A parent task has no repository of its own, only the sub-tasks it groups have one
         */
        if ($params['action'] == 'create' and empty($params['tasks'])) {
            // Repo name is the alias if it exists, otherwise it is the source repository name
            if (!empty($params['alias'])) {
                $name = $params['alias'];
            } else {
                $name = $params['source'];
            }

            if ($params['package-type'] == 'rpm') {
                $params['repo-id'] = $name;
            }

            if ($params['package-type'] == 'deb') {
                $params['repo-id'] = $name . '|' . $params['dist'] . '|' . $params['section'];
            }
        }

        try {
            $paramsJson = json_encode($params, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Could not encode task parameters: ' . $e->getMessage());
        }

        /**
         *  Add the task in database
         */
        $taskId = $this->model->new($type, $paramsJson, $status, $parentTaskId);

        return $taskId;
    }

    /**
     *  Execute one or more tasks
     *  @param int|null $parentTaskId Id of the task that generated these tasks, if any (e.g. a scheduled task targeting a group of repositories)
     */
    public function execute(array $tasksParams, int|null $parentTaskId = null): int|array
    {
        $tasks = [];
        $tasksToExecute = [];

        // Some parameters describe several tasks at once, so they are first split into individual tasks
        $tasksParams = $this->splitParams($tasksParams);

        /**
         *  An action targeting several repositories generates as many tasks, which are grouped under
         *  a parent task so that they are listed, followed and reported as a single operation
         */
        if (count($tasksParams) > 1 and empty($parentTaskId)) {
            $parentTaskId = $this->new([
                'action'   => $tasksParams[0]['action'],
                'schedule' => $tasksParams[0]['schedule'],

                // Kept so that a scheduled parent task can create its sub-tasks when it runs
                'tasks'    => $tasksParams
            ]);

            /**
             *  A scheduled parent task only creates its sub-tasks when it reaches its execution time,
             *  so there is nothing left to do for now
             */
            if ($tasksParams[0]['schedule']['scheduled'] == 'true') {
                return $parentTaskId;
            }

            // The parent task stays running as long as one of its sub-tasks is
            $this->updateDate($parentTaskId, date('Y-m-d'));
            $this->updateTime($parentTaskId, date('H:i:s'));
            $this->updateStatus($parentTaskId, 'running');
        }

        foreach ($tasksParams as $taskParams) {
            // Generate a new task containing all the parameters needed to execute the task retrieve its Id
            $taskId = $this->new($taskParams, $parentTaskId);

            // Execute the task now if it is not scheduled
            if ($taskParams['schedule']['scheduled'] != 'true') {
                $tasksToExecute[] = $taskId;
            }

            // Add task Id to the list of executed tasks
            $tasks[] = $taskId;
        }

        /**
         *  All the tasks are created before any of them is executed, so that a parent task cannot be
         *  closed by one of its sub-tasks while the remaining ones are still being created
         */
        foreach ($tasksToExecute as $taskId) {
            $this->executeId($taskId);
        }

        // The parent task is the entry point of the whole operation
        if (!empty($parentTaskId)) {
            return $parentTaskId;
        }

        // Return the Id of the executed task or an array with all the executed tasks Id
        if (count($tasks) == 1) {
            return $tasks[0];
        } else {
            return $tasks;
        }
    }

    /**
     *  Split the parameters describing several tasks at once into individual task parameters
     *  A new repository can target multiple releasever (rpm) or multiple dist/section (deb), and
     *  each of them is a task of its own
     */
    private function splitParams(array $tasksParams): array
    {
        $params = [];

        foreach ($tasksParams as $taskParams) {
            if ($taskParams['action'] != 'create') {
                $params[] = $taskParams;
                continue;
            }

            if ($taskParams['package-type'] == 'rpm') {
                foreach ($taskParams['releasever'] as $releasever) {
                    $params[] = array_merge($taskParams, ['releasever' => $releasever]);
                }
            }

            if ($taskParams['package-type'] == 'deb') {
                foreach ($taskParams['dist'] as $dist) {
                    foreach ($taskParams['section'] as $section) {
                        $params[] = array_merge($taskParams, ['dist' => $dist, 'section' => $section]);
                    }
                }
            }
        }

        return $params;
    }

    /**
     *  Execute a task in background from its task Id
     */
    public function executeId(int $id) : void
    {
        $myprocess = new Process('/usr/bin/php ' . ROOT . '/tasks/execute.php --id="' . $id . '" > ' . MAIN_LOGS_DIR . '/repomanager-task-' . $id . '-log.process 2>&1 &');
        $myprocess->execute();
        $myprocess->close();
    }

    /**
     *  Close the parent task of a sub-task that has just ended
     *  A parent task has no execution of its own, it only dispatches sub-tasks, so it is closed as
     *  soon as none of them is left running, with a status summarizing their results
     */
    public function closeParent(int $parentTaskId) : void
    {
        $taskListingController = new Listing();

        $parentTask = $this->getById($parentTaskId);

        // Nothing to do if the parent task has already been closed
        if (empty($parentTask) or $parentTask['Status'] != 'running') {
            return;
        }

        $summary = $taskListingController->getSubTasksSummary($parentTaskId);

        // The parent task can only be closed once its last sub-task has ended
        if (empty($summary) or $summary['ongoing'] > 0) {
            return;
        }

        // Total duration of the parent task, from the moment it dispatched its sub-tasks
        $duration = Convert::microtimeToHuman(time() - strtotime($parentTask['Date'] . ' ' . $parentTask['Time']));

        /**
         *  Several sub-tasks can end at the same time and all reach this point, so the closing of the
         *  parent task acts as a lock: only the one that actually closed it sends the notification
         */
        if (!$this->model->closeIfRunning($parentTaskId, $summary['status'], $duration)) {
            return;
        }

        $taskNotifyController = new Notify();
        $taskNotifyController->result($parentTaskId, $summary);

        // Update layout containers states
        $this->layoutContainerReloadController->reload('tasks/tasks');
        $this->layoutContainerReloadController->reload('tasks/log');
    }

    /**
     *  Return true if task exists in database
     */
    public function exists(int $id) : bool
    {
        return $this->model->exists($id);
    }

    /**
     *  Relaunch a task
     */
    public function relaunch(int $id) : void
    {
        if (!TaskPermission::allowedAction('relaunch')) {
            throw new Exception('You are not allowed to relaunch a task');
        }

        /**
         *  First, duplicate task in database
         */
        $newTaskId = $this->duplicate($id);

        /**
         *  If a temporary directory was used for the previous task, then rename it to be used for the new task
         */
        if (file_exists(REPOS_DIR . '/temporary-task-' . $id) and is_dir(REPOS_DIR . '/temporary-task-' . $id)) {
            if (!rename(REPOS_DIR . '/temporary-task-' . $id, REPOS_DIR . '/temporary-task-' . $newTaskId)) {
                throw new Exception('Could not rename temporary directory ' . REPOS_DIR . '/temporary-task-' . $id . ' to ' . REPOS_DIR . '/temporary-task-' . $newTaskId);
            }
        }

        /**
         *  Execute task
         */
        $this->executeId($newTaskId);

        $this->layoutContainerReloadController->reload('tasks/logs');
        $this->layoutContainerReloadController->reload('tasks/tasks');
    }

    /**
     *  Duplicate a task in database from its Id and return the new task Id
     */
    public function duplicate(int $id) : int
    {
        return $this->model->duplicate($id);
    }

    /**
     *  Stop a task based on the specified task Id
     */
    public function stop(int $taskId): void
    {
        if (!TaskPermission::allowedAction('stop')) {
            throw new Exception('You are not allowed to stop a task');
        }

        // Check if task exists
        if (!$this->exists($taskId)) {
            throw new Exception('Task #' . $taskId . ' does not exist');
        }

        // Get task details
        $taskInfo = $this->getById($taskId);

        // Check if task is running
        if ($taskInfo['Status'] != 'running') {
            throw new Exception('Task #' . $taskId . ' is not running');
        }

        $taskListingController = new Listing();
        $subTasks = $taskListingController->getByParentId($taskId);

        /**
         *  A parent task has no process and no log of its own, so stopping it means stopping the
         *  sub-tasks it launched. They cannot close it once killed, so it is closed here.
         */
        if (!empty($subTasks)) {
            foreach ($subTasks as $subTask) {
                if ($subTask['Status'] == 'running') {
                    $this->stop((int) $subTask['Id']);
                }
            }

            $this->updateStatus($taskId, 'stopped');

            // Update layout containers states
            $this->layoutContainerReloadController->reload('tasks/tasks');
            $this->layoutContainerReloadController->reload('tasks/log');

            // Save history
            History::set('Stopped task #' . $taskId);

            return;
        }

        if (file_exists(PID_DIR . '/' . $taskId . '.pid')) {
            // Getting PID file content
            $content = file_get_contents(PID_DIR . '/' . $taskId . '.pid');

            // Getting sub PIDs
            preg_match_all('/(?<=SUBPID=).*/', $content, $subpids);

            // Killing sub PIDs
            if (!empty($subpids[0])) {
                $killError = '';

                foreach ($subpids[0] as $subpid) {
                    $subpid = trim(str_replace('"', '', $subpid));

                    // Check if the PID is still running
                    $myprocess = new Process('/usr/bin/ps --pid ' . $subpid);
                    $myprocess->execute();
                    $content = $myprocess->getOutput();
                    $myprocess->close();

                    if ($myprocess->getExitCode() != 0) {
                        continue;
                    }

                    // Kill the process
                    $myprocess = new Process('/usr/bin/kill -9 ' . $subpid);
                    $myprocess->execute();
                    $content = $myprocess->getOutput();
                    $myprocess->close();

                    if ($myprocess->getExitCode() != 0) {
                        $killError .= 'Could not kill PID ' . $subpid . ': ' . $content. '<br>';
                    }
                }
            }

            // Delete PID file
            if (!unlink(PID_DIR . '/' . $taskId . '.pid')) {
                throw new Exception('Error while deleting PID file');
            }
        }

        // Update task in database, set status to 'stopped'
        $this->updateStatus($taskId, 'stopped');

        $taskLogStepController = new Step($taskId);
        $taskLogSubStepController = new SubStep($taskId);

        // Set latest step and substep as stopped
        $taskLogStepController->stopped();
        $taskLogSubStepController->stopped();

        // Update layout containers states
        $this->layoutContainerReloadController->reload('header/menu');
        $this->layoutContainerReloadController->reload('repos/kpi');
        $this->layoutContainerReloadController->reload('repos/list');
        $this->layoutContainerReloadController->reload('tasks/tasks');
        $this->layoutContainerReloadController->reload('tasks/log');

        // Save history
        History::set('Stopped task #' . $taskId);

        if (!empty($killError)) {
            throw new Exception($killError);
        }
    }

    /**
     *  Add subpid to main PID file
     */
    public function addsubpid(int $pid) : void
    {
        // Add specified PID to the main PID file
        if (!file_put_contents(PID_DIR . '/' . $this->id . '.pid', 'SUBPID="' . $pid . '"' . PHP_EOL, FILE_APPEND)) {
            throw new Exception('could not add sub PID to ' . PID_DIR . '/' . $this->id . '.pid file');
        }

        // Also add children PID to the main PID file
        $childrenPid = self::getChildrenPid($pid);

        if ($childrenPid !== false) {
            // Add children PID to the main PID file
            foreach ($childrenPid as $childPid) {
                if (is_numeric($childPid)) {
                    if (!file_put_contents(PID_DIR . '/' . $this->id . '.pid', 'SUBPID="' . $childPid . '"' . PHP_EOL, FILE_APPEND)) {
                        throw new Exception('could not add sub PID to ' . PID_DIR . '/' . $this->id . '.pid file');
                    }
                }

                // If the child PID has children PID, then add them too
                $grandChildrenPid = self::getChildrenPid($childPid);

                if ($grandChildrenPid !== false) {
                    foreach ($grandChildrenPid as $grandChildPid) {
                        if (is_numeric($grandChildPid)) {
                            if (!file_put_contents(PID_DIR . '/' . $this->id . '.pid', 'SUBPID="' . $grandChildPid . '"' . PHP_EOL, FILE_APPEND)) {
                                throw new Exception('could not add sub PID to ' . PID_DIR . '/' . $this->id . '.pid file');
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
        $processController = new Process('/usr/bin/pgrep -P ' . $pid);
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

    /**
     *  Enable a recurrent task
     */
    public function enable(array $tasksId): void
    {
        if (!TaskPermission::allowedAction('enable')) {
            throw new Exception('You are not allowed to enable a task');
        }

        foreach ($tasksId as $id) {
            // Check if task exists
            if (!$this->exists($id)) {
                throw new Exception('Task #' . $id . ' does not exist');
            }

            // Get task details
            $task = $this->getById($id);

            try {
                $taskRawParams = json_decode($task['Raw_params'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new Exception('Could not decode task #' . $id . ' JSON parameters: ' . $e->getMessage());
            }

            // Check if task is a scheduled task
            if ($taskRawParams['schedule']['scheduled'] != 'true') {
                throw new Exception('Task #' . $id . ' is not a scheduled task and cannot be enabled');
            }

            // Do nothing if task is already enabled
            if ($task['Status'] == 'scheduled') {
                return;
            }

            // Enable task
            $this->model->enable($id);
        }
    }

    /**
     *  Disable a recurrent task
     */
    public function disable(array $tasksId) : void
    {
        if (!TaskPermission::allowedAction('disable')) {
            throw new Exception('You are not allowed to disable a task');
        }

        foreach ($tasksId as $id) {
            // Check if task exists
            if (!$this->exists($id)) {
                throw new Exception('Task #' . $id . ' does not exist');
            }

            // Get task details
            $task = $this->getById($id);

            try {
                $taskRawParams = json_decode($task['Raw_params'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new Exception('Could not decode task #' . $id . ' JSON parameters: ' . $e->getMessage());
            }

            // Check if task is a scheduled task
            if ($taskRawParams['schedule']['scheduled'] != 'true') {
                throw new Exception('Task #' . $id . ' is not a scheduled task and cannot be disabled');
            }

            // Do nothing if task is already disabled
            if ($task['Status'] == 'disabled') {
                return;
            }

            // Disable task
            $this->model->disable($id);
        }
    }

    /**
     *  Delete one or more tasks
     */
    public function delete(array $tasksId) : void
    {
        if (!TaskPermission::allowedAction('delete')) {
            throw new Exception('You are not allowed to delete a task');
        }

        foreach ($tasksId as $id) {
            // Check if task exists
            if (!$this->exists($id)) {
                throw new Exception('Task #' . $id . ' does not exist');
            }

            // Get task details
            $task = $this->getById($id);

            try {
                $taskRawParams = json_decode($task['Raw_params'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new Exception('Could not decode task #' . $id . ' JSON parameters: ' . $e->getMessage());
            }

            // Check if task is a scheduled task or a queued task, only these types of tasks can be deleted
            if ($taskRawParams['schedule']['scheduled'] != 'true' and $task['Status'] != 'queued') {
                throw new Exception('Task #' . $id . ' is not a scheduled task and cannot be deleted');
            }

            // Delete task
            $this->model->delete($id);
        }
    }

    /**
     *  Return an array with the day and time left before the task is executed
     */
    public function getDayTimeLeft(int $taskId) : array
    {
        $dateNow = new DateTime(DATE_YMD);
        $timeNow = new DateTime(date('H:i'));

        $schedule = [
            'date' => '',
            'time' => '',
            'left' => [
                'days' => '',
                'time' => ''
            ],
        ];

        /**
         *  Retrieve task details
         */
        $task = $this->getById($taskId);
        $taskRawParams = json_decode($task['Raw_params'], true);

        /**
         *  Case it is a unique task
         */
        if ($taskRawParams['schedule']['schedule-type'] == 'unique') {
            $taskDate = new DateTime($taskRawParams['schedule']['schedule-date']);
            $taskTime = new DateTime($taskRawParams['schedule']['schedule-time']);

            $schedule['date'] = $taskDate->format('Y-m-d');
            $schedule['time'] = $taskTime->format('H:i');
        }

        /**
         *  Case it is a recurring task
         */
        if ($taskRawParams['schedule']['schedule-type'] == 'recurring') {
            /**
             *  Hourly
             */
            if ($taskRawParams['schedule']['schedule-frequency'] == 'hourly') {
                $nextScheduledTaskTime = date('H:00', strtotime(date('H:i') . ' + 1hour '));
                $taskTime = new DateTime($nextScheduledTaskTime);

                if ($nextScheduledTaskTime == '00:00') {
                    $taskDate = $dateNow;
                    $taskDate = $taskDate->modify('+1 day');
                } else {
                    $taskDate = $dateNow;
                }

                $schedule['date'] = $taskDate->format('Y-m-d');
                $schedule['time'] = $taskTime->format('H:i');
            }

            /**
             *  Daily
             */
            if ($taskRawParams['schedule']['schedule-frequency'] == 'daily') {
                $nextScheduledTaskTime = $taskRawParams['schedule']['schedule-time'];

                /**
                 *  If next scheduled task time is less than current time then it means that it has already been executed today, so
                 *  it will be scheduled for tomorrow
                 */
                if (str_replace(':', '', $taskRawParams['schedule']['schedule-time']) < $timeNow->format('Hi')) {
                    $nextScheduledTaskDate = new DateTime('tomorrow');
                } else {
                    $nextScheduledTaskDate = new DateTime(DATE_YMD);
                }

                $taskDate = new DateTime($nextScheduledTaskDate->format('Y-m-d'));
                $taskTime = new DateTime($nextScheduledTaskTime);

                $schedule['date'] = $taskDate->format('Y-m-d');
                $schedule['time'] = $taskTime->format('H:i');
            }

            /**
             *  Weekly
             */
            if ($taskRawParams['schedule']['schedule-frequency'] == 'weekly') {
                $nextScheduledTaskTime = $taskRawParams['schedule']['schedule-time'];

                /**
                 *  Get today day name, this will be the start day
                 */
                $dateLoop = new DateTime(DATE_YMD);

                /**
                 *  Give it 7 tries to find the next scheduled task day
                 */
                for ($i = 0; $i < 7; $i++) {
                    /**
                     *  If the current day of the loop is in the scheduled days, then break the loop because we found the next scheduled task day
                     */
                    if (in_array(strtolower($dateLoop->format('l')), $taskRawParams['schedule']['schedule-day'])) {
                        /**
                         *  If there is actually a scheduled task today, then check if the scheduled time is greater than the current time
                         */
                        if ($dateLoop->format('l') == $dateNow->format('l')) {
                            /**
                             *  If the scheduled time is greater than the current time, then the task will be executed today at the scheduled time
                             */
                            if (explode(':', $taskRawParams['schedule']['schedule-time'])[0] > $timeNow->format('H')) {
                                $nextScheduledTaskDate = new DateTime(DATE_YMD);
                            /**
                             *  If the scheduled time is less than the current time, then the task will be executed another day
                             */
                            } else {
                                /**
                                 *  If there is more than one scheduled day, then the task will be executed the next scheduled day
                                 */
                                if (count($taskRawParams['schedule']['schedule-day']) > 1) {
                                    $dateLoop = $dateLoop->modify('+1 day');
                                    continue;
                                }

                                /**
                                 *  If there is only one scheduled day and it is the same day as today, then the task will be executed next week
                                 */
                                $nextScheduledTaskDate = new DateTime(date('Y-m-d', strtotime('next ' . $dateLoop->format('l'))));
                            }
                        } else {
                            // e.g: strtotime('next monday')
                            $nextScheduledTaskDate = new Datetime(date('Y-m-d', strtotime('next ' . $dateLoop->format('l'))));
                        }

                        break;
                    }

                    $dateLoop = $dateLoop->modify('+1 day');
                }

                $taskDate = new DateTime($nextScheduledTaskDate->format('Y-m-d'));
                $taskTime = new DateTime($nextScheduledTaskTime);

                $schedule['date'] = $taskDate->format('Y-m-d');
                $schedule['time'] = $taskTime->format('H:i');
            }

            /**
             *  Monthly
             */
            if ($taskRawParams['schedule']['schedule-frequency'] == 'monthly') {
                $nextScheduledTaskTime = $taskRawParams['schedule']['schedule-time'];

                /**
                 *  Determine day position
                 *  e.g. 1st monday of the month, last friday of the month, ...
                 */

                /**
                 *  First, define a DateTime object with the current date or whatever
                 *  Then modify the date to get the first/second/third/last monday/tuesday/... of the month and retrieve the date
                 */
                $dateObject = new DateTime(DATE_YMD);
                $nextScheduledTaskDate = $dateObject->modify($taskRawParams['schedule']['schedule-monthly-day-position'] . ' ' . $taskRawParams['schedule']['schedule-monthly-day'] . ' of this month')->format('Y-m-d');

                /**
                 *  Check if the scheduled task date is in the past, or if it's today but the scheduled time has passed
                 */
                $scheduledDateTime = new DateTime($nextScheduledTaskDate . ' ' . $taskRawParams['schedule']['schedule-time']);
                $currentDateTime = new DateTime(DATE_YMD . ' ' . date('H:i:s'));

                if ($scheduledDateTime <= $currentDateTime) {
                    // Reset the date object and get the next month's occurrence
                    $dateObject = new DateTime(DATE_YMD);
                    $nextScheduledTaskDate = $dateObject->modify($taskRawParams['schedule']['schedule-monthly-day-position'] . ' ' . $taskRawParams['schedule']['schedule-monthly-day'] . ' of next month')->format('Y-m-d');
                }

                $taskDate = new DateTime($nextScheduledTaskDate);
                $taskTime = new DateTime($nextScheduledTaskTime);

                $schedule['date'] = $taskDate->format('Y-m-d');
                $schedule['time'] = $taskTime->format('H:i');
            }

            /**
             *  Cron
             */
            if ($taskRawParams['schedule']['schedule-frequency'] == 'cron') {
                try {
                    $nextOccurrence = Cron::nextOccurrence(
                        $taskRawParams['schedule']['schedule-cron'] ?? '',
                        new DateTime(date('Y-m-d H:i'))
                    );

                    if (!empty($nextOccurrence)) {
                        $taskDate = new DateTime($nextOccurrence->format('Y-m-d'));
                        $taskTime = new DateTime($nextOccurrence->format('H:i'));

                        $schedule['date'] = $taskDate->format('Y-m-d');
                        $schedule['time'] = $taskTime->format('H:i');
                    }
                } catch (Exception $e) {
                    $schedule['date'] = '';
                    $schedule['time'] = '';
                }
            }
        }

        /**
         *  Calculate number of days left
         */
        if (!isset($taskDate) || !isset($taskTime)) {
            return $schedule;
        }

        $schedule['left']['days'] = $taskDate->diff($dateNow)->days;

        /**
         *  Calculate time left
         *  If there is less than 1 hour left, then display only minutes
         *  Otherwise display hours and minutes
         */
        if ($taskTime->diff($timeNow)->h == 0) {
            $schedule['left']['time'] = $taskTime->diff($timeNow)->format('%im');
        } else {
            $schedule['left']['time'] = $taskTime->diff($timeNow)->format('%hh%im');
        }

        unset($task, $taskRawParams, $dateNow, $timeNow, $taskDate, $taskTime, $nextScheduledTaskTime, $nextScheduledTaskDate);

        return $schedule;
    }

    /**
     *  Return tasks older than a specific date
     */
    private function getOlderThan(string $date) : array
    {
        return $this->model->getOlderThan($date);
    }

    /**
     *  Clean older tasks from database
     */
    public function clean() : void
    {
        /**
         *  Get the list of tasks older than X days
         */
        $tasks = $this->getOlderThan(date('Y-m-d', strtotime('-' . TASK_CLEAN_OLDER_THAN . ' days')));

        /**
         *  Delete tasks and their logs
         */
        foreach ($tasks as $task) {
            // Old task logs were stored in a txt file
            if (!empty($task['Logfile']) and file_exists(MAIN_LOGS_DIR . '/' . $task['Logfile'])) {
                if (!unlink(MAIN_LOGS_DIR . '/' . $task['Logfile'])) {
                    throw new Exception('Could not delete task log file ' . MAIN_LOGS_DIR . '/' . $task['Logfile']);
                }
            }

            // New task logs are stored in a database file
            $files = [
                MAIN_LOGS_DIR . '/repomanager-task-' . $task['Id'] . '-log.db',
                MAIN_LOGS_DIR . '/repomanager-task-' . $task['Id'] . '-log.db-shm',
                MAIN_LOGS_DIR . '/repomanager-task-' . $task['Id'] . '-log.db-wal',
                MAIN_LOGS_DIR . '/repomanager-task-' . $task['Id'] . '-log.process'
            ];

            foreach ($files as $file) {
                if (file_exists($file)) {
                    if (!unlink($file)) {
                        throw new Exception('Could not delete task log file ' . $file);
                    }
                }
            }

            // Delete task from database
            $this->model->delete($task['Id']);
        }

        unset($tasks, $files);
    }

    /**
     *  Get latest task status for a snapshot Id
     */
    public function getLatestStatus(string $snapId) : array
    {
        return $this->model->getLatestStatus($snapId);
    }

    /**
     *  Generate a human readable literal action from the technical action name
     */
    public static function generateLiteralAction(array $task): array
    {
        // Try to decode the task parameters
        try {
            $taskRawParams = json_decode($task['Raw_params'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return [
                'title' => 'Error: could not decode task #' . $task['Id'] . ' JSON parameters: ' . $e->getMessage(),
                'icon' => 'error.svg'
            ];
        }

        // Default values
        $title = ucfirst($taskRawParams['action']);
        $icon = 'rocket.svg';

        if ($taskRawParams['action'] == 'create') {
            $repoType = $taskRawParams['repo-type'] ?? ($taskRawParams['tasks'][0]['repo-type'] ?? null);

            $title = match ($repoType) {
                'local' => 'New local repository',
                'mirror' => 'New mirror repository',
                default => 'New repository',
            };
            $icon = 'plus.svg';
        }

        if ($taskRawParams['action'] == 'update') {
            $title = 'Update repository';
            $icon = 'update.svg';
        }
        if ($taskRawParams['action'] == 'env') {
            $title = 'Point an environment';
            $icon = 'link.svg';
        }
        if ($taskRawParams['action'] == 'removeEnv') {
            $title = 'Remove an environment';
            $icon = 'delete.svg';
        }
        if ($taskRawParams['action'] == 'rebuild') {
            $title = 'Rebuild repository metadata';
            $icon = 'update.svg';
        }
        if ($taskRawParams['action'] == 'rename') {
            $title = 'Rename repository';
            $icon = 'edit.svg';
        }
        if ($taskRawParams['action'] == 'duplicate') {
            $title = 'Duplicate repository';
            $icon = 'duplicate.svg';
        }
        if ($taskRawParams['action'] == 'delete') {
            $title = 'Delete snapshot';
            $icon = 'delete.svg';
        }

        // If task is running, override icon
        if ($task['Status'] === 'running') {
            $icon = 'loading.svg';
        }

        return [
            'title' => $title,
            'icon' => $icon
        ];
    }
}
