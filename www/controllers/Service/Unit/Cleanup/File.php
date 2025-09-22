<?php

namespace Controllers\Service\Unit\Cleanup;

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
            $files = \Controllers\Filesystem\File::findRecursive(DATA_DIR . '/.temp');
            $dirs = \Controllers\Common::findDirRecursive(DATA_DIR . '/.temp');

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
                    if (\Controllers\Filesystem\Directory::isEmpty($dir)) {
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
            $files = \Controllers\Filesystem\File::findRecursive(DATA_DIR . '/tasks/pid', ['pid']);

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
            $dirs = \Controllers\Common::findDirRecursive(REPOS_DIR, 'temporary-task-.*');

            if (!empty($dirs)) {
                foreach ($dirs as $dir) {
                    if (filemtime($dir) < strtotime('-3 days')) {
                        if (!\Controllers\Filesystem\Directory::deleteRecursive($dir)) {
                            throw new Exception('Could not delete temporary directory ' . $dir);
                        }

                        parent::log($dir . ' deleted');
                    }
                }
            }
        }

        /**
         *  Clean service units logs older than 15 days
         */
        if (is_dir(SERVICE_LOGS_DIR)) {
            $files = \Controllers\Filesystem\File::findRecursive(SERVICE_LOGS_DIR, ['log']);

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
