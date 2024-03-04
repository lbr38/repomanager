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

        ob_start();

        $this->taskLog->step('CREATING REPO');

        echo '<div class="hide createRepoDiv"><pre>';

        $this->taskLog->steplogWrite();

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
                $mymetadata = new \Controllers\Task\Repo\Metadata\Rpm();
                $mymetadata->setPid($this->task->getPid());
                $mymetadata->setRoot($repoPath);
                $mymetadata->setLogfile($this->taskLog->getSteplog());
                $mymetadata->create();
            }

            if ($this->repo->getPackageType() == 'deb') {
                $mymetadata = new \Controllers\Task\Repo\Metadata\Deb();
                $mymetadata->setPid($this->task->getPid());
                $mymetadata->setRoot($repoPath);
                $mymetadata->setRepo($this->repo->getName());
                $mymetadata->setDist($this->repo->getDist());
                $mymetadata->setSection($this->repo->getSection());
                $mymetadata->setArch($this->repo->getArch());
                $mymetadata->setGpgResign($this->repo->getGpgSign());
                $mymetadata->setLogfile($this->taskLog->getSteplog());
                $mymetadata->create();
            }
        } catch (Exception $e) {
            $createMetadataError++;
            $createMetadataErrorMsg = $e->getMessage();
        }

        echo '</pre></div>';
        $this->taskLog->steplogWrite();

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
                        throw new Exception('Repo creation has failed and directory cannot be cleaned: ' . $repoPath);
                    }
                }
                if ($this->repo->getPackageType() == 'deb') {
                    if (!\Controllers\Filesystem\Directory::deleteRecursive($repoPath)) {
                        throw new Exception('Repo creation has failed and directory cannot be cleaned: ' . $repoPath);
                    }
                }
            }

            /**
             *  Throw exception to stop the process
             */
            $msg = 'Repo creation has failed';

            if (!empty($createMetadataErrorMsg)) {
                $msg .= ' - ' . $createMetadataErrorMsg;
            }

            throw new Exception($msg);
        }

        $this->taskLog->steplogWrite();

        /**
         *  Create symbolic link (environment)
         *  Only if user has specified to point an environment to the created snapshot
         */
        if ($this->task->getAction() == "new" or $this->task->getAction() == "update") {
            if (!empty($this->repo->getEnv())) {
                if ($this->repo->getPackageType() == 'rpm') {
                    $targetFile = $this->repo->getDateFormatted() . '_' . $this->repo->getName();
                    $link = REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv();
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $targetFile = $this->repo->getDateFormatted() . '_' . $this->repo->getSection();
                    $link = REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv();
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

        $this->taskLog->stepOK();

        return true;
    }
}
