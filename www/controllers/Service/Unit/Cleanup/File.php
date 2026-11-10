<?php

namespace Controllers\Service\Unit\Cleanup;

use Controllers\Filesystem\Directory;
use Controllers\Filesystem\File as FsFile;
use Exception;

class File extends \Controllers\Service\Service
{
    public function __construct(string $unit)
    {
        parent::__construct($unit);
    }

    /**
     *  Clean temporary files
     */
    public function run() : void
    {
        parent::log('Cleaning files...');

        /**
         *  Clean temp files and directories older than 3 days
         */
        if (is_dir(DATA_DIR . '/.temp')) {
            $files = FsFile::findRecursive(DATA_DIR . '/.temp');
            $dirs = Directory::findRecursive(DATA_DIR . '/.temp');

            if (!empty($files)) {
                foreach ($files as $file) {
                    if (filemtime($file) < strtotime('-3 days')) {
                        if (!unlink($file)) {
                            throw new Exception('Could not delete temporary file ' . $file);
                        }

                        parent::log($file . ' deleted');
                    }
                }
            }

            if (!empty($dirs)) {
                foreach ($dirs as $dir) {
                    if (Directory::isEmpty($dir)) {
                        if (!rmdir($dir)) {
                            throw new Exception('Could not delete temporary directory ' . $dir);
                        }

                        parent::log($dir . ' deleted');
                    }
                }
            }
        }

        /**
         *  Clean pid files older than 7 days
         */
        if (is_dir(DATA_DIR . '/tasks/pid')) {
            $files = FsFile::findRecursive(DATA_DIR . '/tasks/pid', [], ['pid']);

            if (!empty($files)) {
                foreach ($files as $file) {
                    if (filemtime($file) < strtotime('-7 days')) {
                        if (!unlink($file)) {
                            throw new Exception('Could not delete pid file ' . $file);
                        }

                        parent::log($file . ' deleted');
                    }
                }
            }
        }

        /**
         *  Clean temp mirror directories older than 3 days
         */
        if (is_dir(REPOS_DIR)) {
            $dirs = Directory::findRecursive(REPOS_DIR, 'temporary-task-.*');

            if (!empty($dirs)) {
                foreach ($dirs as $dir) {
                    if (filemtime($dir) < strtotime('-3 days')) {
                        if (!Directory::deleteRecursive($dir)) {
                            throw new Exception('Could not delete temporary directory ' . $dir);
                        }

                        parent::log($dir . ' deleted');
                    }
                }
            }
        }

        /**
         *  Clean mirrors sync working directories older than 3 days
         *  A working directory remains after a failed sync, so that the next sync (or the relaunch of the
         *  failed task) resumes from it. After 3 days, it is deleted, as the repositories temporary directories.
         *  If the failed sync was the first one, the mirror does not exist in database: its directory is
         *  then left empty and is deleted too (a mirror in database always has at least one snapshot directory)
         */
        if (is_dir(MIRRORS_DIR)) {
            $workingDirs = array_merge(
                glob(MIRRORS_DIR . '/*/*/.tmp-sync', GLOB_ONLYDIR),
                glob(MIRRORS_DIR . '/rpm/*/*/.tmp-sync', GLOB_ONLYDIR)
            );

            foreach ($workingDirs as $workingDir) {
                $mirrorDir = dirname($workingDir);

                if (filemtime($workingDir) >= strtotime('-3 days')) {
                    continue;
                }

                // Lock the mirror during the cleaning, skip it if a task is currently syncing it
                $lockFile = $mirrorDir . '/.tmp-lock';
                $lock = fopen($lockFile, 'c');

                if ($lock === false) {
                    continue;
                }

                if (!flock($lock, LOCK_EX | LOCK_NB)) {
                    fclose($lock);
                    continue;
                }

                try {
                    if (!Directory::deleteRecursive($workingDir)) {
                        throw new Exception('Could not delete mirror working directory ' . $workingDir);
                    }

                    parent::log($workingDir . ' deleted');
                } finally {
                    // Delete the lock file before releasing the lock, as the tasks do
                    unlink($lockFile);
                    flock($lock, LOCK_UN);
                    fclose($lock);
                }

                // Delete the mirror directory if nothing remains (the mirror has never been created)
                if (count(scandir($mirrorDir)) == 2) {
                    if (!rmdir($mirrorDir)) {
                        throw new Exception('Could not delete mirror directory ' . $mirrorDir);
                    }

                    parent::log($mirrorDir . ' deleted');

                    // rpm mirrors are stored in rpm/<name>/<releasever>, delete rpm/<name> if no other release version remains
                    $parentDir = dirname($mirrorDir);

                    if (dirname($parentDir) == MIRRORS_DIR . '/rpm' and count(scandir($parentDir)) == 2) {
                        rmdir($parentDir);
                        parent::log($parentDir . ' deleted');
                    }
                }
            }
        }

        /**
         *  Clean service units logs older than 15 days
         */
        if (is_dir(SERVICE_LOGS_DIR)) {
            $files = FsFile::findRecursive(SERVICE_LOGS_DIR, [], ['log']);

            if (!empty($files)) {
                foreach ($files as $file) {
                    if (filemtime($file) < strtotime('-15 days')) {
                        if (!unlink($file)) {
                            throw new Exception('Could not delete log file ' . $file);
                        }

                        parent::log($file . ' deleted');
                    }
                }
            }
        }

        parent::log('Files cleaning finished');

        unset($dirs, $dir, $files, $file);
    }
}
