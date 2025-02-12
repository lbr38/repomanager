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
        echo $this->getDate() . ' Cleaning files' . PHP_EOL;

        try {
            /**
             *  Clean temp files and directories older than 3 days
             */
            if (is_dir(DATA_DIR . '/.temp')) {
                $files = \Controllers\Common::findRecursive(DATA_DIR . '/.temp');
                $dirs = \Controllers\Common::findDirRecursive(DATA_DIR . '/.temp');

                if (!empty($files)) {
                    foreach ($files as $file) {
                        if (filemtime($file) < strtotime('-3 days')) {
                            if (!unlink($file)) {
                                throw new Exception('Could not clean temporary file <b>' . $file . '</b>');
                            }
                        }
                    }
                }

                if (!empty($dirs)) {
                    foreach ($dirs as $dir) {
                        if (\Controllers\Filesystem\Directory::isEmpty($dir)) {
                            echo $this->getDate() . ' Deleting ' . $dir . PHP_EOL;
                            if (!rmdir($dir)) {
                                throw new Exception('Could not clean temporary directory <b>' . $dir . '</b>');
                            }
                        }
                    }
                }

                unset($files, $dirs);
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
                                throw new Exception('Could not clean pid file <b>' . $file . '</b>');
                            }
                        }
                    }
                }

                unset($files);
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
                                throw new Exception('Could not clean temporary directory <b>' . $dir . '</b>');
                            }
                        }
                    }
                }

                unset($dirs);
            }

            /**
             *  Clean websocket logs older than 15 days
             */
            if (is_dir(WS_LOGS_DIR)) {
                $files = \Controllers\Common::findRecursive(WS_LOGS_DIR, 'log');

                if (!empty($files)) {
                    foreach ($files as $file) {
                        if (filemtime($file) < strtotime('-15 days')) {
                            if (!unlink($file)) {
                                throw new Exception('Could not clean websocket log file <b>' . $file . '</b>');
                            }
                        }
                    }
                }

                unset($files);
            }
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', $e->getMessage());
        }
    }
}
