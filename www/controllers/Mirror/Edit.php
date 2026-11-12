<?php

namespace Controllers\Mirror;

use Exception;
use Controllers\Repo\Repo;
use Controllers\Repo\Snapshot\Retention;
use Controllers\History\Save as History;
use Controllers\User\Permission\Repo as RepoPermission;
use Controllers\User\Permission\Mirror as MirrorPermission;

/**
 *  Edit the properties of a mirror: description, tags and snapshots retention
 *  Only the specified properties are edited, all of them are validated before any of them is saved
 */
class Edit
{
    /**
     *  Return the mirror to edit, if the user is allowed to edit it
     */
    public function getMirror(mixed $mirrorId): array
    {
        if (!MirrorPermission::allowedAction('edit')) {
            throw new Exception('You do not have permission to edit mirrors');
        }

        if (empty($mirrorId) or !is_numeric($mirrorId)) {
            throw new Exception('Mirror Id is invalid');
        }

        $mirror = (new Mirror())->getById((int) $mirrorId);

        if (empty($mirror) or !RepoPermission::allowedToViewMirror((int) $mirrorId)) {
            throw new Exception('You do not have permission to edit this mirror');
        }

        return $mirror;
    }

    /**
     *  Validate the properties to edit
     *  Parameters: mirror-id, and the properties to edit: description, tags (list), retention and retention-keep-all
     */
    public function validate(array $params): void
    {
        $this->getMirror($params['mirror-id'] ?? null);

        if (isset($params['description'])) {
            Repo::checkDescription($params['description']);
        }

        if (isset($params['tags'])) {
            Repo::cleanTags($params['tags']);
        }

        if (self::hasRetention($params)) {
            Retention::fromInput($params['retention'] ?? null, $params['retention-keep-all'] ?? 'false');
        }
    }

    /**
     *  Edit the mirror properties, they must have been validated first
     */
    public function edit(array $params): void
    {
        $mirrorController = new Mirror();
        $mirror = $this->getMirror($params['mirror-id'] ?? null);
        $mirrorId = (int) $mirror['Id'];

        if (isset($params['description'])) {
            $mirrorController->updateDescription($mirrorId, $params['description']);
        }

        if (isset($params['tags'])) {
            $mirrorController->updateTags($mirrorId, $params['tags']);
        }

        if (self::hasRetention($params)) {
            $mirrorController->updateRetention($mirrorId, Retention::fromInput($params['retention'] ?? null, $params['retention-keep-all'] ?? 'false'));
        }

        History::set('Editing <span class="label-white">' . htmlspecialchars(Mirror::getLabel($mirror['Name'], $mirror['Releasever'])) . '</span> mirror properties');

        // Mirrors are listed with repositories
        (new \Controllers\Layout\ContainerReload())->reload('repos/list');
    }

    /**
     *  Return true if the snapshots retention must be edited
     */
    private static function hasRetention(array $params): bool
    {
        return array_key_exists('retention', $params) or array_key_exists('retention-keep-all', $params);
    }
}
