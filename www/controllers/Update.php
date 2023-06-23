<?php

namespace Controllers;

use Exception;

class Update
{
    private $model;
    private $workingDir = '/tmp/repomanager-update_' . GIT_VERSION;
    private $sqlQueriesDir = ROOT . '/update/database';

    public function __construct()
    {
        $this->model = new \Models\Update();
    }

    /**
     *  Enable / disable maintenance
     */
    public function setMaintenance(string $status)
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
    public function updateDB(string $targetVersion = null)
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
                if (!file_exists(DB_UPDATE_DONE_DIR . '/' . $targetVersion . '.done')) {
                    $this->model->updateDB($updateFile);

                    /**
                     *  Create a file to indicate that the update has been done
                     */
                    touch(DB_UPDATE_DONE_DIR . '/' . $targetVersion . '.done');
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
         *  For each files found execute its queries
         */
        if (!empty($updateFiles)) {
            foreach ($updateFiles as $updateFile) {
                if (file_exists($updateFile)) {
                    /**
                     *  Get target version from filename
                     */
                    $targetVersion = basename($updateFile, '.php');

                    /**
                     *  Execute file if it has not been done yet
                     */
                    if (!file_exists(DB_UPDATE_DONE_DIR . '/' . $targetVersion . '.done')) {
                        $this->model->updateDB($updateFile);

                        /**
                         *  Create a file to indicate that the update has been done
                         */
                        touch(DB_UPDATE_DONE_DIR . '/' . $targetVersion . '.done');
                    }
                }
            }
        }
    }
}
