<?php

namespace Models\Repo\Source;

use Exception;
use Controllers\Database\Log as DbLog;

class Source extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Get source repository
     */
    public function get(string $sourceType, string $sourceName): array
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
    public function getIdByTypeName(string $type, string $name): int
    {
        $id = null;

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
    public function getDefinition(string $id): string
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
     *  SQL condition matching the source repositories whose name, URL or description contains the searched text (case insensitive)
     */
    private const SEARCH_CONDITION = "(json_extract(COALESCE(Definition, '{}'), '$.name') LIKE :search ESCAPE '\\'"
        . " OR json_extract(COALESCE(Definition, '{}'), '$.url') LIKE :search ESCAPE '\\'"
        . " OR json_extract(COALESCE(Definition, '{}'), '$.description') LIKE :search ESCAPE '\\')";

    /**
     *  List all source repositories
     *  Optionally only the ones of a type, and the ones matching a search (see SEARCH_CONDITION)
     */
    public function listAll(string|null $type, bool $withOffset, int $offset, string $search = ''): array
    {
        $data = [];
        $conditions = [];

        $query = "SELECT * FROM sources";

        // If a source type has been specified
        if (!empty($type)) {
            $conditions[] = "json_extract(COALESCE(Definition, '{}'), '$.type') = :type";
        }

        // If a search has been specified
        if ($search !== '') {
            $conditions[] = self::SEARCH_CONDITION;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        $query .= " ORDER BY json_extract(COALESCE(Definition, '{}'), '$.type') ASC, json_extract(COALESCE(Definition, '{}'), '$.name') ASC";

        // If offset is specified
        if ($withOffset) {
            $query .= " LIMIT 10 OFFSET :offset";
        }

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
        if (!empty($type)) {
            $stmt->bindValue(':type', $type);
        }
        if ($search !== '') {
            $stmt->bindValue(':search', '%' . addcslashes($search, '%_\\') . '%');
        }
        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Count source repositories by type, optionally only the ones matching a search (see SEARCH_CONDITION)
     */
    public function countByType(string $search = ''): array
    {
        $data = [];

        $query = "SELECT json_extract(COALESCE(Definition, '{}'), '$.type') AS Type, COUNT(*) AS Count FROM sources";

        if ($search !== '') {
            $query .= " WHERE " . self::SEARCH_CONDITION;
        }

        $query .= " GROUP BY Type";

        $stmt = $this->db->prepare($query);
        if ($search !== '') {
            $stmt->bindValue(':search', '%' . addcslashes($search, '%_\\') . '%');
        }
        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!empty($row['Type'])) {
                $data[$row['Type']] = $row['Count'];
            }
        }

        return $data;
    }

    /**
     *  Add a new source repository
     */
    public function new(string $definition, string $method): void
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
    public function edit(string $id, string $definition): void
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
    public function delete(int $id): void
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
    public function exists(string $type, string $name): bool
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
    public function existsId(string $id): bool
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
    public function editDefinition(int $id, string $definition): void
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
