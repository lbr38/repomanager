<?php

namespace Controllers;

use Exception;

class Update
{
    private $model;
    private $sqlQueriesDir = ROOT . '/update/database';

    public function __construct()
    {
        $this->model = new \Models\Update();
    }

    /**
     *  Enable / disable maintenance
     */
    public function setMaintenance(string $status) : void
    {
        if ($status == 'on') {
            /**
             *  Create 'update-running' file to enable maintenance page on the site
             */
            if (!file_exists(DATA_DIR . "/update-running")) {
                touch(DATA_DIR . "/update-running");
            }
        }

        if ($status == 'off') {
            if (file_exists(DATA_DIR . "/update-running")) {
                unlink(DATA_DIR . "/update-running");
            }
        }
    }

    /**
     *  Execute SQL queries to update database
     */
    public function updateDB(string $targetVersion = '') : void
    {
        if (!is_dir($this->sqlQueriesDir)) {
            return;
        }

        /**
         *  If a target release version is specified, only execute database update file that contains this version number
         */
        if (!empty($targetVersion)) {
            $updateFile = $this->sqlQueriesDir . '/' . $targetVersion . '.php';

            /**
             *  Execute file if exist
             */
            if (file_exists($updateFile)) {
                /**
                 *  Execute file if it has not been done yet
                 */
                if (!file_exists(DB_UPDATE_DONE_DIR . '/' . basename($targetVersion) . '.done')) {
                    try {
                        $this->model->updateDB($updateFile);

                        /**
                         *  Create a file to indicate that the update has been done
                         */
                        touch(DB_UPDATE_DONE_DIR . '/' . basename($targetVersion) . '.done');
                    } catch (Exception $e) {
                        throw new Exception('error while executing update file ' . $updateFile . ': ' . $e->getMessage());
                    }
                }
            }

            return;
        }

        /**
         *  Else execute all database update files
         */

        /**
         *  Get all the files
         */
        $updateFiles = glob($this->sqlQueriesDir . '/*.php');

        /**
         *  Execute always-before file
         */
        if (file_exists($this->sqlQueriesDir . '/_always-before.php')) {
            $this->model->updateDB($this->sqlQueriesDir . '/_always-before.php');
        }

        /**
         *  For each files found execute its queries
         */
        if (!empty($updateFiles)) {
            foreach ($updateFiles as $updateFile) {
                /**
                 *  Ignore always-before and always-after files as they are special files that
                 *  are executed before and after all other update files
                 */
                if (in_array(basename($updateFile), ['_always-before.php', '_always-after.php'])) {
                    continue;
                }

                if (file_exists($updateFile)) {
                    /**
                     *  Get target version from filename
                     */
                    $targetVersion = basename($updateFile, '.php');

                    /**
                     *  Execute file if it has not been done yet
                     */
                    if (!file_exists(DB_UPDATE_DONE_DIR . '/' . $targetVersion . '.done')) {
                        try {
                            $this->model->updateDB($updateFile);

                            /**
                             *  Create a file to indicate that the update has been done
                             */
                            touch(DB_UPDATE_DONE_DIR . '/' . $targetVersion . '.done');
                        } catch (Exception $e) {
                            throw new Exception('error while executing update file ' . $updateFile . ': ' . $e->getMessage());
                        }
                    }
                }
            }
        }

        /**
         *  Execute always-after file
         */
        if (file_exists($this->sqlQueriesDir . '/_always-after.php')) {
            $this->model->updateDB($this->sqlQueriesDir . '/_always-after.php');
        }
    }

    /**
     *  Return true if update is running
     */
    public static function running() : bool
    {
        if (file_exists(DATA_DIR . '/update-running')) {
            return true;
        }

        return false;
    }
}
