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
    private $gpgCheck;
    private $gpgResign;
    private $timeStart;
    private $timeEnd;

    public function __construct()
    {
        $this->model = new \Models\Task\Task();
        $this->profileController = new \Controllers\Profile();
        $this->layoutContainerStateController = new \Controllers\Layout\ContainerState();
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    public function setPid(int $pid)
    {
        $this->pid = $pid;
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

    public function getId()
    {
        return $this->id;
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
     *  Get task PID by Id
     */
    public function getPidById(int $id)
    {
        return $this->model->getPidById($id);
    }

    /**
     * Update PID in database
     * @param int $id
     * @param int $pid
     */
    public function updatePid(int $id, int $pid) : void
    {
        $this->model->updatePid($id, $pid);
    }

    /**
     *  Update logfile in database
     */
    public function updateLogfile(int $id, string $logfile) : void
    {
        $this->model->updateLogfile($id, $logfile);
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
     *  Generate a PID for the task
     */
    public function generatePid()
    {
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

        if (!empty($repoDist) and !empty($repoSection)) {
            $repo = $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection;
        }

        if (!empty($repoName) and empty($repoDist) and empty($repoSection)) {
            $repo = $repoName;
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
         *  By default the task is new and immediate
         */
        $status = 'new';
        $type   = 'immediate';

        /**
         *  If task is scheduled
         */
        if ($params['schedule']['scheduled'] == 'true') {
            $status = 'scheduled';
            $type   = 'scheduled';
        }

        /**
         *  If task is 'create' then inject the name / dist / section into the repo-id field
         */
        if ($params['action'] == 'create') {
            /**
             *  Repo name is the alias if it exists, otherwise it is the source repository name ('rpm-source' or 'deb-source' field)
             */
            if (!empty($params['alias'])) {
                $name = $params['alias'];
            } else {
                $name = $params[$params['package-type'] . '-source'];
            }

            if ($params['package-type'] == 'rpm') {
                $params['repo-id'] = $name;
            }

            if ($params['package-type'] == 'deb') {
                $params['repo-id'] = $name . '|' . $params['dist'] . '|' . $params['section'];
            }
        }

        /**
         *  Add the task in database
         */
        $taskId = $this->model->new($type, json_encode($params), $status);

        /**
         *  If the task is not scheduled (immediate), add current date and time to the database
         */
        if ($params['schedule']['scheduled'] != 'true') {
            $this->setDate($taskId, date('Y-m-d'));
            $this->setTime($taskId, date('H:i:s'));
        }

        /**
         *  If the task is scheduled, also add the schedule informations to the database
         */
        if ($params['schedule']['scheduled'] == 'true') {
            $this->setScheduleDate($taskId, $params['schedule']['schedule-date']);
            $this->setScheduleTime($taskId, $params['schedule']['schedule-time']);
            $this->setScheduleFrequency($taskId, $params['schedule']['schedule-frequency']);
            $this->setScheduleDay($taskId, implode(',', $params['schedule']['schedule-day']));
            $this->setScheduleReminder($taskId, implode(',', $params['schedule']['schedule-reminder']));
            $this->setScheduleNotifyError($taskId, $params['schedule']['schedule-notify-error']);
            $this->setScheduleNotifySuccess($taskId, $params['schedule']['schedule-notify-success']);
            $this->setScheduleRecipient($taskId, implode(',', $params['schedule']['schedule-recipient']));
        }

        return $taskId;
    }

    /**
     *  Set date in database
     */
    public function setDate(int $id, string $date) : void
    {
        $this->model->setDate($id, $date);
    }

    /**
     *  Set time in database
     */
    public function setTime(int $id, string $time) : void
    {
        $this->model->setTime($id, $time);
    }

    /**
     *  Set schedule date in database
     */
    public function setScheduleDate(int $id, string $date) : void
    {
        $this->model->setScheduleDate($id, $date);
    }

    /**
     *  Set schedule time in database
     */
    public function setScheduleTime(int $id, string $time) : void
    {
        $this->model->setScheduleTime($id, $time);
    }

    /**
     *  Set schedule frequency in database
     */
    public function setScheduleFrequency(int $id, string $frequency) : void
    {
        $this->model->setScheduleFrequency($id, $frequency);
    }

    /**
     *  Set schedule day in database
     */
    public function setScheduleDay(int $id, string $day) : void
    {
        $this->model->setScheduleDay($id, $day);
    }

    /**
     *  Set schedule reminder in database
     */
    public function setScheduleReminder(int $id, string $reminder) : void
    {
        $this->model->setScheduleReminder($id, $reminder);
    }

    /**
     *  Set schedule notify error in database
     */
    public function setScheduleNotifyError(int $id, string $notifyError) : void
    {
        $this->model->setScheduleNotifyError($id, $notifyError);
    }

    /**
     *  Set schedule notify success in database
     */
    public function setScheduleNotifySuccess(int $id, string $notifySuccess) : void
    {
        $this->model->setScheduleNotifySuccess($id, $notifySuccess);
    }

    /**
     *  Set schedule recipient in database
     */
    public function setScheduleRecipient(int $id, string $recipient) : void
    {
        $this->model->setScheduleRecipient($id, $recipient);
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
         *  Clear cache
         */
        \Controllers\App\Cache::clear();

        /**
         *  Update layout containers states
         */
        $this->layoutContainerStateController->update('header/menu');
        $this->layoutContainerStateController->update('repos/list');
        $this->layoutContainerStateController->update('tasks/list');
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
     *  End task
     */
    public function end()
    {
        /**
         *  Generate a 'completed' file in the task steps temporary directory, so that logbuilder.php stops
         */
        if (!touch(TEMP_DIR . '/' . $this->id . '/completed')) {
            throw new Exception('Could not create file ' . TEMP_DIR . '/' . $this->id . '/completed');
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
         *  Update duration
         */
        $this->updateDuration($this->id, $this->getDuration());

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
        $this->layoutContainerStateController->update('tasks/list');
        $this->layoutContainerStateController->update('browse/list');
        $this->layoutContainerStateController->update('browse/actions');

        /**
         *  Clean unused repos from profiles
         */
        $this->profileController->cleanProfiles();

        /**
         *  If task was a scheduled task, send notifications if needed
         */

        /**
         *  Get task details
         */
        $task = $this->getById($this->id);

        if ($task['Type'] == 'scheduled') {
            /**
             *  If the task has a notification on error, send it
             */
            if ($task['Schedule_notify_error'] == 'true' and $this->status == 'error') {
                $msg = 'Scheduled task #' . $this->id . ' failed:' . $this->error . '</span>';
                $mailSubject = '[ Error ] Scheduled task #' . $this->id . ' failed on ' . WWW_HOSTNAME;
                $mymail = new \Controllers\Mail($task['Schedule_recipient'], $mailSubject, $msg, 'https://' . WWW_HOSTNAME . '/tasks', 'Scheduled tasks');
            }

            /**
             *  If the task has a notification on success, send it
             */
            if ($task['Schedule_notify_success'] == 'true' and $this->status == 'success') {
                $msg = 'Scheduled task #' . $this->id . ' succeeded';
                $mailSubject = '[ Success ] Scheduled task #' . $this->id . ' succeeded on ' . WWW_HOSTNAME;
                $mymail = new \Controllers\Mail($task['Schedule_recipient'], $mailSubject, $msg, 'https://' . WWW_HOSTNAME . '/tasks', 'Scheduled tasks');
            }
        }
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
         *  Update task in database, set status to 'stopped'
         */
        $this->updateStatus($taskId, 'stopped');

        \Controllers\App\Cache::clear();

        /**
         *  Update layout containers states
         */
        $this->layoutContainerStateController->update('header/menu');
        $this->layoutContainerStateController->update('repos/list');
        $this->layoutContainerStateController->update('tasks/list');

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

    /**
     *  Generate scheduled tasks reminders
     */
    public function generateReminders(int $taskId)
    {
        $myRepo = new \Controllers\Repo\Repo();

        /**
         *  Get task details
         */
        $task = $this->getById($taskId);
        $taskRawParams = json_decode($task['Raw_params'], true);

        /**
         *  Case the scheduled task is unique
         */
        if ($taskRawParams['schedule']['schedule-type'] == 'unique') {
            $message  = 'Task #' . $taskId . ':<br>';
            $message .= 'Planned at: ' . $task['Schedule_time'] . '<br>';
        }

        /**
         *  Define action message
         */
        $message .= 'Action: ';

        /**
         *  Retrieve action and repository details
         */

        /**
         *  Case the action is 'create'
         */
        if ($taskRawParams['action'] == 'create') {
            /**
             *  If an alias is defined, use it, otherwise use the source repository name
             */
            if (!empty($taskRawParams['alias'])) {
                $repo = $taskRawParams['alias'];
            } else {
                $repo = $taskRawParams[$taskRawParams['package-type'] . '-source'];
            }

            /**
             *  If package type is deb, add dist and section
             */
            if ($taskRawParams['package-type'] == 'deb') {
                $repo .= ' ❯ ' . $taskRawParams['dist'] . ' ❯ ' . $taskRawParams['section'];
            }

            /**
             *  If package type is rpm, add releasever
             */
            if ($taskRawParams['package-type'] == 'rpm') {
                $repo .= ' (release ver. ' . $taskRawParams['releasever'] . ')';
            }

            $action = 'Create repository <span class="label-black">' . $repo . '</span>';
        }

        /**
         *  Case the action is 'update', 'env', 'removeEnv', 'duplicate' or 'rebuild'
         */
        if (in_array($taskRawParams['action'], ['update', 'duplicate', 'env', 'removeEnv', 'rebuild'])) {
            /**
             *  Retrieve repository details
             */
            $myRepo->getAllById(null, $taskRawParams['snap-id']);

            /**
             *  Define repository name
             */
            $repo = $myRepo->getName();
            if (!empty($myRepo->getDist()) and !empty($myRepo->getSection())) {
                $repo .= ' ❯ ' . $myRepo->getDist() . ' ❯ ' . $myRepo->getSection();
            }

            // Case the action is 'update'
            if ($taskRawParams['action'] == 'update') {
                $action = 'Update repository <span class="label-black">' . $repo . '</span> <span class="label-black">' . $myRepo->getDate() . '</span>';
            }

            // Case the action is 'duplicate'
            if ($taskRawParams['action'] == 'duplicate') {
                $targetRepo = $taskRawParams['name'];

                if (!empty($myRepo->getDist()) and !empty($myRepo->getSection())) {
                    $targetRepo .= ' ❯ ' . $myRepo->getDist() . ' ❯ ' . $myRepo->getSection();
                }

                $action = 'Duplicate <span class="label-black">' . $repo . '</span> <span class="label-black">' . $myRepo->getDate() . '</span> to <span class="label-black">' . $targetRepo . '</span>';
            }

            // Case the action is 'env'
            if ($taskRawParams['action'] == 'env') {
                $action = 'Point environment ' . $taskRawParams['env'] . ' to <span class="label-black">' . $repo . '</span> <span class="label-black">' . $myRepo->getDate() . '</span>';
            }

            // Case the action is 'removeEnv'
            if ($taskRawParams['action'] == 'removeEnv') {
                $action = 'Remove environment ' . $taskRawParams['env'] . ' from <span class="label-black">' . $repo . '</span> <span class="label-black">' . $myRepo->getDate() . '</span>';
            }

            // Case the action is 'rebuild'
            if ($taskRawParams['action'] == 'rebuild') {
                $action = 'Rebuild metadata of <span class="label-black">' . $repo . '</span> <span class="label-black">' . $myRepo->getDate() . '</span>';
            }
        }

        /**
         *  Complete message with action and repository
         */
        $message .= $action . ' <span class="label-black">' . $repo . '</span>';

        return $message;
    }

    /**
     *  Enable a task
     */
    public function enable(int $id)
    {
        $this->model->enable($id);
    }

    /**
     *  Disable a task
     */
    public function disable(int $id)
    {
        $this->model->disable($id);
    }

    /**
     *  Delete a task
     */
    public function delete(int $id)
    {
        $this->model->delete($id);
    }
}
