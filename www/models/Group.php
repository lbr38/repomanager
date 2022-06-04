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
            Common::dbError($e);
        }
    }

    /**
     *  Renommer un groupe en base de données
     */
    public function rename(string $actualName, string $newName)
    {
        try {
            $stmt = $this->db->prepare("UPDATE groups SET Name = :newname WHERE Name = :actualname");
            $stmt->bindValue(':newname', $newName);
            $stmt->bindValue(':actualname', $actualName);
            $stmt->execute();
        } catch (\Exception $e) {
            Common::dbError($e);
        }
    }

    /**
     *  Supprimer un groupe en base de données
     *  Supprimer également les correspondances repo <=> groupe dans la table group_members
     *  @param name
     */
    public function delete(string $name)
    {
        /**
         *  1. Suppression de toutes les entrées concernant ce groupe dans group_members afin que les repos repassent sur le groupe par défaut
         */
        try {
            $stmt = $this->db->prepare("DELETE FROM group_members WHERE Id_group IN (SELECT Id FROM groups WHERE Name = :name)");
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            Common::dbError($e);
        }

        /**
         *  2. Suppression du groupe
         */
        try {
            $stmt = $this->db->prepare("DELETE FROM groups WHERE Name = :name");
            $stmt->bindValue(':name', $name);
            $stmt->execute();
        } catch (\Exception $e) {
            Common::dbError($e);
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
            Common::dbError($e);
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
            Common::dbError($e);
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
        $result = $this->db->query("SELECT * FROM groups");

        $group = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $group[] = $datas;
        }

        return $group;
    }

    /**
     *  LISTER TOUS LES NOMS DE GROUPES
     *  Sauf le groupe par défaut
     */
    public function listAllName()
    {
        $query = $this->db->query("SELECT * FROM groups");

        $group = array();

        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) {
            $group[] = $datas['Name'];
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
            Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        } else {
            return true;
        }
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
            Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        } else {
            return true;
        }
    }
}
