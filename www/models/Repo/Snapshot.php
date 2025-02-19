<?php

namespace Models\Repo;

use Exception;

class Snapshot extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Return true if a task is queued or running for the specified snapshot
     */
    public function taskRunning(int $snapId) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM tasks
            WHERE json_extract(COALESCE(Raw_params, '{}'), '$.snap-id') == :snapId
            AND Status IN ('queued', 'running')");
            $stmt->bindValue(':snapId', strval($snapId));
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }
}
