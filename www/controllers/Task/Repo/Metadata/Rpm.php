<?php

namespace Controllers\Task\Repo\Metadata;

use Exception;

class Rpm
{
    private $root;
    private $createrepo = '/usr/bin/createrepo_c';
    private $createrepoArgs = '-v --compress-type=gz --general-compress-type=gz';
    private $modifyrepo = '/usr/bin/modifyrepo_c';
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
         *  If a comps.xml file exists in the root directory, include it in the metadata
         */
        if (file_exists($this->root . '/comps.xml')) {
            $this->createrepoArgs .= ' --groupfile=' . $this->root . '/comps.xml';
        }

        file_put_contents($this->logfile, 'Creating repository metadata' . PHP_EOL, FILE_APPEND);

        /**
         *  Create repository metadata
         */
        $myprocess = new \Controllers\Process($this->createrepo . ' ' . $this->createrepoArgs . ' ' . $this->root . '/');
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
            throw new Exception('Could not generate repository metadata');
        }

        $myprocess->close();

        /**
         *  Delete comps.xml as it is no longer needed
         */
        if (file_exists($this->root . '/comps.xml')) {
            if (!unlink($this->root . '/comps.xml')) {
                throw new Exception('Could not delete ' . $this->root . '/comps.xml');
            }
        }

        /**
         *  If a 'modules-temp.yaml' file exists in the root directory, include it in the metadata
         *  This file has been given a temporary name to avoid being included automatically by createrepo (which seems to fail to parse it correctly)
         *  So it has to be renamed to modules.yaml and then added to the metadata by modifyrepo
         */
        if (file_exists($this->root . '/modules-temp.yaml')) {
            file_put_contents($this->logfile, PHP_EOL . 'Adding <code>modules.yaml</code> to repository metadata', FILE_APPEND);

            /**
             *  Rename to modules.yaml
             */
            if (!rename($this->root . '/modules-temp.yaml', $this->root . '/modules.yaml')) {
                throw new Exception('Could not rename modules-temp.yaml to modules.yaml');
            }

            /**
             *  Include modules.yaml in the metadata
             */
            $myprocess = new \Controllers\Process($this->modifyrepo . ' ' . $this->root . '/modules.yaml ' . $this->root . '/repodata/');
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
                throw new Exception('Failed to add modules.yaml to repository metadata');
            }

            $myprocess->close();

            /**
             *  Delete modules.yaml as it is no longer needed
             */
            if (file_exists($this->root . '/modules.yaml')) {
                if (!unlink($this->root . '/modules.yaml')) {
                    throw new Exception('Could not delete ' . $this->root . '/modules.yaml');
                }
            }
        }

        /**
         *  If updateinfo.xml file exists in the root directory, include it in the metadata
         */
        if (file_exists($this->root . '/updateinfo.xml')) {
            file_put_contents($this->logfile, PHP_EOL . 'Adding <code>updateinfo.xml</code> to repository metadata', FILE_APPEND);

            /**
             *  Include updateinfo.xml in the metadata
             */
            $myprocess = new \Controllers\Process($this->modifyrepo . ' ' . $this->root . '/updateinfo.xml ' . $this->root . '/repodata/');
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
                throw new Exception('Failed to add updateinfo.xml to repository metadata');
            }

            $myprocess->close();

            /**
             *  Delete updateinfo.xml as it is no longer needed
             */
            if (file_exists($this->root . '/updateinfo.xml')) {
                if (!unlink($this->root . '/updateinfo.xml')) {
                    throw new Exception('Could not delete ' . $this->root . '/updateinfo.xml');
                }
            }
        }
    }
}
