<?php

namespace Controllers\Repo\Metadata;

use Exception;
use Controllers\Process;

class Rpm extends Metadata
{
    private $root;
    private $createrepo = '/usr/bin/createrepo_c';
    private $createrepoArgs = '-v --compress-type=gz --general-compress-type=gz';
    // private $modifyrepo = '/usr/bin/modifyrepo_c';

    public function setRoot(string $root)
    {
        $this->root = $root;
    }

    /**
     *  Create metadata files
     */
    public function create(): void
    {
        // Check which of createrepo or createrepo_c is present on the system
        if (!file_exists($this->createrepo)) {
            throw new Exception('Could not find createrepo on the system');
        }

        // Check if root path exists
        if (!is_dir($this->root)) {
            throw new Exception("Repository root directory '" . $this->root . "' does not exist");
        }

        // If a comps.xml file exists in the root directory, include it in the metadata
        if (file_exists($this->root . '/comps.xml')) {
            $this->createrepoArgs .= ' --groupfile=' . $this->root . '/comps.xml';
        }

        $this->taskLogSubStepController->new('create-metadata', 'GENERATING REPOSITORY METADATA');

        // Create repository metadata
        $myprocess = new Process($this->createrepo . ' ' . $this->createrepoArgs . ' ' . $this->root . '/');
        $myprocess->setBackground(true);
        $myprocess->execute();

        /**
         *  Retrieve PID of the launched process
         *  Then write PID to main PID file
         */
        $this->taskController->addsubpid($myprocess->getPid());

        // Retrieve output from process
        $output = $myprocess->getOutput();

        $this->taskLogSubStepController->output($output, 'pre');

        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Could not generate repository metadata');
        }

        $myprocess->close();

        // Delete temporary metadata files as they are no longer needed
        foreach (['comps.xml', 'updateinfo.xml', 'modules.yaml'] as $file) {
            if (file_exists($this->root . '/' . $file)) {
                if (!unlink($this->root . '/' . $file)) {
                    throw new Exception('Could not delete ' . $this->root . '/' . $file);
                }
            }
        }

        $this->taskLogSubStepController->completed();
    }
}
