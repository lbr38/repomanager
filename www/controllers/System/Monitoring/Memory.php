<?php

namespace Controllers\System\Monitoring;

use Exception;

class Memory
{
    /**
     *  Get memory usage (%)
     *  Use a python library to get the memory usage
     */
    public static function getUsage() : string
    {
        $processController = new \Controllers\Process('python3 ' . ROOT . '/bin/get-memory-usage.py');
        $processController->execute();
        $output = trim($processController->getOutput());
        $processController->close();

        if ($processController->getExitCode() != 0) {
            throw new Exception('Failed to get memory usage: ' . $output);
        }

        if (empty($output)) {
            throw new Exception('No memory usage data returned.');
        }

        return $output;
    }
}
