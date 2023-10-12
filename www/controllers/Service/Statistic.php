<?php

namespace Controllers\Service;

use Exception;
use Datetime;

class Statistic extends Service
{
    protected $logController;
    private $statController;
    private $repoListingController;

    public function __construct()
    {
        $this->logController = new \Controllers\Log\Log();
        $this->statController = new \Controllers\Stat();
        $this->repoListingController = new \Controllers\Repo\Listing();
    }

    /**
     *  Clean old statistics
     */
    public function statsClean()
    {
        /**
         *  Exit the function if current time != 00:00
         */
        if (date('H:i') != '00:00') {
            return;
        }

        echo 'Cleaning old statistics...' . PHP_EOL;

        try {
            $this->statController->clean();
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', 'Error while executing stats cleaning operation: ' . $e->getMessage());
        }
    }

    /**
     *  Generate statistics on repo size and repo package count
     */
    public function statsGenerate()
    {
        /**
         *  Exit the function if current time != 00:00
         */
        if (date('H:i') != '00:00') {
            return;
        }

        echo 'Generating statistics...' . PHP_EOL;

        /**
         *  Get all repos
         */
        $reposList = $this->repoListingController->list();

        if (!empty($reposList)) {
            try {
                foreach ($reposList as $repo) {
                    /**
                     *  Continue if the repo snapshot has no environment, because stats are only generated for snapshots environments
                     */
                    if (empty($repo['envId'])) {
                        continue;
                    }

                    if ($repo['Package_type'] == 'rpm') {
                        if (file_exists(REPOS_DIR . '/' . $repo['Name'] . '_' . $repo['Env'])) {
                            /**
                             *  Calculate repo size in bytes
                             */
                            $repoSize = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/' . $repo['Name'] . '_' . $repo['Env'] . '/');

                            /**
                             *  Calculate number of packages in the repo
                             */
                            $packagesCount = count(\Controllers\Common::findRecursive(REPOS_DIR . '/' . $repo['Name'] . '_' . $repo['Env'] . '/', 'rpm'));
                        }
                    }

                    if ($repo['Package_type'] == 'deb') {
                        if (file_exists(REPOS_DIR . '/' . $repo['Name'] . '/' . $repo['Dist'] . '/' . $repo['Section'] . '_' . $repo['Env'])) {
                            /**
                             *  Calculate repo size in bytes
                             */
                            $repoSize = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/' . $repo['Name'] . '/' . $repo['Dist'] . '/' . $repo['Section'] . '_' . $repo['Env'] . '/');

                            /**
                             *  Calculate number of packages in the repo
                             */
                            $packagesCount = count(\Controllers\Common::findRecursive(REPOS_DIR . '/' . $repo['Name'] . '/' . $repo['Dist'] . '/' . $repo['Section'] . '_' . $repo['Env'] . '/', 'deb'));
                        }
                    }

                    /**
                     *  Add repo size and package count to stats database
                     */
                    if (!empty($repoSize)) {
                        $this->statController->add(date('Y-m-d'), date('H:i:s'), $repoSize, $packagesCount, $repo['envId']);
                    }
                }
            } catch (Exception $e) {
                $this->logController->log('error', 'Service', 'Error while executing stats generation operation: ' . $e->getMessage());
            }
        }
    }

    /**
     *  Run log parser service script in background
     */
    public function runStatsParseAccessLog()
    {
        try {
            /**
             *  Check if the access log parsing is already running (a php process must be running)
             */
            $myprocess = new \Controllers\Process('ps aux | grep "' . ROOT . '/tools/service.php logparser" | grep -v grep');
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
             *  Else, run the access log parsing
             */
            echo 'Running access log parsing...' . PHP_EOL;

            $myprocess = new \Controllers\Process("php /var/www/repomanager/tools/service.php 'logparser' >/dev/null 2>/dev/null &");
            $myprocess->execute();
            $myprocess->close();
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', 'Error while launching access log parsing operation: ' . $e->getMessage());
        }
    }

    /**
     *  Parse access log and add repo access stats to database
     */
    public function statsParseAccessLog()
    {
        $nextSettingsCheck = time() + 5;

        /**
         *  Get settings
         */
        $this->getSettings();

        /**
         *  Return false if getSettings has disabled stats because of an error or because the user disabled them
         */
        if ($this->statsEnabled != 'true') {
            return;
        }

        /**
         *  Open the access log file
         */
        $file = fopen($this->statsLogPath, 'r');
        fseek($file, 0, SEEK_END);
        $inode = fstat($file)['ino'];
        $size = filesize($this->statsLogPath);

        /**
         *  Parse the access log file
         *  If the file is deleted and recreated (or rotated), the inode will change, so we need to reopen the file
         *  If the file is truncated, the size will be smaller than the previous one, so we need to reopen the file
         */
        while (true) {
            /**
             *  Get and check settings every 5 seconds to make sure that stats are still enabled and that the log file path is still correct
             */
            if (time() >= $nextSettingsCheck) {
                /**
                 *  Get settings
                 */
                $this->getSettings();

                /**
                 *  Break if stats have been disabled
                 */
                if ($this->statsEnabled != 'true') {
                    break;
                }

                $nextSettingsCheck = (time() + 5);
            }

            /**
             *  Stop parsing if the stop file exists
             */
            if (file_exists(DATA_DIR . '/.service-parsing-stop')) {
                unlink(DATA_DIR . '/.service-parsing-stop');
                break;
            }

            /**
             *  Wait if a repomanager update process is running
             */
            while (file_exists(DATA_DIR . '/update-running')) {
                sleep(2);
                continue;
            }

            clearstatcache();

            if (!file_exists($this->statsLogPath) || ($file = @fopen($this->statsLogPath, 'r')) === false) {
                sleep(2);
                continue;
            }

            $currentInode = fstat($file)['ino'];

            if ($currentInode != $inode) {
                fclose($file);
                $file = fopen($this->statsLogPath, 'r');
                fseek($file, 0, SEEK_END);
                $inode = $currentInode;
                $size = 0;
            }

            $currentSize = filesize($this->statsLogPath);

            if ($currentSize < $size) {
                fclose($file);
                $file = fopen($this->statsLogPath, 'r');
                fseek($file, 0, SEEK_END);
                $inode = fstat($file)['ino'];
                $size = 0;
            } else if ($currentSize > $size) {
                fseek($file, $size);

                while (!feof($file)) {
                    /**
                     *  Get line from access log file
                     */
                    $line = fgets($file);

                    /**
                     *  Skip line if it does not contain a repo access
                     */
                    if (!preg_match('/urlgrabber|APT-CURL|APT-HTTP/', $line)) {
                        continue;
                    }

                    /**
                     *  Parse line
                     */
                    $line = explode(' ', $line);
                    // $dateExplode = str_replace('[', '', $line[3]);
                    $dateExplode = explode(':', str_replace('[', '', $line[3]));
                    // Date
                    $date = DateTime::createFromFormat('d/M/Y', $dateExplode[0])->format('Y-m-d');
                    // Time
                    $time = $dateExplode[1] . ':' . $dateExplode[2] . ':' . $dateExplode[3];
                    // Source IP
                    $sourceIp = $line[0];
                    // Source host from IP
                    $sourceHost = gethostbyaddr($sourceIp);
                    // Complete request
                    $request = $line[5] . ' ' . $line[6] . ' ' . $line[7];
                    // Request result
                    $requestResult = $line[8];

                    // For debugging
                    // echo 'Date: ' . $date . PHP_EOL;
                    // echo 'Time: ' . $time . PHP_EOL;
                    // echo 'Source IP: ' . $sourceIp . PHP_EOL;
                    // echo 'Source host: ' . $sourceHost . PHP_EOL;
                    // echo 'Request: ' . $request . PHP_EOL;
                    // echo 'Request result: ' . $requestResult . PHP_EOL . PHP_EOL;

                    /**
                     *  Add repo access to database
                     */
                    $this->statController->addAccess($date, $time, $sourceHost, $sourceIp, $request, $requestResult);

                    unset($line, $dateExplode, $date, $time, $sourceIp, $sourceHost, $request, $requestResult);
                }

                $size = ftell($file);
            }

            usleep(100);
        }
    }
}
