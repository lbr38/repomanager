<?php

namespace Models\Task\Schedule;

use Exception;
use \Controllers\Database\Log as DbLog;

class Schedule extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    public function getScheduled()
    {
        $data = [];

        try {
            $result = $this->db->query("SELECT * FROM tasks WHERE Status = 'queued'");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }
}
