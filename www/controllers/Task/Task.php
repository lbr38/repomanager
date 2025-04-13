<?php

namespace Controllers\Task;

use Exception;
use Datetime;

class Task
{
    private $id;
    private $pid;
    private $model;
    private $action;
    private $status;
    private $error;
    private $type;
    private $date;
    private $time;
    private $repoName;
    private $gpgCheck;
    private $gpgSign;
    private $timeStart;
    private $timeEnd;

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

    /**
     *  Get task details by Id
     */
    public function getById(int $id)
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
     *  List all queued tasks
     *  It is possible to filter the type of task ('immediate' or 'scheduled')
     *  It is possible to add an offset to the request
     */
    public function listQueued(string $type = '', bool $withOffset = false, int $offset = 0)
    {
        return $this->model->listQueued($type, $withOffset, $offset);
    }

    /**
     *  List all running tasks
     *  It is possible to filter the type of task ('immediate' or 'scheduled')
     *  It is possible to add an offset to the request
     */
    public function listRunning(string $type = '', bool $withOffset = false, int $offset = 0)
    {
        return $this->model->listRunning($type, $withOffset, $offset);
    }

    /**
     *  List all scheduled tasks
     *  It is possible to add an offset to the request
     */
    public function listScheduled(bool $withOffset = false, int $offset = 0)
    {
        return $this->model->listScheduled($withOffset, $offset);
    }

    /**
     *  List all done tasks (with or without errors)
     *  It is possible to filter the type of task ('immediate' or 'scheduled')
     *  It is possible to add an offset to the request
     */
    public function listDone(string $type = 'immediate', bool $withOffset = false, int $offset = 0)
    {
        return $this->model->listDone($type, $withOffset, $offset);
    }

    /**
     *  Return last done task Id
     *  Can return null if no task is found (e.g. brand new installation with no task)
     */
    public function getLastTaskId() : int|null
    {
        return $this->model->getLastTaskId();
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
     */
    public function getRepo(string $id)
    {
        $myrepo = new \Controllers\Repo\Repo();

        /**
         *  Retrieve all informations about the task from the database
         */
        $taskInfo = $this->getById($id);
        $taskRawParams = json_decode($taskInfo['Raw_params'], true);

        if (!empty($taskRawParams['source-snap-id'])) {
            if (is_numeric($taskRawParams['source-snap-id'])) {
                $myrepo->getAllById('', $taskRawParams['source-snap-id'], '');
                $repoName    = $myrepo->getName();
                $repoDist    = $myrepo->getDist();
                $repoSection = $myrepo->getSection();
            }
        } else if (!empty($taskRawParams['repo-id'])) {
            if (is_numeric($taskRawParams['repo-id'])) {
                $myrepo->getAllById($taskRawParams['repo-id'], '', '');
                $repoName    = $myrepo->getName();
                $repoDist    = $myrepo->getDist();
                $repoSection = $myrepo->getSection();
            } else {
                $repo = explode('|', $taskRawParams['repo-id']);
                $repoName = $repo[0];
                if (!empty($repo[1]) and !empty($repo[2])) {
                    $repoDist    = $repo[1];
                    $repoSection = $repo[2];
                }
            }
        } else if (!empty($taskRawParams['snap-id'])) {
            if (is_numeric($taskRawParams['snap-id'])) {
                $myrepo->getAllById('', $taskRawParams['snap-id'], '');
                $repoName    = $myrepo->getName();
                $repoDist    = $myrepo->getDist();
                $repoSection = $myrepo->getSection();
            }
        }

        if (!empty($repoName) and !empty($repoDist) and !empty($repoSection)) {
            $repo = $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection;
        } elseif (!empty($repoName)) {
            $repo = $repoName;
        } else {
            $repo = 'unknown';
        }

        unset($myrepo);

        return $repo;
    }

    /**
     *  Add a new task in database
     *  @param array $params
     */
    private function new(array $params) : int
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

        /**
         *  If task is not scheduled, overwrite the schedule parameters to clear them and only keep the 'scheduled' field
         */
        if ($params['schedule']['scheduled'] == 'false') {
            $params['schedule'] = array(
                'scheduled' => 'false'
            );
        }

        /**
         *  If task is 'create' then inject the name / dist / section into the repo-id field
         */
        if ($params['action'] == 'create') {
            /**
             *  Repo name is the alias if it exists, otherwise it is the source repository name
             */
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
        $taskId = $this->model->new($type, $paramsJson, $status);

        return $taskId;
    }

    /**
     *  Execute one or more tasks
     */
    public function execute(array $tasksParams)
    {
        /**
         *  $tasksParams can contain one or more tasks
         *  Each task is an array containing all the parameters needed to execute the task
         */
        foreach ($tasksParams as $taskParams) {
            /**
             *  If the task is a new repo, we need to loop through all the releasever (rpm) or dist/section (deb) and create a dedicated task for each of them
             */
            if ($taskParams['action'] == 'create') {
                if ($taskParams['package-type'] == 'rpm') {
                    foreach ($taskParams['releasever'] as $releasever) {
                        /**
                         *  Create a new array with the same parameters as the original array, but with only one releasever
                         */
                        $params = $taskParams;

                        /**
                         *  Replace the releasever array with a single releasever
                         */
                        $params['releasever'] = $releasever;

                        /**
                         *  Generate a new task containing all the parameters needed to execute the task retrieve its Id
                         */
                        $taskId = $this->new($params);

                        /**
                         *  Execute the task now if it is not scheduled
                         */
                        if ($params['schedule']['scheduled'] != 'true') {
                            $this->executeId($taskId);
                        }
                    }
                }

                if ($taskParams['package-type'] == 'deb') {
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
                             *  Generate a new task containing all the parameters needed to execute the task retrieve its Id
                             */
                            $taskId = $this->new($params);

                            /**
                             *  Execute the task now if it is not scheduled
                             */
                            if ($params['schedule']['scheduled'] != 'true') {
                                $this->executeId($taskId);
                            }
                        }
                    }
                }

            /**
             *  Every other task can be executed directly
             */
            } else {
                /**
                 *  Generate a new task containing all the parameters needed to execute the task retrieve its Id
                 */
                $taskId = $this->new($taskParams);

                /**
                 *  Execute the task now if it is not scheduled
                 */
                if ($taskParams['schedule']['scheduled'] != 'true') {
                    $this->executeId($taskId);
                }
            }
        }
    }

    /**
     *  Execute a task in background from its task Id
     */
    public function executeId(int $id)
    {
        $myprocess = new \Controllers\Process('/usr/bin/php ' . ROOT . '/tasks/execute.php --id="' . $id . '" >/dev/null 2>/dev/null &');
        $myprocess->execute();
        $myprocess->close();
    }

    /**
     *  Return true if task exists in database
     */
    public function exists(int $id) : bool
    {
        return $this->model->exists($id);
    }

    /**
     *  Start task
     */
    public function start()
    {
        /**
         *  Generate time start
         */
        $this->timeStart = microtime(true);

        /**
         *  Set status as 'running' in database
         */
        $this->updateStatus($this->id, 'running');

        /**
         *  Update layout containers states
         */
        $this->layoutContainerReloadController->reload('header/menu');
        $this->layoutContainerReloadController->reload('repos/list');
        $this->layoutContainerReloadController->reload('tasks/list');
        $this->layoutContainerReloadController->reload('browse/list');
        $this->layoutContainerReloadController->reload('browse/actions');

        /**
         *  Add current PHP execution PID to the PID file to make sure it can be killed with the stop button
         */
        $this->addsubpid(getmypid());
    }

    /**
     *  End task
     */
    public function end()
    {
        /**
         *  Get task details
         */
        $task = $this->getById($this->id);
        $taskRawParams = json_decode($task['Raw_params'], true);

        /**
         *  Delete pid file
         */
        if (file_exists(PID_DIR . '/' . $this->id . '.pid')) {
            if (!unlink(PID_DIR . '/' . $this->id . '.pid')) {
                throw new Exception('Could not delete PID file ' . PID_DIR . '/' . $this->id . '.pid');
            }
        }

        /**
         *  Update duration
         */
        $this->updateDuration($this->id, $this->getDuration());

        /**
         *  If task was a scheduled task
         */
        if ($task['Type'] == 'scheduled') {
            $myTaskNotify = new \Controllers\Task\Notify();

            /**
             *  Send notifications if needed
             */

            /**
             *  If the task has a notification on error, send it
             */
            if ($taskRawParams['schedule']['schedule-notify-error'] == 'true' and $this->status == 'error') {
                $myTaskNotify->error($task, $this->error);
            }

            /**
             *  If the task has a notification on success, send it
             */
            if ($taskRawParams['schedule']['schedule-notify-success'] == 'true' and $this->status == 'done') {
                $myTaskNotify->success($task);
            }

            /**
             *  If it is a recurring task, duplicate the task in database and reschedule it
             */
            if ($taskRawParams['schedule']['schedule-type'] == 'recurring') {
                $newTaskId = $this->duplicate($this->id);

                /**
                 *  Reset real execution date and time
                 */
                $this->updateDate($newTaskId, '');
                $this->updateTime($newTaskId, '');
                $this->updateStatus($newTaskId, 'scheduled');
            }

            unset($myTaskNotify);
        }

        /**
         *  Clean unused repos from profiles
         */
        $this->profileController->cleanProfiles();

        /**
         *  Update layout containers states
         */
        $this->layoutContainerReloadController->reload('header/menu');
        $this->layoutContainerReloadController->reload('repos/list');
        $this->layoutContainerReloadController->reload('repos/properties');
        $this->layoutContainerReloadController->reload('tasks/list');
        $this->layoutContainerReloadController->reload('browse/list');
        $this->layoutContainerReloadController->reload('browse/actions');
    }

    /**
     *  Relaunch a task
     */
    public function relaunch(int $id)
    {
        if (!IS_ADMIN) {
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
    }

    /**
     *  Duplicate a task in database from its Id and return the new task Id
     */
    private function duplicate(int $id) : int
    {
        return $this->model->duplicate($id);
    }

    /**
     *  Stop a task based on the specified PID
     */
    public function kill(string $taskId)
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to stop a task');
        }

        if (file_exists(PID_DIR . '/' . $taskId . '.pid')) {
            /**
             *  Getting PID file content
             */
            $content = file_get_contents(PID_DIR . '/' . $taskId . '.pid');

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
            if (!unlink(PID_DIR . '/' . $taskId . '.pid')) {
                throw new Exception('Error while deleting PID file');
            }
        }

        /**
         *  Update task in database, set status to 'stopped'
         */
        $this->updateStatus($taskId, 'stopped');

        $taskLogStepController = new \Controllers\Task\Log\Step($taskId);
        $taskLogSubStepController = new \Controllers\Task\Log\SubStep($taskId);

        /**
         *  Set latest step and substep as stopped
         */
        $taskLogStepController->stopped();
        $taskLogSubStepController->stopped();

        /**
         *  Update layout containers states
         */
        $this->layoutContainerReloadController->reload('header/menu');
        $this->layoutContainerReloadController->reload('repos/list');
        $this->layoutContainerReloadController->reload('tasks/list');

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
        if (!file_put_contents(PID_DIR . '/' . $this->id . '.pid', 'SUBPID="' . $pid . '"' . PHP_EOL, FILE_APPEND)) {
            throw new Exception('Could not add sub PID to ' . PID_DIR . '/' . $this->id . '.pid file');
        }

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
                    if (!file_put_contents(PID_DIR . '/' . $this->id . '.pid', 'SUBPID="' . $childPid . '"' . PHP_EOL, FILE_APPEND)) {
                        throw new Exception('Could not add sub PID to ' . PID_DIR . '/' . $this->id . '.pid file');
                    }
                }

                /**
                 *  If the child PID has children PID, then add them too
                 */
                $grandChildrenPid = $this->getChildrenPid($childPid);

                if ($grandChildrenPid !== false) {
                    foreach ($grandChildrenPid as $grandChildPid) {
                        if (is_numeric($grandChildPid)) {
                            if (!file_put_contents(PID_DIR . '/' . $this->id . '.pid', 'SUBPID="' . $grandChildPid . '"' . PHP_EOL, FILE_APPEND)) {
                                throw new Exception('Could not add sub PID to ' . PID_DIR . '/' . $this->id . '.pid file');
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
     *  Enable a recurrent task
     */
    public function enable(int $id)
    {
        $this->model->enable($id);
    }

    /**
     *  Disable a recurrent task
     */
    public function disable(int $id) : void
    {
        $this->model->disable($id);
    }

    /**
     *  Delete a task
     */
    public function delete(int $id) : void
    {
        $this->model->delete($id);
    }

    /**
     *  Return an array with the day and time left before the task is executed
     */
    public function getDayTimeLeft(int $taskId) : array
    {
        $dateNow = new DateTime(DATE_YMD);
        $timeNow = new DateTime(date('H:i'));

        $schedule = array(
            'date' => '',
            'time' => '',
            'left' => array(
                'days' => '',
                'time' => ''
            ),
        );

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
                 *  But if the scheduled task date is today and the scheduled time is less than the current time, then the task will be executed next month
                 *  Retrieve the next scheduled task date and time
                 */
                if ($nextScheduledTaskDate == DATE_YMD and str_replace(':', '', $taskRawParams['schedule']['schedule-time']) < $timeNow->format('Hi')) {
                    $nextScheduledTaskDate = $dateObject->modify($taskRawParams['schedule']['schedule-monthly-day-position'] . ' ' . $taskRawParams['schedule']['schedule-monthly-day'] . ' of next month')->format('Y-m-d');
                }

                $taskDate = new DateTime($nextScheduledTaskDate);
                $taskTime = new DateTime($nextScheduledTaskTime);

                $schedule['date'] = $taskDate->format('Y-m-d');
                $schedule['time'] = $taskTime->format('H:i');
            }
        }

        /**
         *  Calculate number of days left
         */
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

        unset($task, $taskRawParams, $dateNow, $timeNow, $taskDate, $taskTime, $nextScheduledTaskTime, $nextScheduledTaskDate, $daysLeft, $timeLeft);

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
        try {
            $logController = new \Controllers\Log\Log();

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
                    MAIN_LOGS_DIR . '/repomanager-task-' . $task['Id'] . '-log.db-wal'
                ];

                foreach ($files as $file) {
                    if (file_exists($file)) {
                        if (!unlink($file)) {
                            throw new Exception('Could not delete task log file ' . $file);
                        }
                    }
                }

                // Delete task from database
                $this->delete($task['Id']);
            }
        } catch (Exception $e) {
            $logController->log('error', 'Service', 'Error while cleaning old tasks: ' . $e->getMessage());
        }

        unset($logController, $tasks, $files);
    }
}
