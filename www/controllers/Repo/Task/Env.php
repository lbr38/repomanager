<?php

namespace Controllers\Repo\Task;

use Exception;
use \Controllers\Utils\Generate\Html\Label;

class Env extends \Controllers\Task\Execution
{
    public function __construct(string $taskId)
    {
        parent::__construct($taskId, 'env');

        // Execute the task
        try {
            $this->execute();
        } catch (Exception $e) {
            $this->status = 'error';
            $this->error = $e->getMessage();
        }

        // End the task
        $this->end();
    }

    /**
     *  Point an environment to a snapshot
     */
    public function execute()
    {
        foreach ($this->repoController->getEnv() as $env) {
            $actualDescription = null;

            /**
             *  Define snapshot directory path
             */
            if ($this->repoController->getPackageType() == 'rpm') {
                $snapshotPath = REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $this->repoController->getDate();
            }
            if ($this->repoController->getPackageType() == 'deb') {
                $snapshotPath = REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $this->repoController->getDate();
            }

            $this->taskLogStepController->new('point-env-' . $env, 'POINT ENVIRONMENT ' . Label::envtag($env));
            $this->taskLogSubStepController->new('checking-' . $env, 'CHECKING');

            /**
             *  Check if the source snapshot exists
             */
            if (!$this->repoController->existsSnapId($this->repoController->getSnapId())) {
                throw new Exception('<span class="label-black">' . $this->repoController->getDateFormatted() . '</span> repository snapshot does not exist anymore');
            }

            /**
             *  Check if an environment of the same name already exists on the target snapshot
             */
            if ($this->repoController->existsSnapIdEnv($this->repoController->getSnapId(), $env) === true) {
                if ($this->repoController->getPackageType() == 'rpm') {
                    throw new Exception(Label::envtag($env) . ' environment already exists on <span class="label-white">' . $this->repoController->getName() . '</span>⸺<span class="label-black">' . $this->repoController->getDateFormatted() . '</span>');
                }

                if ($this->repoController->getPackageType() == 'deb') {
                    throw new Exception(Label::envtag($env) . ' environment already exists on <span class="label-white">' . $this->repoController->getName() . ' ❯ ' . $this->repoController->getDist() . ' ❯ ' . $this->repoController->getSection() . '</span>⸺<span class="label-black">' . $this->repoController->getDateFormatted() . '</span>');
                }
            }

            /**
             *  If the user did not specify any description then we get the one currently in place on the environment of the same name (if the environment exists and if it has a description)
             */
            if (empty($this->repoController->getDescription())) {
                if ($this->repoController->getPackageType() == 'rpm') {
                    $actualDescription = $this->rpmRepoController->getDescriptionByName($this->repoController->getName(), $this->repoController->getReleasever(), $env);
                }
                if ($this->repoController->getPackageType() == 'deb') {
                    $actualDescription = $this->debRepoController->getDescriptionByName($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection(), $env);
                }

                /**
                 *  If the description is empty then the description will remain empty
                 */
                if (!empty($actualDescription)) {
                    $this->repoController->setDescription(htmlspecialchars_decode($actualDescription));
                } else {
                    $this->repoController->setDescription('');
                }
            }

            $this->taskLogSubStepController->completed();
            $this->taskLogSubStepController->new('create-symlink-' . $env, 'CREATING SYMLINK');

            if ($this->repoController->getPackageType() == 'rpm') {
                /**
                 *  If there is already an environment of the same name pointing to a snapshot.
                 */
                if ($this->rpmRepoController->existsEnv($this->repoController->getName(), $this->repoController->getReleasever(), $env)) {
                    /**
                     *  Retrieve the Id of the already existing environment
                     */
                    $actualEnvIds = $this->rpmRepoController->getEnvIdFromRepoName($this->repoController->getName(), $this->repoController->getReleasever(), $env);

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
                if (is_link(REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $env)) {
                    if (!unlink(REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $env)) {
                        throw new Exception('Could not delete existing symbolic link: ' . REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $env);
                    }
                }

                /**
                 *  Create symbolic link
                 */
                if (!symlink($this->repoController->getDate(), REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $env)) {
                    throw new Exception('Could not create symbolic link: ' . REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $env);
                }
            }

            if ($this->repoController->getPackageType() == 'deb') {
                /**
                 *  If there is already an environment of the same name pointing to a snapshot.
                 */
                if ($this->debRepoController->existsEnv($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection(), $env)) {
                    /**
                     *  Retrieve the Id of the already existing environment
                     */
                    $actualEnvIds = $this->debRepoController->getEnvIdFromRepoName($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection(), $env);

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
                if (is_link(REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $env)) {
                    if (!unlink(REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $env)) {
                        throw new Exception('Could not delete existing symbolic link: ' . REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $env);
                    }
                }

                /**
                 *  Create symbolic link
                 */
                if (!symlink($this->repoController->getDate(), REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $env)) {
                    throw new Exception('Could not create symbolic link: ' . REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $env);
                }
            }

            $this->taskLogSubStepController->completed();

            /**
             *  Add environment to database
             */
            $this->taskLogSubStepController->new('update-database', 'UPDATING DATABASE');
            $this->repoEnvController->add($env, $this->repoController->getDescription(), $this->repoController->getSnapId());
            $this->taskLogSubStepController->completed();
            $this->taskLogStepController->completed();
        }

        $this->taskLogStepController->new('clean', 'CLEANING');

        /**
         *  Clean unused repos in groups
         */
        $this->repoController->cleanGroups();

        /**
         *  Clean unused snapshots
         */
        try {
            $snapshotsRemoved = $this->repoSnapshotController->clean();
            $this->taskLogStepController->completed($snapshotsRemoved);
        } catch (Exception $e) {
            $this->taskLogStepController->error($e->getMessage());
        }
    }
}
