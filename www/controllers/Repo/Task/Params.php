<?php

namespace Controllers\Repo\Task;

use Controllers\Repo\Repo;

/**
 *  Build the parameters of the repositories tasks, in the format sent by the tasks forms of the web interface
 *  Values that are not specified get the same defaults as the forms (current repository settings, global settings),
 *  so that the built parameters are always complete for the tasks validation (Controllers\Task\Form) and execution
 *  Values must already have the tasks parameters format: 'true' / 'false' strings, lists of strings...
 */
class Params
{
    // Execute the task immediately
    public const NOW = ['scheduled' => 'false'];

    /**
     *  Create a new repository
     *  Accepted values: repo-type, package-type, name, source, releasever, dist, section, arch, env, description, tags, group,
     *  gpg-check, gpg-sign, retention, retention-keep-all and advanced-params (see advancedParams())
     *  releasever (rpm), dist and section (deb) can be lists: a repository is created for each of them
     */
    public static function create(array $values, array $schedule = self::NOW): array
    {
        $packageType = $values['package-type'] ?? '';

        $params = [
            'action'             => 'create',
            'package-type'       => $packageType,
            'repo-type'          => $values['repo-type'] ?? '',
            'source'             => $values['source'] ?? '',
            // The name of a curated repository is the source repository name if not specified
            'alias'              => $values['name'] ?? '',
            'arch'               => $values['arch'] ?? self::defaultArch($packageType),
            'env'                => $values['env'] ?? [],
            'description'        => $values['description'] ?? '',
            'tags'               => $values['tags'] ?? [],
            'group'              => $values['group'] ?? '',
            'gpg-check'          => $values['gpg-check'] ?? 'true',
            'gpg-sign'           => $values['gpg-sign'] ?? self::defaultGpgSign($packageType),
            'retention'          => $values['retention'] ?? '',
            'retention-keep-all' => $values['retention-keep-all'] ?? 'false',
            'advanced-params'    => self::advancedParams($packageType, $values['advanced-params'] ?? []),
            'schedule'           => $schedule
        ];

        if ($packageType == 'rpm') {
            $params['releasever'] = (array) ($values['releasever'] ?? []);
        }

        if ($packageType == 'deb') {
            $params['dist'] = (array) ($values['dist'] ?? []);
            $params['section'] = (array) ($values['section'] ?? []);
        }

        return $params;
    }

    /**
     *  Update a repository: create a new snapshot from the source repository (curated), or from the latest snapshot (local)
     *  The repository must be loaded with its snapshot to update (its latest snapshot)
     *  Accepted values: arch, gpg-check (curated only), gpg-sign, env and advanced-params
     *  Default values are the current settings of the snapshot
     */
    public static function update(Repo $repo, array $values = [], array $schedule = self::NOW): array
    {
        $params = self::target($repo, 'update', $schedule);
        $params['arch'] = $values['arch'] ?? $repo->getArch();
        $params['gpg-sign'] = $values['gpg-sign'] ?? ($repo->getSigned() == 'true' ? 'true' : 'false');
        $params['advanced-params'] = self::advancedParams($repo->getPackageType(), $values['advanced-params'] ?? [], $repo->getAdvancedParams());

        // GPG check only applies to curated repositories
        if ($repo->getType() == 'curated') {
            $params['gpg-check'] = $values['gpg-check'] ?? 'true';
        }

        // Environments to point to the new snapshot are optional
        if (!empty($values['env'])) {
            $params['env'] = $values['env'];
        }

        return $params;
    }

    /**
     *  Point environment(s) to a snapshot
     */
    public static function env(Repo $repo, array $envs, array $schedule = self::NOW): array
    {
        $params = self::target($repo, 'env', $schedule);
        $params['env'] = $envs;

        return $params;
    }

    /**
     *  Remove an environment from a snapshot, the repository must be loaded with the environment (env Id)
     *  This task is always executed immediately
     */
    public static function removeEnv(Repo $repo): array
    {
        $params = self::target($repo, 'removeEnv', self::NOW);
        $params['env-id'] = strval($repo->getEnvId());

        return $params;
    }

    /**
     *  Delete a snapshot (the repository is deleted with its last snapshot)
     */
    public static function delete(Repo $repo, array $schedule = self::NOW): array
    {
        return self::target($repo, 'delete', $schedule);
    }

    /**
     *  Rebuild the metadata of a snapshot, GPG signature defaults to the current snapshot signature
     */
    public static function rebuild(Repo $repo, string|null $gpgSign = null, array $schedule = self::NOW): array
    {
        $params = self::target($repo, 'rebuild', $schedule);
        $params['gpg-sign'] = $gpgSign ?? ($repo->getSigned() == 'true' ? 'true' : 'false');

        return $params;
    }

    /**
     *  Duplicate a snapshot into a new repository
     *  Accepted values: name, env, description, tags and group
     *  The new repository has the same architectures and signature as the snapshot
     */
    public static function duplicate(Repo $repo, array $values, array $schedule = self::NOW): array
    {
        $params = self::target($repo, 'duplicate', $schedule);
        $params['name'] = $values['name'] ?? '';
        $params['env'] = $values['env'] ?? [];
        $params['description'] = $values['description'] ?? '';
        $params['tags'] = $values['tags'] ?? [];
        $params['group'] = $values['group'] ?? '';
        $params['arch'] = $repo->getArch();
        $params['gpg-sign'] = $repo->getSigned() == 'true' ? 'true' : 'false';

        return $params;
    }

    /**
     *  Rename a repository
     */
    public static function rename(Repo $repo, string $name, array $schedule = self::NOW): array
    {
        return [
            'action'   => 'rename',
            'repo-id'  => strval($repo->getRepoId()),
            'name'     => $name,
            'old-name' => $repo->getName(),
            'schedule' => $schedule
        ];
    }

    /**
     *  Advanced parameters of a repository: packages to include / exclude, number of versions to keep,
     *  additional metadata files to sync (rpm) and metadata custom fields (deb)
     *  Values that are not specified get the current values if any, otherwise the forms defaults
     */
    public static function advancedParams(string $packageType, array $values, array $current = []): array
    {
        $packages = $values['packages'] ?? [];
        $currentPackages = $current['packages'] ?? [];

        $params = [
            'packages' => [
                'keep-latest' => strval($packages['keep-latest'] ?? $currentPackages['keep-latest'] ?? ''),
                'include'     => array_values((array) ($packages['include'] ?? $currentPackages['include'] ?? [])),
                'exclude'     => array_values((array) ($packages['exclude'] ?? $currentPackages['exclude'] ?? []))
            ]
        ];

        // Additional metadata files to sync from the source repository, synced by default
        if ($packageType == 'rpm') {
            foreach (['comps', 'modules', 'updateinfo'] as $file) {
                $params['metadata-sync'][$file] = $values['metadata-sync'][$file] ?? $current['metadata-sync'][$file] ?? 'true';
            }
        }

        // Custom fields of the Release file, empty by default
        if ($packageType == 'deb') {
            foreach (['origin', 'label', 'description'] as $field) {
                $params['metadata-custom-fields'][$field] = $values['metadata-custom-fields'][$field] ?? $current['metadata-custom-fields'][$field] ?? '';
            }
        }

        return $params;
    }

    /**
     *  Default architectures of a new repository (settings)
     */
    public static function defaultArch(string $packageType): array
    {
        if ($packageType == 'deb') {
            return DEB_DEFAULT_ARCH;
        }

        return RPM_DEFAULT_ARCH;
    }

    /**
     *  Default GPG signature of a new repository (settings)
     */
    public static function defaultGpgSign(string $packageType): string
    {
        if ($packageType == 'deb') {
            return DEB_SIGN_REPO == 'true' ? 'true' : 'false';
        }

        return RPM_SIGN_PACKAGES == 'true' ? 'true' : 'false';
    }

    /**
     *  Parameters common to the tasks targeting a snapshot
     */
    private static function target(Repo $repo, string $action, array $schedule): array
    {
        return [
            'action'   => $action,
            'repo-id'  => strval($repo->getRepoId()),
            'snap-id'  => strval($repo->getSnapId()),
            'env-id'   => '',
            'schedule' => $schedule
        ];
    }
}
