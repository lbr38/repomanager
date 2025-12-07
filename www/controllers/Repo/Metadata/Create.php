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
        $createMetadataError = 0;
        $createMetadataErrorMsg = '';

        $this->taskLogStepController->new('create-repo-metadata', 'CREATING REPOSITORY');

        if ($this->repoController->getPackageType() == 'rpm') {
            $snapshotPath = REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $this->repoController->getDate();
        }
        if ($this->repoController->getPackageType() == 'deb') {
            $snapshotPath = REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $this->repoController->getDate();
        }

        /**
         *  Generate repository metadata
         */
        try {
            if ($this->repoController->getPackageType() == 'rpm') {
                $mymetadata = new RpmMetadata($this->taskId);
                $mymetadata->setRoot($snapshotPath);
                $mymetadata->create();
            }

            if ($this->repoController->getPackageType() == 'deb') {
                $mymetadata = new DebMetadata($this->taskId);
                $mymetadata->setRoot($snapshotPath);
                $mymetadata->setRepo($this->repoController->getName());
                $mymetadata->setDist($this->repoController->getDist());
                $mymetadata->setSection($this->repoController->getSection());
                $mymetadata->setArch($this->repoController->getArch());
                $mymetadata->setGpgSign($this->repoController->getGpgSign());
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
            if ($this->action != 'rebuild') {
                if ($this->repoController->getPackageType() == 'rpm') {
                    if (!Directory::deleteRecursive($snapshotPath)) {
                        throw new Exception('Repository creation has failed and directory cannot be cleaned: ' . $snapshotPath);
                    }
                }
                if ($this->repoController->getPackageType() == 'deb') {
                    if (!Directory::deleteRecursive($snapshotPath)) {
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
        if ($this->action == 'create' or $this->action == 'update') {
            if (!empty($this->repoController->getEnv())) {
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
            }
        }

        $this->taskLogSubStepController->completed();

        $this->taskLogStepController->completed();
    }
}
