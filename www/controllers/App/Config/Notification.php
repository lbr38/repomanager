<?php

namespace Controllers\App\Config;

use Exception;

class Notification
{
    public static function get()
    {
        $NOTIFICATION = 0;
        $NOTIFICATION_MESSAGES = array();
        $mynotification = new \Controllers\Notification();

        /**
         *  Retrieve unread notifications from database
         */
        $NOTIFICATION_MESSAGES = $mynotification->getUnread();
        $NOTIFICATION = count($NOTIFICATION_MESSAGES);

        /**
         *  If an update is available, generate a new notification
         */
        if (UPDATE_AVAILABLE == 'true') {
            $message = '<span class="yellowtext">A new release is available: <b>' . GIT_VERSION . '</b>.</span><br><br>Please update your docker image.</span>';
            $NOTIFICATION_MESSAGES[] = array('Title' => 'Update available', 'Message' =>  $message);
            $NOTIFICATION++;
        }

        /**
         *  If current user email is not set, generate a new notification
         */
        if (empty($_SESSION['email'])) {
            $message = '<span>You can configure your email in your user profile. This email can be used as a recipient to send notifications of Repomanager events like planification status or planification reminders</span>';
            $NOTIFICATION_MESSAGES[] = array('Title' => 'Email contact not set', 'Message' =>  $message);
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
