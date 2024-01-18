<?php

namespace Controllers\Repo\Operation;

use Exception;

class Duplicate extends Operation
{
    use Metadata\Create;

    public function __construct(string $poolId, array $operationParams)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->operation = new \Controllers\Operation\Operation();
        $this->log = new \Controllers\Log\OperationLog('repomanager', $this->operation->getPid());

        /**
         *  Check and set snapId parameter
         */
        $requiredParams = array('snapId');
        $this->operationParamsCheck('Duplicate repo', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $this->repo->getSnapId(), null);

        /**
         *  Set additionnal params from the actual repo to duplicate
         */
        $operationParams['targetGpgResign'] = $this->repo->getSigned();
        $operationParams['targetArch'] = $this->repo->getArch();
        $operationParams['targetPackageTranslation'] = $this->repo->getPackageTranslation();

        /**
         *  Check and set others operation parameters
         */
        $requiredParams = array('snapId', 'targetName', 'targetGpgResign', 'targetArch');
        $optionnalParams = array('targetGroup', 'targetDescription', 'targetEnv', 'targetPackageTranslation');
        $this->operationParamsCheck('Duplicate repo', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams, $optionnalParams);

        /**
         *  Set operation details
         */
        $this->operation->setAction('duplicate');
        $this->operation->setType('manual');
        $this->operation->setPoolId($poolId);
        $this->operation->setSourceSnapId($this->repo->getSnapId());
        $this->operation->setRepoName($this->repo->getTargetName());
        if ($this->repo->getPackageType() == 'deb') {
            $this->operation->setRepoName($this->repo->getTargetName() . '|' . $this->repo->getDist() . '|' . $this->repo->getSection());
        }
        $this->operation->setLogfile($this->log->getName());
        $this->operation->start();
    }

    /**
     *  Duplicate repo
     */
    public function execute()
    {
        /**
         *  Clear cache
         */
        \Controllers\App\Cache::clear();

        /**
         *  Launch external script that will build the main log file from the small log files of each step
         */
        $this->log->runLogBuilder($this->operation->getPid(), $this->log->getLocation());

        try {
            ob_start();

            /**
             *  Generate operation summary table
             */
            include(ROOT . '/templates/tables/op-duplicate.inc.php');

            $this->log->step('DUPLICATING');

            /**
             *  Check if source repo snapshot exists
             */
            if ($this->repo->existsSnapId($this->repo->getSnapId()) === false) {
                throw new Exception("Source repo snapshot does not exist");
            }

            /**
             *  Check if a repo with the same name already exists
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if ($this->repo->isActive($this->repo->getTargetName()) === true) {
                    throw new Exception('a repo <span class="label-black">' . $this->repo->getTargetName() . '</span> already exists');
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if ($this->repo->isActive($this->repo->getTargetName(), $this->repo->getDist(), $this->repo->getSection()) === true) {
                    throw new Exception('a repo <span class="label-black">' . $this->repo->getTargetName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection() . '</span> already exists');
                }
            }

            /**
             *  Create the new repo directory with the new repo name
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if (!file_exists(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getTargetName())) {
                    if (!mkdir(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getTargetName(), 0770, true)) {
                        throw new Exception("cannot create directory for the new repo <b>" . $this->repo->getTargetName() . "</b>");
                    }
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if (!file_exists(REPOS_DIR . '/' . $this->repo->getTargetName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection())) {
                    if (!mkdir(REPOS_DIR . '/' . $this->repo->getTargetName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection(), 0770, true)) {
                        throw new Exception("cannot create directory for the new repo <b>" . $this->repo->getTargetName() . "</b>");
                    }
                }
            }

            /**
             *  Copy the repo/section content
             *  The '\' before the cp command is to force the overwrite if a directory with the same name was there
             */
            if ($this->repo->getPackageType() == 'rpm') {
                exec('\cp -r ' . REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName() . '/* ' . REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getTargetName() . '/', $output, $result);
            }
            if ($this->repo->getPackageType() == 'deb') {
                exec('\cp -r ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . '/* ' . REPOS_DIR . '/' . $this->repo->getTargetName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . '/', $output, $result);
            }
            if ($result != 0) {
                throw new Exception('cannot copy data from the source repo to the new repo');
            }

            $this->log->stepOK();

            /**
             *  On a deb repo, the duplicated repo metadata must be rebuilded
             */
            if ($this->repo->getPackageType() == 'deb') {
                /**
                 *  For the needs of the createMetadata function, name of the repo to create must be in $name
                 *  Temporary backuping the actual name then replace it with $this->repo->getTargetName()
                 */
                $backupName = $this->repo->getName();
                $this->repo->setName($this->repo->getTargetName());
                $this->repo->setTargetDate($this->repo->getDate());

                $this->createMetadata();

                /**
                 *  Set back the backuped name
                 */
                $this->repo->setName($backupName);
            }

            $this->log->step('FINALIZING');

            /**
             *  Create a symlink to the new repo, only if the user has specified an environment
             */
            if (!empty($this->repo->getTargetEnv())) {
                if ($this->repo->getPackageType() == 'rpm') {
                    $targetFile = $this->repo->getDateFormatted() . '_' . $this->repo->getTargetName();
                    $link = REPOS_DIR . '/' . $this->repo->getTargetName() . '_' . $this->repo->getTargetEnv();
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $targetFile = $this->repo->getDateFormatted() . '_' . $this->repo->getSection();
                    $link = REPOS_DIR . '/' . $this->repo->getTargetName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv();
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
             *  Insert the new repo in database
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $this->repo->add($this->repo->getSource(), 'rpm', $this->repo->getTargetName());
            }
            if ($this->repo->getPackageType() == 'deb') {
                $this->repo->add($this->repo->getSource(), 'deb', $this->repo->getTargetName());
            }

            /**
             *  Retrieve the Id of the new repo in database
             */
            $targetRepoId = $this->repo->getLastInsertRowID();

            if ($this->repo->getPackageType() == 'rpm') {
                /**
                 *  Set repo releasever
                 */
                $this->repo->updateReleasever($targetRepoId, $this->repo->getReleasever());
            }

            if ($this->repo->getPackageType() == 'deb') {
                /**
                 *  Set repo dist and section
                 */
                $this->repo->updateDist($targetRepoId, $this->repo->getDist());
                $this->repo->updateSection($targetRepoId, $this->repo->getSection());
            }

            /**
             *  Add the new repo snapshot in database
             */
            $this->repo->addSnap($this->repo->getDate(), $this->repo->getTime(), $this->repo->getSigned(), $this->repo->getTargetArch(), $this->repo->getTargetPackageTranslation(), $this->repo->getType(), $this->repo->getStatus(), $targetRepoId);

            /**
             *  Retrieve the Id of the new repo snapshot in database
             */
            $targetSnapId = $this->repo->getLastInsertRowID();

            /**
             *  Add the new repo environment in database, only if the user has specified an environment
             */
            if (!empty($this->repo->getTargetEnv())) {
                $this->repo->addEnv($this->repo->getTargetEnv(), $this->repo->getTargetDescription(), $targetSnapId);
            }

            /**
             *  Apply permissions on the new repo
             */
            if ($this->repo->getPackageType() == 'rpm') {
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getTargetName(), 'file', 660);
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getTargetName(), 'dir', 770);
                \Controllers\Filesystem\File::recursiveChown(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getTargetName(), WWW_USER, 'repomanager');
            }
            if ($this->repo->getPackageType() == 'deb') {
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getTargetName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection(), 'file', 660);
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getTargetName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection(), 'dir', 770);
                \Controllers\Filesystem\File::recursiveChown(REPOS_DIR . '/' . $this->repo->getTargetName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection(), WWW_USER, 'repomanager');
            }

            $this->log->stepOK();

            /**
             *  Add the new repo to a group if a group has been specified
             */
            if (!empty($this->repo->getTargetGroup())) {
                $this->log->step('ADDING TO GROUP');

                /**
                 *  Add the new repo to the specified group
                 */
                $this->repo->addRepoIdToGroup($targetRepoId, $this->repo->getTargetGroup());

                $this->log->stepOK();
            }

            /**
             *  Clean unused repos in groups
             */
            $this->repo->cleanGroups();

            /**
             *  Set operation status to done
             */
            $this->operation->setStatus('done');
        } catch (\Exception $e) {
            /**
             *  Print a red error message in the log file
             */
            $this->log->stepError($e->getMessage());

            /**
             *  Set operation status to error
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
