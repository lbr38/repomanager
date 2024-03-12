<?php

namespace Controllers\Task;

use Exception;

class Task
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Task\Task();
    }

    /**
     *  Get task details by Id
     */
    public function getById(int $id)
    {
        return $this->model->getById($id);
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
        $myprocess = new \Controllers\Process('/usr/bin/php ' . ROOT . '/operations/execute.php --id="' . $id . '" >/dev/null 2>/dev/null &');
        $myprocess->execute();
        $myprocess->close();
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
}
