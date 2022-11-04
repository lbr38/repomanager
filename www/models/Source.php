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
     *  Return source repo URL
     */
    public function getUrl(string $sourceType, string $sourceName)
    {
        $fullUrl = '';

        try {
            $stmt = $this->db->prepare("SELECT Url FROM sources WHERE Type = :type AND Name = :name");
            $stmt->bindValue(':type', $sourceType);
            $stmt->bindValue(':name', $sourceName);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $fullUrl = $row['Url'];
        }

        return $fullUrl;
    }

    /**
     *  Get GPG key URL of the specified source repo
     */
    public function getGpgKeyUrl(string $sourceType, string $sourceName)
    {
        $gpgKeyUrl = '';

        try {
            $stmt = $this->db->prepare("SELECT Gpgkey FROM sources WHERE Type = :type AND Name = :name");
            $stmt->bindValue(':type', $sourceType);
            $stmt->bindValue(':name', $sourceName);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $gpgKeyUrl = $row['Gpgkey'];
        }

        return $gpgKeyUrl;
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
     *  Rename a source repo in database
     */
    public function rename(string $type, string $name, string $newName)
    {
        try {
            $stmt = $this->db->prepare("UPDATE sources SET Name = :newname WHERE Type = :type AND Name = :name");
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':newname', $newName);
            $stmt->bindValue(':name', $name);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  Also rename source in 'repos' table
         */
        try {
            $stmt = $this->db->prepare("UPDATE repos SET Source = :newname WHERE Source = :name AND Package_type = :type");
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':newname', $newName);
            $stmt->bindValue(':name', $name);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Edit source repo URL in database
     */
    public function editUrl(string $type, string $name, string $url)
    {
        try {
            $stmt = $this->db->prepare("UPDATE sources SET Url = :url WHERE Type = :type AND Name = :name");
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':url', $url);
            $stmt->bindValue(':name', $name);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Edit source repo GPG key URL
     */
    public function editGpgKey(string $sourceId, string $url = '')
    {
        /**
         *  Insert new URL in database
         */
        try {
            $stmt = $this->db->prepare('UPDATE sources SET Gpgkey = :gpgkeyurl WHERE Id = :id');
            $stmt->bindValue(':id', $sourceId);
            $stmt->bindValue(':gpgkeyurl', $url);
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
     *  List all source repos
     */
    public function listAll(string $type = null)
    {
        $sources = array();

        /**
         *  If a source type has been specified
         */
        if (!empty($type)) {
            $stmt = $this->db->prepare("SELECT * FROM sources WHERE Type = :type ORDER BY Type ASC, Name ASC");
            $stmt->bindValue(':type', $type);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM sources ORDER BY Type ASC, Name ASC");
        }
        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $sources[] = $row;
        }

        return $sources;
    }
}
