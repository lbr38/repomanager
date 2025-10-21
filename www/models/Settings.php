<?php

namespace Models;

use Exception;
use \Controllers\Database\Log as DbLog;

class Settings extends Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Get settings
     */
    public function get() : array
    {
        $settings = [];

        try {
            $result = $this->db->query("SELECT * FROM settings");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $settings = $row;
        }

        return $settings;
    }

    /**
     *  Apply settings
     */
    public function apply(array $settingsToApply) : void
    {
        /**
         *  Build request
         */
        $request = "UPDATE settings SET ";

        foreach ($settingsToApply as $key => $value) {
            $request .= $key . " = :" . $key . ", ";
        }

        $request = rtrim($request, ", ");

        try {
            $stmt = $this->db->prepare($request);

            foreach ($settingsToApply as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }

            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }
}
