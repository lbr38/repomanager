<?php

namespace Controllers\Service;

use Exception;
use Datetime;

class File extends Service
{
    protected $logController;

    public function __construct()
    {
        $this->logController = new \Controllers\Log\Log();
    }

    /**
     *  Clean temporary files
     */
    public function cleanUp()
    {
        echo $this->getDate() . ' Cleaning temporary files' . PHP_EOL;

        try {
            /**
             *  Clean files older than 7 days in .temp
             */
            if (is_dir(DATA_DIR . '/.temp')) {
                $files = \Controllers\Common::findRecursive(DATA_DIR . '/.temp');
                $dirs = \Controllers\Common::findDirRecursive(DATA_DIR . '/.temp');

                if (!empty($files)) {
                    foreach ($files as $file) {
                        if (filemtime($file) < strtotime('-7 days')) {
                            if (!unlink($file)) {
                                throw new Exception('Error while cleaning .temp directory: cannot delete file <b>' . $file . '</b>');
                            }
                        }
                    }

                    unset($files);
                }

                /**
                 *  Also delete dirs older than 7 days
                 */
                if (!empty($dirs)) {
                    foreach ($dirs as $dir) {
                        if (\Controllers\Filesystem\Directory::isEmpty($dir)) {
                            echo $this->getDate() . ' Deleting ' . $dir . PHP_EOL;
                            if (!rmdir($dir)) {
                                throw new Exception('Error while cleaning .temp directory: cannot delete directory <b>' . $dir . '</b>');
                            }
                        }
                    }

                    unset($dirs);
                }
            }

            /**
             *  Clean pid files older than 7 days
             */
            if (is_dir(DATA_DIR . '/tasks/pid')) {
                $files = \Controllers\Common::findRecursive(DATA_DIR . '/tasks/pid', 'pid');

                if (!empty($files)) {
                    foreach ($files as $file) {
                        if (filemtime($file) < strtotime('-7 days')) {
                            if (!unlink($file)) {
                                throw new Exception('Error while cleaning pid files: cannot delete file <b>' . $file . '</b>');
                            }
                        }
                    }

                    unset($files);
                }
            }

            /**
             *  Clean pool files older than 7 days
             */
            if (is_dir(DATA_DIR . '/tasks/pool')) {
                $files = \Controllers\Common::findRecursive(DATA_DIR . '/tasks/pool', 'json');

                if (!empty($files)) {
                    foreach ($files as $file) {
                        if (filemtime($file) < strtotime('-7 days')) {
                            if (!unlink($file)) {
                                throw new Exception('Error while cleaning pool files: cannot delete file <b>' . $file . '</b>');
                            }
                        }
                    }

                    unset($files);
                }
            }

            /**
             *  Clean temp mirror directories older than 3 days
             */
            if (is_dir(REPOS_DIR)) {
                $dirs = \Controllers\Common::findDirRecursive(REPOS_DIR, 'download-mirror-.*');

                if (!empty($dirs)) {
                    foreach ($dirs as $dir) {
                        if (filemtime($dir) < strtotime('-3 days')) {
                            if (!\Controllers\Filesystem\Directory::deleteRecursive($dir)) {
                                $this->logController->log('error', 'Service', 'Error while cleaning temporary downloaded files: cannot delete directory <b>' . $dir . '</b>');
                            }
                        }
                    }

                    unset($dirs);
                }
            }
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', $e->getMessage());
        }
    }
}
