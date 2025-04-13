<?php

namespace Controllers\Log;

use Exception;

class Log
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Log\Log();
    }

    /**
     *  Get all logs or logs of a specific type
     */
    public function getUnread(string $type = null, int $limit = 0)
    {
        return $this->model->getUnread($type, $limit);
    }

    /**
     *  Log a message
     *  Only log if a similar log message is not already logged
     */
    public function log(string $type, string $component, string $message, string $details = '')
    {
        try {
            /**
             *  Get all unread log
             */
            $logs = $this->getUnread($type);

            if (!empty($logs)) {
                /**
                 *  Loop through all logs
                 */
                foreach ($logs as $log) {
                    /**
                     *  If a similar log message is already logged, quit
                     */
                    if ($log['Component'] == $component && $log['Message'] == $message && $log['Details'] == $details) {
                        return;
                    }
                }
            }

            /**
             *  Log the message
             */
            $this->model->log($type, $component, $message, $details);

        /**
         *  If the log could not be saved in database, log the error in error_log
         */
        } catch (Exception $e) {
            error_log('Here is a database error while trying to save log: ' . $e . PHP_EOL);

            /**
             *  If the log cannot be saved then log directly in error_log
             */
            if (!empty($details)) {
                error_log('Here is the original error that could not be saved in database: ' . $message . ': ' . $details . PHP_EOL);
            } else {
                error_log('Here is the original error that could not be saved in database: ' . $message . PHP_EOL);
            }
        }
    }

    /**
     *  Acquit a log message
     */
    public function acquit(int $id)
    {
        $this->model->acquit($id);
    }
}
