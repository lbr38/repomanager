<?php

namespace Controllers\Repo\Edit;

use Exception;
use Controllers\History\Save as History;
use Controllers\History\Details;
use Controllers\Repo\Repo;
use Controllers\Repo\Snapshot\Retention;
use Controllers\User\Permission\Repo as RepoPermission;

/**
 *  Edit the properties of a repository: description, tags, source repository (curated repositories) and snapshots retention
 *  Only the specified properties are edited, all of them are validated before any of them is saved
 */
class Form
{
    /**
     *  Return the repository to edit, if the user is allowed to edit it
     *  Repository properties are common to all its snapshots, the latest snapshot is only used to retrieve the repository type
     */
    public function getRepo(mixed $repoId): Repo
    {
        $repoController = new Repo();

        if (!RepoPermission::allowedAction('edit')) {
            throw new Exception('You do not have permission to edit repositories');
        }

        if (empty($repoId) or !is_numeric($repoId) or !$repoController->existsId((string) $repoId)) {
            throw new Exception('Repository Id is invalid');
        }

        if (!RepoPermission::allowedToView((int) $repoId)) {
            throw new Exception('You do not have permission to edit this repository');
        }

        $snapId = $repoController->getLatestSnapId((int) $repoId);

        if (empty($snapId)) {
            throw new Exception('Repository has no active snapshot');
        }

        $repoController->getAllById($repoId, $snapId);

        return $repoController;
    }

    /**
     *  Validate the properties to edit
     *  Parameters: repo-id, and the properties to edit: description, tags (list), source, retention and retention-keep-all
     */
    public function validate(array $params): void
    {
        $repoController = $this->getRepo($params['repo-id'] ?? null);

        if (isset($params['description'])) {
            Repo::checkDescription($params['description']);
        }

        if (isset($params['tags'])) {
            Repo::cleanTags($params['tags']);
        }

        if (isset($params['source'])) {
            if ($repoController->getType() != 'curated') {
                throw new Exception('Only curated repositories have a source repository');
            }

            \Controllers\Task\Form\Param\Source::check($params['source'], $repoController->getPackageType());
        }

        if (self::hasRetention($params)) {
            Retention::fromInput($params['retention'] ?? null, $params['retention-keep-all'] ?? 'false');
        }
    }

    /**
     *  Edit the repository properties, they must have been validated first
     */
    public function edit(array $params): void
    {
        $repoController = $this->getRepo($params['repo-id'] ?? null);
        $repoId = $repoController->getRepoId();

        // Values before and after the edition, for history
        $before = [
            'description' => $repoController->getDescription(),
            'tags'        => $repoController->getTags(),
            'source'      => $repoController->getSource(),
            'retention'   => Retention::describe($repoController->getRetention())
        ];
        $after = [];

        if (isset($params['description'])) {
            $after['description'] = $params['description'];
        }

        if (isset($params['tags'])) {
            $after['tags'] = Repo::cleanTags($params['tags']);
        }

        if (isset($params['source'])) {
            $after['source'] = $params['source'];
        }

        if (self::hasRetention($params)) {
            $after['retention'] = Retention::describe(Retention::fromInput($params['retention'] ?? null, $params['retention-keep-all'] ?? 'false'));
        }

        if (isset($params['description'])) {
            $repoController->updateDescription($repoId, $params['description']);
        }

        if (isset($params['tags'])) {
            $repoController->updateTags($repoId, $params['tags']);
        }

        if (isset($params['source'])) {
            $repoController->updateSource($repoId, $params['source']);
        }

        if (self::hasRetention($params)) {
            $repoController->updateRetention($repoId, Retention::fromInput($params['retention'] ?? null, $params['retention-keep-all'] ?? 'false'));
        }

        // Add history
        History::set(
            'Editing <span class="label-white">' . htmlspecialchars($repoController->getLabel()) . '</span> repository properties (' . $repoController->getType() . ')',
            category: 'repository',
            changes: Details::diff($before, $after, ['source' => 'Source repository', 'retention' => 'Snapshots retention'])
        );

        // Reload repositories list
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
