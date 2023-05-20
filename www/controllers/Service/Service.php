<?php

namespace Controllers\Service;

use Exception;
use Datetime;

class Service
{
    protected $logController;
    private $servicePlanificationController;
    private $serviceStatisticController;
    private $serviceFileController;
    private $curlHandle;
    private $currentTime;
    private $lastTime;
    private $root;
    private $wwwUser;
    private $reposDir;
    private $plansEnabled;
    private $plansRemindersEnabled;
    protected $statsEnabled;
    protected $statsLogPath;

    /**
     *  Get some global settings for the service to run
     */
    protected function getSettings()
    {
        echo 'Getting settings...' . PHP_EOL;

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
             *  General settings
             */
            if (!empty($settings['WWW_DIR'])) {
                $this->root = $settings['WWW_DIR'];
            } else {
                $this->logController->log('error', 'Service', 'Could not retrieve root directory.');
                $missingSetting++;
            }

            if (!empty($settings['WWW_USER'])) {
                $this->wwwUser = $settings['WWW_USER'];
            } else {
                $this->logController->log('error', 'Service', "Could not retrieve 'Web user' setting.");
                $missingSetting++;
            }

            if (!empty($settings['REPOS_DIR'])) {
                $this->reposDir = $settings['REPOS_DIR'];
            } else {
                $this->logController->log('error', 'Service', "Could not retrieve 'Repositories storage directory' setting.");
                $missingSetting++;
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
                if (empty($settings['STATS_LOG_PATH'])) {
                    $this->logController->log('error', 'Service', "Could not retrieve 'Path to access log to scan for statistics' setting.");
                    // Disable statistics
                    $this->statsEnabled = 'false';
                } else {
                    $this->statsLogPath = $settings['STATS_LOG_PATH'];

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
            }

            /**
             *  Plans related settings
             */
            if (!empty($settings['PLANS_ENABLED'])) {
                $this->plansEnabled = $settings['PLANS_ENABLED'];
            } else {
                $this->logController->log('error', 'Service', "Could not retrieve 'Enable plan' setting.");
                // Disable plans
                $this->plansEnabled = 'false';
            }

            if ($this->plansEnabled == 'true') {
                if (!empty($settings['PLANS_REMINDERS_ENABLED'])) {
                    $this->plansRemindersEnabled = $settings['PLANS_REMINDERS_ENABLED'];
                } else {
                    $this->logController->log('error', 'Service', "Could not retrieve 'Enable plan reminders' setting.");
                    // Disable plan reminders
                    $this->plansRemindersEnabled = 'false';
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
     *  Check if a new version is available on Github
     */
    private function checkVersion()
    {
        echo 'Checking for a new version on github...' . PHP_EOL;

        try {
            $outputFile = fopen(DATA_DIR . '/version.available', "w");

            curl_setopt($this->curlHandle, CURLOPT_URL, 'https://raw.githubusercontent.com/lbr38/repomanager/stable/www/version');
            curl_setopt($this->curlHandle, CURLOPT_FILE, $outputFile);
            curl_setopt($this->curlHandle, CURLOPT_TIMEOUT, 30);

            /**
             *  Execute curl
             */
            curl_exec($this->curlHandle);

            /**
             *  If curl has failed (meaning a curl param might be invalid)
             */
            if (curl_errno($this->curlHandle)) {
                curl_close($this->curlHandle);
                fclose($outputFile);

                throw new Exception('Error while retrieving new version from Github (curl error): ' . curl_error($this->curlHandle));
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
                    throw new Exception('Error while retrieving new version from Github (file not found)');
                } else {
                    throw new Exception('Error while retrieving new version from Github (http return code is: ' . $status["http_code"] . ')');
                }

                curl_close($this->curlHandle);
                fclose($outputFile);
            }
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', $e->getMessage());
        }
    }

    /**
     *  Get notifications
     */
    private function getNotifications()
    {
        echo 'Getting notifications...' . PHP_EOL;

        try {
            $mynotification = new \Controllers\Notification();
            $mynotification->retrieve();
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', 'Error while retrieving notifications: ' . $e->getMessage());
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
                 *  Apply permissions
                 */
                //$this->serviceFileController->applyPermissions();

                /**
                 *  Reset counter
                 */
                $counter = 0;
            }

            $this->currentTime = date('H:i');

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
                $this->runService('logparser');
            }

            /**
             *  Execute plans actions (if plans are enabled)
             */
            if ($this->plansEnabled == 'true' && $this->currentTime != $this->lastTime) {
                /**
                 *  Execute plans
                 */
                $this->runService('plan-exec');

                /**
                 *  Send plans reminder
                 */
                if ($this->plansRemindersEnabled == 'true') {
                    $this->runService('plan-reminder');
                }
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
    private function runService(string $parameter)
    {
        try {
            /**
             *  Check if the service with specified parameter is already running (a php process must be running)
             */
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

            /**
             *  Else, run the service with the specified parameter
             */
            echo "Running service with parameter '" . $parameter . "'..." . PHP_EOL;

            $myprocess = new \Controllers\Process("php /var/www/repomanager/tools/service.php '" . $parameter . "' >/dev/null 2>/dev/null &");
            $myprocess->execute();
            $myprocess->close();
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', 'Error while launching service with parameter '. $parameter . ': ' . $e->getMessage());
        }
    }
}
