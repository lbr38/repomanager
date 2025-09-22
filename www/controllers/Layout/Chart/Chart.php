<?php

namespace Controllers\Layout\Chart;

use Exception;

class Chart
{
    public static function get(string $id)
    {
        try {
            if (!file_exists(ROOT . '/controllers/Layout/Chart/vars/' . $id . '.vars.inc.php')) {
                throw new Exception('could not retrieve chart data for chart ID ' . $id);
            }

            include_once(ROOT . '/controllers/Layout/Chart/vars/' . $id . '.vars.inc.php');

            /**
             *  Return chart data
             */
            return [
                'datasets' => $datasets,
                'labels' => $labels,
                'options' => $options
            ];
        } catch (Exception $e) {
            throw new Exception('Error rendering chart: ' . $e->getMessage());
        }
    }
}
