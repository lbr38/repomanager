<?php

namespace Controllers\Task;

use \Controllers\Utils\Generate\Html\Label;
use \Controllers\Mail;

use Datetime;
use Exception;
use JsonException;

class Notify extends Task
{
    private $logController;

    public function __construct()
    {
        parent::__construct();

        $this->logController = new \Controllers\Log\Log();
    }

    /**
     *  Generate task action details
     */
    private function generateAction(array $taskRawParams) : string
    {
        $action = 'Unknown action';

        // Case the action is 'create'
        if ($taskRawParams['action'] == 'create') {
            $action = 'Create new repository';
        }

        // Case the action is 'update', 'env', 'removeEnv', 'duplicate', 'rebuild' or 'delete'
        if (in_array($taskRawParams['action'], ['update', 'duplicate', 'env', 'removeEnv', 'rebuild', 'delete'])) {
            // Case the action is 'update'
            if ($taskRawParams['action'] == 'update') {
                $action = 'Update repository';
            }

            // Case the action is 'duplicate'
            if ($taskRawParams['action'] == 'duplicate') {
                $action = 'Duplicate';
            }

            // Case the action is 'env'
            if ($taskRawParams['action'] == 'env') {
                $action = 'Point environment';
            }

            // Case the action is 'removeEnv'
            if ($taskRawParams['action'] == 'removeEnv') {
                $action = 'Remove environment';
            }

            // Case the action is 'rebuild'
            if ($taskRawParams['action'] == 'rebuild') {
                $action = 'Rebuild repository metadata';
            }

            // Case the action is 'delete'
            if ($taskRawParams['action'] == 'delete') {
                $action = 'Delete repository snapshot';
            }
        }

        return $action;
    }

    /**
     *  Generate repository details for the task
     */
    private function generateRepository(array $taskRawParams) : array
    {
        $repoController = new \Controllers\Repo\Repo();

        // Case the action is 'create'
        if ($taskRawParams['action'] == 'create') {
            // If an alias is defined, use it, otherwise use the source repository name
            if (!empty($taskRawParams['alias'])) {
                $repo = $taskRawParams['alias'];
            } else {
                $repo = $taskRawParams['source'];
            }

            // Case it's deb, add dist and section
            if ($taskRawParams['package-type'] == 'deb') {
                $repo .= ' ❯ ' . $taskRawParams['dist'] . ' ❯ ' . $taskRawParams['section'];
            }

            // Case it's rpm, add releasever
            if ($taskRawParams['package-type'] == 'rpm') {
                $repo .= ' ❯ ' . $taskRawParams['releasever'];
            }

            return [
                'repository' => $repo
            ];
        }

        // Case the action is 'update', 'env', 'removeEnv', 'duplicate', 'rebuild' or 'delete'
        if (in_array($taskRawParams['action'], ['update', 'duplicate', 'env', 'removeEnv', 'rebuild', 'delete'])) {
            // Retrieve repository details
            $repoController->getAllById(null, $taskRawParams['snap-id']);

            // Define repository name
            $repo = $repoController->getName();

            // Case it's deb, add dist and section
            if ($repoController->getPackageType() == 'deb') {
                $repo .= ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
            }

            // Case it's rpm, add releasever
            if ($repoController->getPackageType() == 'rpm') {
                $repo .= ' ❯ ' . $repoController->getReleasever();
            }

            // Case the action is 'update'
            if ($taskRawParams['action'] == 'update') {
                return [
                    'repository'    => $repo,
                    'snapshot-date' => DateTime::createFromFormat('Y-m-d', $repoController->getDate())->format('d-m-Y')
                ];
            }

            // Case the action is 'duplicate'
            if ($taskRawParams['action'] == 'duplicate') {
                $targetRepo = $taskRawParams['name'];

                // Case it's deb, add dist and section
                if ($repoController->getPackageType() == 'deb') {
                    $targetRepo .= ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
                }

                // Case it's rpm, add releasever
                if ($repoController->getPackageType() == 'rpm') {
                    $targetRepo .= ' ❯ ' . $repoController->getReleasever();
                }

                return [
                    'repository'    => $repo,
                    'snapshot-date' => DateTime::createFromFormat('Y-m-d', $repoController->getDate())->format('d-m-Y'),
                    'target-repo'   => $targetRepo
                ];
            }

            // Case the action is 'env'
            if ($taskRawParams['action'] == 'env') {
                return [
                    'repository'    => $repo,
                    'snapshot-date' => DateTime::createFromFormat('Y-m-d', $repoController->getDate())->format('d-m-Y'),
                    'environment'   => $taskRawParams['env']
                ];
            }

            // Case the action is 'removeEnv'
            if ($taskRawParams['action'] == 'removeEnv') {
                return [
                    'repository'    => $repo,
                    'snapshot-date' => DateTime::createFromFormat('Y-m-d', $repoController->getDate())->format('d-m-Y'),
                    'environment'   => $taskRawParams['env']
                ];
            }

            // Case the action is 'rebuild'
            if ($taskRawParams['action'] == 'rebuild') {
                return [
                    'repository'    => $repo,
                    'snapshot-date' => DateTime::createFromFormat('Y-m-d', $repoController->getDate())->format('d-m-Y')
                ];
            }

            // Case the action is 'delete'
            if ($taskRawParams['action'] == 'delete') {
                return [
                    'repository'    => $repo,
                    'snapshot-date' => DateTime::createFromFormat('Y-m-d', $repoController->getDate())->format('d-m-Y')
                ];
            }
        }

        return [];
    }

    /**
     *  Generate and send tasks reminders
     *  https://mjml.io/
     */
    public function reminder(array $taskIds) : void
    {
        try {
            foreach ($taskIds as $taskId) {
                $this->send(
                    $taskId,
                    '📅​ Reminder: scheduled task #' . $taskId . ' on ' . WWW_HOSTNAME,
                    'scheduled'
                );
            }
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', 'Error while sending scheduled tasks reminder: ' . $e->getMessage());
        }
    }

    /**
     *  Notify task error
     */
    public function error(int $taskId, string $error) : void
    {
        $this->send($taskId, '❌​ Scheduled task #' . $taskId . ' failed on ' . WWW_HOSTNAME, 'error: ' . strip_tags($error));
    }

    /**
     *  Notify task success
     */
    public function success(int $taskId) : void
    {
        $this->send($taskId, '✅​ Scheduled task #' . $taskId . ' succeeded on ' . WWW_HOSTNAME, 'success');
    }

    /**
     *  Generate and send task message
     */
    private function send(int $taskId, string $mailSubject, string $status) : void
    {
        $btn = 'View task log';

        try {
            // Status must be either 'success', 'error' or 'scheduled'
            if (!in_array($status, ['success', 'error', 'scheduled'])) {
                throw new Exception('invalid task status ' . $status);
            }

            if ($status == 'scheduled') {
                $btn = 'Go to tasks list';
            }

            // Get task details
            $task = $this->getById($taskId);

            try {
                $taskRawParams = json_decode($task['Raw_params'], true);
            } catch (JsonException $e) {
                throw new Exception('cannot decode JSON parameters: ' . $e->getMessage());
            }

            // Task Id
            $message  = '<h1 style="margin:0px">Task #' . $task['Id'] . '</h1>';
            $message .= '<hr style="margin-top:25px; margin-bottom:20px;">';

            // Date and time

            // Case it is a done task (success or error), show date and time
            if (in_array($status, ['success', 'error'])) {
                if (!empty($task['Date']) and !empty($task['Time'])) {
                    $message .= '<p>Date: <b>' . DateTime::createFromFormat('Y-m-d', $task['Date'])->format('d-m-Y') . ' ' . $task['Time'] . '</b></p>';
                }
            }

            // Case it is a scheduled task (reminder), show scheduled date and time
            if ($status == 'scheduled') {
                if ($taskRawParams['schedule']['schedule-type'] == 'unique') {
                    $scheduleDate = $taskRawParams['schedule']['schedule-date'];
                    $scheduleTime = $taskRawParams['schedule']['schedule-time'];

                    $message .= '<p>Scheduled date: <b>' . DateTime::createFromFormat('Y-m-d', $scheduleDate)->format('d-m-Y') . ' ' . $scheduleTime . '</b></p>';
                }

                // TODO: reccuring tasks are not yet supported for reminders (see sendReminders() in ScheduledTask.php)
                if ($taskRawParams['schedule']['schedule-type'] == 'recurring') {
                    // Hourly
                    if ($taskRawParams['schedule']['schedule-frequency'] == 'hourly') {
                        $message .= '<p>Scheduled interval: <b>Every hour<b></p>';
                    }

                    // Daily
                    if ($taskRawParams['schedule']['schedule-frequency'] == 'daily') {
                        $message .= '<p>Scheduled interval: <b>Every day at ' . $taskRawParams['schedule']['schedule-time'] . '<b></p>';
                    }

                    // Weekly
                    if ($taskRawParams['schedule']['schedule-frequency'] == 'weekly') {
                        $message .= '<p>Scheduled interval: <b>Every ' . ucfirst($taskRawParams['schedule']['schedule-day']) . ' at ' . $taskRawParams['schedule']['schedule-time'] . '<b></p>';
                    }

                    // Monthly
                    if ($taskRawParams['schedule']['schedule-frequency'] == 'monthly') {
                        $message .= '<p>Scheduled interval: <b>Every month on day ' . $taskRawParams['schedule']['schedule-day'] . ' at ' . $taskRawParams['schedule']['schedule-time'] . '<b></p>';
                    }
                }
            }

            // Action
            $message .= '<p>Action: <b>' . $this->generateAction($taskRawParams) . '</b></p>';

            // Repository details
            foreach ($this->generateRepository($taskRawParams) as $key => $value) {
                if ($key == 'repository') {
                    $message .= '<p>Repository: <span class="label-transparent">' . $value . '</span></p>';
                }

                if ($key == 'snapshot-date') {
                    $message .= '<p>Snapshot date: <span class="label-black">' . $value . '</span></p>';
                }

                if ($key == 'environment') {
                    $message .= '<p>Environment: ';

                    foreach ($value as $env) {
                        $message .= Label::envtag($env) . ' ';
                    }

                    $message .= '</p>';
                }

                if ($key == 'target-repo') {
                    $message .= '<p>Target repository: <span class="label-transparent">' . $value . '</span></p>';
                }
            }

            // Duration
            if (!empty($task['Duration'])) {
                $message .= 'Total duration: <b>' . $task['Duration'] . '</b></p>';
            }

            // Status (success, error, scheduled)
            $message .= '<p>Status: <b>';

            if ($status == 'success') {
                $message .= '✅ Success';
            }
            if ($status == 'error') {
                $message .= '❌ Error';
            }
            if ($status == 'scheduled') {
                $message .= '📅 Scheduled';
            }

            $message .= '</b></p>';
            $message .= '<br>';

            // Send email
            new Mail(implode(',', $taskRawParams['schedule']['schedule-recipient']), $mailSubject, $message, __SERVER_PROTOCOL__ . '://' . WWW_HOSTNAME . '/run/' . $task['Id'], $btn);
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', 'Error while sending scheduled task #' . $taskId . ' notification: ' . $e->getMessage());
        }
    }
}
