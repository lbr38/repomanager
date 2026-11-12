<?php

namespace Controllers\Group;

use Exception;
use Controllers\Repo\Repo;
use Controllers\Host\Host;
use Controllers\Utils\Validate;
use Controllers\History\Save as History;
use Controllers\History\Details;
use Controllers\User\Permission\Repo as RepoPermission;
use Controllers\User\Permission\Host as HostPermission;

class Group
{
    private $type;
    private $model;

    public function __construct(string $type)
    {
        $this->type = $type;
        $this->model = new \Models\Group\Group($type);
    }

    /**
     *  Return false if group does not exist
     */
    public function getIdByName(string $name): int
    {
        // Check if group exists
        if (!$this->exists($name)) {
            throw new Exception('Group $name does not exist');
        }

        return $this->model->getIdByName($name);
    }

    /**
     *  Return the group's name from its ID
     */
    public function getNameById(int $id): string
    {
        // Check if group exists
        if (!$this->existsId($id)) {
            throw new Exception('Group Id $id does not exist');
        }

        return $this->model->getNameById($id);
    }

    /**
     *  Return true if the group's ID exists in the database
     */
    public function existsId(int $groupId): bool
    {
        return $this->model->existsId($groupId);
    }

    /**
     *  Check if the group exists in the database by its name
     */
    public function exists(string $name): bool
    {
        return $this->model->exists($name);
    }

    /**
     *  Throw an exception if the user is not allowed to create, edit or delete groups of this type
     */
    private function checkEditPermission(): void
    {
        if ($this->type == 'repo' and RepoPermission::allowedAction('edit-groups')) {
            return;
        }

        if ($this->type == 'host' and HostPermission::allowedAction('edit-groups')) {
            return;
        }

        throw new Exception('You are not allowed to perform this action');
    }

    /**
     *  Create a new group
     */
    public function new(string $name): void
    {
        $this->checkEditPermission();

        $name = Validate::string($name);

        // Check that the group name does not contain invalid characters
        if (!Validate::alphaNumericHyphen($name, ['.', ' ', '(', ')', '+'])) {
            throw new Exception('Group ' . $name . ' contains invalid characters');
        }

        // Group name cannot be 'Default'
        if (strtolower($name) === 'default') {
            throw new Exception('Default is a reserved group name');
        }

        // Check if group name already exists
        if ($this->exists($name) === true) {
            throw new Exception('Group name ' . $name . ' already exists');
        }

        // Add the new group to the database
        $this->model->add($name);

        if ($this->type == 'repo') {
            History::set('Create <code>' . $name . '</code> repository group', category: 'group');
        }

        if ($this->type == 'host') {
            History::set('Create <code>' . $name . '</code> host group', category: 'group');
        }
    }

    /**
     *  Edit a group
     */
    public function edit(int $id, string $name, array $data): void
    {
        $this->checkEditPermission();

        // Check if group exists
        if (!$this->existsId($id)) {
            throw new Exception('Group does not exist');
        }

        // Check if group name is valid
        if (!Validate::alphaNumericHyphen($name, ['.', ' '])) {
            throw new Exception('Group name ' . $name . ' contains invalid characters');
        }

        // Current name and members, for history
        $before = ['name' => $this->getNameById($id), 'members' => $this->membersLabels($id)];

        // Edit group name
        $this->updateName($id, $name);

        // If group type is 'repo', members can be repositories (Id) or mirrors ('mirror-<Id>')
        if ($this->type == 'repo') {
            $reposId = [];
            $mirrorsId = [];

            foreach ($data as $memberId) {
                if (str_starts_with($memberId, 'mirror-')) {
                    $mirrorsId[] = substr($memberId, strlen('mirror-'));
                    continue;
                }

                $reposId[] = $memberId;
            }

            $repoController = new Repo();
            $repoController->addReposIdToGroup($id, $reposId);

            $mirrorController = new \Controllers\Mirror\Mirror();
            $mirrorController->setGroupMembers($id, $mirrorsId);
        }

        // If group type is 'host'
        if ($this->type == 'host') {
            $hostController = new Host();
            $hostController->addHostsIdToGroup($id, $data);
        }

        // Add history, with the changes of name and members
        $changes = Details::diff($before, ['name' => $name, 'members' => $this->membersLabels($id)], [
            'name'    => 'Name',
            'members' => $this->type == 'repo' ? 'Repositories' : 'Hosts'
        ]);

        if ($this->type == 'repo') {
            History::set('Repository group <code>' . $name . '</code> edited', category: 'group', changes: $changes);
        }

        if ($this->type == 'host') {
            History::set('Host group <code>' . $name . '</code> edited', category: 'group', changes: $changes);
        }

        unset($repoController, $hostController, $mirrorController);
    }

    /**
     *  Return the labels of the members of a group (repositories and mirrors, or hosts), for history
     */
    private function membersLabels(int $id): array
    {
        $labels = [];

        if ($this->type == 'host') {
            foreach ($this->getHostsMembers($id) as $host) {
                $labels[] = $host['Hostname'];
            }

            return $labels;
        }

        foreach ($this->getReposMembers($id) as $repo) {
            if ($repo['Package_type'] == 'deb') {
                $labels[] = $repo['Name'] . ' ❯ ' . $repo['Dist'] . ' ❯ ' . $repo['Section'];
            } else {
                $labels[] = $repo['Name'] . ' ❯ ' . $repo['Releasever'];
            }
        }

        $mirrorController = new \Controllers\Mirror\Mirror();

        foreach ($mirrorController->getGroupsIds() as $mirrorId => $groupId) {
            if ($groupId != $id) {
                continue;
            }

            $mirror = $mirrorController->getById((int) $mirrorId);

            if (!empty($mirror)) {
                $labels[] = 'mirror ' . \Controllers\Mirror\Mirror::getLabel($mirror['Name'], $mirror['Releasever'] ?? '');
            }
        }

        return $labels;
    }

    /**
     *  Delete one or more groups
     */
    public function delete(array $groups): void
    {
        $this->checkEditPermission();

        foreach ($groups as $id) {
            // Check if group exists
            if (!$this->existsId($id)) {
                throw new Exception('Group with id #' . $id . ' does not exist');
            }

            // Retrieve group name, for history
            $name = $this->getNameById($id);

            // Delete group in database
            $this->model->delete($id);

            // Mirrors members of the group go back to the 'Default' group
            if ($this->type == 'repo') {
                (new \Controllers\Mirror\Mirror())->removeGroupMembers($id);
            }

            if ($this->type == 'repo') {
                History::set('Delete repository group <code>' . $name . '</code>', category: 'group');
            }

            if ($this->type == 'host') {
                History::set('Delete host group <code>' . $name . '</code>', category: 'group');
            }
        }
    }

    /**
     *  Return information for all groups in the database
     *  Except the default group
     */
    public function listAll($withDefault = false): array
    {
        $groups = $this->model->listAll();

        // Add default group 'Default' to the end of the list
        if ($withDefault === true) {
            $groups[] = [
                'Id' => 0,
                'Name' => 'Default'
            ];
        }

        return $groups;
    }

    /**
     *  Remove repositories that no longer exist from groups
     */
    public function cleanRepos(): void
    {
        $this->model->cleanRepos();
    }

    /**
     *  Update group name in database
     */
    private function updateName(int $id, string $name): void
    {
        $this->model->updateName($id, $name);
    }

    /**
     *  Return the list of repos in a group
     */
    public function getReposMembers(int $id): array
    {
        return $this->model->getReposMembers($id);
    }

    /**
     *  Return the list of group Ids the specified repo is member of
     */
    public function getRepoGroupsIds(int $repoId): array
    {
        return $this->model->getRepoGroupsIds($repoId);
    }

    /**
     *  Return the group Ids of all the repos member of a group, as an array repo Id => list of group Ids
     */
    public function getReposGroupsIds(): array
    {
        return $this->model->getReposGroupsIds();
    }

    /**
     *  Return the list of repos not in any group
     */
    public function getReposNotMembers(): array
    {
        return $this->model->getReposNotMembers();
    }

    /**
     *  Return the list of hosts in a group
     */
    public function getHostsMembers(int $id): array
    {
        return $this->model->getHostsMembers($id);
    }

    /**
     *  Return the list of hosts not in any group
     */
    public function getHostsNotMembers(): array
    {
        return $this->model->getHostsNotMembers();
    }
}
