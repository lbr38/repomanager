<?php

namespace Controllers\Task;

use Exception;
use Datetime;

class Notify
{
    /**
     *  Generate task action details
     */
    public function generateAction(array $taskRawParams)
    {
        $myRepo = new \Controllers\Repo\Repo();
        $action = '';

        /**
         *  Generate action and repository details
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
                $repo = $taskRawParams['source'];
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

            $action = '<b>Create new repository</b> <span class="label-transparent">' . $repo . '</span>';
        }

        /**
         *  Case the action is 'update', 'env', 'removeEnv', 'duplicate', 'rebuild' or 'delete'
         */
        if (in_array($taskRawParams['action'], ['update', 'duplicate', 'env', 'removeEnv', 'rebuild', 'delete'])) {
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
                $action = '<b>Update repository</b> <span class="label-transparent">' . $repo . '</span> <span class="label-black">' . DateTime::createFromFormat('Y-m-d', $myRepo->getDate())->format('d-m-Y') . '</span>';
            }

            // Case the action is 'duplicate'
            if ($taskRawParams['action'] == 'duplicate') {
                $targetRepo = $taskRawParams['name'];

                if (!empty($myRepo->getDist()) and !empty($myRepo->getSection())) {
                    $targetRepo .= ' ❯ ' . $myRepo->getDist() . ' ❯ ' . $myRepo->getSection();
                }

                $action = '<b>Duplicate</b> <span class="label-transparent">' . $repo . '</span> <span class="label-black">' . DateTime::createFromFormat('Y-m-d', $myRepo->getDate())->format('d-m-Y') . '</span> <b>to</b> <span class="label-transparent">' . $targetRepo . '</span>';
            }

            // Case the action is 'env'
            if ($taskRawParams['action'] == 'env') {
                $action = '<b>Point environment</b> <span class="label-transparent">' . $taskRawParams['env'] . '</span> <b>to</b> <span class="label-transparent">' . $repo . '</span> <span class="label-black">' . DateTime::createFromFormat('Y-m-d', $myRepo->getDate())->format('d-m-Y') . '</span>';
            }

            // Case the action is 'removeEnv'
            if ($taskRawParams['action'] == 'removeEnv') {
                $action = '<b>Remove environment</b> ' . $taskRawParams['env'] . ' <b>from repository</b> <span class="label-transparent">' . $repo . '</span> <span class="label-black">' . DateTime::createFromFormat('Y-m-d', $myRepo->getDate())->format('d-m-Y') . '</span>';
            }

            // Case the action is 'rebuild'
            if ($taskRawParams['action'] == 'rebuild') {
                $action = '<b>Rebuild metadata of repository</b> <span class="label-transparent">' . $repo . '</span> <span class="label-black">' . DateTime::createFromFormat('Y-m-d', $myRepo->getDate())->format('d-m-Y') . '</span>';
            }

            // Case the action is 'delete'
            if ($taskRawParams['action'] == 'delete') {
                $action = '<b>Delete repository snapshot</b> <span class="label-transparent">' . $repo . '</span> <span class="label-black">' . DateTime::createFromFormat('Y-m-d', $myRepo->getDate())->format('d-m-Y') . '</span>';
            }
        }

        return $action;
    }

    /**
     *  Generate and send tasks reminders
     *  https://mjml.io/
     */
    public function reminder(array $taskIds)
    {
        $myTask = new \Controllers\Task\Task();

        try {
            foreach ($taskIds as $taskId) {
                /**
                 *  Get task details
                 */
                $task = $myTask->getById($taskId);
                $taskRawParams = json_decode($task['Raw_params'], true);

                $message  = '<p>Task <b>#' . $taskId . '</b></p>';
                $message .= '<p>Scheduled on <b>' . DateTime::createFromFormat('Y-m-d', $taskRawParams['schedule']['schedule-date'])->format('d-m-Y') . ' ' . $taskRawParams['schedule']['schedule-time'] . '</b></p>';
                $message .= '<p>Action: ' . $this->generateAction($taskRawParams) . '</p>';
                $message .= '<br>';

                /**
                 *  Send email
                 */
                $mailSubject = '[ Reminder ] Scheduled task #' . $taskId . ' to come on ' . WWW_HOSTNAME;
                $mymail = new \Controllers\Mail(implode(',', $taskRawParams['schedule']['schedule-recipient']), $mailSubject, $message, 'https://' . WWW_HOSTNAME . '/run', 'Tasks');
            }
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', 'Error while sending scheduled tasks reminder: ' . $e->getMessage());
        }
    }

    /**
     *  Notify task error
     */
    public function error(array $task, string $error)
    {
        $taskRawParams = json_decode($task['Raw_params'], true);

        $message  = '<p>Scheduled task <b>#' . $task['Id'] . '</b> failed</p>';
        $message .= '<p>Executed on: <b>' . DateTime::createFromFormat('Y-m-d', $task['Date'])->format('d-m-Y') . ' ' . $task['Time'] . '</b></p>';
        $message .= '<p>Action: ' . $this->generateAction($taskRawParams) . '</p>';
        $message .= '<p>Error: ' . $error . '</p>';
        $message .= '<br>';

        $mailSubject = '[ ERROR ] Scheduled task #' . $task['Id'] . ' failed on ' . WWW_HOSTNAME;
        $mymail = new \Controllers\Mail(implode(',', $taskRawParams['schedule']['schedule-recipient']), $mailSubject, $message, __SERVER_PROTOCOL__ . '://' . WWW_HOSTNAME . '/run/' . $task['Id'], 'View log file');
    }

    /**
     *  Notify task success
     */
    public function success(array $task)
    {
        $taskRawParams = json_decode($task['Raw_params'], true);

        $message  = '<p>Scheduled task <b>#' . $task['Id'] . '</b> succeeded</p>';
        $message .= '<p>Executed on: <b>' . DateTime::createFromFormat('Y-m-d', $task['Date'])->format('d-m-Y') . ' ' . $task['Time'] . '</b></p>';
        $message .= '<p>Action: ' . $this->generateAction($taskRawParams) . '</p>';
        $message .= '<br>';

        $mailSubject = '[ SUCCESS ] Scheduled task #' . $task['Id'] . ' succeeded on ' . WWW_HOSTNAME;
        $mymail = new \Controllers\Mail(implode(',', $taskRawParams['schedule']['schedule-recipient']), $mailSubject, $message, __SERVER_PROTOCOL__ . '://' . WWW_HOSTNAME . '/run/' . $task['Id'], 'View log file');
    }
}
