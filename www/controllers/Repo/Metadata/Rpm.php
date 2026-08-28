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
    private $additionalMetadataFiles = [
        'updateinfo.xml.gz' => [
            'mdtype' => 'updateinfo',
            'temp' => 'tmp-updateinfo'
        ],
        'comps.xml' => [
            'mdtype' => 'group',
            'temp' => 'tmp-comps'
        ],
        'modules.yaml' => [
            'mdtype' => 'modules',
            'temp' => 'tmp-modules'
        ]
    ];

    public function setRoot(string $root): void
    {
        $this->root = $root;
    }

    /**
     *  Create metadata files
     *  Use createrepo_c to generate repository metadata
     *  Use modifyrepo_c to add additional metadata files to the repository metadata
     */
    public function create(): void
    {
        $this->taskLogSubStepController->new('create-metadata', 'GENERATING REPOSITORY METADATA');

        // Check if createrepo_c and modifyrepo_c exist on the system
        foreach ([$this->createrepo, $this->modifyrepo] as $bin) {
            if (!file_exists($bin)) {
                throw new Exception('Could not find ' . $bin . ' on the system');
            }
        }

        // Check if root path exists
        if (!is_dir($this->root)) {
            throw new Exception('Repository root directory ' . $this->root . ' does not exist');
        }

        /**
         *  Rename additional metadata files to temporary names to avoid them being included automatically by createrepo_c
         *  They will be added later using modifyrepo_c
         *  This to avoid issues with broken modules.yaml (from Oracle 8 Appstream repo notably) raising errors in createrepo_c (modifyrepo_c does not have this issue)
         *  https://github.com/lbr38/repomanager/issues/399
         *  https://github.com/lbr38/repomanager/issues/408
         */
        foreach ($this->additionalMetadataFiles as $file => $value) {
            if (file_exists($this->root . '/' . $file)) {
                if (!rename($this->root . '/' . $file, $this->root . '/' . $value['temp'])) {
                    throw new Exception('Could not rename ' . $this->root . '/' . $file . ' to ' . $this->root . '/' . $value['temp']);
                }
            }
        }

        // Launch createrepo_c to generate repository metadata
        $process = new Process($this->createrepo . ' ' . $this->createrepoArgs . ' ' . $this->root . '/');
        $process->setBackground(true);
        $process->execute();

        // Add PID to main PID file
        $this->taskController->addsubpid($process->getPid());

        // Retrieve output from process
        $this->taskLogSubStepController->output($process->getOutput(), 'pre');

        if ($process->getExitCode() != 0) {
            throw new Exception('Could not generate repository metadata');
        }

        $process->close();

        $this->taskLogSubStepController->completed();

        $this->taskLogSubStepController->new('add-updateinfo', 'ADDING UPDATEINFO TO METADATA');

        // Add additional metadata files to the repository metadata using modifyrepo_c
        foreach ($this->additionalMetadataFiles as $file => $value) {
            // If the temporary file does not exist, skip the addition of this metadata file
            if (!file_exists($this->root . '/' . $value['temp'])) {
                continue;
            }

            // Rename the temporary file back to its original name
            if (!rename($this->root . '/' . $value['temp'], $this->root . '/' . $file)) {
                throw new Exception('Could not rename ' . $this->root . '/' . $value['temp'] . ' to ' . $this->root . '/' . $file);
            }

            // Add the file to the repository metadata using modifyrepo_c
            $process = new Process($this->modifyrepo . ' ' . $this->modifyrepoArgs . ' --mdtype=' . $value['mdtype'] . ' ' . $this->root . '/' . $file . ' ' . $this->root . '/repodata/');
            $process->setBackground(true);
            $process->execute();

            // Retrieve PID of the launched process then write PID to main PID file
            $this->taskController->addsubpid($process->getPid());

            // Retrieve output from process
            $this->taskLogSubStepController->output($process->getOutput(), 'pre');

            if ($process->getExitCode() != 0) {
                throw new Exception('Could not add ' . $value['mdtype'] . ' to repository metadata');
            }

            $process->close();

            // Delete the file as it's now part of the metadata
            if (!unlink($this->root . '/' . $file)) {
                throw new Exception('Could not delete ' . $this->root . '/' . $file);
            }
        }

        $this->taskLogSubStepController->completed();
    }
}
