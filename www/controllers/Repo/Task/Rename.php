<?php

namespace Controllers\Repo\Task;

use \Controllers\Filesystem\Directory;
use Exception;

class Rename extends \Controllers\Task\Execution
{
    use \Controllers\Repo\Metadata\Create;

    public function __construct(string $taskId)
    {
        parent::__construct($taskId, 'rename');

        // Get source repository details from its snapshot Id
        $this->sourceRepoController->getAllById(null, $this->params['snap-id'], null);

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
     *  Rename repository
     */
    public function execute()
    {
        $this->taskLogStepController->new('renaming', 'RENAMING');
        $this->taskLogSubStepController->new('renaming-directory', 'RENAMING REPOSITORY DIRECTORY');

        // Check if source repo snapshot exists
        if ($this->sourceRepoController->existsSnapId($this->sourceRepoController->getSnapId()) === false) {
            throw new Exception('Source repository snapshot does not exist');
        }

        // Check if a repo with the same name already exists
        if ($this->repoController->getPackageType() == 'rpm') {
            if ($this->rpmRepoController->isActive($this->repoController->getName(), $this->repoController->getReleasever())) {
                throw new Exception('<span class="label-black">' . $this->repoController->getName() . ' (release ver. ' . $this->repoController->getReleasever() . ')</span> repository already exists');
            }
        }
        if ($this->repoController->getPackageType() == 'deb') {
            if ($this->debRepoController->isActive($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection())) {
                throw new Exception('<span class="label-black">' . $this->repoController->getName() . ' ❯ ' . $this->repoController->getDist() . ' ❯ ' . $this->repoController->getSection() . '</span> repository already exists');
            }
        }

        // Define source snapshot path
        if ($this->sourceRepoController->getPackageType() == 'rpm') {
            $sourcePath = REPOS_DIR . '/rpm/' . $this->sourceRepoController->getName() . '/' . $this->sourceRepoController->getReleasever();
        }
        if ($this->sourceRepoController->getPackageType() == 'deb') {
            $sourcePath = REPOS_DIR . '/deb/' . $this->sourceRepoController->getName() . '/' . $this->sourceRepoController->getDist() . '/' . $this->sourceRepoController->getSection();
        }

        // Define target snapshot path
        if ($this->repoController->getPackageType() == 'rpm') {
            $parentDir = REPOS_DIR . '/rpm/' . $this->repoController->getName();
            $targetPath = REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever();
        }
        if ($this->repoController->getPackageType() == 'deb') {
            $parentDir = REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist();
            $targetPath = REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection();
        }

        // Create parent directory if it does not already exists
        if (!is_dir($parentDir)) {
            if (!mkdir($parentDir, 0770, true)) {
                throw new Exception('Cannot create directory ' . $parentDir);
            }
        }

        // Delete target path if it already exists (might happen if the task has been stopped or failed previously)
        if (file_exists($targetPath)) {
            if (!Directory::deleteRecursive($targetPath)) {
                throw new Exception('Cannot remove existing target directory ' . $targetPath);
            }
        }

        // Move source repository to target path
        if (!rename($sourcePath, $targetPath)) {
            throw new Exception('Cannot rename source repository ' . $sourcePath . ' to ' . $targetPath);
        }

        $this->taskLogSubStepController->completed();
        $this->taskLogStepController->completed();

        if ($this->repoController->getPackageType() == 'deb') {
            try {
                // On a deb repo, the renamed repository needs to have its metadata rebuilded
                $this->createMetadata();

            // If an error occurred after repository has been moved, try to revert the move
            } catch (Exception $e) {
                // Try to revert the move
                if (file_exists($targetPath)) {
                    if (!rename($targetPath, $sourcePath)) {
                        throw new Exception('An error occured while creating the repository metadata: ' . $e->getMessage() . '. Additionally, the system could not revert the renamed repository from ' . $targetPath . ' to ' . $sourcePath . '. Manual intervention is required to restore the repository to its original state.');
                    }
                }

                // Throw initial exception to set the task as error
                throw new Exception('An error occured while creating the repository metadata: ' . $e->getMessage(). ' The renamed repository has been reverted to its original state. Please check that everything is fine before retrying the rename operation.');
            }
        }

        try {
            $this->taskLogStepController->new('finalizing', 'FINALIZING');
            $this->taskLogSubStepController->new('renaming-in-database', 'RENAMING IN DATABASE');

            // Update source repository name in database
            $this->sourceRepoController->updateName($this->sourceRepoController->getRepoId(), $this->repoController->getName());

            // Delete the old parent directories if they are empty
            if ($this->sourceRepoController->getPackageType() == 'rpm') {
                Directory::deleteIfEmpty([
                    REPOS_DIR . '/rpm/' . $this->sourceRepoController->getName() . '/' . $this->sourceRepoController->getReleasever(),
                    REPOS_DIR . '/rpm/' . $this->sourceRepoController->getName()
                ]);
            }

            if ($this->sourceRepoController->getPackageType() == 'deb') {
                Directory::deleteIfEmpty([
                    REPOS_DIR . '/deb/' . $this->sourceRepoController->getName() . '/' . $this->sourceRepoController->getDist() . '/' . $this->sourceRepoController->getSection(),
                    REPOS_DIR . '/deb/' . $this->sourceRepoController->getName() . '/' . $this->sourceRepoController->getDist(),
                    REPOS_DIR . '/deb/' . $this->sourceRepoController->getName()
                ]);
            }

            $this->taskLogSubStepController->completed();
            $this->taskLogStepController->completed();
        } catch (Exception $e) {
            throw new Exception('An error occured while renaming the repository in database: ' . $e->getMessage());
        }
    }
}
