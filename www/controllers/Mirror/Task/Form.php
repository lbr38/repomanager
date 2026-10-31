<?php

namespace Controllers\Mirror\Task;

use Controllers\Mirror\Mirror;
use Controllers\Task\Form\Param;
use Controllers\Utils\Validate;
use Controllers\History\Save as History;
use Controllers\User\Permission\Repo as RepoPermission;
use Exception;

/**
 *  Validate mirrors task forms
 */
class Form
{
    private const VALID_ACTIONS = ['create', 'update', 'env', 'removeEnv', 'delete'];

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

    private static function validateCreate(array $task): void
    {
        $mirrorController = new Mirror();
        $sourceController = new \Controllers\Repo\Source\Source();

        $name = $task['name'] ?? '';
        $packageType = $task['package-type'] ?? '';
        $source = $task['source'] ?? '';

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

        if (empty($task['arch']) or !is_array($task['arch'])) {
            throw new Exception('At least one architecture must be specified');
        }

        foreach ($task['arch'] as $arch) {
            if (!Validate::alphaNumericHyphen($arch)) {
                throw new Exception('Architecture contains invalid characters');
            }
        }

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

            foreach (array_merge($dists, $task['components'] ?? []) as $value) {
                if (!Validate::alphaNumericHyphen($value, ['.', '/']) or in_array('..', explode('/', $value))) {
                    throw new Exception('Distribution or component contains invalid characters');
                }
            }
        }

        Param\Description::check($task['description'] ?? '');

        foreach ((array) ($task['tags'] ?? []) as $tag) {
            if (!Validate::alphaNumericHyphen($tag)) {
                throw new Exception('Tag ' . htmlspecialchars($tag) . ' contains invalid characters');
            }
        }

        if (!empty($task['group']) and (!is_numeric($task['group']) or !(new \Controllers\Group\Repo())->existsId((int) $task['group']))) {
            throw new Exception('Group does not exist');
        }

        History::set('Running task: New mirror <span class="label-white">' . htmlspecialchars(Mirror::getLabel($name, $releasever)) . '</span>');
    }

    private static function validateUpdate(array $task): void
    {
        $mirrorController = new Mirror();

        if (empty($task['mirror-id']) or !is_numeric($task['mirror-id'])) {
            throw new Exception('Mirror Id is invalid');
        }

        $mirror = $mirrorController->getById((int) $task['mirror-id']);

        if (empty($mirror)) {
            throw new Exception('Mirror does not exist');
        }

        if (!RepoPermission::allowedToViewMirror((int) $mirror['Id'])) {
            throw new Exception('You are not allowed to execute this action on this mirror');
        }

        History::set('Running task: Sync mirror <span class="label-white">' . htmlspecialchars(Mirror::getLabel($mirror['Name'], $mirror['Releasever'])) . '</span>');
    }

    /**
     *  Validate env, removeEnv and delete actions
     */
    private static function validateMirrorAction(array $task): void
    {
        $mirrorController = new Mirror();
        $mirrorSnapshotController = new \Controllers\Mirror\Snapshot();

        if (empty($task['mirror-id']) or !is_numeric($task['mirror-id'])) {
            throw new Exception('Mirror Id is invalid');
        }

        $mirror = $mirrorController->getById((int) $task['mirror-id']);

        if (empty($mirror)) {
            throw new Exception('Mirror does not exist');
        }

        if (!RepoPermission::allowedToViewMirror((int) $mirror['Id'])) {
            throw new Exception('You are not allowed to execute this action on this mirror');
        }

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

        $descriptions = [
            'env'       => 'Point an environment on mirror',
            'removeEnv' => 'Remove an environment from mirror',
            'delete'    => empty($task['mirror-snap-id']) ? 'Delete mirror' : 'Delete a snapshot of mirror'
        ];

        History::set('Running task: ' . $descriptions[$task['action']] . ' <span class="label-white">' . htmlspecialchars(Mirror::getLabel($mirror['Name'], $mirror['Releasever'])) . '</span>');
    }
}
