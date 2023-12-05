<?php

namespace Models;

use Exception;

class Group extends Model
{
    public function __construct(string $type)
    {
        if ($type == 'host') {
            $this->getConnection('hosts');
        }
        if ($type == 'repo') {
            $this->getConnection('main');
        }
    }

    /**
     *  Ajoute un nouveau groupe en base de données
     */
    public function add(string $name)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO groups (Name) VALUES (:name)");
            $stmt->bindValue(':name', $name);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Delete a group
     *  Also delete group_members entries
     *  @param id
     */
    public function delete(string $id)
    {
        /**
         *  Delete all entries in group_members table for this group
         */
        try {
            $stmt = $this->db->prepare("DELETE FROM group_members WHERE Id_group = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  Delete group
         */
        try {
            $stmt = $this->db->prepare("DELETE FROM groups WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Supprime dans les groupes les repos/sections qui n'existent plus
     */
    public function cleanRepos()
    {
        $this->db->exec("DELETE FROM group_members WHERE Id_repo NOT IN (SELECT Id FROM repos)");
    }

    /**
     *  Retourne l'Id d'un groupe en base de données, à partir de son nom
     */
    public function getIdByName(string $name)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM groups WHERE Name = :name");
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['Id'];
        }

        return $id;
    }

    /**
     *  Retourne le nom du groupe à partir de son Id en base de données
     */
    public function getNameById(string $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT Name from groups WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $name = $row['Name'];
        }

        return $name;
    }

    /**
     *  LISTER LES INFORMATIONS DE TOUS LES GROUPES
     *  Sauf le groupe par défaut
     */
    public function listAll()
    {
        $result = $this->db->query("SELECT * FROM groups ORDER BY Name ASC");

        $group = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $group[] = $datas;
        }

        return $group;
    }

    /**
     *  Vérifie que l'Id du groupe existe en BDD
     *  Retourne true si existe
     *  Retourne false si n'existe pas
     */
    public function existsId(string $groupId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM groups WHERE Id=:id");
            $stmt->bindValue(':id', $groupId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Retourne true si le groupe existe en base de données
     */
    public function exists(string $name)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM groups WHERE Name=:name");
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Update group name in database
     */
    public function updateName(int $id, string $name)
    {
        try {
            $stmt = $this->db->prepare("UPDATE groups SET Name = :name WHERE Id = :id");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Return the list of repos in a group
     */
    public function getReposMembers(int $id)
    {
        $data = array();

        try {
            $stmt = $this->db->prepare("SELECT DISTINCT
            repos.Id AS repoId,
            repos.Name,
            repos.Releasever,
            repos.Dist,
            repos.Section,
            repos.Source,
            repos.Package_type
            FROM group_members 
            INNER JOIN repos
                ON repos.Id = group_members.Id_repo
            INNER JOIN repos_snap
                ON repos_snap.Id_repo = repos.Id
            WHERE repos_snap.Status = 'active' 
            AND Id_group = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return the list of repos not in any group
     */
    public function getReposNotMembers()
    {
        $data = array();

        $result = $this->db->query("SELECT DISTINCT
        repos.Id AS repoId,
        repos.Name,
        repos.Releasever,
        repos.Dist,
        repos.Section,
        repos.Source,
        repos.Package_type
        FROM repos
        INNER JOIN repos_snap
            ON repos_snap.Id_repo = repos.Id
        WHERE repos_snap.Status = 'active' AND repos.Id NOT IN (SELECT Id_repo FROM group_members)");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return the list of hosts in a group
     */
    public function getHostsMembers(int $id)
    {
        $data = array();

        try {
            $stmt = $this->db->prepare("SELECT
            hosts.Id,
            hosts.Hostname,
            hosts.Ip
            FROM hosts
            INNER JOIN group_members
                ON hosts.Id = group_members.Id_host
            INNER JOIN groups
                ON groups.Id = group_members.Id_group
            WHERE Id_group = :id
            AND hosts.Status = 'active'");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return the list of hosts not in any group
     */
    public function getHostsNotMembers()
    {
        $data = array();

        try {
            $result = $this->db->query("SELECT
            hosts.Id,
            hosts.Hostname,
            hosts.Ip
            FROM hosts
            WHERE hosts.Id NOT IN (SELECT Id_host FROM group_members)
            AND hosts.Status = 'active'");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }
}
