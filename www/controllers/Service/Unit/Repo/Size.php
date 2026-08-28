<?php

namespace Controllers\Service\Unit\Repo;

use Controllers\Filesystem\Directory;
use Controllers\Repo\Snapshot\Snapshot;
use Controllers\Repo\Listing;

class Size extends \Controllers\Service\Service
{
    public function __construct(string $unit)
    {
        parent::__construct($unit);
    }

    /**
     *  Calculate the size of all repository snapshots
     */
    public function calculate(): void
    {
        $repoListingController = new Listing();
        $repoSnapshotController = new Snapshot();

        parent::log('Calculating repository snapshots size...');

        // Get all active repos from the database
        $repos = $repoListingController->list();

        foreach ($repos as $repo) {
            // Determine the path to the snapshot directory
            if ($repo['Package_type'] == 'rpm') {
                $snapshotPath = REPOS_DIR . '/rpm/' . $repo['Name'] . '/' . $repo['Releasever'] . '/' . $repo['Date'];
            }
            if ($repo['Package_type'] == 'deb') {
                $snapshotPath = REPOS_DIR . '/deb/' . $repo['Name'] . '/' . $repo['Dist'] . '/' . $repo['Section'] . '/' . $repo['Date'];
            }

            if (!is_dir($snapshotPath)) {
                parent::logError("Snapshot directory does not exist: $snapshotPath");
                continue;
            }

            // Update the size in the database
            $repoSnapshotController->updateSize($repo['snapId'], Directory::getSize($snapshotPath));
        }

        parent::log('Repository snapshots size calculation completed.');
    }
}
