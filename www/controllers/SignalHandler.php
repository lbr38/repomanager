<?php

namespace Controllers;

use \Controllers\Log\Cli as LogCli;

class SignalHandler
{
    public $shutdown = false;

    public function __construct()
    {
        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGINT, [$this, 'signalHandler']);
        pcntl_signal(SIGCHLD, [$this, 'signalHandler']);
    }

    /**
     *  Signal handler
     */
    public function signalHandler($signal)
    {
        switch ($signal) {
            case SIGTERM:
                LogCli::debug('Caught SIGTERM');

                // Set shutdown flag to true for services to stop gracefully when possible
                $this->shutdown = true;

                break;
            case SIGINT:
                LogCli::debug('Caught SIGINT');

                // Set shutdown flag to true for services to stop gracefully when possible
                $this->shutdown = true;

                break;
            case SIGCHLD:
                LogCli::debug('Caught SIGCHLD');
                // Reap ALL terminated children, non-blocking
                // WNOHANG = return immediately if no child has exited
                while (($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
                    LogCli::debug('Child PID ' . $pid . ' terminated');

                    // Log how it terminated
                    if (pcntl_wifexited($status)) {
                        LogCli::debug('  → Exit code: ' . pcntl_wexitstatus($status));
                    } elseif (pcntl_wifsignaled($status)) {
                        LogCli::debug('  → Killed by signal: ' . pcntl_wtermsig($status));
                    }
                }
                break;
        }
    }
}
