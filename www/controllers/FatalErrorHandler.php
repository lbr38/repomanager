<?php

namespace Controllers;

use Error;
use Exception;

class FatalErrorHandler
{
    private $taskId;
    private $logController;
    private $layoutContainerReloadController;
    private $reservedMemory;

    public function __construct()
    {
        $this->logController = new \Controllers\Log\Log();
        $this->layoutContainerReloadController = new \Controllers\Layout\ContainerReload();

        /**
         *   Keep some memory reserved for fatalHandler() to run even in a memory error state
         */
        $this->reservedMemory = str_repeat(' ', 1024 * 1024); // 1MB

        register_shutdown_function(array($this, 'fatalHandler'));
    }

    public function setTaskId(int $taskId)
    {
        $this->taskId = $taskId;
    }

    /**
     *  Fatal error handler
     *  Avoid creating variables here as PHP is already in a memory error state, variables may not be created
     */
    public function fatalHandler()
    {
        /**
         *  Free up reserved memory to make sure this function can run even in a memory error state
         */
        $this->reservedMemory = null;

        $error = error_get_last();

        if ($error !== null) {
            // Other available error keys:
            // $error['file'];
            // $error['line'];

            /**
             *  Check if the error is a fatal error
             */
            if ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR) {
                /**
                 *  Print a log message
                 *  If a task Id has been set, log the error with the task Id
                 */
                if (!empty($this->taskId)) {
                    $this->logController->log('error', 'Fatal error occured while running task #' . $this->taskId . ' (you may have to stop the task manually)', $error['message']);
                } else {
                    $this->logController->log('error', 'Fatal error occured', $error['message']);
                }

                $this->layoutContainerReloadController->reload('header/general-log-messages');

                /**
                 *  Exit
                 */
                exit(1);
            }
        }
    }
}
