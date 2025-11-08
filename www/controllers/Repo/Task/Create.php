<?php

namespace Controllers\Repo\Task;

use \Controllers\Utils\Generate\Html\Label;
use \Controllers\Filesystem\File;
use Exception;

class Create extends \Controllers\Task\Execution
{
    use \Controllers\Repo\Package\Sync;
    use \Controllers\Repo\Package\Sign;
    use \Controllers\Repo\Metadata\Create;
    use Finalize;

    private $type;
    private $packagesToSign = null;

    public function __construct(string $taskId)
    {
        parent::__construct($taskId, 'create');

        // Set parameters in case the task is a local repo
        if ($this->params['repo-type'] == 'local') {
            $this->repoController->setName($this->params['alias']);
        }

        // Set parameters in case the task is a mirror repo
        if ($this->params['repo-type'] == 'mirror') {
            // Alias parameter can be empty, if it's the case, the value will be 'source'
            if (!empty($this->params['alias'])) {
                $this->repoController->setName($this->params['alias']);
            } else {
                $this->repoController->setName($this->repoController->getSource());
            }
        }

        // Set repo type for the task to be executed
        $this->type = $this->params['repo-type'];

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
        $this->repoController->setDate(date('Y-m-d'));
        $this->repoController->setTime(date('H:i'));

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
    }

    /**
     *  Create a local repository
     */
    private function local()
    {
        /**
         *  Set today date and time as target date and time
         */
        $this->repoController->setDate(date('Y-m-d'));
        $this->repoController->setTime(date('H:i'));

        $this->taskLogStepController->new('create-repo', 'CREATING REPOSITORY');
        $this->taskLogSubStepController->new('create-dirs', 'CREATING DIRECTORIES');

        /**
         *  Check if a repo/section with the same name is already active with snapshots
         */
        if ($this->repoController->getPackageType() == 'rpm') {
            if ($this->rpmRepoController->isActive($this->repoController->getName(), $this->repoController->getReleasever())) {
                throw new Exception(Label::white($this->repoController->getName() . ' ❯ ' . $this->repoController->getReleasever()) . ' repository already exists');
            }
        }
        if ($this->repoController->getPackageType() == 'deb') {
            if ($this->debRepoController->isActive($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection())) {
                throw new Exception(Label::white($this->repoController->getName() . ' ❯ ' . $this->repoController->getDist() . ' ❯ ' . $this->repoController->getSection()) . ' repository already exists');
            }
        }

        /**
         *  Define snapshot directory path
         */
        if ($this->repoController->getPackageType() == 'rpm') {
            $snapshotPath = REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $this->repoController->getDate();
        }
        if ($this->repoController->getPackageType() == 'deb') {
            $snapshotPath = REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $this->repoController->getDate();
        }

        /**
         *  Create snapshot directory and subdirectories
         */
        if ($this->repoController->getPackageType() == 'rpm') {
            if (!is_dir($snapshotPath . '/packages')) {
                if (!mkdir($snapshotPath . '/packages', 0770, true)) {
                    throw new Exception('Could not create directory ' . $snapshotPath . '/packages');
                }
            }
        }
        if ($this->repoController->getPackageType() == 'deb') {
            if (!is_dir($snapshotPath . '/pool/' . $this->repoController->getSection())) {
                if (!mkdir($snapshotPath . '/pool/' . $this->repoController->getSection(), 0770, true)) {
                    throw new Exception('Could not create directory ' . $snapshotPath . '/pool/' . $this->repoController->getSection());
                }
            }
        }

        /**
         *  Create environment symlink, if an environment has been specified
         */
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

        $this->taskLogSubStepController->completed();

        $this->taskLogSubStepController->new('updating-database', 'UPDATING DATABASE');

        /**
         *  Check if repository already exists in database
         */
        if ($this->repoController->getPackageType() == 'rpm') {
            $exists = $this->rpmRepoController->exists($this->repoController->getName(), $this->repoController->getReleasever());
        }
        if ($this->repoController->getPackageType() == 'deb') {
            $exists = $this->debRepoController->exists($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection());
        }

        /**
         *  If no repo of this name exists in database then we add it
         *  Note: here we set the source as $this->repoController->getName()
         */
        if ($exists === false) {
            /**
             *  Retrieve repo Id from the last insert row
             */
            if ($this->repoController->getPackageType() == 'rpm') {
                $this->rpmRepoController->add($this->repoController->getName(), $this->repoController->getReleasever(), $this->repoController->getName());
                $this->repoController->setRepoId($this->rpmRepoController->getLastInsertRowID());
            }
            if ($this->repoController->getPackageType() == 'deb') {
                $this->debRepoController->add($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection(), $this->repoController->getName());
                $this->repoController->setRepoId($this->debRepoController->getLastInsertRowID());
            }

        /**
         *  Else if a repo of this name exists, we attach this new snapshot and this new env to this repo
         */
        } else {
            /**
             *  Retrieve and set repo Id from database
             */
            if ($this->repoController->getPackageType() == 'rpm') {
                $repoId = $this->rpmRepoController->getIdByNameReleasever($this->repoController->getName(), $this->repoController->getReleasever());
            }

            if ($this->repoController->getPackageType() == 'deb') {
                $repoId = $this->debRepoController->getIdByNameDistComponent($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection());
            }

            if (empty($repoId)) {
                throw new Exception('Could not retrieve repository Id from database');
            }

            $this->repoController->setRepoId($repoId);
        }

        unset($exists, $repoId);

        /**
         *  Add snapshot to database
         */
        $this->repoSnapshotController->add($this->repoController->getDate(), $this->repoController->getTime(), 'false', $this->repoController->getArch(), array(), array(), array(), $this->repoController->getType(), 'active', $this->repoController->getRepoId());

        /**
         *  Retrieve snapshot Id from the last insert row
         */
        $this->repoController->setSnapId($this->repoSnapshotController->getLastInsertRowID());

        /**
         *  Add env to database if an env has been specified by the user
         */
        if (!empty($this->repoController->getEnv())) {
            foreach ($this->repoController->getEnv() as $env) {
                $this->repoEnvController->add($env, $this->repoController->getDescription(), $this->repoController->getSnapId());
            }
        }

        $this->taskLogSubStepController->completed();

        $this->taskLogSubStepController->new('applying-permissions', 'APPLYING PERMISSIONS');

        /**
         *  Apply permissions on the new repo
         */
        File::recursiveChmod($snapshotPath, 'dir', 770);
        File::recursiveChmod($snapshotPath, 'file', 660);

        $this->taskLogSubStepController->completed();

        /**
         *  Add repo to group if a group has been specified
         */
        if (!empty($this->repoController->getGroup())) {
            $this->taskLogStepController->new('add-to-group', 'ADDING REPOSITORY TO GROUP');
            $this->repoController->addRepoIdToGroup($this->repoController->getRepoId(), $this->repoController->getGroup());
            $this->taskLogStepController->completed();
        }

        $this->taskLogSubStepController->new('cleaning', 'CLEANING');

        /**
         *  Clean unused repos in groups
         */
        $this->repoController->cleanGroups();

        $this->taskLogSubStepController->completed();
        $this->taskLogStepController->completed();
    }
}
