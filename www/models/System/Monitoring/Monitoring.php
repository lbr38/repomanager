<?php

namespace Models\System\Monitoring;

use Exception;

class Monitoring extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Get monitoring data between two timestamps
     */
    public function get(string $timestampStart, string $timestampEnd) : array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT * FROM system_monitoring WHERE Timestamp BETWEEN :timestampStart AND :timestampEnd ORDER BY Timestamp ASC");
            $stmt->bindValue(':timestampStart', $timestampStart);
            $stmt->bindValue(':timestampEnd', $timestampEnd);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e->getMessage());
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Set monitoring data (%) in the database
     */
    public function set(float $cpuUsage, float $memoryUsage, float $diskUsage) : void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO system_monitoring (Timestamp, Cpu_usage, Memory_usage, Disk_usage) VALUES (:timestamp, :cpuUsage, :memoryUsage, :diskUsage)");
            $stmt->bindValue(':timestamp', time());
            $stmt->bindValue(':cpuUsage', $cpuUsage);
            $stmt->bindValue(':memoryUsage', $memoryUsage);
            $stmt->bindValue(':diskUsage', $diskUsage);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e->getMessage());
        }
    }

    /**
     *  Clean old monitoring data
     */
    public function clean(string $timestamp) : void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM system_monitoring WHERE Timestamp < :timestamp");
            $stmt->bindValue(':timestamp', $timestamp);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e->getMessage());
        }
    }
}
