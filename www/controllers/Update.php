<?php

namespace Controllers;

use Exception;

class Update
{
    private $model;
    private $workingDir = '/tmp/repomanager-update_' . GIT_VERSION;

    public function __construct()
    {
        $this->model = new \Models\Update();
    }

    /**
     *  Acquit update log window, delete update log files
     */
    public function acquit()
    {
        if (file_exists(UPDATE_SUCCESS_LOG)) {
            unlink(UPDATE_SUCCESS_LOG);
        }

        if (file_exists(UPDATE_ERROR_LOG)) {
            unlink(UPDATE_ERROR_LOG);
        }
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
            if (!file_exists(ROOT . "/update-running")) {
                touch(ROOT . "/update-running");
            }
        }

        if ($status == 'off') {
            if (file_exists(ROOT . "/update-running")) {
                unlink(ROOT . "/update-running");
            }
        }
    }

    /**
     *  Create a tar.gz backup file before update
     */
    private function backup()
    {
        if (UPDATE_BACKUP_ENABLED == 'yes') {
            $backupName = DATE_YMD . '_' . TIME . '_repomanager_full_backup.tar.gz';

            exec("tar --exclude='" . BACKUP_DIR . "' -czf /tmp/${backupName} " . ROOT . ' ' . DATA_DIR, $output, $return);
            if ($return != 0) {
                throw new Exception('Error while backuping actual repomanager configuration.');
            }

            /**
             *  Move backup file to backup dir
             */
            exec("mv /tmp/${backupName} " . BACKUP_DIR . "/", $output, $return);
            if ($return != 0) {
                throw new Exception('Error while moving backup file to the backup dir.');
            }
        }
    }

    /**
     *  Download new release tar archive
     */
    private function download()
    {
        /**
         *  Quit if wget if not installed
         */
        if (!file_exists('/usr/bin/wget')) {
            throw new Exception('/usr/bin/wget not found.');
        }

        exec('wget --no-cache -q "https://github.com/lbr38/repomanager/releases/download/' . GIT_VERSION . '/repomanager_' . GIT_VERSION . '.tar.gz" -O "' . $this->workingDir . '/repomanager_' . GIT_VERSION . '.tar.gz"', $output, $return);
        if ($return != 0) {
            throw new Exception('Error while downloading new release.');
        }

        /**
         *  Extract archive
         */
        exec('tar xzf ' . $this->workingDir . '/repomanager_' . GIT_VERSION . '.tar.gz -C ' . $this->workingDir . '/', $output, $return);
        if ($return != 0) {
            throw new Exception('Error while extracting new release archive.');
        }
    }

    /**
     *  Execute SQL queries to update database
     */
    public function updateDB()
    {
        $this->sqlQueriesDir = ROOT . '/update/database';

        if (!is_dir($this->sqlQueriesDir)) {
            return;
        }

        /**
         *  Get all the files
         */
        $updateFiles = glob($this->sqlQueriesDir . '/*.php');

        /**
         *  For each files found execute its queries
         */
        if (!empty($updateFiles)) {
            foreach ($updateFiles as $updateFile) {
                $this->model->updateDB($updateFile);
            }
        }
    }

    /**
     *  Update web source files
     */
    private function updateWeb()
    {
        /**
         *  Delete actual web root dir content
         */
        if (is_dir(ROOT)) {
            exec("rm -rf '" . ROOT . "/*", $output, $return);
            if ($return != 0) {
                throw new Exception('Error while deleting web root content <b>' . ROOT . '</b>');
            }
        }

        /**
         *  Copy new files to web root dir
         */
        exec("\cp -r " . $this->workingDir . '/repomanager/www/* ' . ROOT . '/', $output, $return);
        if ($return != 0) {
            throw new Exception('Error while copying <b>' . $this->workingDir . '/repomanager/www/</b> content to <b>' . ROOT . '/</b>');
        }

        /**
         *  Delete actual data dir tools content
         */
        if (is_dir(DATA_DIR . '/tools')) {
            exec('rm -rf ' . DATA_DIR . '/tools', $output, $return);
            if ($return != 0) {
                throw new Exception('Error while deleting tools directory content <b>' . DATA_DIR . '/tools/</b>');
            }
        }

        /**
         *  Copy new tools dir content
         */
        exec("\cp -r " . $this->workingDir . '/repomanager/tools ' . DATA_DIR . '/', $output, $return);
        if ($return != 0) {
            throw new Exception('Error while copying <b>' . $this->workingDir . '/repomanager/tools</b> to <b>' . DATA_DIR . '/</b>');
        }

        /**
         *  Delete actual repomanager bash script
         */
        if (is_file(DATA_DIR . '/repomanager')) {
            if (!unlink(DATA_DIR . '/repomanager')) {
                throw new Exception('Error while deleting repomanager bash script <b>' . DATA_DIR . '/repomanager</b>');
            }
        }

        /**
         *  Copy new repomanager bash script
         */
        exec("\cp " . $this->workingDir . '/repomanager/repomanager ' . DATA_DIR . '/repomanager', $output, $return);
        if ($return != 0) {
            throw new Exception('Error while copying <b>' . $this->workingDir . '/repomanager/repomanager</b> to <b>' . DATA_DIR . '/repomanager</b>');
        }
    }

    /**
     *  Execute update
     */
    public function update()
    {
        try {
            if (!is_dir(LOGS_DIR . '/update')) {
                mkdir(LOGS_DIR . '/update', 0770, true);
            }

            /**
             *  Delete old log files if exist
             */
            if (file_exists(UPDATE_ERROR_LOG)) {
                unlink(UPDATE_ERROR_LOG);
            }
            if (file_exists(UPDATE_SUCCESS_LOG)) {
                unlink(UPDATE_SUCCESS_LOG);
            }

            /**
             *  Quit if actual version is the same as the available version
             */
            if (VERSION == GIT_VERSION) {
                throw new Exception('Already up to date');
            }

            /**
             *  Enable maintenance page
             */
            $this->setMaintenance('on');

            /**
             *  Create backup before update
             */
            $this->backup();

            /**
             *  Delete working dir if already exist
             */
            if (is_dir($this->workingDir)) {
                exec("rm '$this->workingDir' -rf", $output, $return);
                if ($return != 0) {
                    throw new Exception('Error while deleting old working directory <b>' . $this->workingDir . '</b>');
                }
            }

            /**
             *  Then create it
             */
            if (!mkdir($this->workingDir, 0770, true)) {
                throw new Exception('Error while trying to create working directory <b>' . $this->workingDir . '</b>');
            }

            /**
             *  Download new release
             */
            $this->download();

            /**
             *  Update web source files
             */
            $this->updateWeb();

            /**
             *  Apply database update queries if there are
             */
            $this->updateDB();

            /**
             *  Set permissions on repomanager service script
             */
            if (!chmod(DATA_DIR . '/tools/service/repomanager-service', octdec('0550'))) {
                throw new Exception('Error while trying to set permissions on <b>' . DATA_DIR . '/tools/service/repomanager-service</b>');
            }

            /**
             *  Delete working dir
             */
            if (is_dir($this->workingDir)) {
                exec("rm '$this->workingDir' -rf", $output, $return);
                if ($return != 0) {
                    throw new Exception('Error while cleaning working directory <b>' . $this->workingDir . '</b>');
                }
            }

            /**
             *  Clear cache if any
             */
            Common::clearCache();

            /**
             *  Write to success log to file
             */
            $updateJSON = json_encode(array('Version' => GIT_VERSION, 'Message' => 'Update successful'));
            file_put_contents(UPDATE_SUCCESS_LOG, $updateJSON);
        } catch (Exception $e) {
            /**
             *  Write to error log to file
             */
            $updateJSON = json_encode(array('Version' => GIT_VERSION, 'Message' => 'Error while update Repomanager: ' . $e->getMessage()));

            file_put_contents(UPDATE_ERROR_LOG, $updateJSON);
        }

        /**
         *  Disable maintenance page
         */
        $this->setMaintenance('off');
    }
}
