<?php

namespace Controllers\Api\V3\Repository\Type;

use Controllers\Api\HttpException;
use Controllers\Api\V3\Request;
use Controllers\Repo\Snapshot\Retention;

/**
 *  A repository addressed by the API: /repositories/{type}/{id}
 *  Curated and local repositories (Repo) share the same tables and Ids, mirrors (Mirror) have their own
 *  Both expose the same operations to the API handlers, with the same output format
 */
abstract class Type
{
    // Parameters accepted to edit any type of repository
    protected const EDIT_KEYS = ['description', 'tags', 'retention', 'retention_keep_all'];

    /**
     *  Return the repository of the specified type and Id
     *  Throw a 404 error if it does not exist or if the user is not allowed to view it
     */
    public static function load(string $type, int $id): self
    {
        if ($type == 'mirror') {
            return new Mirror($id);
        }

        return new Repo($type, $id);
    }

    /**
     *  Return the repository type (curated, local or mirror)
     */
    abstract public function getType(): string;

    /**
     *  Return the repository Id
     */
    abstract public function getId(): int;

    /**
     *  Return the repository details
     */
    abstract public function format(): array;

    /**
     *  Return the active snapshots of the repository, most recent first
     */
    abstract public function formatSnapshots(): array;

    /**
     *  Return a snapshot of the repository, throw a 404 error if it is not an active snapshot of the repository
     */
    abstract public function formatSnapshot(int $snapId): array;

    /**
     *  Validate and edit the repository properties from the request (synchronous)
     */
    abstract public function edit(Request $request): void;

    /**
     *  Return the tasks parameters to delete the repository (all its snapshots)
     */
    abstract public function deleteParams(array $schedule): array;

    /**
     *  Return the task parameters to create a new snapshot (sync from the source repository, or copy of the latest snapshot for local repositories)
     */
    abstract public function newSnapshotParams(Request $request, array $schedule): array;

    /**
     *  Return the task parameters to delete a snapshot
     */
    abstract public function deleteSnapshotParams(int $snapId, array $schedule): array;

    /**
     *  Return the task parameters to point environment(s) to a snapshot
     */
    abstract public function envParams(int $snapId, array $envs, array $schedule): array;

    /**
     *  Return the task parameters to remove an environment from a snapshot
     *  Throw a 404 error if the environment does not point to the snapshot
     */
    abstract public function removeEnvParams(int $snapId, string $env): array;

    /**
     *  Return the label of the repository for messages, e.g. 'curated repository #12'
     */
    public function getLabel(): string
    {
        return ($this->getType() == 'mirror' ? 'mirror' : $this->getType() . ' repository') . ' #' . $this->getId();
    }

    /**
     *  Return the snapshots retention: number of unused snapshots to keep (null: global setting), or keep all
     */
    protected static function formatRetention(mixed $retention): array
    {
        if ($retention === null or $retention === '') {
            return ['retention' => null, 'retention_keep_all' => false];
        }

        if ((int) $retention == Retention::KEEP_ALL) {
            return ['retention' => null, 'retention_keep_all' => true];
        }

        return ['retention' => (int) $retention, 'retention_keep_all' => false];
    }

    /**
     *  Return the tags list from the comma-separated tags stored in database
     */
    protected static function formatTags(string|null $tags): array
    {
        return array_values(array_filter(explode(',', $tags ?? ''), function ($tag) {
            return $tag !== '';
        }));
    }

    /**
     *  Return the edit parameters common to all types of repositories (description, tags, retention), in the format of the edit forms
     */
    protected static function editParams(Request $request): array
    {
        $params = [];

        if ($request->has('description')) {
            $params['description'] = $request->string('description', '');
        }

        if ($request->has('tags')) {
            $params['tags'] = $request->list('tags', []);
        }

        return array_merge($params, self::retentionParams($request));
    }

    /**
     *  Return the snapshots retention parameters from the request, in the format of the forms, if specified
     */
    protected static function retentionParams(Request $request): array
    {
        if (!$request->hasAny(['retention', 'retention_keep_all'])) {
            return [];
        }

        // A null or missing retention means the global setting
        $retention = $request->has('retention') ? $request->int('retention', null, true) : null;

        return [
            'retention'          => $retention === null ? '' : strval($retention),
            'retention-keep-all' => $request->boolString('retention_keep_all', false)
        ];
    }

    /**
     *  Return the group Id of a group name, for mirrors (repositories groups are specified by name)
     */
    protected static function groupId(string $name): string
    {
        if ($name === '') {
            return '';
        }

        $groupController = new \Controllers\Group\Repo();

        if (!$groupController->exists($name)) {
            throw HttpException::invalidParameter('Group ' . $name . ' does not exist', '#/group');
        }

        return strval($groupController->getIdByName($name));
    }

    /**
     *  Return the names of the repositories groups, as an array group Id => group name
     */
    protected static function groupNames(): array
    {
        $names = [];

        foreach ((new \Controllers\Group\Repo())->listAll() as $group) {
            $names[$group['Id']] = $group['Name'];
        }

        return $names;
    }

    /**
     *  Throw a 400 error if the request has nothing to edit
     */
    protected static function checkNotEmpty(array $params): void
    {
        if (empty($params)) {
            throw new HttpException(400, 'Nothing to edit, specify at least one parameter');
        }
    }
}
