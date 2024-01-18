<?php

namespace Controllers\Repo\Operation;

use Exception;

class Env extends Operation
{
    public function __construct(string $poolId, array $operationParams)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->operation = new \Controllers\Operation\Operation();
        $this->log = new \Controllers\Log\OperationLog('repomanager', $this->operation->getPid());

        /**
         *  Check and set snapId parameter
         */
        $requiredParams = array('snapId');
        $this->operationParamsCheck('Repo environment', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $this->repo->getSnapId(), null);

        /**
         *  Check and set others operation parameters
         */
        $requiredParams = array('targetEnv');
        $optionnalParams = array('targetDescription');
        $this->operationParamsCheck('Repo environment', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams, $optionnalParams);

        /**
         *  Set operation details
         */
        $this->operation->setAction('env');
        $this->operation->setType('manual');
        $this->operation->setPoolId($poolId);
        $this->operation->setTargetSnapId($this->repo->getSnapId());
        $this->operation->setTargetEnvId($this->repo->getTargetEnv());
        $this->operation->setLogfile($this->log->getName());
        $this->operation->start();
    }

    /**
     *  Point an environment to a snapshot
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
            include(ROOT . '/templates/tables/op-env.inc.php');

            $this->log->step('ADDING NEW ENVIRONMENT ' . \Controllers\Common::envtag($this->repo->getTargetEnv()));

            /**
             *  Check if the source snapshot exists
             */
            if ($this->repo->existsSnapId($this->repo->getSnapId()) === false) {
                throw new Exception('Target snapshot does not exist');
            }

            /**
             *  Check if an environment of the same name already exists on the target snapshot
             */
            if ($this->repo->existsSnapIdEnv($this->repo->getSnapId(), $this->repo->getTargetEnv()) === true) {
                if ($this->repo->getPackageType() == 'rpm') {
                    throw new Exception('A ' . \Controllers\Common::envtag($this->repo->getTargetEnv()) . ' environment already exists on <span class="label-white">' . $this->repo->getName() . '</span>⟶<span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
                }

                if ($this->repo->getPackageType() == 'deb') {
                    throw new Exception('A ' . \Controllers\Common::envtag($this->repo->getTargetEnv()) . ' environment already exists on <span class="label-white">' . $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection() . '</span>⟶<span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
                }
            }

            /**
             *  If the user did not specify any description then we get the one currently in place on the environment of the same name (if the environment exists and if it has a description)
             */
            if (empty($this->repo->getTargetDescription())) {
                if ($this->repo->getPackageType() == 'rpm') {
                    $actualDescription = $this->repo->getDescriptionByName($this->repo->getName(), '', '', $this->repo->getTargetEnv());
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $actualDescription = $this->repo->getDescriptionByName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getTargetEnv());
                }

                /**
                 *  If the description is empty then the description will remain empty
                 */
                if (!empty($actualDescription)) {
                    $this->repo->setTargetDescription($actualDescription);
                } else {
                    $this->repo->setTargetDescription('');
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
                if ($this->repo->existsEnv($this->repo->getName(), null, null, $this->repo->getTargetEnv()) === false) {
                    /**
                     *  Delete symbolic link (just in case)
                     */
                    if (is_link(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getTargetEnv())) {
                        if (!unlink(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getTargetEnv())) {
                            throw new Exception('Could not delete existing symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getTargetEnv());
                        }
                    }

                    /**
                     *  Create symbolic link
                     */
                    if (!symlink($this->repo->getDateFormatted() . '_' . $this->repo->getName(), REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getTargetEnv())) {
                        throw new Exception('Could not create symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getTargetEnv());
                    }

                    /**
                     *  Add environment to database
                     */
                    $this->repo->addEnv($this->repo->getTargetEnv(), $this->repo->getTargetDescription(), $this->repo->getSnapId());

                    /**
                     *  Close current step
                     */
                    $this->log->stepOK();

                /**
                 *  Case 2: There is already an environment of the same name pointing to a snapshot.
                 */
                } else {
                    /**
                     *  Retrieve the Id of the already existing environment
                     */
                    $actualEnvIds = $this->repo->getEnvIdFromRepoName($this->repo->getName(), null, null, $this->repo->getTargetEnv());

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
                    if (is_link(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getTargetEnv())) {
                        if (!unlink(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getTargetEnv())) {
                            throw new Exception('Could not delete existing symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getTargetEnv());
                        }
                    }

                    /**
                     *  Create new symbolic link, pointing to the target snapshot
                     */
                    if (!symlink($this->repo->getDateFormatted() . '_' . $this->repo->getName(), REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getTargetEnv())) {
                        throw new Exception('Could not create symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getTargetEnv());
                    }

                    /**
                     *  Then we declare the new environment and we make it point to the previously created snapshot
                     */
                    $this->repo->addEnv($this->repo->getTargetEnv(), $this->repo->getTargetDescription(), $this->repo->getSnapId());

                    /**
                     *  Close current step
                     */
                    $this->log->stepOK();
                }
            }

            /**
             *  DEB
             */
            if ($this->repo->getPackageType() == 'deb') {
                /**
                 *  Case 1: no environment of the same name exists on this snapshot
                 */
                if ($this->repo->existsEnv($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getTargetEnv()) === false) {
                    /**
                     *  Delete symbolic link (just in case)
                     */
                    if (is_link(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv())) {
                        if (!unlink(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv())) {
                            throw new Exception('Could not delete existing symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv());
                        }
                    }

                    /**
                     *  Create symbolic link
                     */
                    if (!symlink($this->repo->getDateFormatted() . '_' . $this->repo->getSection(), REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv())) {
                        throw new Exception('Could not create symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv());
                    }

                    /**
                     *  Add environment to database
                     */
                    $this->repo->addEnv($this->repo->getTargetEnv(), $this->repo->getTargetDescription(), $this->repo->getSnapId());

                    /**
                     *  Close current step
                     */
                    $this->log->stepOK();

                /**
                 *  Case 2: There is already an environment of the same name pointing to a snapshot.
                 */
                } else {
                    /**
                     *  First we retrieve the Id of the already existing environment because we will need it to modify its linked snapshot in the database.
                     */
                    $actualEnvIds = $this->repo->getEnvIdFromRepoName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getTargetEnv());

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
                    if (is_link(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv())) {
                        if (!unlink(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv())) {
                            throw new Exception('Could not delete existing symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv());
                        }
                    }

                    /**
                     *  Create new symbolic link, pointing to the target snapshot
                     */
                    if (!symlink($this->repo->getDateFormatted() . '_' . $this->repo->getSection(), REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv())) {
                        throw new Exception('Could not create symbolic link: ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getTargetEnv());
                    }

                    /**
                     *  Then we declare the new environment and we make it point to the previously created snapshot
                     */
                    $this->repo->addEnv($this->repo->getTargetEnv(), $this->repo->getTargetDescription(), $this->repo->getSnapId());

                    /**
                     *  Close current step
                     */
                    $this->log->stepOK();
                }
            }

            $this->log->step('FINALIZING');

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
            $this->log->stepOK();

            /**
             *  Cleaning of unused snapshots
             */
            $snapshotsRemoved = $this->repo->cleanSnapshots();

            if (!empty($snapshotsRemoved)) {
                $this->log->step('CLEANING');
                $this->log->stepOK($snapshotsRemoved);
            }

            /**
             *  Cleaning of unused repos in groups
             */
            $this->repo->cleanGroups();

            /**
             *  Clear cache
             */
            \Controllers\App\Cache::clear();

            /**
             *  Set operation status to done
             */
            $this->operation->setStatus('done');
        } catch (\Exception $e) {
            /**
             * Print a red error message in the log file
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
