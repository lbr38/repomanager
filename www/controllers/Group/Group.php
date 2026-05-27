<?php

namespace Controllers\Group;

use Exception;
use Controllers\Repo\Repo;
use Controllers\Host\Host;
use Controllers\Utils\Validate;
use Controllers\History\Save as History;

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
     *  Create a new group
     */
    public function new(string $name): void
    {
        $name = Validate::string($name);

        // Check that the group name does not contain invalid characters
        if (!Validate::alphaNumericHyphen($name, ['.', ' '])) {
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
            History::set('Create <code>' . $name . '</code> repository group ');
        }

        if ($this->type == 'host') {
            History::set('Create <code>' . $name . '</code> host group');
        }
    }

    /**
     *  Edit a group
     */
    public function edit(int $id, string $name, array $data): void
    {
        // Check if group exists
        if (!$this->existsId($id)) {
            throw new Exception('Group does not exist');
        }

        // Check if group name is valid
        if (!Validate::alphaNumericHyphen($name, ['.', ' '])) {
            throw new Exception('Group name ' . $name . ' contains invalid characters');
        }

        // Edit group name
        $this->updateName($id, $name);

        // If group type is 'repo'
        if ($this->type == 'repo') {
            $repoController = new Repo();
            $repoController->addReposIdToGroup($data, $id);
        }

        // If group type is 'host'
        if ($this->type == 'host') {
            $hostController = new Host();
            $hostController->addHostsIdToGroup($data, $id);
        }

        if ($this->type == 'repo') {
            History::set('Repository group <code>' . $name . '</code> edited');
        }

        if ($this->type == 'host') {
            History::set('Host group <code>' . $name . '</code> edited');
        }

        unset($repoController, $hostController);
    }

    /**
     *  Delete one or more groups
     */
    public function delete(array $groups): void
    {
        foreach ($groups as $id) {
            // Check if group exists
            if (!$this->existsId($id)) {
                throw new Exception('Group with id #' . $id . ' does not exist');
            }

            // Retrieve group name, for history
            $name = $this->getNameById($id);

            // Delete group in database
            $this->model->delete($id);

            if ($this->type == 'repo') {
                History::set('Delete repository group <code>' . $name . '</code>');
            }

            if ($this->type == 'host') {
                History::set('Delete host group <code>' . $name . '</code>');
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
