<?php

namespace Controllers\Repo\Operation;

use Exception;

class Create extends Operation
{
    use Package\Sync;
    use Package\Sign;
    use Metadata\Create;
    use Finalize;

    private $type;

    public function __construct(string $poolId, array $operationParams)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->operation = new \Controllers\Operation\Operation();
        $this->log = new \Controllers\Log\OperationLog('repomanager', $this->operation->getPid());

        /**
         *  Check and set operation parameters
         */
        $requiredParams = array('packageType', 'type', 'targetArch');
        $optionnalParams = array('targetEnv', 'targetPackageTranslation', 'targetGroup', 'targetDescription');

        /**
         *  Required parameters in case the repo type is 'rpm'
         */
        if ($operationParams['packageType'] == 'rpm') {
            $requiredParams[] = 'releasever';
        }

        /**
         *  Required parameters in case the repo type is 'deb'
         */
        if ($operationParams['packageType'] == 'deb') {
            $requiredParams[] = 'dist';
            $requiredParams[] = 'section';
        }

        /**
         *  Required parameters in case the operation is a mirror
         */
        if ($operationParams['type'] == 'mirror') {
            $requiredParams[] = 'source';
            $requiredParams[] = 'targetGpgCheck';
            $requiredParams[] = 'targetGpgResign';
        }

        /**
         *  Required parameters in case the operation is a local repo
         */
        if ($operationParams['type'] == 'local') {
            $this->repo->setName($operationParams['alias']);
        }

        $this->operationParamsCheck('Create repo', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams, $optionnalParams);

        if ($operationParams['type'] == 'mirror') {
            /**
             *  Alias parameter can be empty, if it's the case, the value will be 'source'
             */
            if (!empty($operationParams['alias'])) {
                $this->repo->setName($operationParams['alias']);
            } else {
                $this->repo->setName($this->repo->getSource());
            }
        }

        /**
         *  Set operation details
         */
        $this->operation->setAction('new');
        $this->operation->setType('manual');
        $this->operation->setPoolId($poolId);
        $this->operation->setRepoName($this->repo->getName());
        if ($this->repo->getPackageType() == 'deb') {
            $this->operation->setRepoName($this->repo->getName() . '|' . $this->repo->getDist() . '|' . $this->repo->getSection());
        }
        if ($operationParams['type'] == 'mirror') {
            $this->operation->setGpgCheck($this->repo->getTargetGpgCheck());
            $this->operation->setGpgResign($this->repo->getTargetGpgResign());
        }
        $this->operation->setLogfile($this->log->getName());
        $this->operation->start();

        /**
         *  Run the operation
         */
        if ($operationParams['type'] == 'mirror') {
            $this->type = 'mirror';
        }
        if ($operationParams['type'] == 'local') {
            $this->type = 'local';
        }
    }

    /**
     *  Create repository
     */
    public function execute()
    {
        if ($this->type == 'mirror') {
            $this->mirror();
        }

        if ($this->type == 'local') {
            $this->local();
        }
    }

    /**
     *  Create a mirror repository
     */
    private function mirror()
    {
        /**
         *  Define the date and time of the new mirror snapshot
         */
        $this->repo->setTargetDate(date('Y-m-d'));
        $this->repo->setTargetTime(date('H:i'));

        /**
         *  Clear cache
         */
        \Controllers\App\Cache::clear();

        /**
         *  Run the external script that will build the main log file from the small log files of each step
         */
        $this->log->runLogBuilder($this->operation->getPid(), $this->log->getLocation());

        try {
            /**
             *  Print operation details
             */
            $this->printDetails('CREATE A NEW ' . strtoupper($this->repo->getPackageType()) . ' REPOSITORY MIRROR');

            /**
             *  Sync packages
             */
            $this->syncPackage();

            /**
             *  Sign repository / packages
             */
            $this->signPackage();

            /**
             *  Create repository and symlinks
             */
            $this->createMetadata();

            /**
             *  Finalize repository (add to database and set permissions)
             */
            $this->finalize();

            /**
             *  Set operation status to 'done'
             */
            $this->operation->setStatus('done');
        } catch (\Exception $e) {
            $this->log->stepError($e->getMessage());

            /**
             *  Set operation status to 'error'
             */
            $this->operation->setStatus('error');
            $this->operation->setError($e->getMessage());
        }

        /**
         *  Get total duration
         */
        $duration = $this->operation->getDuration();

        /**
         *  Close operation
         */
        $this->log->stepDuration($duration);
        $this->operation->close();
    }

    /**
     *  Create a local repository
     */
    private function local()
    {
        /**
         *  Set today date and time as target date and time
         */
        $this->repo->setTargetDate(date('Y-m-d'));
        $this->repo->setTargetTime(date("H:i"));

        /**
         *  Clear cache
         */
        \Controllers\App\Cache::clear();

        /**
         *  Launch the external script that will build the main log file from the small log files of each step
         */
        $this->log->runLogBuilder($this->operation->getPid(), $this->log->getLocation());

        try {
            ob_start();

            /**
             *  Generate operation summary table
             */
            include(ROOT . '/templates/tables/op-new-local.inc.php');

            $this->log->step('CREATING REPO');

            /**
             *  Create repo directory and subdirectories
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if (!is_dir(REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName() . '/packages')) {
                    if (!mkdir(REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName() . '/packages', 0770, true)) {
                        throw new Exception('Could not create directory ' . REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName() . '/packages');
                    }
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if (!is_dir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getSection() . '/pool/' . $this->repo->getSection())) {
                    if (!mkdir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getSection() . '/pool/' . $this->repo->getSection(), 0770, true)) {
                        throw new Exception('Could not create directory ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getSection() . '/pool/' . $this->repo->getSection());
                    }
                }
            }

            /**
             *  Create environment symlink, if an environment has been specified
             */
            if (!empty($this->repo->getTargetEnv())) {
                if ($this->repo->getPackageType() == 'rpm') {
                    $targetFile = $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName();
                    $link = REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getTargetEnv();
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $targetFile = $this->repo->getTargetDateFormatted() . '_' . $this->repo->getSection();
                    $link = REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv();
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

            /**
             *  Check if repository exists in database
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $exists = $this->repo->exists($this->repo->getName());
            }
            if ($this->repo->getPackageType() == 'deb') {
                $exists = $this->repo->exists($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection());
            }

            /**
             *  If no repo of this name exists in database then we add it
             *  Note: here we set the source as $this->repo->getName()
             */
            if ($exists === false) {
                if ($this->repo->getPackageType() == 'rpm') {
                    $this->repo->add($this->repo->getName(), 'rpm', $this->repo->getName());
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $this->repo->add($this->repo->getName(), 'deb', $this->repo->getName());
                }

                /**
                 *  Retrieve repo Id from the last insert row
                 */
                $this->repo->setRepoId($this->repo->getLastInsertRowID());

                /**
                 *  Set repo releasever
                 */
                if ($this->repo->getPackageType() == 'rpm') {
                    $this->repo->updateReleasever($this->repo->getRepoId(), $this->repo->getReleasever());
                }

                /**
                 *  Set repo dist and section
                 */
                if ($this->repo->getPackageType() == 'deb') {
                    $this->repo->updateDist($this->repo->getRepoId(), $this->repo->getDist());
                    $this->repo->updateSection($this->repo->getRepoId(), $this->repo->getSection());
                }

            /**
             *  Else if a repo of this name exists, we attach this new snapshot and this new env to this repo
             */
            } else {
                /**
                 *  Retrieve and set repo Id from database
                 */
                if ($this->repo->getPackageType() == 'rpm') {
                    $this->repo->setRepoId($this->repo->getIdByName($this->repo->getName(), '', ''));
                }

                if ($this->repo->getPackageType() == 'deb') {
                    $this->repo->setRepoId($this->repo->getIdByName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection()));
                }
            }

            unset($exists);

            /**
             *  Add snapshot to database
             */
            $this->repo->addSnap($this->repo->getTargetDate(), $this->repo->getTargetTime(), 'no', $this->repo->getTargetArch(), $this->repo->getTargetPackageTranslation(), $this->repo->getType(), 'active', $this->repo->getRepoId());

            /**
             *  Retrieve snapshot Id from the last insert row
             */
            $this->repo->setSnapId($this->repo->getLastInsertRowID());

            /**
             *  Add env to database if an env has been specified by the user
             */
            if (!empty($this->repo->getTargetEnv())) {
                $this->repo->addEnv($this->repo->getTargetEnv(), $this->repo->getTargetDescription(), $this->repo->getSnapId());
            }

            /**
             *  Apply permissions on the new repo
             */
            if ($this->repo->getPackageType() == 'rpm') {
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName(), 'dir', 770);
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName(), 'file', 660);
                \Controllers\Filesystem\File::recursiveChown(REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName(), WWW_USER, 'repomanager');
            }
            if ($this->repo->getPackageType() == 'deb') {
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getName(), 'dir', 770);
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getName(), 'file', 660);
                \Controllers\Filesystem\File::recursiveChown(REPOS_DIR . '/' . $this->repo->getName(), WWW_USER, 'repomanager');
            }

            $this->log->stepOK();

            /**
             *  Add repo to group if a group has been specified
             */
            if (!empty($this->repo->getTargetGroup())) {
                $this->log->step('ADDING TO GROUP');
                $this->repo->addRepoIdToGroup($this->repo->getRepoId(), $this->repo->getTargetGroup());
                $this->log->stepOK();
            }

            /**
             *  Clean unused repos in groups
             */
            $this->repo->cleanGroups();

            /**
             *  Set operation status to 'done'
             */
            $this->operation->setStatus('done');
        } catch (\Exception $e) {
            /**
             *  Print a red error message in the log file
             */
            $this->log->stepError($e->getMessage());

            /**
             *  Set operation status to 'error'
             */
            $this->operation->setStatus('error');
            $this->operation->setError($e->getMessage());
        }

        /**
         *  Get total duration
         */
        $duration = $this->operation->getDuration();

        /**
         *  Close operation
         */
        $this->log->stepDuration($duration);
        $this->operation->close();
    }
}
