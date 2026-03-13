<?php

namespace Controllers\App\Config;

use Exception;

class Notification
{
    public static function get()
    {
        $NOTIFICATION = 0;
        $NOTIFICATION_MESSAGES = [];
        $mynotification = new \Controllers\Notification();

        /**
         *  Retrieve unread notifications from database
         */
        $NOTIFICATION_MESSAGES = $mynotification->getUnread();
        $NOTIFICATION = count($NOTIFICATION_MESSAGES);

        /**
         *  If an update is available, generate a new notification
         */
        if (IS_ADMIN && UPDATE_AVAILABLE) {
            $NOTIFICATION++;
        }

        if (!defined('NOTIFICATION')) {
            define('NOTIFICATION', $NOTIFICATION);
        }

        if (!defined('NOTIFICATION_MESSAGES')) {
            define('NOTIFICATION_MESSAGES', $NOTIFICATION_MESSAGES);
        }

        unset($NOTIFICATION, $NOTIFICATION_MESSAGES, $mynotification);
    }
}
