<?php
/**
 *  5.0.0 update
 */
$repoController = new \Controllers\Repo\Repo();
$repoListingController = new \Controllers\Repo\Listing();
$statsDb = new \Models\Connection('stats');
use \Controllers\Log\Cli as CliLog;

/**
 *  Get all existing repositories
 */
$repos = $repoListingController->list();

try {
    CliLog::warning('5.0.0 stats database migration started');

    /**
     *  Add a 'Releasever' column to the access_rpm table
     */
    if (!$statsDb->columnExist('access_rpm', 'Releasever')) {
        $statsDb->exec("ALTER TABLE access_rpm ADD COLUMN Releasever VARCHAR(255) DEFAULT '' NOT NULL");
    }

    /**
     *  Update existing stats entries with the release version
     */
    if (!empty($repos)) {
        foreach ($repos as $repo) {
            if ($repo['Package_type'] == 'deb') {
                continue;
            }

            $name = $repo['Name'];
            $releasever = $repo['Releasever'];
            $env = $repo['Env'];

            // Update all matching entries in the access_rpm table
            $stmt = $statsDb->prepare("UPDATE access_rpm SET Releasever = :releasever WHERE Name = :name AND Env = :env");
            $stmt->bindValue(':releasever', $releasever);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':env', $env);
            $stmt->execute();
        }
    }

    /**
     *  Recreate index on access_rpm table
     */
    $statsDb->exec("DROP INDEX access_rpm_index");
    $statsDb->exec("DROP INDEX access_rpm_name_env_index");
    $statsDb->exec("CREATE INDEX IF NOT EXISTS access_rpm_index ON access_rpm (Date, Time, Name, Releasever, Env, Source, IP, Request, Request_result)");
    $statsDb->exec("CREATE INDEX IF NOT EXISTS access_rpm_name_env_index ON access_rpm (Name, Releasever, Env)");

    CliLog::warning('5.0.0 stats database migration completed.');
} catch (Exception $e) {
    throw new Exception('error while updating stats database: ' . $e->getMessage());
}

/**
 *  Migrate existing repositories to the new structure
 */
CliLog::warning('5.0.0 repositories migration started');

try {
    // Create parent directories
    foreach (['rpm', 'deb'] as $dir) {
        if (!is_dir(REPOS_DIR . '/' . $dir)) {
            if (!mkdir(REPOS_DIR . '/' . $dir, 0770, true)) {
                throw new Exception('unable to create directory ' . REPOS_DIR . '/' . $dir);
            }
        }
    }
} catch (Exception $e) {
    throw new Exception('error while preparing update: ' . $e->getMessage());
}

// Migrate existing repositories
try {
    $previousOldSnapshotPath = null;

    // Quit if there is no repository
    if (empty($repos)) {
        CliLog::warning('No existing repository found, nothing to migrate. End of migration.');
        return;
    }

    // Migrate each repository
    foreach ($repos as $repo) {
        $type = $repo['Package_type'];
        $name = $repo['Name'];
        $dist = $repo['Dist'];
        $section = $repo['Section'];
        $releasever = $repo['Releasever'];
        $date = DateTime::createFromFormat('Y-m-d', $repo['Date'])->format('d-m-Y');
        $newDateFormat = $repo['Date'];
        $env = $repo['Env'];

        // Print informations
        if ($type == 'rpm') {
            CliLog::log('Migrating RPM repository: ' . $name . ' (release version ' . $releasever . ')');
        }
        if ($type == 'deb') {
            CliLog::log('Migrating DEB repository: ' . $name . ' > ' . $dist . ' > ' . $section);
        }

        CliLog::log(' - Snapshot: ' . $date);

        if (!empty($env)) {
            CliLog::log(' - Environment: ' . $env);
        }

        if ($type == 'rpm') {
            $oldSnapshotPath = REPOS_DIR . '/' . $date . '_' . $name;
            $oldEnvLink      = REPOS_DIR . '/' . $name . '_' . $env;
            $newSnapshotPath = REPOS_DIR . '/rpm/' . $name . '/' . $releasever . '/' . $newDateFormat;
            $parentDir       = REPOS_DIR . '/rpm/' . $name . '/' . $releasever;
        }

        if ($type == 'deb') {
            $oldSnapshotPath = REPOS_DIR . '/' . $name . '/' . $dist . '/' . $date . '_' . $section;
            $oldEnvLink      = REPOS_DIR . '/' . $name . '/' . $dist . '/' . $section . '_' . $env;
            $newSnapshotPath = REPOS_DIR . '/deb/' . $name . '/' . $dist . '/' . $section . '/' . $newDateFormat;
            $parentDir       = REPOS_DIR . '/deb/' . $name . '/' . $dist . '/' . $section;
        }

        // Create parent directory if it does not exist
        if (!is_dir($parentDir)) {
            if (!mkdir($parentDir, 0770, true)) {
                throw new Exception('unable to create directory ' . $parentDir);
            }
        }

        // Move snapshot directory if not already done before (same snapshot can be used for multiple env)
        if ($previousOldSnapshotPath != $oldSnapshotPath) {
            // Check if old path exists
            if (!is_dir($oldSnapshotPath)) {
                throw new Exception('snapshot directory ' . $oldSnapshotPath . ' does not exist');
            }

            // Check if new path already exists
            if (is_dir($newSnapshotPath)) {
                throw new Exception('snapshot directory ' . $newSnapshotPath . ' already exists');
            }

            // Move snapshot directory
            CliLog::log(' -> Moving snapshot to ' . $newSnapshotPath);
            if (!rename($oldSnapshotPath, $newSnapshotPath)) {
                throw new Exception('unable to move ' . $oldSnapshotPath . ' to ' . $newSnapshotPath);
            }
        }

        // Create environment symlink
        if (!empty($env)) {
            CliLog::log(' -> Recreating environment symlink: ' . $env);
            $envLink = $parentDir . '/' . $env;

            // Remove existing symlink if it exists (this should not happen)
            if (is_link($envLink)) {
                if (!unlink($envLink)) {
                    throw new Exception('unable to remove existing environment symlink ' . $envLink);
                }
            }

            if (!symlink($newDateFormat, $envLink)) {
                throw new Exception('unable to create environment symlink ' . $envLink);
            }

            // Delete old environment symlink if it exists
            if (is_link($oldEnvLink)) {
                CliLog::log(' -> Removing old environment symlink: ' . $oldEnvLink);
                if (!unlink($oldEnvLink)) {
                    throw new Exception('unable to remove old environment symlink ' . $oldEnvLink);
                }
            }
        }

        // Delete empty directories (mostly for DEB repos)
        if ($type == 'deb') {
            if (\Controllers\Filesystem\Directory::isEmpty(REPOS_DIR . '/' . $name . '/' . $dist)) {
                \Controllers\Filesystem\Directory::deleteRecursive(REPOS_DIR . '/' . $name . '/' . $dist);
            }

            if (\Controllers\Filesystem\Directory::isEmpty(REPOS_DIR . '/' . $name)) {
                \Controllers\Filesystem\Directory::deleteRecursive(REPOS_DIR . '/' . $name);
            }
        }

        $previousOldSnapshotPath = $oldSnapshotPath;

        CliLog::log('');
    }
} catch (Exception $e) {
    throw new Exception('error while migrating existing repositories: ' . $e->getMessage());
}

CliLog::warning('5.0.0 migration completed. Please check that everything is fine with your repositories.');
