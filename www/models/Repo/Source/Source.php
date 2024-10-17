<?php

namespace Models\Repo\Source;

use Exception;

class Source extends \Models\Model
{
    public function __construct()
    {
        /**
         *  Open a new database connection
         */
        $this->getConnection('main');
    }

    /**
     *  Return all source informations
     */
    public function getAll(string $sourceType, string $sourceName)
    {
        $data = array();

        try {
            $stmt = $this->db->prepare("SELECT * FROM sources WHERE Type = :type AND Name = :name");
            $stmt->bindValue(':type', $sourceType);
            $stmt->bindValue(':name', $sourceName);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        return $data;
    }

    /**
     *  Get source repo Id from its name
     */
    public function getIdByName(string $type, string $name)
    {
        $id = '';

        try {
            $stmt = $this->db->prepare("SELECT Id FROM sources WHERE Type = :type AND Name = :name");
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['Id'];
        }

        return $id;
    }

    /**
     *  Get source repo type from its Id
     */
    public function getType(string $id)
    {
        $type = '';

        try {
            $stmt = $this->db->prepare("SELECT Type FROM sources WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $type = $row['Type'];
        }

        return $type;
    }

    /**
     *  Get source repo details from its Id
     */
    public function getDetails(string $id)
    {
        $data = '';

        try {
            $stmt = $this->db->prepare("SELECT Details FROM sources WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row['Details'];
        }

        return $data;
    }

    /**
     *  Add a new source repository
     */
    public function new(string $repoType, string $name, string $params)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO sources ('Type', 'Name', 'Details') VALUES (:type, :name, :params)");
            $stmt->bindValue(':type', $repoType);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':params', $params);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Edit a source repository
     */
    public function edit(string $id, string $name, string $params)
    {
        try {
            $stmt = $this->db->prepare('UPDATE sources SET Name = :name, Details = :params WHERE Id = :id');
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':params', $params);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Edit a source repo
     */
    // public function edit(string $id, string $name, string $url, string|null $gpgKeyURL, string|null $sslCertificatePath, string|null $sslPrivateKeyPath, string|null $sslCaCertificatePath)
    // {
    //     try {
    //         $stmt = $this->db->prepare('UPDATE sources SET Name = :name, Url = :url, Gpgkey = :gpgKeyUrl, Ssl_certificate_path = :sslCertificatePath, Ssl_private_key_path = :sslPrivateKeyPath, Ssl_ca_certificate_path = :sslCaCertificatePath WHERE Id = :id');
    //         $stmt->bindValue(':id', $id);
    //         $stmt->bindValue(':name', $name);
    //         $stmt->bindValue(':url', $url);
    //         $stmt->bindValue(':gpgKeyUrl', $gpgKeyURL);
    //         $stmt->bindValue(':sslCertificatePath', $sslCertificatePath);
    //         $stmt->bindValue(':sslPrivateKeyPath', $sslPrivateKeyPath);
    //         $stmt->bindValue(':sslCaCertificatePath', $sslCaCertificatePath);
    //         $stmt->execute();
    //     } catch (\Exception $e) {
    //         $this->db->logError($e);
    //     }
    // }

    /**
     *  Delete a source repository
     */
    public function delete(string $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM sources WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Check if source repo exists in database
     */
    public function exists(string $type, string $name)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM sources WHERE Type = :type AND Name = :name");
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
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
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  List all source repos
     */
    public function listAll(string $type, bool $withOffset, int $offset)
    {
        $data = array();

        $query = "SELECT * FROM sources";

        /**
         *  If a source type has been specified
         */
        if (!empty($type)) {
            $query .= " WHERE Type = :type";
        }

        $query .= " ORDER BY Type ASC, Name ASC";

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
        $stmt->bindValue(':type', $type);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }
}
