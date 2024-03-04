<?php

namespace Controllers\Task\Repo\Metadata;

use Exception;

class Rpm
{
    private $root;
    private $createrepo = '/usr/bin/createrepo_c';
    private $logfile;
    private $pid;
    private $task;

    public function __construct()
    {
        $this->task = new \Controllers\Task\Task(false);
    }

    public function setRoot(string $root)
    {
        $this->root = $root;
    }

    public function setLogfile(string $logfile)
    {
        $this->logfile = $logfile;
    }

    public function setPid(string $pid)
    {
        $this->pid = $pid;
    }

    /**
     *  Create metadata files
     */
    public function create()
    {
        /**
         *  Check which of createrepo or createrepo_c is present on the system
         */
        if (!file_exists($this->createrepo)) {
            throw new Exception('Could not find createrepo on the system');
        }

        /**
         *  Set task pid to the main pid passed
         */
        $this->task->setPid($this->pid);

        /**
         *  Check if root path exists
         */
        if (!is_dir($this->root)) {
            throw new Exception("Repository root directory '" . $this->root . "' does not exist");
        }

        /**
         *  Create repository metadata
         */
        $myprocess = new \Controllers\Process($this->createrepo . ' -v ' . $this->root . '/');
        $myprocess->setBackground(true);
        $myprocess->execute();

        /**
         *  Retrieve PID of the launched process
         *  Then write PID to main PID file
         */
        $this->task->addsubpid($myprocess->getPid());

        /**
         *  Print output to logfile
         */
        $myprocess->getOutput($this->logfile);

        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Failed to generate repository metadata');
        }

        $myprocess->close();
    }
}
