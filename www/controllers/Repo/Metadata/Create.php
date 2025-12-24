<?php

namespace Controllers\Repo\Metadata;

use \Controllers\Repo\Metadata\Rpm as RpmMetadata;
use \Controllers\Repo\Metadata\Deb as DebMetadata;
use \Controllers\Filesystem\Directory;
use Exception;

trait Create
{
    /**
     *  Create repo metadata and symbolic links (environments)
     */
    private function createMetadata()
    {
        $workingDir = REPOS_DIR . '/temporary-task-' . $this->taskId;

        $this->taskLogStepController->new('create-repo-metadata', 'CREATING REPOSITORY');

        // Define final snapshot path and parent directory
        if ($this->repoController->getPackageType() == 'rpm') {
            $parentDir = REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever();
            $snapshotPath = $parentDir . '/' . $this->repoController->getDate();
        }
        if ($this->repoController->getPackageType() == 'deb') {
            $parentDir = REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection();
            $snapshotPath = $parentDir . '/' . $this->repoController->getDate();
        }

        // If action is 'rebuild', set working dir to the existing snapshot path
        if ($this->action == 'rebuild') {
            $workingDir = $snapshotPath;
        }

        /**
         *  Generate repository metadata
         */
        try {
            if ($this->repoController->getPackageType() == 'rpm') {
                $mymetadata = new RpmMetadata($this->taskId);
                $mymetadata->setRoot($workingDir);
                $mymetadata->create();
            }

            if ($this->repoController->getPackageType() == 'deb') {
                $mymetadata = new DebMetadata($this->taskId);
                $mymetadata->setRoot($workingDir);
                $mymetadata->setRepo($this->repoController->getName());
                $mymetadata->setDist($this->repoController->getDist());
                $mymetadata->setSection($this->repoController->getSection());
                $mymetadata->setArch($this->repoController->getArch());
                $mymetadata->setGpgSign($this->repoController->getGpgSign());
                $mymetadata->create();
            }
        } catch (Exception $e) {
            throw new Exception('Repository creation has failed: ' . $e->getMessage());
        }

        $this->taskLogSubStepController->completed();

        if ($this->action == 'create' or $this->action == 'update') {
            // Rename temporary working dir to the final path

            // Delete the target snapshot directory if it already exists
            if (is_dir($snapshotPath)) {
                if (!Directory::deleteRecursive($snapshotPath)) {
                    throw new Exception('Cannot delete existing directory: ' . $snapshotPath);
                }
            }

            // Create parent directory if not exists
            if (!is_dir($parentDir)) {
                if (!mkdir($parentDir, 0770, true)) {
                    throw new Exception('Could not create directory: ' . $parentDir);
                }
            }

            // Rename temporary working directory to the final snapshot path
            if (!rename($workingDir, $snapshotPath)) {
                throw new Exception('Could not rename working directory ' . $workingDir);
            }

            /**
             *  Create symbolic link (environment)
             *  Only if user has specified to point an environment to the created snapshot
             */
            if (!empty($this->repoController->getEnv())) {
                $this->taskLogSubStepController->new('pointing-env', 'POINTING ENVIRONMENT(S)');

                foreach ($this->repoController->getEnv() as $env) {
                    if ($this->repoController->getPackageType() == 'rpm') {
                        $link = REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $env;
                    }
                    if ($this->repoController->getPackageType() == 'deb') {
                        $link = REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $env;
                    }

                    /**
                     *  If a symlink with the same name already exists, we remove it
                     */
                    if (is_link($link)) {
                        if (!unlink($link)) {
                            throw new Exception('Could not remove existing symlink ' . $link);
                        }
                    }

                    /**
                     *  Create symlink
                     */
                    if (!symlink($this->repoController->getDate(), $link)) {
                        throw new Exception('Could not point environment to the repository');
                    }

                    unset($link);
                }

                $this->taskLogSubStepController->completed();
            }
        }

        $this->taskLogStepController->completed();
    }
}
