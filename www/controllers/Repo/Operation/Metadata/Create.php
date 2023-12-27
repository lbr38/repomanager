<?php

namespace Controllers\Repo\Operation\Metadata;

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

        $this->log->step('CREATING REPO');

        echo '<div class="hide createRepoDiv"><pre>';

        $this->log->steplogWrite();

        if ($this->repo->getPackageType() == 'rpm') {
            $repoPath = REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName();
        }
        if ($this->repo->getPackageType() == 'deb') {
            $repoPath = REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getSection();
        }

        /**
         *  Generate repository metadata
         */
        try {
            if ($this->repo->getPackageType() == 'rpm') {
                $mymetadata = new \Controllers\Repo\Operation\Metadata\Rpm();
                $mymetadata->setPid($this->operation->getPid());
                $mymetadata->setRoot($repoPath);
                $mymetadata->setLogfile($this->log->getSteplog());
                $mymetadata->create();
            }

            if ($this->repo->getPackageType() == 'deb') {
                $mymetadata = new \Controllers\Repo\Operation\Metadata\Deb();
                $mymetadata->setPid($this->operation->getPid());
                $mymetadata->setRoot($repoPath);
                $mymetadata->setRepo($this->repo->getName());
                $mymetadata->setDist($this->repo->getDist());
                $mymetadata->setSection($this->repo->getSection());
                $mymetadata->setArch($this->repo->getTargetArch());
                $mymetadata->setGpgResign($this->repo->getTargetGpgResign());
                $mymetadata->setLogfile($this->log->getSteplog());
                $mymetadata->create();
            }
        } catch (Exception $e) {
            $createMetadataError++;
            $createMetadataErrorMsg = $e->getMessage();
        }

        echo '</pre></div>';
        $this->log->steplogWrite();

        /**
         *  If there was error while creating metadata, then delete everything
         */
        if ($createMetadataError != 0) {
            /**
             *  Delete everything to make sure the operation can be relaunched (except if action is 'reconstruct')
             */
            if ($this->operation->getAction() != "reconstruct") {
                if ($this->repo->getPackageType() == 'rpm') {
                    if (!\Controllers\Common::deleteRecursive($repoPath)) {
                        throw new Exception('Repo creation has failed and directory cannot be cleaned: ' . $repoPath);
                    }
                }
                if ($this->repo->getPackageType() == 'deb') {
                    if (!\Controllers\Common::deleteRecursive($repoPath)) {
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

        $this->log->steplogWrite();

        /**
         *  Create symbolic link (environment)
         *  Only if user has specified to point an environment to the created snapshot
         */
        if ($this->operation->getAction() == "new" or $this->operation->getAction() == "update") {
            if (!empty($this->repo->getTargetEnv())) {
                if ($this->repo->getPackageType() == 'rpm') {
                    exec('cd ' . REPOS_DIR . '/ && ln -sfn ' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName() . ' ' . $this->repo->getName() . '_' . $this->repo->getTargetEnv(), $output, $result);
                }
                if ($this->repo->getPackageType() == 'deb') {
                    exec('cd ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/ && ln -sfn ' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getSection() . ' ' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv(), $output, $result);
                }
                if ($result != 0) {
                    throw new Exception('Repo finalization has failed');
                }
            }
        }

        $this->log->stepOK();

        return true;
    }
}
