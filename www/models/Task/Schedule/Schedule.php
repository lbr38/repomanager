<?php

namespace Models\Task\Schedule;

use Exception;
use Datetime;

class Schedule extends \Models\Model
{
    public function __construct()
    {
        /**
         *  Open a connection to the database
         */
        $this->getConnection('main');
    }

    public function getScheduled()
    {
        $data = array();

        try {
            $result = $this->db->query("SELECT * FROM tasks WHERE Status = 'queued'");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }
}
