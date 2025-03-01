<?php

namespace Models;

use Exception;

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
        $settings = array();

        try {
            $result = $this->db->query("SELECT * FROM settings");
        } catch (\Exception $e) {
            $this->db->logError($e);
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
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Enable or disable debug mode
     */
    public function enableDebugMode(string $enable) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE settings SET DEBUG_MODE = :enable");
            $stmt->bindValue(':enable', $enable);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }
}
