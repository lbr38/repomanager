<?php

namespace Controllers\Service;

use Exception;
use Datetime;

class Service
{
    protected $logController;
    private $serviceStatisticController;
    private $serviceFileController;
    private $curlHandle;
    private $currentTime;
    private $root = '/var/www/repomanager';
    private $scheduledTasksRemindersEnabled;
    private $cveImportEnabled;
    private $cveImportTime;
    private $manageHostsEnabled;
    protected $statsEnabled;
    protected $statsLogPath = '/var/log/nginx/repomanager_access.log';

    /**
     *  Get some global settings for the service to run
     */
    protected function getSettings()
    {
        $mysettings = new \Controllers\Settings();

        /**
         *  Get all settings
         */
        $settings = $mysettings->get();

        /**
         *  Hosts related settings
         */
        if (!empty($settings['MANAGE_HOSTS'])) {
            $this->manageHostsEnabled = $settings['MANAGE_HOSTS'];
        } else {
            $this->logController->log('error', 'Service', "Could not retrieve 'Manage hosts' setting.");
            // Disable hosts management
            $this->manageHostsEnabled = 'false';
        }

        /**
         *  Statistics related settings
         */
        if (!empty($settings['STATS_ENABLED'])) {
            $this->statsEnabled = $settings['STATS_ENABLED'];
        } else {
            $this->logController->log('error', 'Service', "Could not retrieve 'Enable repositories statistics' setting.");
            // Disable statistics
            $this->statsEnabled = 'false';
        }

        if ($this->statsEnabled == 'true') {
            /**
             *  Check if the log file is readable
             */
            if (!is_readable($this->statsLogPath)) {
                $this->logController->log('error', 'Service', "Access log file to scan for statistics <b>" . $this->statsLogPath . "</b> is not readable.");
                // Disable statistics
                $this->statsEnabled = 'false';
            }

            /**
             *  Check if the statistics database exists
             */
            if (!is_file(STATS_DB)) {
                $this->logController->log('error', 'Service', "Statistics database is not initialized.");
                // Disable statistics
                $this->statsEnabled = 'false';
            }
        }

        /**
         *  Scheduled tasks related settings
         */
        if (!empty($settings['SCHEDULED_TASKS_REMINDERS'])) {
            $this->scheduledTasksRemindersEnabled = $settings['SCHEDULED_TASKS_REMINDERS'];
        } else {
            $this->logController->log('error', 'Service', "Could not retrieve 'Enable scheduled tasks reminders' setting.");
            // Disable scheduled tasks reminders
            $this->scheduledTasksRemindersEnabled = 'false';
        }


        /**
         *  CVE related settings
         */
        if (!empty($settings['CVE_IMPORT'])) {
            $this->cveImportEnabled = $settings['CVE_IMPORT'];
        } else {
            $this->logController->log('error', 'Service', "Could not retrieve 'Import CVEs' setting.");
            // Disable cve import
            $this->cveImportEnabled = 'false';
        }

        if ($this->cveImportEnabled == 'true') {
            if (!empty($settings['CVE_IMPORT_TIME'])) {
                $this->cveImportTime = $settings['CVE_IMPORT_TIME'];
            } else {
                $this->logController->log('error', 'Service', "Could not retrieve 'Import scheduled time' setting.");
            }
        }

        sleep(5);
    }

    /**
     *  Get notifications
     */
    private function getNotifications()
    {
        echo $this->getDate() . ' Getting notifications...' . PHP_EOL;

        try {
            $mynotification = new \Controllers\Notification();
            $mynotification->retrieve();
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', 'Error while retrieving notifications: ' . $e->getMessage());
        }
    }

    /**
     *  Get current date and time
     */
    protected function getDate()
    {
        return '[' . date('D M j H:i:s') . ']';
    }

    /**
     *  Check if a new version is available on Github
     */
    private function checkVersion()
    {
        echo $this->getDate() . ' Checking for a new version on github...' . PHP_EOL;

        try {
            $outputFile = fopen(DATA_DIR . '/version.available', "w");

            curl_setopt($this->curlHandle, CURLOPT_URL, 'https://raw.githubusercontent.com/lbr38/repomanager/main/www/version');
            curl_setopt($this->curlHandle, CURLOPT_FILE, $outputFile);
            curl_setopt($this->curlHandle, CURLOPT_TIMEOUT, 120);

            /**
             *  If a proxy has been specified
             */
            if (!empty(PROXY)) {
                curl_setopt($this->curlHandle, CURLOPT_PROXY, PROXY);
            }

            /**
             *  Execute curl
             */
            if (curl_exec($this->curlHandle) === false) {
                curl_close($this->curlHandle);
                fclose($outputFile);

                /**
                 *  If curl has failed (meaning a curl param might be invalid)
                 */
                throw new Exception('(curl error): ' . curl_error($this->curlHandle));
            }

            /**
             *  Check that the http return code is 200 (the file has been downloaded)
             */
            $status = curl_getinfo($this->curlHandle);

            if ($status["http_code"] != 200) {
                /**
                 *  If return code is 404
                 */
                if ($status["http_code"] == '404') {
                    throw new Exception('(file not found)');
                } else {
                    throw new Exception('(http return code is ' . $status["http_code"] . ')');
                }

                curl_close($this->curlHandle);
                fclose($outputFile);
            }
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', 'Error while check for new version from Github ' . $e->getMessage());
        }
    }

    /**
     *  Return repomanager service status
     */
    public static function isRunning()
    {
        $myprocess = new \Controllers\Process('ps aux | grep "tools/service.php" | grep -v grep');
        $myprocess->execute();
        $content = $myprocess->getOutput();
        $myprocess->close();

        if ($myprocess->getExitCode() != 0) {
            return false;
        }

        return true;
    }

    /**
     *  Main function
     */
    public function run()
    {
        $this->serviceStatisticController = new \Controllers\Service\Statistic();
        $this->serviceFileController = new \Controllers\Service\File();
        $this->taskController = new \Controllers\Task\Task();
        $this->logController = new \Controllers\Log\Log();
        $this->curlHandle = curl_init();
        $this->lastScheduledTaskRunning = '';
        $this->lastStatsRunning = '';

        $counter = 0;

        while (true) {
            $this->currentTime = date('H:i');

            /**
             *  Get settings
             */
            $this->getSettings();

            /**
             *  Execute actions on service start (counter = 0) and then every hour (counter = 720)
             *  3600 / 5sec (sleep 5) = 720
             */
            if ($counter == 0 || $counter == 720) {
                /**
                 *  Check version
                 */
                $this->checkVersion();

                /**
                 *  Get notifications
                 */
                $this->getNotifications();

                /**
                 *  Cleanup files
                 */
                $this->serviceFileController->cleanUp();

                /**
                 *  Reset counter
                 */
                $counter = 0;
            }

            /**
             *  Execute scheduled tasks
             */
            if ($this->currentTime != $this->lastScheduledTaskRunning) {
                $this->lastScheduledTaskRunning = date('H:i');

                /**
                 *  Execute scheduled task
                 */
                $this->runService('scheduled tasks', 'scheduled-task-exec', true);

                /**
                 *  Send scheduled tasks reminders
                 */
                if ($this->scheduledTasksRemindersEnabled == 'true' and $this->currentTime == '00:00') {
                    $this->runService('scheduled tasks reminder', 'scheduled-task-reminder');
                }
            }

            /**
             *  Start websocket server
             */
            $this->runService('websocket server', 'wss');

            /**
             *  Parse access logs to generate stats (if enabled)
             */
            if ($this->statsEnabled == 'true') {
                if ($this->currentTime != $this->lastStatsRunning) {
                    $this->lastStatsRunning = date('H:i');

                    /**
                     *  Clean old statistics and generate repo size statistics at midnight
                     */
                    if ($this->currentTime == '00:00') {
                        /**
                         *  Clean old statistics
                         */
                        echo $this->getDate() . ' Cleaning old statistics...' . PHP_EOL;
                        $this->serviceStatisticController->statsClean();

                        /**
                         *  Generate repo size statistics
                         */
                        echo $this->getDate() . ' Generating statistics...' . PHP_EOL;
                        $this->serviceStatisticController->statsGenerate();

                        /**
                         *  Clean old tasks
                         */
                        echo $this->getDate() . ' Cleaning old tasks...' . PHP_EOL;
                        $this->taskController->clean();
                    }

                    /**
                     *  Parse access logs to generate repo access statistics
                     */
                    $this->runService('stats parsing', 'stats-parse');
                    $this->runService('stats processing', 'stats-process');
                }
            }

            /**
             *  Import CVEs
             */
            if ($this->cveImportEnabled == 'true' && $this->currentTime == $this->cveImportTime) {
                $this->runService('cve import', 'cve-import');
            }

            pcntl_signal_dispatch();
            sleep(5);

            $counter++;
        }
    }

    /**
     *  Run this service with the specified parameter
     */
    private function runService(string $name, string $parameter, bool $force = false)
    {
        try {
            /**
             *  Check if the service with specified parameter is already running to avoid running it twice
             *  A php process must be running
             *
             *  If force != false, then the service will be run even if it is already running (e.g: for running multiple scheduled tasks at the same time)
             */
            if ($force === false) {
                $myprocess = new \Controllers\Process('/usr/bin/ps aux | grep "repomanager.' . $parameter . '" | grep -v grep');
                $myprocess->execute();
                $content = $myprocess->getOutput();
                $myprocess->close();

                /**
                 *  Quit if there is already a process running
                 */
                if ($myprocess->getExitCode() == 0) {
                    return;
                }
            }

            /**
             *  Else, run the service with the specified parameter
             */
            echo $this->getDate() . ' Running ' . $name . '...' . PHP_EOL;

            $myprocess = new \Controllers\Process("/usr/bin/php " . ROOT . "/tools/service.php '" . $parameter . "' >/dev/null 2>/dev/null &");
            $myprocess->execute();
            $myprocess->close();
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', 'Error while launching service with parameter '. $parameter . ': ' . $e->getMessage());
        }
    }
}
