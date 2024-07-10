<?php

namespace Controllers;

class SignalHandler
{
    private $filepath;

    public function __construct()
    {
        pcntl_signal(SIGTERM, array($this, 'signalHandler'));
        pcntl_signal(SIGINT, array($this, 'signalHandler'));
    }

    /**
     *  Signal handler
     */
    public function signalHandler($signal)
    {
        switch ($signal) {
            case SIGTERM:
                print "Caught SIGTERM\n";
                if (!empty($this->filepath) and !file_exists($this->filepath)) {
                    touch($this->filepath);
                }
                exit;
            case SIGKILL:
                print "Caught SIGKILL\n";
                if (!empty($this->filepath) and !file_exists($this->filepath)) {
                    touch($this->filepath);
                }
                exit;
            case SIGINT:
                print "Caught SIGINT\n";
                if (!empty($this->filepath) and !file_exists($this->filepath)) {
                    touch($this->filepath);
                }
                exit;
        }
    }

    /**
     *  Create a file on interrupt
     */
    public function touchFileOnInterrupt(string $filepath)
    {
        $this->filepath = $filepath;
    }
}
