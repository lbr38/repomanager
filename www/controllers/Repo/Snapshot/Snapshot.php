<?php

namespace Controllers\Repo\Snapshot;

use Controllers\Filesystem\Directory;
use Exception;
use DateTime;

class Snapshot
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Repo\Snapshot\Snapshot();
    }

    /**
     *  Return the list of all active snapshots
     */
    public function get(): array
    {
        return $this->model->get();
    }

    /**
     *  Return snapshot details by ID
      */
    public function getById(int $id): array
    {
        return $this->model->getById($id);
    }

    /**
     *  Return snapshot date by ID
     */
    public function getDateById(int $id): string
    {
        if (!$this->exists($id)) {
            throw new Exception('Unknown snapshot ID #' . $id);
        }

        $snapshot = $this->getById($id);

        return $snapshot['Date'];
    }

    /**
     *  Return the list of unused snapshots for the specified repo Id and retention parameter
     */
    private function getUnused(string $repoId, string $retention) : array
    {
        return $this->model->getUnused($repoId, $retention);
    }

    /**
     *  Return the last insert row ID in database
     */
    public function getLastInsertRowID()
    {
        return $this->model->getLastInsertRowID();
    }

    /**
     *  Add a snapshot in database
     */
    public function add(string $date, string $time, string $gpgSignature, array $arch, array $includeTranslation, array $packagesIncluded, array $packagesExcluded, string $type, string $status, int $repoId): void
    {
        $this->model->add($date, $time, $gpgSignature, $arch, $includeTranslation, $packagesIncluded, $packagesExcluded, $type, $status, $repoId);
    }

    /**
     *  Update snapshot date in the database
     */
    public function updateDate(int $snapId, string $date): void
    {
        $this->model->updateDate($snapId, $date);
    }

    /**
     *  Update snapshot time in the database
     */
    public function updateTime(int $snapId, string $time): void
    {
        $this->model->updateTime($snapId, $time);
    }

    /**
     *  Update snapshot GPG signature in the database
     */
    public function updateGpgSignature(int $snapId, string $gpgSignature): void
    {
        $this->model->updateGpgSignature($snapId, $gpgSignature);
    }

    /**
     *  Update snapshot included packages in the database
     */
    public function updatePackagesIncluded(int $snapId, array $packagesIncluded): void
    {
        $this->model->updatePackagesIncluded($snapId, implode(',', $packagesIncluded));
    }

    /**
     *  Update snapshot excluded packages in the database
     */
    public function updatePackagesExcluded(int $snapId, array $packagesExcluded): void
    {
        $this->model->updatePackagesExcluded($snapId, implode(',', $packagesExcluded));
    }

    /**
     *  Update snapshot status in the database
     */
    public function updateStatus(int $snapId, string $status): void
    {
        $this->model->updateStatus($snapId, $status);
    }

    /**
     *  Update snapshot rebuild status in the database
     */
    public function updateRebuild(int $snapId, string $status): void
    {
        $this->model->updateRebuild($snapId, $status);
    }

    /**
     *  Update snapshot architectures in the database
     */
    public function updateArch(int $snapId, array $arch): void
    {
        $this->model->updateArch($snapId, $arch);
    }

    /**
     *  Clean unused snapshots and return a message
     */
    public function clean(int $repoId) : string
    {
        $returnOutput = '';
        $removedSnaps = [];
        $removedSnapsError = [];
        $removedSnapsFinalArray = [];
        $repoController = new \Controllers\Repo\Repo();
        $scheduledTaskController = new \Controllers\Task\Scheduled();

        // Get repository details
        $repoController->getAllById($repoId);
        $repoName       = $repoController->getName();
        $packageType    = $repoController->getPackageType();

        if ($packageType == 'deb') {
            $repoDist       = $repoController->getDist();
            $repoSection    = $repoController->getSection();
        }

        if ($packageType == 'rpm') {
            $repoReleasever = $repoController->getReleasever();
        }

        // Get the list of unused snapshots for this repository
        $unusedSnapshots = $this->getUnused($repoId, RETENTION);

        // Process each snapshots
        foreach ($unusedSnapshots as $unusedSnapshot) {
            $snapId            = $unusedSnapshot['snapId'];
            $snapDate          = $unusedSnapshot['Date'];
            $snapDateFormatted = DateTime::createFromFormat('Y-m-d', $snapDate)->format('d-m-Y');
            $successful        = false;

            if ($packageType == 'rpm') {
                if (is_dir(REPOS_DIR . '/rpm/' . $repoName . '/' . $repoReleasever . '/' . $snapDate)) {
                    $successful = Directory::deleteRecursive(REPOS_DIR . '/rpm/' . $repoName . '/' . $repoReleasever . '/' . $snapDate);
                }

                // Delete the parent directories if they are empty
                if (Directory::isEmpty(REPOS_DIR . '/rpm/' . $repoName . '/' . $repoReleasever)) {
                    Directory::deleteRecursive(REPOS_DIR . '/rpm/' . $repoName . '/' . $repoReleasever);
                }

                if (Directory::isEmpty(REPOS_DIR . '/rpm/' . $repoName)) {
                    Directory::deleteRecursive(REPOS_DIR . '/rpm/' . $repoName);
                }
            }

            if ($packageType == 'deb') {
                if (is_dir(REPOS_DIR . '/deb/' . $repoName . '/' . $repoDist . '/' . $repoSection . '/' . $snapDate)) {
                    $successful = Directory::deleteRecursive(REPOS_DIR . '/deb/' . $repoName . '/' . $repoDist . '/' . $repoSection . '/' . $snapDate);
                }

                // Delete the parent directories if they are empty
                if (Directory::isEmpty(REPOS_DIR . '/deb/' . $repoName . '/' . $repoDist . '/' . $repoSection)) {
                    Directory::deleteRecursive(REPOS_DIR . '/deb/' . $repoName . '/' . $repoDist . '/' . $repoSection);
                }

                if (Directory::isEmpty(REPOS_DIR . '/deb/' . $repoName . '/' . $repoDist)) {
                    Directory::deleteRecursive(REPOS_DIR . '/deb/' . $repoName . '/' . $repoDist);
                }

                if (Directory::isEmpty(REPOS_DIR . '/deb/' . $repoName)) {
                    Directory::deleteRecursive(REPOS_DIR . '/deb/' . $repoName);
                }
            }

            // If there was an error during deletion
            if (!$successful) {
                if ($packageType == 'rpm') {
                    $removedSnapsError[] = 'Error while deleting snapshot <span class="label-white">' . $repoName . '</span>⸺<span class="label-black">' . $snapDateFormatted . '</span>';
                }
                if ($packageType == 'deb') {
                    $removedSnapsError[] = 'Error while deleting snapshot <span class="label-white">' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</span>⸺<span class="label-black">' . $snapDateFormatted . '</span>';
                }

                // Move on to the next snapshot (and therefore do not change the status of the snapshot in the database since it could not be deleted)
                continue;
            }

            // Case where the snapshot has been successfully deleted
            if ($packageType == 'rpm') {
                $removedSnaps[] = '<span class="label-white">' . $repoName . ' ❯ ' . $repoReleasever . '</span>⸺<span class="label-black">' . $snapDateFormatted . '</span> snapshot has been deleted';
            }

            if ($packageType == 'deb') {
                $removedSnaps[] = '<span class="label-white">' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</span>⸺<span class="label-black">' . $snapDateFormatted . '</span> snapshot has been deleted';
            }

            // Change the status in the database
            $this->updateStatus($snapId, 'deleted');

            // Delete any scheduled tasks that were using this snapshot
            try {
                $scheduledTaskController->deleteBySnapId($snapId);
            } catch (Exception $e) {
                $removedSnapsError[] = 'Error while deleting scheduled tasks using snapshot <span class="label-white">' . $repoName . '</span>⸺<span class="label-black">' . $snapDateFormatted . '</span>: ' . $e->getMessage();
            }
        }

        // Merge the two arrays containing deletion or error messages
        if (!empty($removedSnapsError)) {
            $removedSnapsFinalArray = array_merge($removedSnapsFinalArray, $removedSnapsError);
        }

        if (!empty($removedSnaps)) {
            $removedSnapsFinalArray = array_merge($removedSnapsFinalArray, $removedSnaps);
        }

        // If messages have been retrieved, then we forge the message that will be displayed in the log
        if (!empty($removedSnapsFinalArray)) {
            $returnOutput = '<div class="flex flex-direction-column row-gap-10">';

            foreach ($removedSnapsFinalArray as $removedSnap) {
                $returnOutput .= '<p class="wordbreakall">' . $removedSnap . '</p>';
            }

            $returnOutput .= '</div>';
        }

        if (!empty($removedSnapsError)) {
            throw new Exception($returnOutput);
        }

        return $returnOutput;
    }

    /**
     *  Return true if a snapshot with the specified ID exists
     */
    public function exists(int $id) : bool
    {
        return $this->model->exists($id);
    }

    /**
     *  Return true if a task is queued or running for the specified snapshot
     */
    public function taskRunning(int $snapId) : bool
    {
        return $this->model->taskRunning($snapId);
    }
}
