<?php

namespace Models\History;

use Controllers\Database\Log as DbLog;
use Exception;

class History extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Return the number of failed local authentications for the specified username and IP address since the specified date and time
     */
    public function countFailedLogins(string $username, string $ip, string $date, string $time) : int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) AS Count FROM history
            WHERE (Date > :date OR (Date = :date AND Time >= :time))
            AND State = 'error'
            AND Username = :username
            AND Ip = :ip
            AND Action LIKE 'Authentication failed for %(local account)%'");
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':ip', $ip);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
            return 0;
        }

        $row = $result->fetchArray(SQLITE3_ASSOC);

        return (int) ($row['Count'] ?? 0);
    }

    /**
     *  Return the WHERE clause and its values for the specified filters
     *  Filters: user (Id), category, state, search (text searched in the action, username and IP addresses)
     */
    private function filtersClause(array $filters): array
    {
        $conditions = [];
        $values = [];

        if (!empty($filters['user'])) {
            $conditions[] = 'Id_user = :user';
            $values[':user'] = (int) $filters['user'];
        }

        if (!empty($filters['category'])) {
            if ($filters['category'] == 'other') {
                $conditions[] = "(Category IS NULL OR Category = '')";
            } else {
                $conditions[] = 'Category = :category';
                $values[':category'] = $filters['category'];
            }
        }

        if (!empty($filters['state'])) {
            $conditions[] = 'State = :state';
            $values[':state'] = $filters['state'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(Action LIKE :search ESCAPE '\\' OR Username LIKE :search ESCAPE '\\' OR Ip LIKE :search ESCAPE '\\' OR Ip_forwarded LIKE :search ESCAPE '\\' OR Details LIKE :search ESCAPE '\\')";
            $values[':search'] = '%' . addcslashes($filters['search'], '%_\\') . '%';
        }

        if (empty($conditions)) {
            return ['', $values];
        }

        return [' WHERE ' . implode(' AND ', $conditions), $values];
    }

    /**
     *  Return history lines matching the specified filters, most recent first
     */
    public function get(array $filters, int $limit, int $offset) : array
    {
        $data = [];

        [$where, $values] = $this->filtersClause($filters);

        try {
            $stmt = $this->db->prepare("SELECT * FROM history" . $where . " ORDER BY Date DESC, Time DESC, Id DESC LIMIT :limit OFFSET :offset");

            foreach ($values as $name => $value) {
                $stmt->bindValue($name, $value);
            }

            $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
            return $data;
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return the number of history lines matching the specified filters
     */
    public function count(array $filters) : int
    {
        [$where, $values] = $this->filtersClause($filters);

        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) AS Count FROM history" . $where);

            foreach ($values as $name => $value) {
                $stmt->bindValue($name, $value);
            }

            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
            return 0;
        }

        $row = $result->fetchArray(SQLITE3_ASSOC);

        return (int) ($row['Count'] ?? 0);
    }

    /**
     *  Clean history logs older than the specified date
     */
    public function cleanup(string $date) : void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM history WHERE Date < :date");
            $stmt->bindValue(':date', $date);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }
}
