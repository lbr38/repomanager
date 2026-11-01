<?php

namespace Controllers\Layout\Chart;

use Exception;

class Chart
{
    /**
     *  Get chart data for the period defined by timeStart and timeEnd timestamps
     *  If no period is specified, the period is the last 24 hours
     */
    public static function get(string $id, string $timeStart = '', string $timeEnd = '') : array
    {
        try {
            /**
             *  Only allow safe characters in the chart ID to prevent path traversal / local file inclusion
             */
            if (!preg_match('/^[a-z0-9-]+$/i', $id)) {
                throw new Exception('invalid chart ID');
            }

            if (!file_exists(ROOT . '/controllers/Layout/Chart/vars/' . $id . '.vars.inc.php')) {
                throw new Exception('could not retrieve chart data for chart ID ' . $id);
            }

            // Default period is the last 24 hours
            if (empty($timeStart) || empty($timeEnd)) {
                $timeStart = strtotime('-1 day');
                $timeEnd   = time();
            }

            // Check that timeStart and timeEnd are valid timestamps
            if (!is_numeric($timeStart) || !is_numeric($timeEnd) || $timeStart > $timeEnd) {
                throw new Exception('invalid time range specified');
            }

            $timeStart = (int) $timeStart;
            $timeEnd   = (int) $timeEnd;

            include(ROOT . '/controllers/Layout/Chart/vars/' . $id . '.vars.inc.php');

            unset($timeStart, $timeEnd);

            /**
             *  Return chart data
             */
            return [
                'datasets' => $datasets,
                'labels' => $labels,
                'options' => $options
            ];
        } catch (Exception $e) {
            throw new Exception('Error rendering chart #' . $id . ': ' . $e->getMessage());
        }
    }
}
