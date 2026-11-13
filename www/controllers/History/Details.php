<?php

namespace Controllers\History;

use Exception;
use Controllers\Repo\Snapshot\Retention;

/**
 *  Build the structured details of a history entry:
 *  - changes: list of values before and after the action (['field' => label, 'before' => value, 'after' => value])
 *  - params: parameters of the action (label => value)
 */
class Details
{
    // Words kept in uppercase when a label is generated from a key
    private const ACRONYMS = ['oidc', 'sso', 'gpg', 'rpm', 'deb', 'cve', 'id', 'url', 'http', 'ip', 'api'];

    // Task parameters that are not displayed (internal Ids, or parameters already described by the action)
    private const TASK_IGNORED_PARAMS = ['action', 'domain', 'repo-id', 'snap-id', 'env-id', 'mirror-id', 'mirror-snap-id', 'repo-status', 'old-name', 'schedule', 'target', 'retention', 'retention-keep-all'];

    // Labels of the task parameters, nested parameters are separated by a dot
    private const TASK_LABELS = [
        'package-type'                                     => 'Package type',
        'repo-type'                                        => 'Repository type',
        'source'                                           => 'Source repository',
        'alias'                                            => 'Name',
        'name'                                             => 'Name',
        'releasever'                                       => 'Release version',
        'dist'                                             => 'Distributions',
        'section'                                          => 'Sections',
        'components'                                       => 'Components',
        'repos'                                            => 'Repositories',
        'arch'                                             => 'Architectures',
        'env'                                              => 'Environments',
        'description'                                      => 'Description',
        'tags'                                             => 'Tags',
        'group'                                            => 'Group',
        'gpg-check'                                        => 'Check GPG signatures',
        'gpg-sign'                                         => 'Sign with GPG',
        'advanced-params.packages.keep-latest'             => 'Keep only latest package versions',
        'advanced-params.packages.include'                 => 'Packages to include',
        'advanced-params.packages.exclude'                 => 'Packages to exclude',
        'advanced-params.metadata-sync.comps'              => 'Sync comps metadata',
        'advanced-params.metadata-sync.modules'            => 'Sync modules metadata',
        'advanced-params.metadata-sync.updateinfo'         => 'Sync updateinfo metadata',
        'advanced-params.metadata-custom-fields.origin'    => 'Metadata origin',
        'advanced-params.metadata-custom-fields.label'     => 'Metadata label',
        'advanced-params.metadata-custom-fields.description' => 'Metadata description'
    ];

    /**
     *  Return the changes between two sets of values
     *  Only the keys of $after are compared (the submitted values), unchanged values are ignored
     *  Arrays are compared without taking the order of their values into account
     *  The values of the $secrets keys are never returned, only the fact that they have been changed
     */
    public static function diff(array $before, array $after, array $labels = [], array $secrets = []): array
    {
        $changes = [];

        foreach ($after as $key => $value) {
            $oldValue = self::normalize($before[$key] ?? null);
            $newValue = self::normalize($value);

            if (self::comparable($oldValue) === self::comparable($newValue)) {
                continue;
            }

            $change = ['field' => $labels[$key] ?? self::label((string) $key)];

            if (in_array($key, $secrets, true)) {
                $change['secret'] = true;
            } else {
                $change['before'] = $oldValue;
                $change['after']  = $newValue;
            }

            $changes[] = $change;
        }

        return $changes;
    }

    /**
     *  Return a human readable label from a key, e.g. 'OIDC_CLIENT_ID' => 'OIDC client ID'
     */
    public static function label(string $key): string
    {
        $words = preg_split('/[_\-\s.]+/', strtolower($key), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $i => $word) {
            if (in_array($word, self::ACRONYMS, true)) {
                $words[$i] = strtoupper($word);
            }
        }

        return ucfirst(implode(' ', $words));
    }

    /**
     *  Return the parameters of a task, as label => value
     *  Internal parameters and empty values are ignored
     */
    public static function task(array $task): array
    {
        $params = [];

        foreach (self::flatten(array_diff_key($task, array_flip(self::TASK_IGNORED_PARAMS))) as $key => $value) {
            $value = self::normalize($value);

            if ($value === '' or $value === []) {
                continue;
            }

            // Group is submitted as an Id
            if ($key == 'group' and is_string($value) and is_numeric($value)) {
                $value = self::groupName((int) $value);
            }

            $params[self::TASK_LABELS[$key] ?? self::label($key)] = $value;
        }

        // Snapshots retention (number of snapshots to keep, or keep all of them)
        if (array_key_exists('retention', $task) or array_key_exists('retention-keep-all', $task)) {
            try {
                $params['Snapshots retention'] = Retention::describe(Retention::fromInput($task['retention'] ?? null, $task['retention-keep-all'] ?? 'false'));
            } catch (Exception $e) {
                // Invalid values are rejected by the task validation
            }
        }

        // Scheduling
        $schedule = $task['schedule'] ?? [];

        $params['Execution'] = self::schedule($schedule);

        if (self::isScheduled($task)) {
            $notify = [];

            if (($schedule['schedule-notify-error'] ?? 'false') == 'true') {
                $notify[] = 'on error';
            }

            if (($schedule['schedule-notify-success'] ?? 'false') == 'true') {
                $notify[] = 'on success';
            }

            if (!empty($notify)) {
                $params['Notifications'] = ucfirst(implode(', ', $notify));
            }

            $recipients = self::normalize($schedule['schedule-recipient'] ?? []);

            if (!empty($recipients)) {
                $params['Recipients'] = $recipients;
            }

            $reminders = self::normalize($schedule['schedule-reminder'] ?? []);

            if (!empty($reminders)) {
                $params['Reminders'] = array_map(fn ($days) => $days . ' day(s) before', (array) $reminders);
            }
        }

        return $params;
    }

    /**
     *  Return true if the task is scheduled
     */
    public static function isScheduled(array $task): bool
    {
        return ($task['schedule']['scheduled'] ?? 'false') == 'true';
    }

    /**
     *  Return a human readable description of the scheduling of a task
     */
    public static function schedule(array $schedule): string
    {
        if (($schedule['scheduled'] ?? 'false') != 'true') {
            return 'Immediate';
        }

        $time = $schedule['schedule-time'] ?? '';
        $at = !empty($time) ? ' at ' . $time : '';

        if (($schedule['schedule-type'] ?? '') == 'unique') {
            return 'Scheduled once, on ' . ($schedule['schedule-date'] ?? '') . $at;
        }

        return match ($schedule['schedule-frequency'] ?? '') {
            'hourly'  => 'Recurring, every hour',
            'daily'   => 'Recurring, every day' . $at,
            'weekly'  => 'Recurring, every week on ' . implode(', ', (array) ($schedule['schedule-day'] ?? [])) . $at,
            'monthly' => 'Recurring, every month on the ' . ($schedule['schedule-monthly-day-position'] ?? '') . ' ' . ($schedule['schedule-monthly-day'] ?? '') . $at,
            'cron'    => 'Recurring, cron expression ' . ($schedule['schedule-cron'] ?? ''),
            default   => 'Recurring'
        };
    }

    /**
     *  Flatten a nested array, nested keys are separated by a dot
     *  Lists (arrays with numeric keys) are kept as values
     */
    private static function flatten(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($value) and !array_is_list($value)) {
                $result += self::flatten($value, $prefix . $key . '.');
                continue;
            }

            $result[$prefix . $key] = $value;
        }

        return $result;
    }

    /**
     *  Normalize a value to be stored and compared: a string, or a list of strings without empty values
     */
    private static function normalize(mixed $value): string|array
    {
        if (is_array($value)) {
            $list = [];

            foreach ($value as $item) {
                if (is_array($item)) {
                    $item = json_encode($item);
                }

                if ($item === null or $item === '') {
                    continue;
                }

                $list[] = is_bool($item) ? ($item ? 'true' : 'false') : (string) $item;
            }

            return $list;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) ($value ?? '');
    }

    /**
     *  Return a value that can be compared, the order of the values of a list is ignored
     */
    private static function comparable(string|array $value): string
    {
        if (is_array($value)) {
            sort($value);
            return json_encode($value);
        }

        return $value;
    }

    /**
     *  Return the name of a repository group from its Id
     */
    private static function groupName(int $id): string
    {
        try {
            return (new \Controllers\Group\Repo())->getNameById($id);
        } catch (Exception $e) {
            return '#' . $id;
        }
    }
}
