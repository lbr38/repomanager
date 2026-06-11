<?php

namespace Controllers\Layout\Kpi;

use Exception;

class Kpi
{
    public static function get(string $id) : array
    {
        try {
            // Only allow safe characters in the chart ID to prevent path traversal / local file inclusion
            if (!preg_match("#^" . ROOT . "#", realpath(ROOT . '/controllers/Layout/Kpi/vars/' . $id . '.vars.inc.php'))) {
                throw new Exception('could not retrieve KPI data for KPI ID ' . $id);
            }

            if (!file_exists(ROOT . '/controllers/Layout/Kpi/vars/' . $id . '.vars.inc.php')) {
                throw new Exception('could not retrieve KPI data for KPI ID ' . $id . ' (file does not exist)');
            }

            // Default values that the vars file may override
            $value = null;
            $options = [];

            include(ROOT . '/controllers/Layout/Kpi/vars/' . $id . '.vars.inc.php');

            /**
             *  Return KPI data
             */
            return [
                'value' => $value,
                'options' => $options
            ];
        } catch (Exception $e) {
            throw new Exception('Error rendering KPI #' . $id . ': ' . $e->getMessage());
        }
    }
}
