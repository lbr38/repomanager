<?php

namespace Controllers\Service;

use Exception;
use Datetime;

class Statistic extends Service
{
    protected $logController;
    private $statController;
    private $repoController;
    private $repoListingController;

    public function __construct()
    {
        $this->logController = new \Controllers\Log\Log();
        $this->statController = new \Controllers\Stat();
        $this->repoController = new \Controllers\Repo\Repo();
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

        echo $this->getDate() . ' Cleaning old statistics...' . PHP_EOL;

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

        echo $this->getDate() . ' Generating statistics...' . PHP_EOL;

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
            $myprocess = new \Controllers\Process('ps aux | grep "' . ROOT . '/tools/service.php stats/accesslog/parse" | grep -v grep');
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
            echo $this->getDate() . ' Running access log parsing...' . PHP_EOL;

            $myprocess = new \Controllers\Process("/usr/bin/php /var/www/repomanager/tools/service.php 'stats/accesslog/parse' >/dev/null 2>/dev/null &");
            $myprocess->execute();
            $myprocess->close();
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', 'Error while launching access log parsing operation: ' . $e->getMessage());
        }
    }

    /**
     *  Parse access log and add repo access stats to database
     */
    public function parseAccessLog()
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
             *  Get and check settings every 5 seconds to make sure that stats are still enabled
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
                     *  Add full log line to database
                     */
                    $this->statController->addAccessToQueue($line);

                    unset($line);
                }

                $size = ftell($file);
            }

            // usleep(100);
            usleep(100000); // 0.1 second
        }
    }

    /**
     *  Process accesslog in the queue and add stats to database
     */
    public function processAccessLog()
    {
        while (true) {
            /**
             *  Get and check settings regulary to make sure that stats are still enabled
             */
            $this->getSettings();

            /**
             *  Break if stats have been disabled
             */
            if ($this->statsEnabled != 'true') {
                break;
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

            /**
             *  Retrieve access log entries from the queue (100 entries max)
             */
            $queue = $this->statController->getAccessQueue();

            /**
             *  Process access log entries
             */
            if (!empty($queue)) {
                foreach ($queue as $line) {
                    $id = $line['Id'];
                    $type = '';
                    $dist = '';
                    $section = '';

                    /**
                     *  Parse request
                     */
                    $request = explode(' ', $line['Request']);

                    print_r($request);

                    /**
                     *  Date and time
                     */
                    $dateExplode = explode(':', str_replace('[', '', $request[3]));
                    $date = DateTime::createFromFormat('d/M/Y', $dateExplode[0])->format('Y-m-d');
                    $time = $dateExplode[1] . ':' . $dateExplode[2] . ':' . $dateExplode[3];

                    /**
                     *  Source IP
                     */
                    $sourceIp = $request[0];

                    /**
                     *  Source host from IP
                     */
                    $sourceHost = gethostbyaddr($sourceIp);

                    /**
                     *  Complete request
                     */
                    $fullRequest = str_replace('"', '', $request[5] . ' ' . $request[6] . ' ' . $request[7]);

                    /**
                     *  Request result
                     */
                    $requestResult = $request[8];

                    /**
                     *  Request grabber
                     */
                    $requestGrabber = $request[12];

                    /**
                     *  Retrieve repo type
                     *
                     *
                     *  Case the request matches deb repo pattern
                     *  e.g: GET /repo/ubuntu/jammy/main_preprod/dists/jammy/Release HTTP/1.1
                     *
                     *  If APT-HTTP or APT-CURL is found in the request grabber, it means that the request is made by apt
                     */
                    if (preg_match('/APT-HTTP|APT-CURL/', $requestGrabber) and preg_match('#/repo/.*/pool/|/repo/.*/dists/#', $fullRequest)) {
                        $type = 'deb';

                        /**
                         *  Parse repo name, dist, section and env
                         */
                        $fullRequestExplode = explode('/', $fullRequest);
                        $name = $fullRequestExplode[2];
                        $dist = $fullRequestExplode[3];
                        $section = explode('_', $fullRequestExplode[4])[0];
                        $env = explode('_', $fullRequestExplode[4])[1];

                        /**
                         *  If the repo does not exist, continue
                         */
                        if (!$this->repoController->existsEnv($name, $dist, $section, $env)) {
                            echo 'Could not find repo for request with Id ' . $id . PHP_EOL;
                            continue;
                        }
                    }

                    /**
                     *  Case the request matches rpm repo pattern
                     *  e.g: GET /repo/epel_prod/repodata/repomd.xml HTTP/1.1
                     *
                     *  If APT-HTTP or APT-CURL is NOT found in the request grabber, it means that the request might be made by yum
                     */
                    if (!preg_match('/APT-HTTP|APT-CURL/', $requestGrabber)
                        and preg_match('/yum.*/', $requestGrabber)
                        and preg_match('#/repo/.*/packages/|/repo/.*/repodata/#', $fullRequest)) {
                        $type = 'rpm';

                        /**
                         *  Parse repo name and env
                         */
                        $fullRequestExplode = explode('/', $fullRequest);
                        $name = explode('_', $fullRequestExplode[2])[0];
                        $env = explode('_', $fullRequestExplode[2])[1];

                        /**
                         *  If the repo does not exist, continue
                         */
                        if (!$this->repoController->existsEnv($name, '', '', $env)) {
                            echo 'Could not find repo for request with Id ' . $id . PHP_EOL;
                            continue;
                        }
                    }

                    /**
                     *  If the request does not match any of the above patterns, skip it
                     *  It might be a request for a file that is not part of a repository (/gpgkeys/ for example)
                     */
                    if (empty($type)) {
                        echo 'Could not find repo type for request with Id ' . $id . PHP_EOL;

                        /**
                         *  Delete the log line from the queue
                         */
                        $this->statController->deleteFromQueue($id);

                        continue;
                    }

                    // For debugging
                    echo 'Date: ' . $date . PHP_EOL;
                    echo 'Time: ' . $time . PHP_EOL;
                    echo 'Source IP: ' . $sourceIp . PHP_EOL;
                    echo 'Source host: ' . $sourceHost . PHP_EOL;
                    echo 'Request: ' . $fullRequest . PHP_EOL;
                    echo 'Request result: ' . $requestResult . PHP_EOL;
                    echo 'Request grabber: ' . $requestGrabber . PHP_EOL;
                    echo 'Type: ' . $type . PHP_EOL;
                    echo 'Name: ' . $name . PHP_EOL;
                    if (!empty($dist) and !empty($section)) {
                        echo 'Dist: ' . $dist . PHP_EOL;
                        echo 'Section: ' . $section . PHP_EOL;
                    }
                    echo 'Env: ' . $env . PHP_EOL;

                    /**
                     *  Add repo access log to database
                     */
                    if (!empty($date) and !empty($time) and !empty($type) and !empty($name) and !empty($env) and !empty($sourceHost) and !empty($sourceIp) and !empty($fullRequest) and !empty($requestResult)) {
                        $this->statController->addAccess($date, $time, $type, $name, $dist, $section, $env, $sourceHost, $sourceIp, $fullRequest, $requestResult);
                    }

                    /**
                     *  Then delete the log line from the queue
                     */
                    $this->statController->deleteFromQueue($id);

                    unset($id, $line, $dateExplode, $date, $time, $sourceIp, $sourceHost, $fullRequest, $requestResult, $type, $name, $dist, $section, $env);
                }
            }

            sleep(2);
        }
    }
}
