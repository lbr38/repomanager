<?php

namespace Controllers;

use Exception;

class System
{
    /**
     *  Repomanager service status
     */
    public static function serviceStatus()
    {
        if (DOCKER == 'true') {
            $myprocess = new Process('ps aux | grep repomanager-service | grep -v grep');
        } else {
            $myprocess = new Process('systemctl is-active repomanager --quiet');
        }
        $myprocess->execute();
        $content = $myprocess->getOutput();
        $myprocess->close();

        if ($myprocess->getExitCode() != 0) {
            return false;
        }

        return true;
    }
}
