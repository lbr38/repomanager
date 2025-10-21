<?php

namespace Models\Repo\Source;

use Exception;
use \Controllers\Database\Log as DbLog;

class Source extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Get source repository definition
     */
    public function get(string $sourceType, string $sourceName)
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT * FROM sources
            WHERE json_extract(COALESCE(Definition, '{}'), '$.type') = :type
            AND json_extract(COALESCE(Definition, '{}'), '$.name') = :name");
            $stmt->bindValue(':type', $sourceType);
            $stmt->bindValue(':name', $sourceName);
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
     *  Get source repo Id from its type and name
     */
    public function getIdByTypeName(string $type, string $name)
    {
        $id = '';

        try {
            $stmt = $this->db->prepare("SELECT Id FROM sources
            WHERE json_extract(COALESCE(Definition, '{}'), '$.type') = :type
            AND json_extract(COALESCE(Definition, '{}'), '$.name') = :name");
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':name', $name);
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
     *  Get source repo definition from its Id
     */
    public function getDefinition(string $id)
    {
        $data = '';

        try {
            $stmt = $this->db->prepare("SELECT Definition FROM sources WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row['Definition'];
        }

        return $data;
    }

    /**
     *  List all source repositories
     */
    public function listAll(string|null $type, bool $withOffset, int $offset)
    {
        $data = [];

        $query = "SELECT * FROM sources";

        /**
         *  If a source type has been specified
         */
        if (!empty($type)) {
            $query .= " WHERE json_extract(COALESCE(Definition, '{}'), '$.type') = :type";
        }

        $query .= " ORDER BY json_extract(COALESCE(Definition, '{}'), '$.type') ASC, json_extract(COALESCE(Definition, '{}'), '$.name') ASC";

        /**
         *  If offset is specified
         */
        if ($withOffset) {
            $query .= " LIMIT 10 OFFSET :offset";
        }

        /**
         *  Prepare query
         */
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
        if (!empty($type)) {
            $stmt->bindValue(':type', $type);
        }
        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Add a new source repository
     */
    public function new(string $definition, string $method) : void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO sources ('Definition', 'Method') VALUES (:definition, :method)");
            $stmt->bindValue(':definition', $definition);
            $stmt->bindValue(':method', $method);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Edit a source repository
     */
    public function edit(string $id, string $definition)
    {
        try {
            $stmt = $this->db->prepare('UPDATE sources SET Definition = :definition WHERE Id = :id');
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':definition', $definition);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Delete a source repository
     */
    public function delete(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM sources WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Check if source repo exists in database
     *  Using COALESCE to specify a default JSON value '{}' for the Definition column in case the column is empty (brand new table)
     */
    public function exists(string $type, string $name)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM sources
            WHERE json_extract(COALESCE(Definition, '{}'), '$.type') = :type
            AND json_extract(COALESCE(Definition, '{}'), '$.name') = :name");
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Check if source repo exists in database
     */
    public function existsId(string $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM sources WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Edit source repository definition params
     */
    public function editDefinition(int $id, string $definition)
    {
        try {
            $stmt = $this->db->prepare('UPDATE sources SET Definition = :definition WHERE Id = :id');
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':definition', $definition);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }
}
