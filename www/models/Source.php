<?php

namespace Models;

use Exception;

class Source extends Model
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
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        return $data;
    }

    /**
     *  Get source repo Id from its name
     */
    public function getIdByName(string $name)
    {
        $id = '';

        try {
            $stmt = $this->db->prepare("SELECT Id FROM sources WHERE Name = :name");
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
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $type = $row['Type'];
        }

        return $type;
    }

    /**
     *  Add a new source repo in database
     */
    public function new(string $repoType, string $name, string $url, string $gpgKeyURL = null, string $gpgKeyText = null)
    {
        try {
            /**
             *  Case no GPG key URL has been specified
             */
            if (empty($gpgKeyURL)) {
                $stmt = $this->db->prepare("INSERT INTO sources ('Type', 'Name', 'Url') VALUES (:type, :name, :url)");
            } else {
                $stmt = $this->db->prepare("INSERT INTO sources ('Type', 'Name', 'Url', 'Gpgkey') VALUES (:type, :name, :url, :gpgkey)");
                $stmt->bindValue(':gpgkey', $gpgKeyURL);
            }
            $stmt->bindValue(':type', $repoType);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':url', $url);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Edit a source repo
     */
    public function edit(string $id, string $name, string $url, string|null $gpgKeyURL, string|null $sslCertificatePath, string|null $sslPrivateKeyPath)
    {
        try {
            $stmt = $this->db->prepare('UPDATE sources SET Name = :name, Url = :url, Gpgkey = :gpgKeyUrl, Ssl_certificate_path = :sslCertificatePath, Ssl_private_key_path = :sslPrivateKeyPath WHERE Id = :id');
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':url', $url);
            $stmt->bindValue(':gpgKeyUrl', $gpgKeyURL);
            $stmt->bindValue(':sslCertificatePath', $sslCertificatePath);
            $stmt->bindValue(':sslPrivateKeyPath', $sslPrivateKeyPath);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Delete a source repo
     */
    public function delete(string $sourceId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM sources WHERE Id = :id");
            $stmt->bindValue(':id', $sourceId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Check if source repo exists in database
     */
    public function exists(string $type, string $source)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM sources WHERE Type = :type AND Name = :name");
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':name', $source);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
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
            \Controllers\Common::dbError($e);
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
