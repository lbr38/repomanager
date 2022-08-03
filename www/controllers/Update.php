<?php

namespace Controllers;

use Exception;

class Update
{
    private $model;
    private $workingDir;
    private $backupConfDir;
    private $log = array();

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

            exec("tar --exclude='" . BACKUP_DIR . "' --exclude='" . ROOT . "/db' -czf /tmp/${backupName} " . ROOT, $output, $result);

            if ($result != 0) {
                $error++;
                throw new Exception('Error while backuping actual repomanager configuration.');
            } else {
                /**
                 *  Move backup file to backup dir
                 */
                exec("mv /tmp/${backupName} " . BACKUP_DIR . "/");
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
            $this->log[] = $output;
            throw new Exception('Error while downloading new release.');
        }

        /**
         *  Extract archive
         */
        exec('tar xzf ' . $this->workingDir . '/repomanager_' . GIT_VERSION . '.tar.gz -C ' . $this->workingDir . '/', $output, $return);
        if ($return != 0) {
            $this->log[] = $output;
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
        foreach ($updateFiles as $updateFile) {
            $this->model->updateDB($updateFile);
        }
    }

    /**
     *  Update web source files
     */
    private function updateWeb()
    {
        /**
         *  Delete actual root webdir and copy it from the new release
         */
        if (is_dir(ROOT)) {
            exec("rm -rf '" . ROOT . "/*");
        }

        exec("\cp -r " . $this->workingDir . '/repomanager/www/* ' . ROOT . '/', $output, $return);
        if ($return != 0) {
            $this->log[] = $output;
            throw new Exception('Error while copying <b>' . $this->workingDir . '/repomanager/www</b> to <b>' . ROOT . '/</b>');
        }

        /**
         *  Copy scripts and tools to datadir
         */
        if (is_dir(DATA_DIR . '/tools')) {
            exec("rm -rf " . DATA_DIR . '/tools');
        }
        exec("\cp -r " . $this->workingDir . '/repomanager/tools ' . DATA_DIR . '/', $output, $return);
        if ($return != 0) {
            $this->log[] = $output;
            throw new Exception('Error while copying <b>' . $this->workingDir . '/repomanager/tools</b> to <b>' . DATA_DIR . '/</b>');
        }

        if (is_file(DATA_DIR . '/repomanager')) {
            unlink(DATA_DIR . '/repomanager');
        }
        exec("\cp " . $this->workingDir . '/repomanager/repomanager ' . DATA_DIR . '/repomanager', $output, $return);
        if ($return != 0) {
            $this->log[] = $output;
            throw new Exception('Error while copying <b>' . $this->workingDir . '/repomanager/repomanager</b> to <b>' . DATA_DIR . '/repomanager</b>');
        }
    }

    /**
     *  Execute update
     */
    public function update()
    {
        $error = 0;

        try {
            /**
             *  Quit if actual version is the same as the available version
             */
            if (VERSION == GIT_VERSION) {
                return '<span>Already up to date.</span>';
            }

            /**
             *  Enable maintenance
             */
            $this->setMaintenance('on');

            /**
             *  Create backup before update
             */
            $this->backup();

            /**
             *  Update
             */
            $this->workingDir = '/tmp/repomanager-update_' . GIT_VERSION;
            $this->backupConfDir = $this->workingDir . '/backup-conf';

            /**
             *  Delete working dirs if already exist
             */
            if (is_dir($this->workingDir)) {
                exec("rm '$this->workingDir' -rf");
            }

            /**
             *  Then create it
             */
            mkdir($this->workingDir, 0770, true);
            mkdir($this->backupConfDir, 0770, true);

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
             *  Apply permissions on repomanager service script
             */
            chmod(DATA_DIR . '/tools/service/repomanager-service', 550);

            /**
             *  Edit version file
             */
            file_put_contents(ROOT . 'version', GIT_VERSION);

            /**
             *  Delete working dir
             */
            exec("rm '$this->workingDir' -rf ");

            return '<span class="greentext">Update to ' . GIT_VERSION . ' successful</span>';
        } catch (Exception $e) {
            /**
             *  If an error occured, insert error log into update.log
             */
            if (!empty($this->log)) {
                if (!is_dir(DATA_DIR . '/logs/update')) {
                    mkdir(DATA_DIR . '/logs/update', 0770, true);
                }

                if (file_exists(DATA_DIR . '/logs/update/update.log')) {
                    unlink(DATA_DIR . '/logs/update/update.log');
                }

                touch(DATA_DIR . '/logs/update/update.log');

                foreach ($this->log as $log) {
                    file_put_contents(DATA_DIR . '/logs/update/update.log', $log . PHP_EOL, FILE_APPEND);
                }
            }

            /**
             *  Return catched error message
             */
            return '<span class="redtext">Error while updating to ' . GIT_VERSION . ': ' . $e->getMessage() . '</span>';
        }

        $this->setMaintenance('off');
    }
}
