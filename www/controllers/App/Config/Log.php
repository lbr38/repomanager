<?php

namespace Controllers\App\Config;

use Exception;

class Log
{
    public static function get()
    {
        $LOG = 0;
        $LOG_MESSAGES = array();
        $mylog = new \Controllers\Log\Log();

        /**
         *  Count unread notifications
         */
        $LOG = count($mylog->getUnread(''));

        /**
         *  Retrieve 5 last unread notifications from database
         */
        if ($LOG > 0) {
            $LOG_MESSAGES = $mylog->getUnread('', 5);
        }

        if (!defined('LOG')) {
            define('LOG', $LOG);
        }

        if (!defined('LOG_MESSAGES')) {
            define('LOG_MESSAGES', $LOG_MESSAGES);
        }

        unset($LOG, $LOG_MESSAGES, $mylog);
    }
}
