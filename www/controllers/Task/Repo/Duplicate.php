<?php

namespace Controllers\Task\Repo;

use Exception;

class Duplicate
{
    use \Controllers\Task\Param;
    use Metadata\Create;

    private $sourceRepo;
    private $repo;
    private $task;
    private $taskLogStepController;
    private $taskLogSubStepController;

    public function __construct(string $taskId)
    {
        $this->sourceRepo = new \Controllers\Repo\Repo();
        $this->repo = new \Controllers\Repo\Repo();
        $this->task = new \Controllers\Task\Task();
        $this->taskLogStepController = new \Controllers\Task\Log\Step($taskId);
        $this->taskLogSubStepController = new \Controllers\Task\Log\SubStep($taskId);

        /**
         *  Retrieve task params
         */
        $task = $this->task->getById($taskId);
        $taskParams = json_decode($task['Raw_params'], true);

        /**
         *  Check snap Id parameter
         */
        $requiredParams = array('snap-id');
        $this->taskParamsCheck('Duplicate repository snapshot', $taskParams, $requiredParams);

        /**
         *  Getting all source repo details from its snapshot Id
         *  Do the same for the actual repo to herit all source repo parameters
         */
        $this->sourceRepo->getAllById(null, $taskParams['snap-id'], null);
        $this->repo->getAllById(null, $taskParams['snap-id'], null);

        /**
         *  Set additionnal params from the actual repo to duplicate
         */
        $taskParams['gpg-sign'] = $this->sourceRepo->getSigned();
        $taskParams['arch'] = $this->sourceRepo->getArch();

        /**
         *  Repo override some parameters defined by the user
         */

        /**
         *  Check and set others task parameters
         */
        $requiredParams = array('name', 'gpg-sign', 'arch');
        $optionalParams = array('group', 'description', 'env');

        $this->taskParamsCheck('Duplicate repository', $taskParams, $requiredParams);
        $this->taskParamsSet($taskParams, $requiredParams, $optionalParams);

        /**
         *  Prepare task and task log
         */

        /**
         *  Set task Id
         */
        $this->task->setId($taskId);
        $this->task->setAction('duplicate');

        /**
         *  Start task
         */
        $this->task->setDate(date('Y-m-d'));
        $this->task->setTime(date('H:i:s'));
        $this->task->updateDate($taskId, $this->task->getDate());
        $this->task->updateTime($taskId, $this->task->getTime());
        $this->task->start();
    }

    /**
     *  Duplicate repo
     */
    public function execute()
    {
        try {
            $this->taskLogStepController->new('duplicating', 'DUPLICATING');

            /**
             *  Check if source repo snapshot exists
             */
            if ($this->sourceRepo->existsSnapId($this->sourceRepo->getSnapId()) === false) {
                throw new Exception('Source repository snapshot does not exist');
            }

            /**
             *  Check if a repo with the same name already exists
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if ($this->repo->isActive($this->repo->getName()) === true) {
                    throw new Exception('A repo <span class="label-black">' . $this->repo->getName() . '</span> already exists');
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if ($this->repo->isActive($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection()) === true) {
                    throw new Exception('A repo <span class="label-black">' . $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection() . '</span> already exists');
                }
            }

            /**
             *  Create the new repo directory with the new repo name
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if (!file_exists(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName())) {
                    if (!mkdir(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName(), 0770, true)) {
                        throw new Exception('Cannot create directory for the new repo <span class="label-black">' . $this->repo->getName() . '</span>');
                    }
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if (!file_exists(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection())) {
                    if (!mkdir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection(), 0770, true)) {
                        throw new Exception('Cannot create directory for the new repo <span class="label-black">' . $this->repo->getName() . '</span>');
                    }
                }
            }

            /**
             *  Copy the repo/section content
             *  The '\' before the cp command is to force the overwrite if a directory with the same name was there
             */
            if ($this->repo->getPackageType() == 'rpm') {
                exec('\cp -r ' . REPOS_DIR . '/' . $this->sourceRepo->getDateFormatted() . '_' . $this->sourceRepo->getName() . '/* ' . REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName() . '/', $output, $result);
            }
            if ($this->repo->getPackageType() == 'deb') {
                exec('\cp -r ' . REPOS_DIR . '/' . $this->sourceRepo->getName() . '/' . $this->sourceRepo->getDist() . '/' . $this->sourceRepo->getDateFormatted() . '_' . $this->sourceRepo->getSection() . '/* ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . '/', $output, $result);
            }
            if ($result != 0) {
                throw new Exception('Could not copy data from the source repo to the new repo');
            }

            $this->taskLogStepController->completed();

            /**
             *  On a deb repo, the duplicated repo metadata must be rebuilded
             */
            if ($this->repo->getPackageType() == 'deb') {
                $this->createMetadata();
            }

            $this->taskLogStepController->new('finalizing', 'FINALIZING');

            /**
             *  Create a symlink to the new repo, only if the user has specified an environment
             */
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

            /**
             *  Insert the new repo in database
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $this->repo->add($this->repo->getSource(), 'rpm', $this->repo->getName());
            }
            if ($this->repo->getPackageType() == 'deb') {
                $this->repo->add($this->repo->getSource(), 'deb', $this->repo->getName());
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
            $this->repo->addSnap($this->repo->getDate(), $this->repo->getTime(), $this->repo->getSigned(), $this->repo->getArch(), array(), $this->repo->getPackagesToInclude(), $this->repo->getPackagesToExclude(), $this->repo->getType(), $this->repo->getStatus(), $targetRepoId);

            /**
             *  Retrieve the Id of the new repo snapshot in database
             */
            $targetSnapId = $this->repo->getLastInsertRowID();

            /**
             *  Add the new repo environment in database, only if the user has specified an environment
             */
            if (!empty($this->repo->getEnv())) {
                $this->repo->addEnv($this->repo->getEnv(), $this->repo->getDescription(), $targetSnapId);
            }

            /**
             *  Apply permissions on the new repo
             */
            if ($this->repo->getPackageType() == 'rpm') {
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName(), 'file', 660);
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName(), 'dir', 770);
                \Controllers\Filesystem\File::recursiveChown(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName(), WWW_USER, 'repomanager');
            }
            if ($this->repo->getPackageType() == 'deb') {
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection(), 'file', 660);
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection(), 'dir', 770);
                \Controllers\Filesystem\File::recursiveChown(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection(), WWW_USER, 'repomanager');
            }

            $this->taskLogStepController->completed();

            /**
             *  Add the new repo to a group if a group has been specified
             */
            if (!empty($this->repo->getGroup())) {
                $this->taskLogStepController->new('adding-to-group', 'ADDING REPOSITORY TO GROUP');

                /**
                 *  Add the new repo to the specified group
                 */
                $this->repo->addRepoIdToGroup($targetRepoId, $this->repo->getGroup());

                $this->taskLogStepController->completed();
            }

            /**
             *  Clean unused repos in groups
             */
            $this->repo->cleanGroups();

            /**
             *  Set task status to done
             */
            $this->task->setStatus('done');
            $this->task->updateStatus($this->task->getId(), 'done');
        } catch (Exception $e) {
            // Set sub step error message and mark step as error
            $this->taskLogSubStepController->error($e->getMessage());
            $this->taskLogStepController->error();

            // Set task status to error
            $this->task->setStatus('error');
            $this->task->updateStatus($this->task->getId(), 'error');
            $this->task->setError('Failed');
        }

        /**
         *  Get total duration
         */
        $duration = \Controllers\Common::convertMicrotime($this->task->getDuration());

        /**
         *  End task
         */
        $this->taskLogStepController->new('duration', 'DURATION');
        $this->taskLogStepController->none('Total duration: ' . $duration);
        $this->task->end();
    }
}
