<?php

namespace Controllers\Task;

use Controllers\Utils\Generate\Html\Label;
use Controllers\Mail;

use DateTime;
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

        // Case the action is 'update', 'env', 'removeEnv', 'duplicate', 'rebuild', 'rename' or 'delete'
        if (in_array($taskRawParams['action'], ['update', 'duplicate', 'env', 'removeEnv', 'rebuild', 'rename', 'delete'])) {
            // Retrieve repository details
            $repoController->getAllById(null, $taskRawParams['snap-id']);

            // Case it's a deb repository
            if ($repoController->getPackageType() == 'deb') {
                $repo = $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
            }

            // Case it's rpm repository
            if ($repoController->getPackageType() == 'rpm') {
                $repo = $repoController->getName() . ' ❯ ' . $repoController->getReleasever();
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

            // Case the action is 'rename'
            if ($taskRawParams['action'] == 'rename') {
                // Case it's a deb repository
                if ($repoController->getPackageType() == 'deb') {
                    $repo = $taskRawParams['old-name'] . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
                    $targetRepo = $taskRawParams['name'] . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
                }

                // Case it's rpm repository
                if ($repoController->getPackageType() == 'rpm') {
                    $repo = $taskRawParams['old-name'] . ' ❯ ' . $repoController->getReleasever();
                    $targetRepo = $taskRawParams['name'] . ' ❯ ' . $repoController->getReleasever();
                }

                return [
                    'repository'    => $repo,
                    'target-repo'   => $targetRepo
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
     *  Send a single reminder summarizing all the upcoming tasks of each recipient
     */
    public function reminder(array $taskIds) : void
    {
        $tasksByRecipient = [];

        foreach ($taskIds as $taskId) {
            try {
                $task = $this->getById($taskId);
                $taskRawParams = json_decode($task['Raw_params'], true, 512, JSON_THROW_ON_ERROR);

                $details = [
                    'taskId' => $task['Id'],
                    'rows'   => $this->details($task, $taskRawParams, true)
                ];
            } catch (Exception $e) {
                $this->logController->log('error', 'Service', 'Error while preparing scheduled task #' . $taskId . ' reminder: ' . $e->getMessage());
                continue;
            }

            foreach ($taskRawParams['schedule']['schedule-recipient'] as $recipient) {
                $tasksByRecipient[$recipient][] = $details;
            }
        }

        foreach ($tasksByRecipient as $recipient => $tasks) {
            $count = count($tasks);

            try {
                new Mail(
                    $recipient,
                    '📅​ Reminder: ' . $count . ' upcoming scheduled task' . ($count > 1 ? 's' : '') . ' on ' . WWW_HOSTNAME,
                    Mail::render('reminder', ['tasks' => $tasks]),
                    __SERVER_PROTOCOL__ . '://' . WWW_HOSTNAME . '/tasks',
                    'Go to tasks list'
                );
            } catch (Exception $e) {
                $this->logController->log('error', 'Service', 'Error while sending scheduled tasks reminder to ' . $recipient . ': ' . $e->getMessage());
            }
        }
    }

    /**
     *  Notify the result of a scheduled task, from the summary of the sub-tasks it dispatched
     */
    public function result(int $taskId, array $summary): void
    {
        try {
            $task = $this->getById($taskId);
            $taskRawParams = json_decode($task['Raw_params'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->logController->log('error', 'Service', 'Error while sending scheduled task #' . $taskId . ' notification: cannot decode JSON parameters: ' . $e->getMessage());

            return;
        }

        $schedule = $taskRawParams['schedule'] ?? [];

        // Case at least one sub-task has failed
        if ($summary['status'] == 'error') {
            if (($schedule['schedule-notify-error'] ?? 'false') == 'true') {
                $this->send($taskId, '❌​ Scheduled task #' . $taskId . ' failed on ' . WWW_HOSTNAME, 'error', $summary);
            }

            return;
        }

        // Case all sub-tasks have succeeded
        if (($schedule['schedule-notify-success'] ?? 'false') == 'true') {
            $this->send($taskId, '✅​ Scheduled task #' . $taskId . ' succeeded on ' . WWW_HOSTNAME, 'success', $summary);
        }
    }

    /**
     *  Generate and send task result message
     */
    private function send(int $taskId, string $mailSubject, string $status, array $summary) : void
    {
        try {
            $task = $this->getById($taskId);
            $taskRawParams = json_decode($task['Raw_params'], true, 512, JSON_THROW_ON_ERROR);

            $message = Mail::render('task', [
                'taskId'   => $task['Id'],
                'status'   => $status,
                'rows'     => $this->details($task, $taskRawParams, false),
                'summary'  => $summary,
                'duration' => $task['Duration'] ?? ''
            ]);

            new Mail(implode(',', $taskRawParams['schedule']['schedule-recipient']), $mailSubject, $message, __SERVER_PROTOCOL__ . '://' . WWW_HOSTNAME . '/run/' . $task['Id'], 'View task log');
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', 'Error while sending scheduled task #' . $taskId . ' notification: ' . $e->getMessage());
        }
    }

    /**
     *  Generate task details rows, as label => HTML value
     */
    private function details(array $task, array $taskRawParams, bool $upcoming) : array
    {
        $rows = [
            'Action' => self::generateLiteralAction($task)['title']
        ];

        if ($upcoming) {
            $schedule = $taskRawParams['schedule'];

            if ($schedule['schedule-type'] == 'recurring') {
                $rows['Frequency'] = match ($schedule['schedule-frequency']) {
                    'hourly'  => 'Every hour',
                    'daily'   => 'Every day at ' . $schedule['schedule-time'],
                    'weekly'  => 'Every ' . implode(', ', array_map('ucfirst', $schedule['schedule-day'])) . ' at ' . $schedule['schedule-time'],
                    'monthly' => 'Every ' . $schedule['schedule-monthly-day-position'] . ' ' . ucfirst($schedule['schedule-monthly-day']) . ' of the month at ' . $schedule['schedule-time'],
                    'cron'    => 'Cron ' . htmlspecialchars($schedule['schedule-cron'] ?? '', ENT_QUOTES, 'UTF-8'),
                    default   => 'Unknown'
                };
            }

            $next = $this->getDayTimeLeft($task['Id']);

            if (!empty($next['date'])) {
                $rows['Next run'] = DateTime::createFromFormat('Y-m-d', $next['date'])->format('d-m-Y') . ' ' . $next['time'];
            }
        } elseif (!empty($task['Date']) and !empty($task['Time'])) {
            $rows['Started'] = DateTime::createFromFormat('Y-m-d', $task['Date'])->format('d-m-Y') . ' ' . $task['Time'];
        }

        /**
         *  A task targeting several repositories or a dynamic set of them has no repository of
         *  its own, only its sub-tasks have one
         */
        if (Target::isDynamic($taskRawParams)) {
            $rows['Target'] = Target::describe($taskRawParams['target']);
        } elseif (empty($taskRawParams['tasks'])) {
            foreach ($this->generateRepository($taskRawParams) as $key => $value) {
                if ($key == 'repository') {
                    $rows['Repository'] = Label::white(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), true);
                }

                if ($key == 'snapshot-date') {
                    $rows['Snapshot date'] = $value;
                }

                if ($key == 'environment') {
                    $rows['Environment'] = implode(' ', array_map([Label::class, 'envtag'], $value));
                }

                if ($key == 'target-repo') {
                    $rows['Target repository'] = Label::white(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), true);
                }
            }
        }

        return $rows;
    }
}
