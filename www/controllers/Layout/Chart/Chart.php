<?php

namespace Controllers\Layout\Chart;

use Exception;

class Chart
{
    public static function get(string $id, int $days) : array
    {
        try {
            // Only allow safe characters in the chart ID to prevent path traversal / local file inclusion
            if (!preg_match("#^" . ROOT . "#", realpath(ROOT . '/controllers/Layout/Chart/vars/' . $id . '.vars.inc.php'))) {
                throw new Exception('could not retrieve chart data for chart ID ' . $id);
            }

            if (!file_exists(ROOT . '/controllers/Layout/Chart/vars/' . $id . '.vars.inc.php')) {
                throw new Exception('could not retrieve chart data for chart ID ' . $id . ' (file does not exist)');
            }

            // Timestart is X days ago
            $timeStart = strtotime('-' . $days . ' days');
            $timeEnd   = time();

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

    /**
     *  Get chart data for a specific period defined by timeStart and timeEnd parameters
     *  Should replace get() method when all charts support custom periods
     *  TODO: ranges
     */
    public static function getPeriod(string $id, string $timeStart, string $timeEnd): array
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

            // Check that timeStart and timeEnd are valid dates
            if (strtotime($timeStart) === false || strtotime($timeEnd) === false) {
                throw new Exception('invalid time range specified');
            }

            // Convert timeStart and timeEnd to timestamps
            $timeStart = strtotime($timeStart);
            $timeEnd   = strtotime($timeEnd);

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
