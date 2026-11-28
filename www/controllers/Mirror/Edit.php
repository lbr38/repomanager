<?php

namespace Controllers\Mirror;

use Exception;
use Controllers\Repo\Repo;
use Controllers\Repo\Snapshot\Retention;
use Controllers\History\Save as History;
use Controllers\History\Details;
use Controllers\User\Permission\Repo as RepoPermission;
use Controllers\User\Permission\Mirror as MirrorPermission;

/**
 *  Edit the properties of a mirror: description, tags, snapshots retention, and the content of an openbsd mirror (versions, contents and
 *  architectures, e.g. to add the next OpenBSD version under the same URL for sysupgrade)
 *  Only the specified properties are edited, all of them are validated before any of them is saved
 *  The content is used by the next syncs: existing snapshots are not modified
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
     *  Parameters: mirror-id, and the properties to edit: description, tags (list), retention and retention-keep-all,
     *  versions, content and arch (lists, openbsd mirrors only)
     */
    public function validate(array $params): void
    {
        $mirror = $this->getMirror($params['mirror-id'] ?? null);

        if (self::hasContent($params)) {
            self::getContent($mirror, $params);
        }

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

        // Values before and after the edition, for history
        $mirrorParams = Mirror::getParams($mirror);
        $before = [
            'description' => $mirror['Description'] ?? '',
            'tags'        => array_filter(explode(',', $mirror['Tags'] ?? '')),
            'retention'   => Retention::describe(isset($mirror['Retention']) ? (int) $mirror['Retention'] : null),
            'versions'    => $mirrorParams['versions'] ?? [],
            'content'     => $mirrorParams['content'] ?? [],
            'arch'        => $mirrorParams['arch'] ?? []
        ];
        $after = [];

        if (self::hasContent($params)) {
            $content = self::getContent($mirror, $params);
            $after = array_merge($after, $content);
        }

        if (isset($params['description'])) {
            $after['description'] = $params['description'];
        }

        if (isset($params['tags'])) {
            $after['tags'] = Repo::cleanTags($params['tags']);
        }

        if (self::hasRetention($params)) {
            $after['retention'] = Retention::describe(Retention::fromInput($params['retention'] ?? null, $params['retention-keep-all'] ?? 'false'));
        }

        if (isset($params['description'])) {
            $mirrorController->updateDescription($mirrorId, $params['description']);
        }

        if (isset($params['tags'])) {
            $mirrorController->updateTags($mirrorId, $params['tags']);
        }

        if (self::hasRetention($params)) {
            $mirrorController->updateRetention($mirrorId, Retention::fromInput($params['retention'] ?? null, $params['retention-keep-all'] ?? 'false'));
        }

        if (isset($content)) {
            $mirrorController->updateParams($mirrorId, array_merge($mirrorParams, $content));
        }

        History::set(
            'Editing <span class="label-white">' . htmlspecialchars(Mirror::getLabel($mirror['Name'], $mirror['Releasever'])) . '</span> mirror properties',
            category: 'mirror',
            changes: Details::diff($before, $after, ['retention' => 'Snapshots retention', 'versions' => 'Versions', 'content' => 'Content', 'arch' => 'Architectures'])
        );

        // Mirrors are listed with repositories
        (new \Controllers\Layout\ContainerReload())->reload('repos/list');
    }

    /**
     *  Return true if the content of the mirror must be edited (versions, contents or architectures)
     */
    private static function hasContent(array $params): bool
    {
        return array_key_exists('versions', $params) or array_key_exists('content', $params) or array_key_exists('arch', $params);
    }

    /**
     *  Return the checked and normalized content of an openbsd mirror: the specified versions, contents and architectures, the current ones otherwise
     *  The content of the other mirrors cannot be edited: their tree depends on it (e.g. rpm release version, alpine branch)
     */
    private static function getContent(array $mirror, array $params): array
    {
        if ($mirror['Package_type'] != 'openbsd') {
            throw new Exception('Only the versions, content and architectures of an openbsd mirror can be edited');
        }

        $current = Mirror::getParams($mirror);

        [$versions, $contents] = Mirror::normalizeOpenbsdParams($params['versions'] ?? $current['versions'] ?? null, $params['content'] ?? $current['content'] ?? null);

        $archs = $params['arch'] ?? $current['arch'] ?? null;

        Mirror::checkArchs($archs);

        return ['versions' => $versions, 'content' => $contents, 'arch' => array_values(array_unique($archs))];
    }

    /**
     *  Return true if the snapshots retention must be edited
     */
    private static function hasRetention(array $params): bool
    {
        return array_key_exists('retention', $params) or array_key_exists('retention-keep-all', $params);
    }
}
