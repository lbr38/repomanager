<?php

namespace Controllers;

class SignalHandler
{
    private $files = [];

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
                echo 'Caught SIGTERM' . PHP_EOL;
                $this->createFiles();
                exit;
            case SIGKILL:
                echo 'Caught SIGKILL' . PHP_EOL;
                $this->createFiles();
                exit;
            case SIGINT:
                echo 'Caught SIGINT' . PHP_EOL;
                $this->createFiles();
                exit;
        }
    }

    /**
     *  Create file(s) on interrupt
     */
    public function touchFileOnInterrupt(array $files)
    {
        $this->files = $files;
    }

    /**
     *  Create the files on interrupt
     */
    public function createFiles()
    {
        foreach ($this->files as $file) {
            if (!file_exists($file)) {
                touch($file);
            }
        }
    }
}
