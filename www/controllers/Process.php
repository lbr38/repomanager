<?php

namespace Controllers;

class Process
{
    private $workingDir = ROOT . '/.temp'; // Working dir for php
    private $command;
    private $env = array('HOME' => ROOT, 'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin');
    private $process;
    private $pipes;
    private $pid;
    private $runInBackground = false;
    private $output;
    private $exitcode = null;

    public function __construct(string $command, array $env = null)
    {
        $this->command = $command;

        /**
         *  If others env vars have been specified then add them
         */
        if (!empty($env)) {
            $this->env = array_merge($this->env, $env);
        }
    }

    /**
     *  Return exit code of the process
     *  The exit code is present in the last access to the process status, before its end.
     */
    public function getExitCode()
    {
        return $this->exitcode;
    }

    /**
     *  Print process output
     *  The output can be append into a file if specified
     */
    public function getOutput(string $filePath = null)
    {
        /**
         *  Get output while the process is running
         */
        if (is_resource($this->process)) {
            while ($this->isRunning()) {
                /**
                 *  If a file has been specified, then append output to the file
                 */
                if (!empty($filePath)) {
                    file_put_contents($filePath, stream_get_contents($this->pipes[1]), FILE_APPEND); // stdout
                    file_put_contents($filePath, stream_get_contents($this->pipes[2]), FILE_APPEND); // stderr

                /**
                 *  Else print output directly
                 */
                } else {
                    $this->output .= stream_get_contents($this->pipes[1]);
                    $this->output .= stream_get_contents($this->pipes[2]);
                }
            }
        }

        /**
         *  Get output one last time after the process has finished to be sure to have captured the whole output
         */
        if (!empty($filePath)) {
            file_put_contents($filePath, stream_get_contents($this->pipes[1]), FILE_APPEND); // stdout
            file_put_contents($filePath, stream_get_contents($this->pipes[2]), FILE_APPEND); // stderr
        } else {
            $this->output .= stream_get_contents($this->pipes[1]);
            $this->output .= stream_get_contents($this->pipes[2]);
        }

        return trim($this->output);
    }

    /**
     *  Return PID of the process
     */
    public function getPid()
    {
        $procInfo = proc_get_status($this->process);

        if (!empty($procInfo['pid'])) {
            return $procInfo['pid'];
        }

        return null;
    }

    /**
     *  Choose to run process in background or not
     */
    public function setBackground(bool $runInBackground)
    {
        $this->runInBackground = $runInBackground;
    }

    /**
     *  Create and execute a new process with the specified command
     */
    public function execute()
    {
        /**
         *  File descriptors for each subprocess.
         *  http://phptutorial.info/?proc-open
         *  https://gist.github.com/swichers/027d5ae903350cbd4af8
         */
        $descriptors = array(
            0 => array("pipe", "r"), // stdin is a pipe that the child will read from
            1 => array("pipe", "w"), // stdout is a pipe that the child will write to
            2 => array("pipe", "w")  // stderr is a pipe that the child will write to
        );

        /**
         *  Execution
         */
        $this->process = proc_open($this->command, $descriptors, $this->pipes, $this->workingDir, $this->env);

        /**
         *  Make sure pipes are not blocking execution
         */
        stream_set_blocking($this->pipes[1], 0);
        stream_set_blocking($this->pipes[2], 0);

        /**
         *  Get process PID
         */
        $this->pid = $this->getPid();

        /**
         *  If process must not run in background, just loop and get its output till it has finished
         */
        if ($this->runInBackground === false) {
            $this->getOutput();
        }
    }

    /**
     *  Return true if the process is running
     */
    public function isRunning()
    {
        $status = proc_get_status($this->process);

        if ($status['running'] === true) {
            return true;
        }

        if ($status['running'] === false && $this->exitcode === null) {
            $this->exitcode = $status['exitcode'];
        }

        return false;
    }

    /**
     *  Close pipes and process
     */
    public function close()
    {
        /**
         *  Close pipes
         */
        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
        fclose($this->pipes[2]);

        proc_close($this->process);
    }
}
