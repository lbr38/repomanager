<?php

namespace Controllers\Group;

use Exception;
use \Controllers\History\Save as History;

class Group
{
    private $id;
    private $name;
    private $type;
    private $model;

    public function __construct(string $type)
    {
        $this->type = $type;
        $this->model = new \Models\Group\Group($type);
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     *  Return false if group does not exist
     */
    public function getIdByName(string $name)
    {
        /**
         *  Check if group exists
         */
        if ($this->exists($name) === false) {
            throw new Exception("Group <b>$name</b> does not exist");
        }

        return $this->model->getIdByName($name);
    }

    /**
     *  Retourne le nom du groupe à partir de son Id
     */
    public function getNameById(string $id)
    {
        /**
         *  On vérifie que le groupe existe
         */
        if ($this->existsId($id) === false) {
            throw new Exception("Group Id <b>$id</b> does not exist");
        }

        return $this->model->getNameById($id);
    }

    /**
     *  Retourne true si l'Id du groupe existe en base de données
     */
    public function existsId(string $groupId)
    {
        return $this->model->existsId($groupId);
    }

    /**
     *  Vérifie si le groupe existe en base de données, à partir de son nom
     */
    public function exists(string $name = '')
    {
        return $this->model->exists($name);
    }

    /**
     *  Create a new group
     */
    public function new(string $name) : void
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        $name = \Controllers\Common::validateData($name);

        /**
         *  Check that group name does not contain invalid characters
         */
        if (\Controllers\Common::isAlphanumDash($name) === false) {
            throw new Exception("Group <b>$name</b> contains invalid characters");
        }

        /**
         *  Group name cannot be 'Default'
         */
        if (strtolower($name) === 'default') {
            throw new Exception('Default is a reserved group name');
        }

        /**
         *  Check if group name already exists
         */
        if ($this->exists($name) === true) {
            throw new Exception("Group name <b>$name</b> already exists");
        }

        /**
         *  Add the new group to the database
         */
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
    public function edit(int $id, string $name, array $data) : void
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        /**
         *  Check if group exists
         */
        if (!$this->existsId($id)) {
            throw new Exception('Group does not exist');
        }

        /**
         *  Check if group name is valid
         */
        if (\Controllers\Common::isAlphanumDash($name) === false) {
            throw new Exception("Group name <b>$name</b> contains invalid characters");
        }

        /**
         *  Edit group name
         */
        $this->updateName($id, $name);

        /**
         *  Edit group data
         */

        /**
         *  If group type is 'repo'
         */
        if ($this->type == 'repo') {
            $myrepo = new \Controllers\Repo\Repo();
            $myrepo->addReposIdToGroup($data, $id);
        }

        /**
         *  If group type is 'host'
         */
        if ($this->type == 'host') {
            $myhost = new \Controllers\Host();
            $myhost->addHostsIdToGroup($data, $id);
        }

        if ($this->type == 'repo') {
            History::set('Repository group <code>' . $name . '</code> edited');
        }
        if ($this->type == 'host') {
            History::set('Host group <code>' . $name . '</code> edited');
        }
    }

    /**
     *  Delete one or more groups
     */
    public function delete(array $groups) : void
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

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
     *  Retourne les informations de tous les groupes en base de données
     *  Sauf le groupe par défaut
     */
    public function listAll($withDefault = false)
    {
        $groups = $this->model->listAll();

        /**
         *  Add default group 'Default' to the end of the list
         */
        if ($withDefault === true) {
            $groups[] = array('Id' => 0, 'Name' => 'Default');
        }

        return $groups;
    }

    /**
     *  Supprime des groupes les repos qui n'existent plus
     */
    public function cleanRepos()
    {
        $this->model->cleanRepos();
    }

    /**
     *  Update group name in database
     */
    private function updateName(int $id, string $name)
    {
        $this->model->updateName($id, $name);
    }

    /**
     *  Return the list of repos in a group
     */
    public function getReposMembers(int $id)
    {
        return $this->model->getReposMembers($id);
    }

    /**
     *  Return the list of repos not in any group
     */
    public function getReposNotMembers()
    {
        return $this->model->getReposNotMembers();
    }

    /**
     *  Return the list of hosts in a group
     */
    public function getHostsMembers(int $id)
    {
        return $this->model->getHostsMembers($id);
    }

    /**
     *  Return the list of hosts not in any group
     */
    public function getHostsNotMembers()
    {
        return $this->model->getHostsNotMembers();
    }
}
