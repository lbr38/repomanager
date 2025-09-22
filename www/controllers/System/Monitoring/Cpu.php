<?php

namespace Controllers\System\Monitoring;

use Exception;

class Cpu
{
    /**
     *  Get CPU usage (%)
     *  Use a python library to get the CPU usage
     */
    public static function getUsage() : string
    {
        $processController = new \Controllers\Process('python3 ' . ROOT . '/bin/get-cpu-usage.py');
        $processController->execute();
        $output = trim($processController->getOutput());
        $processController->close();

        if ($processController->getExitCode() != 0) {
            throw new Exception('Failed to get CPU usage: ' . $output);
        }

        if (empty($output)) {
            throw new Exception('No CPU usage data returned.');
        }

        return $output;
    }
}
