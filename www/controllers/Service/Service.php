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
    private $lastTime;
    private $root = '/var/www/repomanager';
    private $wwwUser = 'www-data';
    private $reposDir = '/home/repo';
    private $scheduledTasksRemindersEnabled;
    private $cveImportEnabled;
    private $cveImportTime;
    protected $statsEnabled;
    protected $statsLogPath = '/var/log/nginx/repomanager_access.log';

    /**
     *  Get some global settings for the service to run
     */
    protected function getSettings()
    {
        $mysettings = new \Controllers\Settings();

        /**
         *  Loop until all settings are retrieved
         */
        while (true) {
            $missingSetting = 0;

            /**
             *  Get all settings
             */
            $settings = $mysettings->get();

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

            /**
             *  Quit loop if all settings are retrieved
             */
            if ($missingSetting == 0) {
                break;
            }

            sleep(5);
        }
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

            curl_setopt($this->curlHandle, CURLOPT_URL, 'https://raw.githubusercontent.com/lbr38/repomanager/stable/www/version');
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
        $this->logController = new \Controllers\Log\Log();
        $this->curlHandle = curl_init();

        $counter = 0;

        while (true) {
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

            $this->currentTime = date('H:i');

            /**
             *  Execute scheduled tasks
             */
            if ($this->currentTime != $this->lastTime) {
                /**
                 *  Execute scheduled task
                 */
                $this->runService('scheduled-task-exec', true);

                /**
                 *  Send scheduled tasks reminders
                 */
                if ($this->scheduledTasksRemindersEnabled == 'true') {
                    $this->runService('scheduled-task-reminder');
                }
            }

            /**
             *  Parse access logs to generate stats (if enabled)
             */
            if ($this->statsEnabled == 'true') {
                if ($this->currentTime != $this->lastTime) {
                    /**
                     *  Clean old statistics
                     */
                    $this->serviceStatisticController->statsClean();

                    /**
                     *  Generate repo size statistics
                     */
                    $this->serviceStatisticController->statsGenerate();
                }

                /**
                 *  Parse access logs to generate repo access statistics
                 */
                $this->runService('stats/accesslog/parse');
                $this->runService('stats/accesslog/process');
            }

            /**
             *  Import CVEs
             */
            if ($this->cveImportEnabled == 'true' && $this->currentTime == $this->cveImportTime) {
                $this->runService('cve-import');
            }

            $this->lastTime = date('H:i');

            pcntl_signal_dispatch();
            sleep(5);

            $counter++;
        }
    }

    /**
     *  Run this service with the specified parameter
     */
    private function runService(string $parameter, bool $force = false)
    {
        try {
            /**
             *  Check if the service with specified parameter is already running to avoid running it twice
             *  A php process must be running
             *
             *  If force != false, then the service will be run even if it is already running (e.g: for running multiple scheduled tasks at the same time)
             */
            if ($force === false) {
                $myprocess = new \Controllers\Process("ps aux | grep '" . ROOT . "/tools/service.php " . $parameter . "' | grep -v grep");
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
            echo $this->getDate() . " Running service with parameter '" . $parameter . "'..." . PHP_EOL;

            $myprocess = new \Controllers\Process("/usr/bin/php " . ROOT . "/tools/service.php '" . $parameter . "' >/dev/null 2>/dev/null &");
            $myprocess->execute();
            $myprocess->close();
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', 'Error while launching service with parameter '. $parameter . ': ' . $e->getMessage());
        }
    }
}
