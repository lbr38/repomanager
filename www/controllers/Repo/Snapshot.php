<?php

namespace Controllers\Repo;

use \Controllers\Filesystem\Directory;
use Exception;
use DateTime;

class Snapshot
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Repo\Snapshot();
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
    public function add(string $date, string $time, string $gpgSignature, array $arch, array $includeTranslation, array $packagesIncluded, array $packagesExcluded, string $type, string $status, int $repoId) : void
    {
        $this->model->add($date, $time, $gpgSignature, $arch, $includeTranslation, $packagesIncluded, $packagesExcluded, $type, $status, $repoId);
    }

    /**
     *  Update snapshot status in the database
     */
    public function updateStatus(string $snapId, string $status) : void
    {
        $this->model->updateStatus($snapId, $status);
    }

    /**
     *  Clean unused snapshots and return a message
     */
    public function clean() : string|null
    {
        $returnOutput = '';
        $removedSnaps = [];
        $removedSnapsError = [];
        $removedSnapsFinalArray = [];
        $repoListingController = new \Controllers\Repo\Listing();
        $scheduledTaskController = new \Controllers\Task\Scheduled();

        /**
         *  Get the list of all repositories with active snapshots
         */
        $repos = $repoListingController->listNameOnly(true);

        /**
         *  For each repository, get the list of unused snapshots (snapshots that have no active environment attached) and process them if there are any
         */
        foreach ($repos as $repo) {
            $repoId         = $repo['Id'];
            $repoName       = $repo['Name'];
            $repoReleasever = $repo['Releasever'];
            $repoDist       = $repo['Dist'];
            $repoSection    = $repo['Section'];
            $packageType    = $repo['Package_type'];

            /**
             *  Get the list of unused snapshots for this repository
             */
            $unusedSnapshots = $this->getUnused($repoId, RETENTION);

            /**
             *  Process if there are unused snapshots
             */
            foreach ($unusedSnapshots as $unusedSnapshot) {
                $snapId            = $unusedSnapshot['snapId'];
                $snapDate          = $unusedSnapshot['Date'];
                $snapDateFormatted = DateTime::createFromFormat('Y-m-d', $snapDate)->format('d-m-Y');
                $successful        = false;

                if ($packageType == 'rpm') {
                    if (is_dir(REPOS_DIR . '/rpm/' . $repoName . '/' . $repoReleasever . '/' . $snapDate)) {
                        $successful = Directory::deleteRecursive(REPOS_DIR . '/rpm/' . $repoName . '/' . $repoReleasever . '/' . $snapDate);
                    }

                    /**
                     *  Delete the parent directories if they are empty
                     */
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

                    /**
                     *  Delete the parent directories if they are empty
                     */
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

                /**
                 *  If there was an error during deletion
                 */
                if (!$successful) {
                    if ($packageType == 'rpm') {
                        $removedSnapsError[] = 'Error while deleting snapshot <span class="label-white">' . $repoName . '</span>⸺<span class="label-black">' . $snapDateFormatted . '</span>';
                    }
                    if ($packageType == 'deb') {
                        $removedSnapsError[] = 'Error while deleting snapshot <span class="label-white">' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</span>⸺<span class="label-black">' . $snapDateFormatted . '</span>';
                    }

                    /**
                     *  Move on to the next snapshot (and therefore do not change the status of the snapshot in the database since it could not be deleted)
                     */
                    continue;
                }

                /**
                 *  Case where the snapshot has been successfully deleted
                 */
                if ($packageType == 'rpm') {
                    $removedSnaps[] = '<span class="label-white">' . $repoName . ' ❯ ' . $repoReleasever . '</span>⸺<span class="label-black">' . $snapDateFormatted . '</span> snapshot has been deleted';
                }
                if ($packageType == 'deb') {
                    $removedSnaps[] = '<span class="label-white">' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</span>⸺<span class="label-black">' . $snapDateFormatted . '</span> snapshot has been deleted';
                }

                /**
                 *  Change the status in the database
                 */
                $this->updateStatus($snapId, 'deleted');

                // Delete any scheduled tasks that were using this snapshot
                try {
                    $scheduledTaskController->deleteBySnapId($snapId);
                } catch (Exception $e) {
                    $removedSnapsError[] = 'Error while deleting scheduled tasks using snapshot <span class="label-white">' . $repoName . '</span>⸺<span class="label-black">' . $snapDateFormatted . '</span>: ' . $e->getMessage();
                }
            }
        }

        /**
         *  Merge the two arrays containing deletion or error messages
         */
        if (!empty($removedSnapsError)) {
            $removedSnapsFinalArray = array_merge($removedSnapsFinalArray, $removedSnapsError);
        }

        if (!empty($removedSnaps)) {
            $removedSnapsFinalArray = array_merge($removedSnapsFinalArray, $removedSnaps);
        }

        /**
         *  If messages have been retrieved, then we forge the message that will be displayed in the log
         */
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
