<?php

namespace Controllers\Repo\Metadata;

use Exception;
use Controllers\Process;

class Rpm extends Metadata
{
    private $root;
    private $createrepo = '/usr/bin/createrepo_c';
    private $createrepoArgs = '-v --compress-type=gz --general-compress-type=gz';
    private $modifyrepo = '/usr/bin/modifyrepo_c';
    private $modifyrepoArgs = '--compress-type=gz';

    public function setRoot(string $root)
    {
        $this->root = $root;
    }

    /**
     *  Add an additional metadata file to the repository metadata using modifyrepo_c
     *  Searches for the file with various compressions and deletes it afterwards
     */
    private function addMetadata(string $filePrefix, string $mdtype): void
    {
        // Check if modifyrepo_c is available
        if (!file_exists($this->modifyrepo)) {
            throw new Exception('Could not find modifyrepo_c on the system');
        }

        // Look for the metadata file with various compression formats (and plain format) and use the first one found
        $metadataFile = null;
        foreach ([$filePrefix, $filePrefix . '.gz', $filePrefix . '.xz', $filePrefix . '.bz2', $filePrefix . '.zst'] as $file) {
            if (file_exists($this->root . '/' . $file)) {
                $metadataFile = $file;
                break;
            }
        }

        // If file doesn't exist, return silently (file may not exist in the source repo)
        if (empty($metadataFile)) {
            return;
        }

        // Add the file to the repository metadata using modifyrepo_c
        $modifyrepoProcess = new Process($this->modifyrepo . ' ' . $this->modifyrepoArgs . ' --mdtype=' . $mdtype . ' ' . $this->root . '/' . $metadataFile . ' ' . $this->root . '/repodata/');
        $modifyrepoProcess->setBackground(true);
        $modifyrepoProcess->execute();

        /**
         *  Retrieve PID of the launched process
         *  Then write PID to main PID file
         */
        $this->taskController->addsubpid($modifyrepoProcess->getPid());

        // Retrieve output from process
        $this->taskLogSubStepController->output($modifyrepoProcess->getOutput(), 'pre');

        if ($modifyrepoProcess->getExitCode() != 0) {
            throw new Exception('Could not add ' . $mdtype . ' to repository metadata');
        }

        $modifyrepoProcess->close();

        // Delete the file as it's now part of the metadata
        if (!unlink($this->root . '/' . $metadataFile)) {
            throw new Exception('Could not delete ' . $this->root . '/' . $metadataFile);
        }
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

        // Add updateinfo to metadata if it exists
        $this->taskLogSubStepController->new('add-updateinfo', 'ADDING UPDATEINFO TO METADATA');
        $this->addMetadata('updateinfo.xml', 'updateinfo');
        $this->taskLogSubStepController->completed();

        // Delete temporary metadata files as they are no longer needed
        foreach (['comps.xml', 'modules.yaml'] as $file) {
            if (file_exists($this->root . '/' . $file)) {
                if (!unlink($this->root . '/' . $file)) {
                    throw new Exception('Could not delete ' . $this->root . '/' . $file);
                }
            }
        }

        $this->taskLogSubStepController->completed();
    }
}
