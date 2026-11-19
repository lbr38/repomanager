<?php

namespace Controllers\Api\V3;

use Controllers\Api\HttpException;

/**
 *  Convert the optional 'schedule' parameter of an API request into tasks schedule parameters
 *  Without 'schedule', the task is executed immediately
 *
 *  "schedule": {
 *      "type": "unique" | "recurring",
 *      "date": "YYYY-MM-DD", "time": "HH:MM",                         (unique)
 *      "frequency": "hourly" | "daily" | "weekly" | "monthly" | "cron",  (recurring)
 *      "time": "HH:MM",                                                 (daily, weekly, monthly)
 *      "days": ["monday", ...],                                         (weekly)
 *      "monthly_day": "monday", "monthly_day_position": "first",        (monthly)
 *      "cron": "0 22 * * *",                                            (cron)
 *      "notify_error": true, "notify_success": false,
 *      "reminders": [1, 3],                                             (days before execution)
 *      "recipients": ["admin@example.com"]
 *  }
 *  The schedule types allowed depend on the task (see config/tasks/), they are checked by the tasks validation
 */
class Schedule
{
    // Tasks schedule parameters => API parameters
    private const MAPPING = [
        'schedule-type'                 => 'type',
        'schedule-date'                 => 'date',
        'schedule-time'                 => 'time',
        'schedule-frequency'            => 'frequency',
        'schedule-day'                  => 'days',
        'schedule-monthly-day'          => 'monthly_day',
        'schedule-monthly-day-position' => 'monthly_day_position',
        'schedule-cron'                 => 'cron',
        'schedule-notify-error'         => 'notify_error',
        'schedule-notify-success'       => 'notify_success',
        'schedule-reminder'             => 'reminders',
        'schedule-recipient'            => 'recipients'
    ];

    public static function fromRequest(Request $request): array
    {
        if (!$request->has('schedule') or $request->object('schedule', []) === []) {
            return ['scheduled' => 'false'];
        }

        $schedule = $request->sub('schedule');
        $schedule->allow(array_values(self::MAPPING));

        $type = $schedule->string('type');

        if (!in_array($type, ['unique', 'recurring'], true)) {
            throw HttpException::invalidParameter('Invalid schedule type ' . $type . ' (unique or recurring)', '#/schedule/type');
        }

        return [
            'scheduled'                     => 'true',
            'schedule-type'                 => $type,
            'schedule-date'                 => $type == 'unique' ? $schedule->string('date') : '',
            'schedule-time'                 => $schedule->string('time', ''),
            'schedule-frequency'            => $type == 'recurring' ? $schedule->string('frequency') : '',
            'schedule-day'                  => $schedule->list('days', []),
            'schedule-monthly-day'          => $schedule->string('monthly_day', ''),
            'schedule-monthly-day-position' => $schedule->string('monthly_day_position', ''),
            'schedule-cron'                 => $schedule->string('cron', ''),
            'schedule-notify-error'         => $schedule->boolString('notify_error', false),
            'schedule-notify-success'       => $schedule->boolString('notify_success', false),
            'schedule-reminder'             => $schedule->list('reminders', []),
            'schedule-recipient'            => $schedule->list('recipients', [])
        ];
    }

    /**
     *  Return the schedule of a task in the API format, or null if the task is not scheduled
     */
    public static function toApi(array $schedule): array|null
    {
        $result = [];

        if (($schedule['scheduled'] ?? 'false') != 'true') {
            return null;
        }

        foreach (self::MAPPING as $taskKey => $apiKey) {
            if (!array_key_exists($taskKey, $schedule)) {
                continue;
            }

            $result[$apiKey] = in_array($apiKey, ['notify_error', 'notify_success']) ? $schedule[$taskKey] == 'true' : $schedule[$taskKey];
        }

        return $result;
    }
}
