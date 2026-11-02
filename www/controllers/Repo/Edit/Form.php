<?php

namespace Controllers\Repo\Edit;

use Exception;
use Controllers\History\Save as History;
use Controllers\Repo\Repo;
use Controllers\Repo\Snapshot\Retention;
use Controllers\User\Permission\Repo as RepoPermission;

class Form
{
    /**
     *  Return the repository to edit
     *  Repository properties are common to all its snapshots, the latest snapshot is only used to retrieve the repository type
     */
    public function getRepo(mixed $repoId): Repo
    {
        $repoController = new Repo();

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
     *  Validate the edit form filled by the user
     */
    public function validate(array $params): void
    {
        $repoController = $this->getRepo($params['repo-id'] ?? null);

        // Check that the selected source repository is valid
        if ($repoController->getType() == 'curated') {
            \Controllers\Task\Form\Param\Source::check($params['source'] ?? '', $repoController->getPackageType());
        }

        // Check that the snapshots retention is valid
        Retention::fromInput($params['retention'] ?? null, $params['retention-keep-all'] ?? 'false');
    }

    /**
     *  Edit the repository
     */
    public function edit(array $params): void
    {
        $repoController = $this->getRepo($params['repo-id']);
        $repoId = $repoController->getRepoId();

        // Update repository source
        if ($repoController->getType() == 'curated') {
            $repoController->updateSource($repoId, $params['source']);
        }

        // Update snapshots retention
        $repoController->updateRetention($repoId, Retention::fromInput($params['retention'] ?? null, $params['retention-keep-all'] ?? 'false'));

        // Add history
        if ($repoController->getPackageType() == 'rpm') {
            History::set('Editing <span class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getReleasever() . '</span> repository properties (' . $repoController->getType() . ')');
        }
        if ($repoController->getPackageType() == 'deb') {
            History::set('Editing <span class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection() . '</span> repository properties (' . $repoController->getType() . ')');
        }

        // Reload repositories list
        (new \Controllers\Layout\ContainerReload())->reload('repos/list');
    }
}
