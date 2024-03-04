<?php

namespace Controllers\Task\Repo;

use Exception;

class Env
{
    use \Controllers\Task\Param;

    private $repo;
    private $task;
    private $taskLog;

    public function __construct(string $taskId)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->task = new \Controllers\Task\Task();
        $this->taskLog = new \Controllers\Task\Log($taskId);

        /**
         *  Retrieve task params
         */
        $task = $this->task->getById($taskId);
        $taskParams = json_decode($task['Raw_params'], true);

        /**
         *  Check snap Id parameter
         */
        $requiredParams = array('snap-id');
        $this->taskParamsCheck('Repo environment', $taskParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $taskParams['snap-id'], null);

        /**
         *  Check and set others task parameters
         */
        $requiredParams = array('env');
        $optionnalParams = array('description');
        $this->taskParamsCheck('Repo environment', $taskParams, $requiredParams);
        $this->taskParamsSet($taskParams, $requiredParams, $optionnalParams);

        /**
         *  Prepare task and task log
         */

        /**
         *  Set task Id
         */
        $this->task->setId($taskId);
        $this->task->setAction('env');

        /**
         *  Generate PID for the task
         */
        $this->task->generatePid();

        /**
         *  Generate log file
         */
        $this->taskLog->generateLog();

        /**
         *  Set PID
         */
        $this->task->updatePid($taskId, $this->task->getPid());

        /**
         *  Set log file location
         */
        $this->task->updateLogfile($taskId, $this->taskLog->getName());

        /**
         *  Start task
         */
        $this->task->start($taskId, 'running');
    }

    /**
     *  Point an environment to a snapshot
     */
    public function execute()
    {
        /**
         *  Launch external script that will build the main log file from the small log files of each step
         */
        $this->taskLog->runLogBuilder($this->task->getId(), $this->taskLog->getLocation());

        try {
            ob_start();

            /**
             *  Generate task summary table
             */
            include(ROOT . '/views/templates/tasks/env.inc.php');

            $this->taskLog->step('ADDING NEW ENVIRONMENT ' . \Controllers\Common::envtag($this->repo->getEnv()));

            /**
             *  Check if the source snapshot exists
             */
            if ($this->repo->existsSnapId($this->repo->getSnapId()) === false) {
                throw new Exception('Repository snapshot does not exist');
            }

            /**
             *  Check if an environment of the same name already exists on the target snapshot
             */
            if ($this->repo->existsSnapIdEnv($this->repo->getSnapId(), $this->repo->getEnv()) === true) {
                if ($this->repo->getPackageType() == 'rpm') {
                    throw new Exception('A ' . \Controllers\Common::envtag($this->repo->getEnv()) . ' environment already exists on <span class="label-white">' . $this->repo->getName() . '</span>⟶<span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
                }

                if ($this->repo->getPackageType() == 'deb') {
                    throw new Exception('A ' . \Controllers\Common::envtag($this->repo->getEnv()) . ' environment already exists on <span class="label-white">' . $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection() . '</span>⟶<span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
                }
            }

            /**
             *  If the user did not specify any description then we get the one currently in place on the environment of the same name (if the environment exists and if it has a description)
             */
            if (empty($this->repo->getDescription())) {
                if ($this->repo->getPackageType() == 'rpm') {
                    $actualDescription = $this->repo->getDescriptionByName($this->repo->getName(), '', '', $this->repo->getEnv());
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $actualDescription = $this->repo->getDescriptionByName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getEnv());
                }

                /**
                 *  If the description is empty then the description will remain empty
                 */
                if (!empty($actualDescription)) {
                    $this->repo->setDescription($actualDescription);
                } else {
                    $this->repo->setDescription('');
                }
            }

            /**
             *  Processing
             *  Two possible cases:
             *   1. This repo/section did not have an environment pointing to the target snapshot, we simply create a symbolic link and create the new environment in the database.
             *   2. This repo/section already had an environment pointing to a snapshot, we delete it and point the environment to the new snapshot.
             */

            /**
             *  RPM
             */
            if ($this->repo->getPackageType() == 'rpm') {
                /**
                 *  Case 1: no environment of the same name exists on this snapshot
                 */
                if ($this->repo->existsEnv($this->repo->getName(), null, null, $this->repo->getEnv()) === false) {
                    /**
                     *  Delete symbolic link (just in case)
                     */
                    if (is_link(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv())) {
                        if (!unlink(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv())) {
                            throw new Exception('Could not delete existing symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv());
                        }
                    }

                    /**
                     *  Create symbolic link
                     */
                    if (!symlink($this->repo->getDateFormatted() . '_' . $this->repo->getName(), REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv())) {
                        throw new Exception('Could not create symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv());
                    }

                    /**
                     *  Add environment to database
                     */
                    $this->repo->addEnv($this->repo->getEnv(), $this->repo->getDescription(), $this->repo->getSnapId());

                    /**
                     *  Close current step
                     */
                    $this->taskLog->stepOK();

                /**
                 *  Case 2: There is already an environment of the same name pointing to a snapshot.
                 */
                } else {
                    /**
                     *  Retrieve the Id of the already existing environment
                     */
                    $actualEnvIds = $this->repo->getEnvIdFromRepoName($this->repo->getName(), null, null, $this->repo->getEnv());

                    /**
                     *  Delete the possible environment of the same name already pointing to a snapshot of this repo (if there is one)
                     */
                    if (!empty($actualEnvIds)) {
                        foreach ($actualEnvIds as $actualEnvId) {
                            $this->repo->removeEnv($actualEnvId['Id']);
                        }
                    }

                    /**
                     *  Delete symbolic link
                     */
                    if (is_link(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv())) {
                        if (!unlink(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv())) {
                            throw new Exception('Could not delete existing symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv());
                        }
                    }

                    /**
                     *  Create new symbolic link, pointing to the target snapshot
                     */
                    if (!symlink($this->repo->getDateFormatted() . '_' . $this->repo->getName(), REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv())) {
                        throw new Exception('Could not create symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv());
                    }

                    /**
                     *  Then we declare the new environment and we make it point to the previously created snapshot
                     */
                    $this->repo->addEnv($this->repo->getEnv(), $this->repo->getDescription(), $this->repo->getSnapId());

                    /**
                     *  Close current step
                     */
                    $this->taskLog->stepOK();
                }
            }

            /**
             *  DEB
             */
            if ($this->repo->getPackageType() == 'deb') {
                /**
                 *  Case 1: no environment of the same name exists on this snapshot
                 */
                if ($this->repo->existsEnv($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getEnv()) === false) {
                    /**
                     *  Delete symbolic link (just in case)
                     */
                    if (is_link(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv())) {
                        if (!unlink(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv())) {
                            throw new Exception('Could not delete existing symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv());
                        }
                    }

                    /**
                     *  Create symbolic link
                     */
                    if (!symlink($this->repo->getDateFormatted() . '_' . $this->repo->getSection(), REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv())) {
                        throw new Exception('Could not create symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv());
                    }

                    /**
                     *  Add environment to database
                     */
                    $this->repo->addEnv($this->repo->getEnv(), $this->repo->getDescription(), $this->repo->getSnapId());

                    /**
                     *  Close current step
                     */
                    $this->taskLog->stepOK();

                /**
                 *  Case 2: There is already an environment of the same name pointing to a snapshot.
                 */
                } else {
                    /**
                     *  First we retrieve the Id of the already existing environment because we will need it to modify its linked snapshot in the database.
                     */
                    $actualEnvIds = $this->repo->getEnvIdFromRepoName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getEnv());

                    /**
                     *  Delete the possible environment of the same name already pointing to a snapshot of this repo (if there is one)
                     */
                    if (!empty($actualEnvIds)) {
                        foreach ($actualEnvIds as $actualEnvId) {
                            $this->repo->removeEnv($actualEnvId['Id']);
                        }
                    }

                    /**
                     *  Delete symbolic link
                     */
                    if (is_link(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv())) {
                        if (!unlink(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv())) {
                            throw new Exception('Could not delete existing symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv());
                        }
                    }

                    /**
                     *  Create new symbolic link, pointing to the target snapshot
                     */
                    if (!symlink($this->repo->getDateFormatted() . '_' . $this->repo->getSection(), REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv())) {
                        throw new Exception('Could not create symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv());
                    }

                    /**
                     *  Then we declare the new environment and we make it point to the previously created snapshot
                     */
                    $this->repo->addEnv($this->repo->getEnv(), $this->repo->getDescription(), $this->repo->getSnapId());

                    /**
                     *  Close current step
                     */
                    $this->taskLog->stepOK();
                }
            }

            $this->taskLog->step('FINALIZING');

            /**
             *  Apply permissions on the modified repo/section
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

            /**
             *  Close current step
             */
            $this->taskLog->stepOK();

            /**
             *  Cleaning of unused snapshots
             */
            $snapshotsRemoved = $this->repo->cleanSnapshots();

            if (!empty($snapshotsRemoved)) {
                $this->taskLog->step('CLEANING');
                $this->taskLog->stepOK($snapshotsRemoved);
            }

            /**
             *  Cleaning of unused repos in groups
             */
            $this->repo->cleanGroups();

            /**
             *  Set task status to done
             */
            $this->task->setStatus('done');
            $this->task->updateStatus($this->task->getId(), 'done');
        } catch (\Exception $e) {
            /**
             * Print a red error message in the log file
             */
            $this->taskLog->stepError($e->getMessage());

            /**
             *  Set task status to error
             */
            $this->task->setStatus('error');
            $this->task->updateStatus($this->task->getId(), 'error');
            $this->task->setError($e->getMessage());
        }

        /**
         *  Get total duration
         */
        $duration = $this->task->getDuration();

        /**
         *  End task
         */
        $this->taskLog->stepDuration($duration);
        $this->task->end();
    }
}
