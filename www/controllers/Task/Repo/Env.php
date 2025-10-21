<?php

namespace Controllers\Task\Repo;

use Exception;
use \Controllers\Utils\Generate\Html\Label;

class Env
{
    use \Controllers\Task\Param;

    private $repo;
    private $task;
    private $rpmRepoController;
    private $debRepoController;
    private $repoSnapshotController;
    private $repoEnvController;
    private $taskLogStepController;
    private $taskLogSubStepController;

    public function __construct(string $taskId)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->task = new \Controllers\Task\Task();
        $this->rpmRepoController = new \Controllers\Repo\Rpm();
        $this->debRepoController = new \Controllers\Repo\Deb();
        $this->repoSnapshotController = new \Controllers\Repo\Snapshot();
        $this->repoEnvController = new \Controllers\Repo\Environment();
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
        $this->taskParamsCheck('Point environment on repository', $taskParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $taskParams['snap-id'], null);

        /**
         *  Check and set others task parameters
         */
        $requiredParams = array('env');
        $optionalParams = array('description');
        $this->taskParamsSet($taskParams, $requiredParams, $optionalParams);

        /**
         *  Prepare task and task log
         */

        /**
         *  Set task Id
         */
        $this->task->setId($taskId);
        $this->task->setAction('env');

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
     *  Point an environment to a snapshot
     */
    public function execute()
    {
        try {
            foreach ($this->repo->getEnv() as $env) {
                $actualDescription = null;

                /**
                 *  Define snapshot directory path
                 */
                if ($this->repo->getPackageType() == 'rpm') {
                    $snapshotPath = REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever() . '/' . $this->repo->getDate();
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $snapshotPath = REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '/' . $this->repo->getDate();
                }

                $this->taskLogStepController->new('point-env-' . $env, 'POINT ENVIRONMENT ' . Label::envtag($env));
                $this->taskLogSubStepController->new('checking-' . $env, 'CHECKING');

                /**
                 *  Check if the source snapshot exists
                 */
                if (!$this->repo->existsSnapId($this->repo->getSnapId())) {
                    throw new Exception('<span class="label-black">' . $this->repo->getDateFormatted() . '</span> repository snapshot does not exist anymore');
                }

                /**
                 *  Check if an environment of the same name already exists on the target snapshot
                 */
                if ($this->repo->existsSnapIdEnv($this->repo->getSnapId(), $env) === true) {
                    if ($this->repo->getPackageType() == 'rpm') {
                        throw new Exception(Label::envtag($env) . ' environment already exists on <span class="label-white">' . $this->repo->getName() . '</span>⸺<span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
                    }

                    if ($this->repo->getPackageType() == 'deb') {
                        throw new Exception(Label::envtag($env) . ' environment already exists on <span class="label-white">' . $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection() . '</span>⸺<span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
                    }
                }

                /**
                 *  If the user did not specify any description then we get the one currently in place on the environment of the same name (if the environment exists and if it has a description)
                 */
                if (empty($this->repo->getDescription())) {
                    if ($this->repo->getPackageType() == 'rpm') {
                        $actualDescription = $this->rpmRepoController->getDescriptionByName($this->repo->getName(), $this->repo->getReleasever(), $env);
                    }
                    if ($this->repo->getPackageType() == 'deb') {
                        $actualDescription = $this->debRepoController->getDescriptionByName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $env);
                    }

                    /**
                     *  If the description is empty then the description will remain empty
                     */
                    if (!empty($actualDescription)) {
                        $this->repo->setDescription(htmlspecialchars_decode($actualDescription));
                    } else {
                        $this->repo->setDescription('');
                    }
                }

                $this->taskLogSubStepController->completed();
                $this->taskLogSubStepController->new('create-symlink-' . $env, 'CREATING SYMLINK');

                if ($this->repo->getPackageType() == 'rpm') {
                    /**
                     *  If there is already an environment of the same name pointing to a snapshot.
                     */
                    if ($this->rpmRepoController->existsEnv($this->repo->getName(), $this->repo->getReleasever(), $env)) {
                        /**
                         *  Retrieve the Id of the already existing environment
                         */
                        $actualEnvIds = $this->rpmRepoController->getEnvIdFromRepoName($this->repo->getName(), $this->repo->getReleasever(), $env);

                        /**
                         *  Delete the possible environment of the same name already pointing to a snapshot of this repo (if there is one)
                         */
                        if (!empty($actualEnvIds)) {
                            foreach ($actualEnvIds as $actualEnvId) {
                                $this->repoEnvController->remove($actualEnvId);
                            }
                        }
                    }

                    /**
                     *  Delete symbolic link if already exists
                     */
                    if (is_link(REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever() . '/' . $env)) {
                        if (!unlink(REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever() . '/' . $env)) {
                            throw new Exception('Could not delete existing symbolic link: ' . REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever() . '/' . $env);
                        }
                    }

                    /**
                     *  Create symbolic link
                     */
                    if (!symlink($this->repo->getDate(), REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever() . '/' . $env)) {
                        throw new Exception('Could not create symbolic link: ' . REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever() . '/' . $env);
                    }
                }

                if ($this->repo->getPackageType() == 'deb') {
                    /**
                     *  If there is already an environment of the same name pointing to a snapshot.
                     */
                    if ($this->debRepoController->existsEnv($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $env)) {
                        /**
                         *  Retrieve the Id of the already existing environment
                         */
                        $actualEnvIds = $this->debRepoController->getEnvIdFromRepoName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $env);

                        /**
                         *  Delete the possible environment of the same name already pointing to a snapshot of this repo (if there is one)
                         */
                        if (!empty($actualEnvIds)) {
                            foreach ($actualEnvIds as $actualEnvId) {
                                $this->repoEnvController->remove($actualEnvId);
                            }
                        }
                    }

                    /**
                     *  Delete symbolic link if already exists
                     */
                    if (is_link(REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '/' . $env)) {
                        if (!unlink(REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '/' . $env)) {
                            throw new Exception('Could not delete existing symbolic link: ' . REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '/' . $env);
                        }
                    }

                    /**
                     *  Create symbolic link
                     */
                    if (!symlink($this->repo->getDate(), REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '/' . $env)) {
                        throw new Exception('Could not create symbolic link: ' . REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '/' . $env);
                    }
                }

                $this->taskLogSubStepController->completed();

                /**
                 *  Add environment to database
                 */
                $this->taskLogSubStepController->new('update-database', 'UPDATING DATABASE');
                $this->repoEnvController->add($env, $this->repo->getDescription(), $this->repo->getSnapId());
                $this->taskLogSubStepController->completed();
                $this->taskLogStepController->completed();
            }

            $this->taskLogStepController->new('clean', 'CLEANING');

            /**
             *  Clean unused repos in groups
             */
            $this->repo->cleanGroups();

            /**
             *  Clean unused snapshots
             */
            try {
                $snapshotsRemoved = $this->repoSnapshotController->clean();
                $this->taskLogStepController->completed($snapshotsRemoved);
            } catch (Exception $e) {
                $this->taskLogStepController->error($e->getMessage());
            }

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
        $duration = \Controllers\Utils\Convert::microtimeToHuman($this->task->getDuration());

        /**
         *  End task
         */
        $this->taskLogStepController->new('duration', 'DURATION');
        $this->taskLogStepController->none('Total duration: ' . $duration);
        $this->task->end();
    }
}
