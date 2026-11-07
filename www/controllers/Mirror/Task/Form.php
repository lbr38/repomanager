<?php

namespace Controllers\Mirror\Task;

use Controllers\Mirror\Mirror;
use Controllers\Repo\Snapshot\Retention;
use Controllers\Task\Form\Param;
use Controllers\Utils\Validate;
use Controllers\Utils\Generate\Html\Label;
use Controllers\History\Save as History;
use Controllers\User\Permission\Repo as RepoPermission;
use Exception;

/**
 *  Validate mirrors task forms
 */
class Form
{
    private const VALID_ACTIONS = ['create', 'update', 'env', 'removeEnv', 'delete'];

    /**
     *  Validate the parameters of a mirror task before it is executed
     */
    public static function validate(array $task): void
    {
        if (!in_array($task['action'], self::VALID_ACTIONS)) {
            throw new Exception('Invalid action: ' . htmlspecialchars($task['action']));
        }

        // Mirrors use the same permissions as repositories
        if (!RepoPermission::allowedAction($task['action'])) {
            throw new Exception('You are not allowed to execute this action');
        }

        Param\Schedule::check($task['schedule'] ?? []);

        if (!empty($task['env']) and is_array($task['env'])) {
            Param\Environment::check($task['env']);
        }

        if ($task['action'] == 'create') {
            self::validateCreate($task);
        }

        if ($task['action'] == 'update') {
            self::validateUpdate($task);
        }

        if (in_array($task['action'], ['env', 'removeEnv', 'delete'])) {
            self::validateMirrorAction($task);
        }
    }

    /**
     *  Validate the creation of a mirror
     */
    private static function validateCreate(array $task): void
    {
        $mirrorController = new Mirror();
        $sourceController = new \Controllers\Repo\Source\Source();

        $packageType = $task['package-type'] ?? '';
        $source = $task['source'] ?? '';

        // If no name has been given, the source repository name is used
        $name = !empty($task['name']) ? $task['name'] : $source;

        Mirror::checkName($name);

        if (!in_array($packageType, Mirror::PACKAGE_TYPES)) {
            throw new Exception('Unsupported package type');
        }

        // rpm mirrors are unique by name and release version, deb mirrors by name
        $releasever = '';

        if ($packageType == 'rpm') {
            Mirror::checkReleasever($task['releasever'] ?? '');
            $releasever = $task['releasever'];
        }

        $mirrorController->checkAvailable($name, $releasever, $packageType);

        if (empty($source) or !$sourceController->exists($packageType, $source)) {
            throw new Exception('Source repository must be specified');
        }

        Mirror::checkArchs($task['arch'] ?? null);

        if (!in_array($task['gpg-check'] ?? '', ['true', 'false'])) {
            throw new Exception('GPG signature check must be enabled or disabled');
        }

        if ($packageType == 'deb') {
            $definition = $sourceController->get('deb', $source)['Definition'] ?? '';
            $nonCompliant = (json_decode($definition, true)['non-compliant'] ?? 'false') == 'true';

            // Distribution(s) of the mirror, flat repositories have no distribution nor component
            $dists = array_values(array_filter((array) ($task['dist'] ?? [])));

            if (!$nonCompliant) {
                if (empty($dists)) {
                    throw new Exception('At least one distribution must be specified');
                }

                if (empty($task['components']) or !is_array($task['components'])) {
                    throw new Exception('At least one component must be specified');
                }
            }

            Mirror::checkDebValues(array_merge($dists, (array) ($task['components'] ?? [])));
        }

        if ($packageType == 'arch') {
            Mirror::checkArchRepos(array_values(array_filter((array) ($task['repos'] ?? []))));
        }

        Param\Description::check($task['description'] ?? '');

        foreach ((array) ($task['tags'] ?? []) as $tag) {
            if (!Validate::alphaNumericHyphen($tag)) {
                throw new Exception('Tag ' . htmlspecialchars($tag) . ' contains invalid characters');
            }
        }

        Retention::fromInput($task['retention'] ?? null, $task['retention-keep-all'] ?? 'false');

        if (!empty($task['group']) and (!is_numeric($task['group']) or !(new \Controllers\Group\Repo())->existsId((int) $task['group']))) {
            throw new Exception('Group does not exist');
        }

        History::set('Running task: New mirror <span class="label-white">' . htmlspecialchars(Mirror::getLabel($name, $releasever)) . '</span>');
    }

    /**
     *  Validate the sync of a mirror
     */
    private static function validateUpdate(array $task): void
    {
        $mirror = self::getAllowedMirror($task);

        History::set('Running task: Sync mirror <span class="label-white">' . htmlspecialchars(Mirror::getLabel($mirror['Name'], $mirror['Releasever'])) . '</span>');
    }

    /**
     *  Return the mirror targeted by a task, if the user is allowed to execute actions on it
     */
    private static function getAllowedMirror(array $task): array
    {
        if (empty($task['mirror-id']) or !is_numeric($task['mirror-id'])) {
            throw new Exception('Mirror Id is invalid');
        }

        $mirror = (new Mirror())->getById((int) $task['mirror-id']);

        if (empty($mirror)) {
            throw new Exception('Mirror does not exist');
        }

        if (!RepoPermission::allowedToViewMirror((int) $mirror['Id'])) {
            throw new Exception('You are not allowed to execute this action on this mirror');
        }

        return $mirror;
    }

    /**
     *  Validate env, removeEnv and delete actions
     */
    private static function validateMirrorAction(array $task): void
    {
        $mirrorSnapshotController = new \Controllers\Mirror\Snapshot();
        $mirror = self::getAllowedMirror($task);

        // Snapshot is required to point an environment, optional to delete (the whole mirror is deleted)
        if ($task['action'] == 'env' or !empty($task['mirror-snap-id'])) {
            if (empty($task['mirror-snap-id']) or !is_numeric($task['mirror-snap-id'])) {
                throw new Exception('Snapshot Id is invalid');
            }

            $snapshot = $mirrorSnapshotController->getById((int) $task['mirror-snap-id']);

            if (empty($snapshot) or $snapshot['Id_mirror'] != $mirror['Id'] or $snapshot['Status'] != 'active') {
                throw new Exception('Snapshot does not exist');
            }
        }

        if ($task['action'] == 'env' and (empty($task['env']) or !is_array($task['env']))) {
            throw new Exception('Environment must be specified');
        }

        if ($task['action'] == 'removeEnv' and (empty($task['env']) or !is_string($task['env']))) {
            throw new Exception('Environment must be specified');
        }

        // A snapshot used by a protected environment cannot be deleted, neither can the whole mirror
        if ($task['action'] == 'delete') {
            $mirrorEnvController = new \Controllers\Mirror\Environment();

            $label = Mirror::getLabel($mirror['Name'], $mirror['Releasever']);

            if (!empty($task['mirror-snap-id'])) {
                $envs = $mirrorEnvController->getBySnapId((int) $task['mirror-snap-id']);
                $target = 'Snapshot ' . Label::white(htmlspecialchars($label) . ' ⸺ ' . Mirror::getSnapshotLabel(Mirror::getSnapshotDirName($snapshot['Date'], $snapshot['Time'])));
            } else {
                $envs = array_keys($mirrorEnvController->getByMirrorId((int) $mirror['Id']));
                $target = 'Mirror ' . Label::white(htmlspecialchars($label));
            }

            $protectedEnvs = array_intersect($envs, (new \Controllers\Environment())->getProtected());

            if (!empty($protectedEnvs)) {
                throw new Exception($target . ' is used by ' . \Controllers\Environment::nameList($protectedEnvs, 'protected') . ' and cannot be deleted');
            }
        }

        $descriptions = [
            'env'       => 'Point an environment on mirror',
            'removeEnv' => 'Remove an environment from mirror',
            'delete'    => empty($task['mirror-snap-id']) ? 'Delete mirror' : 'Delete a snapshot of mirror'
        ];

        History::set('Running task: ' . $descriptions[$task['action']] . ' <span class="label-white">' . htmlspecialchars(Mirror::getLabel($mirror['Name'], $mirror['Releasever'])) . '</span>');
    }
}
