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
     *  Apply permissions on files and directories
     */
    public function applyPermissions()
    {
        echo 'Applying permissions on files and directories...' . PHP_EOL;

        try {
            /**
             *  Get all dirs
             */
            $dirs = \Controllers\Common::findDirRecursive(REPOS_DIR);
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', $e->getMessage());
        }

        if (!empty($dirs)) {
            foreach ($dirs as $dir) {
                /**
                 *  Check if directory is writeable
                 */
                if (!is_writeable($dir)) {
                    $this->logController->log('error', 'Service', "Error while applying permissions on directory <b>" . $dir . '</b>: directory is not writeable');
                    continue;
                }

                /**
                 *  Apply permissions on directory
                 */
                if (!chmod($dir, octdec('0770'))) {
                    $this->logController->log('error', 'Service', "Error while applying '0770' permissions on directory <b>" . $dir . '</b>');
                }
            }
        }

        /**
         *  Get all files
         */
        $files = \Controllers\Common::findRecursive(REPOS_DIR);

        if (!empty($files)) {
            foreach ($files as $file) {
                /**
                 *  Check if file is writeable
                 */
                if (!is_writeable($file)) {
                    $this->logController->log('error', 'Service', 'Error while applying permissions on file <b>' . $file . '</b>: file is not writeable');
                    continue;
                }

                /**
                 *  Apply permissions on file
                 */
                if (!chmod($file, octdec('0660'))) {
                    $this->logController->log('error', 'Service', "Error while applying '0660' permissions on file <b>" . $file . '</b>');
                }
            }
        }

        unset($dirs, $files);
    }

    /**
     *  Clean temporary files
     */
    public function cleanUp()
    {
        echo 'Cleaning temporary files' . PHP_EOL;

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
                        if (\Controllers\Common::dirIsEmpty($dir)) {
                            echo 'Deleting ' . $dir . PHP_EOL;
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
            if (is_dir(DATA_DIR . '/operations/pid')) {
                $files = \Controllers\Common::findRecursive(DATA_DIR . '/operations/pid', 'pid');

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
            if (is_dir(DATA_DIR . '/operations/pool')) {
                $files = \Controllers\Common::findRecursive(DATA_DIR . '/operations/pool', 'json');

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
                            if (!\Controllers\Common::deleteRecursive($dir)) {
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
