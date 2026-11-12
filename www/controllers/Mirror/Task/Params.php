<?php

namespace Controllers\Mirror\Task;

/**
 *  Build the parameters of the mirrors tasks, in the format sent by the mirrors forms of the web interface
 *  Values must already have the tasks parameters format: 'true' / 'false' strings, lists of strings...
 */
class Params
{
    // Execute the task immediately
    public const NOW = ['scheduled' => 'false'];

    /**
     *  Create a new mirror
     *  Accepted values: package-type, name, source, releasever (rpm), dist and components (deb), repos (arch),
     *  arch, gpg-check, env, description, tags, group (group Id), retention and retention-keep-all
     */
    public static function create(array $values, array $schedule = self::NOW): array
    {
        $packageType = $values['package-type'] ?? '';

        $params = [
            'domain'             => 'mirror',
            'action'             => 'create',
            'package-type'       => $packageType,
            // The name of a mirror is the source repository name if not specified
            'name'               => $values['name'] ?? '',
            'source'             => $values['source'] ?? '',
            'arch'               => $values['arch'] ?? [],
            'gpg-check'          => $values['gpg-check'] ?? 'true',
            'env'                => $values['env'] ?? [],
            'description'        => $values['description'] ?? '',
            'tags'               => $values['tags'] ?? [],
            'group'              => $values['group'] ?? '',
            'retention'          => $values['retention'] ?? '',
            'retention-keep-all' => $values['retention-keep-all'] ?? 'false',
            'schedule'           => $schedule
        ];

        if ($packageType == 'rpm') {
            $params['releasever'] = $values['releasever'] ?? '';
        }

        if ($packageType == 'deb') {
            $params['dist'] = $values['dist'] ?? [];
            $params['components'] = $values['components'] ?? [];
        }

        if ($packageType == 'arch') {
            $params['repos'] = $values['repos'] ?? [];
        }

        return $params;
    }

    /**
     *  Sync a mirror into a new snapshot, optionally pointing environment(s) to it
     */
    public static function update(int $mirrorId, array $envs = [], array $schedule = self::NOW): array
    {
        $params = self::target($mirrorId, 'update', $schedule);
        $params['env'] = $envs;

        return $params;
    }

    /**
     *  Point environment(s) to a snapshot of a mirror
     */
    public static function env(int $mirrorId, int $snapId, array $envs, array $schedule = self::NOW): array
    {
        $params = self::target($mirrorId, 'env', $schedule);
        $params['mirror-snap-id'] = strval($snapId);
        $params['env'] = $envs;

        return $params;
    }

    /**
     *  Remove an environment from a mirror, this task is always executed immediately
     */
    public static function removeEnv(int $mirrorId, string $env): array
    {
        $params = self::target($mirrorId, 'removeEnv', self::NOW);
        $params['env'] = $env;

        return $params;
    }

    /**
     *  Delete a snapshot of a mirror, or the whole mirror if no snapshot is specified
     */
    public static function delete(int $mirrorId, int|null $snapId = null, array $schedule = self::NOW): array
    {
        $params = self::target($mirrorId, 'delete', $schedule);

        if ($snapId !== null) {
            $params['mirror-snap-id'] = strval($snapId);
        }

        return $params;
    }

    /**
     *  Parameters common to the tasks targeting a mirror
     */
    private static function target(int $mirrorId, string $action, array $schedule): array
    {
        return [
            'domain'    => 'mirror',
            'action'    => $action,
            'mirror-id' => strval($mirrorId),
            'schedule'  => $schedule
        ];
    }
}
