<?php

namespace Models\Cve\Tools;

use Exception;

class Import extends \Models\Model
{
    public function __construct()
    {
        /**
         *  Open main database
         */
        $this->getConnection('main');
    }

    /**
     *  Set new started CVE import in database
     */
    public function setStartImport()
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO cve_import ('Date', 'Time', 'Status') VALUES (:date, :time, :status)");
            $stmt->bindValue(':date', date('Y-m-d'));
            $stmt->bindValue(':time', date('H:i:s'));
            $stmt->bindValue(':status', 'running');
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        return $this->getLastInsertRowID();
    }

    /**
     *  Set end CVE import in database
     */
    public function setEndImport(string $importId, string $duration)
    {
        try {
            $stmt = $this->db->prepare("UPDATE cve_import SET Duration = :duration WHERE Id = :id");
            $stmt->bindValue(':id', $importId);
            $stmt->bindValue(':duration', $duration);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Set CVE import status in database
     */
    public function setImportStatus(string $importId, string $status)
    {
        try {
            $stmt = $this->db->prepare("UPDATE cve_import SET Status = :status WHERE Id = :id");
            $stmt->bindValue(':id', $importId);
            $stmt->bindValue(':status', $status);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Set new started host import in database
     */
    public function setStartHostImport()
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO cve_affected_hosts_import ('Date', 'Time', 'Status') VALUES (:date, :time, :status)");
            $stmt->bindValue(':date', date('Y-m-d'));
            $stmt->bindValue(':time', date('H:i:s'));
            $stmt->bindValue(':status', 'running');
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        return $this->getLastInsertRowID();
    }

    /**
     *  Set end host import in database
     */
    public function setEndHostImport(string $importId, string $duration)
    {
        try {
            $stmt = $this->db->prepare("UPDATE cve_affected_hosts_import SET Duration = :duration WHERE Id = :id");
            $stmt->bindValue(':id', $importId);
            $stmt->bindValue(':duration', $duration);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Set host import status in database
     */
    public function setHostImportStatus(string $importId, string $status)
    {
        try {
            $stmt = $this->db->prepare("UPDATE cve_affected_hosts_import SET Status = :status WHERE Id = :id");
            $stmt->bindValue(':id', $importId);
            $stmt->bindValue(':status', $status);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Drop and recreate CVE related tables
     */
    public function clearDatabase()
    {
        try {
            $this->db->query("DROP table IF EXISTS cve");
            $this->db->query("DROP table IF EXISTS cve_cpe");
            $this->db->query("DROP table IF EXISTS cve_reference");
            $this->db->query("DROP table IF EXISTS cve_affected_hosts");
            $this->db->query("VACUUM");
            $this->db->checkMainTables();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }
}
