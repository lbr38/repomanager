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
            $repoPath = REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName();
        }
        if ($this->repo->getPackageType() == 'deb') {
            $repoPath = REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection();
        }

        /**
         *  Generate repository metadata
         */
        try {
            if ($this->repo->getPackageType() == 'rpm') {
                $mymetadata = new \Controllers\Task\Repo\Metadata\Rpm($this->task->getId());
                $mymetadata->setRoot($repoPath);
                $mymetadata->create();
            }

            if ($this->repo->getPackageType() == 'deb') {
                $mymetadata = new \Controllers\Task\Repo\Metadata\Deb($this->task->getId());
                $mymetadata->setRoot($repoPath);
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
                    if (!\Controllers\Filesystem\Directory::deleteRecursive($repoPath)) {
                        throw new Exception('Repository creation has failed and directory cannot be cleaned: ' . $repoPath);
                    }
                }
                if ($this->repo->getPackageType() == 'deb') {
                    if (!\Controllers\Filesystem\Directory::deleteRecursive($repoPath)) {
                        throw new Exception('Repository creation has failed and directory cannot be cleaned: ' . $repoPath);
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

        /**
         *  Create symbolic link (environment)
         *  Only if user has specified to point an environment to the created snapshot
         */
        if ($this->task->getAction() == "create" or $this->task->getAction() == "update") {
            if (!empty($this->repo->getEnv())) {
                foreach ($this->repo->getEnv() as $env) {
                    if ($this->repo->getPackageType() == 'rpm') {
                        $targetFile = $this->repo->getDateFormatted() . '_' . $this->repo->getName();
                        $link = REPOS_DIR . '/' . $this->repo->getName() . '_' . $env;
                    }
                    if ($this->repo->getPackageType() == 'deb') {
                        $targetFile = $this->repo->getDateFormatted() . '_' . $this->repo->getSection();
                        $link = REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $env;
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
                    if (!symlink($targetFile, $link)) {
                        throw new Exception('Could not point environment to the repository');
                    }

                    unset($targetFile, $link);
                }
            }
        }

        $this->taskLogStepController->completed();
    }
}
