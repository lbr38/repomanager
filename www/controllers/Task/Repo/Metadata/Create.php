<?php

namespace Controllers\Task\Repo\Metadata;

use Exception;

trait Create
{
    /**
     *  Create repo metadata and symbolic links (environments)
     */
    private function createMetadata()
    {
        $createMetadataError = 0;
        $createMetadataErrorMsg = '';

        $this->taskLogStepController->new('create-repo-metadata', 'CREATING REPOSITORY');

        if ($this->repo->getPackageType() == 'rpm') {
            $snapshotPath = REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever() . '/' . $this->repo->getDate();
        }
        if ($this->repo->getPackageType() == 'deb') {
            $snapshotPath = REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '/' . $this->repo->getDate();
        }

        /**
         *  Generate repository metadata
         */
        try {
            if ($this->repo->getPackageType() == 'rpm') {
                $mymetadata = new \Controllers\Task\Repo\Metadata\Rpm($this->task->getId());
                $mymetadata->setRoot($snapshotPath);
                $mymetadata->create();
            }

            if ($this->repo->getPackageType() == 'deb') {
                $mymetadata = new \Controllers\Task\Repo\Metadata\Deb($this->task->getId());
                $mymetadata->setRoot($snapshotPath);
                $mymetadata->setRepo($this->repo->getName());
                $mymetadata->setDist($this->repo->getDist());
                $mymetadata->setSection($this->repo->getSection());
                $mymetadata->setArch($this->repo->getArch());
                $mymetadata->setGpgSign($this->repo->getGpgSign());
                $mymetadata->create();
            }
        } catch (Exception $e) {
            $createMetadataError++;
            $createMetadataErrorMsg = $e->getMessage();
        }

        /**
         *  If there was error while creating metadata, then delete everything
         */
        if ($createMetadataError != 0) {
            /**
             *  Delete everything to make sure the task can be relaunched (except if action is 'rebuild')
             */
            if ($this->task->getAction() != 'rebuild') {
                if ($this->repo->getPackageType() == 'rpm') {
                    if (!\Controllers\Filesystem\Directory::deleteRecursive($snapshotPath)) {
                        throw new Exception('Repository creation has failed and directory cannot be cleaned: ' . $snapshotPath);
                    }
                }
                if ($this->repo->getPackageType() == 'deb') {
                    if (!\Controllers\Filesystem\Directory::deleteRecursive($snapshotPath)) {
                        throw new Exception('Repository creation has failed and directory cannot be cleaned: ' . $snapshotPath);
                    }
                }
            }

            /**
             *  Throw exception to stop the process
             */
            $msg = 'Repository creation has failed';

            if (!empty($createMetadataErrorMsg)) {
                $msg .= ': ' . $createMetadataErrorMsg;
            }

            throw new Exception($msg);
        }

        $this->taskLogSubStepController->completed();

        $this->taskLogSubStepController->new('pointing-env', 'POINTING ENVIRONMENT(S)');

        /**
         *  Create symbolic link (environment)
         *  Only if user has specified to point an environment to the created snapshot
         */
        if ($this->task->getAction() == 'create' or $this->task->getAction() == 'update') {
            if (!empty($this->repo->getEnv())) {
                foreach ($this->repo->getEnv() as $env) {
                    if ($this->repo->getPackageType() == 'rpm') {
                        $link = REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever() . '/' . $env;
                    }
                    if ($this->repo->getPackageType() == 'deb') {
                        $link = REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '/' . $env;
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
                    if (!symlink($this->repo->getDate(), $link)) {
                        throw new Exception('Could not point environment to the repository');
                    }

                    unset($link);
                }
            }
        }

        $this->taskLogSubStepController->completed();

        $this->taskLogStepController->completed();
    }
}
