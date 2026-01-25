<?php

namespace Controllers\Layout\Chart;

use Exception;

class Chart
{
    public static function get(string $id, int $days) : array
    {
        try {
            if (!file_exists(ROOT . '/controllers/Layout/Chart/vars/' . $id . '.vars.inc.php')) {
                throw new Exception('could not retrieve chart data for chart ID ' . $id);
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
}
