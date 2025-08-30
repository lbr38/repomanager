<?php

namespace Models;

use Exception;

class Stat extends Model
{
    public function __construct()
    {
        /**
         *  Ouverture d'une connexion à la base de données
         */
        $this->getConnection('stats');
    }

    /**
     *  Ajoute de nouvelles statistiques à la table stats
     */
    public function add(string $date, string $time, string $repoSize, string $packagesCount, string $envId)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO stats (Date, Time, Size, Packages_count, Id_env) VALUES (:date, :time, :size, :packages_count, :envId)");
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':size', $repoSize);
            $stmt->bindValue(':packages_count', $packagesCount);
            $stmt->bindValue(':envId', $envId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Add deb repository access log to database
     */
    public function addDebAccess(string $date, string $time, string $name, string $dist, string $component, string $env, string $sourceHost, string $sourceIp, string $request, string $result) : void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO access_deb (Date, Time, Name, Dist, Section, Env, Source, IP, Request, Request_result) VALUES (:date, :time, :name, :dist, :component, :env, :sourceHost, :sourceIp, :request, :result)");
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':dist', $dist);
            $stmt->bindValue(':component', $component);
            $stmt->bindValue(':env', $env);
            $stmt->bindValue(':sourceHost', $sourceHost);
            $stmt->bindValue(':sourceIp', $sourceIp);
            $stmt->bindValue(':request', $request);
            $stmt->bindValue(':result', $result);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Add rpm repository access log to database
     */
    public function addRpmAccess(string $date, string $time, string $name, string $releasever, string $env, string $sourceHost, string $sourceIp, string $request, string $result) : void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO access_rpm (Date, Time, Name, Releasever, Env, Source, IP, Request, Request_result) VALUES (:date, :time, :name, :releasever, :env, :sourceHost, :sourceIp, :request, :result)");
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':releasever', $releasever);
            $stmt->bindValue(':env', $env);
            $stmt->bindValue(':sourceHost', $sourceHost);
            $stmt->bindValue(':sourceIp', $sourceIp);
            $stmt->bindValue(':request', $request);
            $stmt->bindValue(':result', $result);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Add new repo access log to queue
     */
    public function addAccessToQueue(string $request)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO access_queue (Request) VALUES (:request)");
            $stmt->bindValue(':request', $request);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Return access queue
     */
    public function getAccessQueue()
    {
        $datas = array();

        try {
            $stmt = $this->db->prepare("SELECT * FROM access_queue LIMIT 100");
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Delete access log from queue
     */
    public function deleteFromQueue(string $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM access_queue WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Retourne tout le contenu de la table stats
     */
    public function getAll(string $envId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM stats WHERE Id_env = :envId");
            $stmt->bindValue('envId', $envId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Return repo snapshot size (by its env Id) for the last specified days
     */
    public function getEnvSize(string $envId, int $days)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM (SELECT Date, Size FROM stats WHERE Id_env = :envId ORDER BY Date DESC LIMIT :days) ORDER BY Date ASC");
            $stmt->bindValue(':envId', $envId);
            $stmt->bindValue(':days', $days);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Return repo snapshot packages count (by its env Id) for the last specified days
     */
    public function getPkgCount(string $envId, int $days)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM (SELECT Date, Packages_count FROM stats WHERE Id_env = :envId ORDER BY Date DESC LIMIT :days) ORDER BY Date ASC");
            $stmt->bindValue(':envId', $envId);
            $stmt->bindValue(':days', $days);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Return access request of the specified deb repository
     *  It is possible to count the number of requests
     *  It is possible to add an offset to the request
     */
    public function getDebAccess(string $name, string $dist, string $component, string $env, bool $count = false, bool $withOffset = false, int $offset = 0) : array|int
    {
        $data = [];

        try {
            // Case count is enabled
            if ($count) {
                $select = "SELECT COUNT(*) as count";
            } else {
                $select = "SELECT *";
            }

            // Build query
            $query = $select . " FROM access_deb WHERE Name = :name AND Dist = :dist AND Section = :component AND Env = :env";

            /**
             *  Invert the order of the query to get the last access logs first
             *  Order by Id DESC and not by 'Date DESC / TIME DESC' because it kills the performance
             *  Also Id DESC is accurate because it is the order of the insertion in the database (so it's like doing 'ORDER BY Date DESC / TIME DESC')
             */
            if (!$count) {
                $query .= " ORDER BY Id DESC";
            }

            // If offset is specified
            if ($withOffset) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            // Prepare query
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':dist', $dist);
            $stmt->bindValue(':component', $component);
            $stmt->bindValue(':env', $env);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Case count is enabled, return only the count
            if ($count) {
                return $row['count'];
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return access request of the specified rpm repository
     *  It is possible to count the number of requests
     *  It is possible to add an offset to the request
     */
    public function getRpmAccess(string $name, string $releasever, string $env, bool $count = false, bool $withOffset = false, int $offset = 0) : array|int
    {
        $data = [];

        try {
            // Case count is enabled
            if ($count) {
                $select = "SELECT COUNT(*) as count";
            } else {
                $select = "SELECT *";
            }

            // Build query
            $query = $select . " FROM access_rpm WHERE Name = :name AND Releasever = :releasever AND Env = :env";

            /**
             *  Invert the order of the query to get the last access logs first
             *  Order by Id DESC and not by 'Date DESC / TIME DESC' because it kills the performance
             *  Also Id DESC is accurate because it is the order of the insertion in the database (so it's like doing 'ORDER BY Date DESC / TIME DESC')
             */
            if (!$count) {
                $query .= " ORDER BY Id DESC";
            }

            // If offset is specified
            if ($withOffset) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            // Prepare query
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':releasever', $releasever);
            $stmt->bindValue(':env', $env);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Case count is enabled, return only the count
            if ($count) {
                return $row['count'];
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Count the number of access requests to the specified repo/section, on a given date
     */
    public function getDailyAccessCount(string $type, string $name, string|null $dist, string|null $section, string $env, string $date)
    {
        try {
            if ($type == 'deb') {
                $stmt = $this->db->prepare("SELECT COUNT(Id) as Count FROM access_deb
                WHERE Name = :name AND Dist = :dist AND Section = :section AND Env = :env AND Date = :date");
            }
            if ($type == 'rpm') {
                $stmt = $this->db->prepare("SELECT COUNT(Id) as Count FROM access_rpm
                WHERE Name = :name AND Env = :env AND Date = :date");
            }

            if ($type == 'deb') {
                $stmt->bindValue(':dist', $dist);
                $stmt->bindValue(':section', $section);
            }
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':env', $env);
            $stmt->bindValue(':date', $date);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            return $row['Count'];
        }
    }

    /**
     *  Get the total number of access requests to the specified repo/section, on a given date, by IP
     *  It is possible to add an offset to the request
     */
    public function getAccessIpCount(string $type, string $name, string|null $dist, string|null $section, string $env, string $date, bool $withOffset, int $offset)
    {
        $data = [];

        try {
            if ($type == 'deb') {
                $query = "SELECT Source, IP, COUNT(*) as Count FROM access_deb WHERE Name = :name AND Dist = :dist AND Section = :section AND Env = :env AND Date = :date";
            }
            if ($type == 'rpm') {
                $query = "SELECT Source, IP, COUNT(*) as Count FROM access_rpm WHERE Name = :name AND Env = :env AND Date = :date";
            }
            $query .= " GROUP BY IP ORDER BY Count DESC";

             /**
             *  If offset is specified
             */
            if ($withOffset) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            $stmt = $this->db->prepare($query);

            if ($type == 'deb') {
                $stmt->bindValue(':dist', $dist);
                $stmt->bindValue(':section', $section);
            }
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':env', $env);
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Clean oldest repos statistics by deleting rows in database between specified dates
     */
    public function clean(string $dateStart, string $dateEnd)
    {
        try {
            // Use a transaction to group all the delete operations
            $this->db->exec("BEGIN TRANSACTION");
            $stmt = $this->db->prepare("DELETE FROM stats WHERE Date >= :dateStart and Date <= :dateEnd");
            $stmt->bindValue(':dateStart', $dateStart);
            $stmt->bindValue(':dateEnd', $dateEnd);
            $stmt->execute();

            $stmt = $this->db->prepare("DELETE FROM access_deb WHERE Date >= :dateStart and Date <= :dateEnd");
            $stmt->bindValue(':dateStart', $dateStart);
            $stmt->bindValue(':dateEnd', $dateEnd);
            $stmt->execute();

            $stmt = $this->db->prepare("DELETE FROM access_rpm WHERE Date >= :dateStart and Date <= :dateEnd");
            $stmt->bindValue(':dateStart', $dateStart);
            $stmt->bindValue(':dateEnd', $dateEnd);
            $stmt->execute();

            // Commit the transaction
            $this->db->exec("COMMIT");
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Fermeture de la connexion à la base de données
     */
    public function closeConnection()
    {
        $this->db->close();
    }
}
