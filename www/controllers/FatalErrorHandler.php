<?php

namespace Controllers;

use Error;
use Exception;

class FatalErrorHandler
{
    private $taskId;
    private $logController;
    private $layoutContainerStateController;

    public function __construct()
    {
        $this->logController = new \Controllers\Log\Log();
        $this->layoutContainerStateController = new \Controllers\Layout\ContainerState();

        register_shutdown_function(array($this, 'fatalHandler'));
    }

    public function setTaskId(int $taskId)
    {
        $this->taskId = $taskId;
    }

    /**
     *  Fatal error handler
     */
    public function fatalHandler()
    {
        $error = error_get_last();

        if ($error !== null) {
            $type = $error['type'];
            $message = $error['message'];
            $file = $error['file'];
            $line = $error['line'];

            /**
             *  Check if the error is a fatal error
             */
            if ($type === E_ERROR || $type === E_PARSE || $type === E_CORE_ERROR || $type === E_COMPILE_ERROR) {
                /**
                 *  Print a log message
                 *  If a task Id has been set, log the error with the task Id
                 */
                if (!empty($this->taskId)) {
                    $this->logController->log('error', 'Fatal error occured while running task #' . $this->taskId . ' (you may have to stop the task manually)', $message);
                } else {
                    $this->logController->log('error', 'Fatal error occured', $message);
                }

                $this->layoutContainerStateController->update('header/general-log-messages');

                /**
                 *  Exit
                 */
                exit(1);
            }
        }
    }
}
