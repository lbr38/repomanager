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
            /**
             *  Check if its a major release version
             *  If first digit of the version is different, its a major release
             */
            $currentVersionDigit = explode('.', VERSION)[0];
            $newVersionDigit = explode('.', GIT_VERSION)[0];

            /**
             *  Case its a major release
             */
            if ($currentVersionDigit != $newVersionDigit) {
                $message = '<span class="yellowtext">A new major release is available: ';
            } else {
                $message = '<span>A new release is available: ';
            }

            $message .= '<a href="https://github.com/lbr38/repomanager/releases/latest" target="_blank" rel="noopener noreferrer" title="See changelog"><code>' . GIT_VERSION . '</code><img src="/assets/icons/external-link.svg" class="icon" /></a>';
            $message .= '<br><br><span>Please update your docker image by following the steps documented <b><a href="' . PROJECT_UPDATE_DOC_URL . '"><code>here</code></a></b></span>';

            $NOTIFICATION_MESSAGES[] = array('Title' => 'Update available', 'Message' =>  $message);
            $NOTIFICATION++;
        }

        /**
         *  If current user email is not set, generate a new notification
         */
        if (empty($_SESSION['email'])) {
            $message = '<span>You can configure your email in your user profile. This email can be used as a recipient to send notifications of Repomanager events like scheduled tasks status or scheduled tasks reminders</span>';
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
