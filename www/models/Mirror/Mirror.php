<?php

namespace Models\Mirror;

use Exception;
use Controllers\Database\Log as DbLog;

class Mirror extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Return all mirrors
     */
    public function get(): array
    {
        $data = [];

        try {
            $result = $this->db->query("SELECT * FROM mirrors ORDER BY Name ASC, Package_type ASC, CAST(Releasever AS INTEGER) ASC");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return mirror details by Id
     */
    public function getById(int $id): array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT * FROM mirrors WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        return $data;
    }

    /**
     *  Return mirror Id by package type, name and release version, or null if it does not exist
     */
    public function getId(string $packageType, string $name, string $releasever): int|null
    {
        $id = null;

        try {
            $stmt = $this->db->prepare("SELECT Id FROM mirrors WHERE Package_type = :packageType AND Name = :name AND Releasever = :releasever");
            $stmt->bindValue(':packageType', $packageType);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':releasever', $releasever);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['Id'];
        }

        return $id;
    }

    /**
     *  Add a new mirror in database
     */
    public function add(string $name, string $releasever, string $packageType, string $source, string $params, string $description): void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO mirrors ('Name', 'Releasever', 'Package_type', 'Source', 'Params', 'Description') VALUES (:name, :releasever, :packageType, :source, :params, :description)");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':releasever', $releasever);
            $stmt->bindValue(':packageType', $packageType);
            $stmt->bindValue(':source', $source);
            $stmt->bindValue(':params', $params);
            $stmt->bindValue(':description', $description);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Update mirror description
     */
    public function updateDescription(int $id, string $description): void
    {
        try {
            $stmt = $this->db->prepare("UPDATE mirrors SET Description = :description WHERE Id = :id");
            $stmt->bindValue(':description', $description);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Update mirror tags (comma-separated)
     */
    public function updateTags(int $id, string $tags): void
    {
        try {
            $stmt = $this->db->prepare("UPDATE mirrors SET Tags = :tags WHERE Id = :id");
            $stmt->bindValue(':tags', $tags);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Return the group Id of each mirror member of a group, as an array mirror Id => group Id
     */
    public function getGroupsIds(): array
    {
        $data = [];

        try {
            $result = $this->db->query("SELECT Id_mirror, Id_group FROM mirror_group_members");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[$row['Id_mirror']] = $row['Id_group'];
        }

        return $data;
    }

    /**
     *  Add a mirror to a group
     */
    public function addToGroup(int $mirrorId, int $groupId): void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO mirror_group_members ('Id_mirror', 'Id_group') VALUES (:mirrorId, :groupId)");
            $stmt->bindValue(':mirrorId', $mirrorId);
            $stmt->bindValue(':groupId', $groupId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Remove all mirrors from a group
     */
    public function removeGroupMembers(int $groupId): void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM mirror_group_members WHERE Id_group = :groupId");
            $stmt->bindValue(':groupId', $groupId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Remove a mirror from its group
     */
    public function removeFromGroup(int $mirrorId): void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM mirror_group_members WHERE Id_mirror = :mirrorId");
            $stmt->bindValue(':mirrorId', $mirrorId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Delete a mirror from database
     */
    public function delete(int $id): void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM mirrors WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }
}
