<?php

namespace Controllers\Service\Unit;

use Exception;
use DateTime;

class Statistic extends \Controllers\Service\Service
{
    private $statController;
    private $repoListingController;

    public function __construct(string $unit)
    {
        parent::__construct($unit);

        $this->statController = new \Controllers\Stat();
        $this->repoListingController = new \Controllers\Repo\Listing();
    }

    /**
     *  Clean old statistics
     */
    public function clean() : void
    {
        parent::log('Starting statistics cleaning task...');

        $this->statController->clean();

        parent::log('Statistics cleaning task completed');
    }

    /**
     *  Generate statistics on repo size and repo package count
     */
    public function generate() : void
    {
        parent::log('Starting repositories statistics generation task...');

        /**
         *  Get all repos
         */
        $reposList = $this->repoListingController->list();

        if (empty($reposList)) {
            parent::log('No repository found, nothing to do');
            return;
        }

        try {
            foreach ($reposList as $repo) {
                /**
                 *  Continue if the repo snapshot has no environment, because stats are only generated for snapshots environments
                 */
                if (empty($repo['envId'])) {
                    continue;
                }

                if ($repo['Package_type'] == 'rpm') {
                    if (file_exists(REPOS_DIR . '/rpm/' . $repo['Name'] . '/' . $repo['Releasever'] . '/' . $repo['Env'])) {
                        /**
                         *  Calculate repo size in bytes
                         */
                        $repoSize = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/rpm/' . $repo['Name'] . '/' . $repo['Releasever'] . '/' . $repo['Env']);

                        /**
                         *  Calculate number of packages in the repo
                         */
                        $packagesCount = count(\Controllers\Filesystem\File::findRecursive(REPOS_DIR . '/rpm/' . $repo['Name'] . '/' . $repo['Releasever'] . '/' . $repo['Env'], ['rpm']));
                    }
                }

                if ($repo['Package_type'] == 'deb') {
                    if (file_exists(REPOS_DIR . '/deb/' . $repo['Name'] . '/' . $repo['Dist'] . '/' . $repo['Section'] . '/' . $repo['Env'])) {
                        /**
                         *  Calculate repo size in bytes
                         */
                        $repoSize = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/deb/' . $repo['Name'] . '/' . $repo['Dist'] . '/' . $repo['Section'] . '/' . $repo['Env']);

                        /**
                         *  Calculate number of packages in the repo
                         */
                        $packagesCount = count(\Controllers\Filesystem\File::findRecursive(REPOS_DIR . '/deb/' . $repo['Name'] . '/' . $repo['Dist'] . '/' . $repo['Section'] . '/' . $repo['Env'], ['deb']));
                    }
                }

                /**
                 *  Add repo size and package count to stats database
                 */
                if (!empty($repoSize)) {
                    $this->statController->add(date('Y-m-d'), date('H:i:s'), $repoSize, $packagesCount, $repo['envId']);
                }
            }

            parent::log('Repositories statistics generation task completed');
        } catch (Exception $e) {
            throw new Exception('Error while generating repositories statistics: ' . $e->getMessage());
        }
    }

    /**
     *  Parse access log and add repo access stats to database
     */
    public function parseLogs() : void
    {
        $path = '/var/log/nginx/repomanager_access.log';
        $nextSettingsCheck = time() + 5;

        /**
         *  Quit if stats are disabled
         */
        if (parent::getSettings('STATS_ENABLED') != 'true') {
            parent::logDebug('Repositories stats are disabled, quitting access log parsing task');
            return;
        }

        /**
         *  Check if the access log file exists
         */
        if (!file_exists($path)) {
            throw new Exception('Access log file ' . $path . ' does not exist');
        }

        /**
         *  Check if the access log file is readable
         */
        if (!is_readable($path)) {
            throw new Exception('Access log file ' . $path . ' is not readable');
        }

        parent::log('Starting access log parsing task');

        /**
         *  Open the access log file
         */
        $file = fopen($path, 'r');
        fseek($file, 0, SEEK_END);
        $inode = fstat($file)['ino'];
        $size = filesize($path);

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
                 *  Break if stats have been disabled
                 */
                if (parent::getSettings('STATS_ENABLED') != 'true') {
                    parent::log('Repositories stats are disabled, quitting access log parsing task');
                    break;
                }

                $nextSettingsCheck = (time() + 5);
            }

            /**
             *  Stop parsing if the stop file exists
             */
            if (file_exists(DATA_DIR . '/.service.' . $this->unit . '.stop')) {
                parent::log('Stop file found, quitting access log parsing task');
                unlink(DATA_DIR . '/.service.' . $this->unit . '.stop');
                break;
            }

            /**
             *  Wait if a repomanager update or maintenance is running
             */
            while (UPDATE_RUNNING or MAINTENANCE) {
                parent::logDebug('An update or maintenance is in progress, pausing access log parsing task');
                sleep(2);
                continue;
            }

            clearstatcache();

            if (!file_exists($path) || ($file = @fopen($path, 'r')) === false) {
                sleep(2);
                continue;
            }

            // Get current inode
            $currentInode = fstat($file)['ino'];

            // If current inode is different from the previous one, the file has been rotated or recreated
            if ($currentInode != $inode) {
                fclose($file);
                $file = fopen($path, 'r');
                fseek($file, 0, SEEK_END);
                $inode = $currentInode;
                $size = 0;
            }

            $currentSize = filesize($path);

            if ($currentSize < $size) {
                fclose($file);
                $file = fopen($path, 'r');
                fseek($file, 0, SEEK_END);
                $inode = fstat($file)['ino'];
                $size = 0;
            } else if ($currentSize > $size) {
                fseek($file, $size);

                while (!feof($file)) {
                    // Get line from access log file
                    $line = fgets($file);

                    // Skip line if it does not contain a repo access
                    if (!preg_match('/urlgrabber|libdnf|APT-CURL|APT-HTTP/', $line)) {
                        continue;
                    }

                    parent::logDebug('Adding access log line to the queue: ' . trim($line));

                    // Add full log line to the queue in database
                    $this->statController->addAccessToQueue($line);

                    unset($line);
                }

                $size = ftell($file);
            }

            pcntl_signal_dispatch();

            // 0.1 second
            usleep(100000);
        }
    }

    /**
     *  Process accesslog in the queue and add stats to database
     */
    public function processQueue() : void
    {
        while (true) {
            /**
             *  Break if stats have been disabled
             */
            if (parent::getSettings('STATS_ENABLED') != 'true') {
                parent::logDebug('Repositories stats are disabled, quitting access queue processing task');
                break;
            }

            /**
             *  Stop parsing if the stop file exists
             */
            if (file_exists(DATA_DIR . '/.service.' . $this->unit . '.stop')) {
                parent::logDebug('Stop file found, quitting access queue processing task');
                unlink(DATA_DIR . '/.service.' . $this->unit . '.stop');
                break;
            }

            /**
             *  Wait if a repomanager update or maintenance is running
             */
            while (UPDATE_RUNNING or MAINTENANCE) {
                parent::logDebug('An update or maintenance is in progress, pausing access logs processing task');
                sleep(5);
                continue;
            }

            clearstatcache();

            /**
             *  Retrieve access log entries from the queue (100 entries max)
             */
            $queue = $this->statController->getAccessQueue();

            /**
             *  Get all repos
             */
            $reposList = $this->repoListingController->list();

            if (empty($queue)) {
                parent::logDebug('No access log entries in the queue to process, exiting');
                break;
            }

            if (empty($reposList)) {
                parent::logDebug('No repository found, exiting');
                break;
            }

            parent::log('Processing ' . count($queue) . ' access log entries from the queue...');

            /**
             *  Process access log entries
             */
            foreach ($queue as $line) {
                $id = $line['Id'];

                /**
                 *  Parse request
                 */
                $request = explode(' ', $line['Request']);

                // debug
                // echo print_r($request, true) . PHP_EOL;

                /**
                 *  Date and time
                 */
                $dateExplode = explode(':', str_replace('[', '', $request[3]));

                /**
                 *  Check if date is valid
                 *  If not, then delete the log line from the queue as it is invalid
                 */
                if (empty($dateExplode[0]) or !DateTime::createFromFormat('d/M/Y', $dateExplode[0])) {
                    /**
                     *  Delete the log line from the queue
                     */
                    $this->statController->deleteFromQueue($id);
                    continue;
                }

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
                 *  Loop through repos list until the repo called in the request is found
                 */
                foreach ($reposList as $repo) {
                    $releasever = '';
                    $dist = '';
                    $section = '';

                    /**
                     *  Continue if the repo snapshot has no environment, because stats are only generated for snapshots environments
                     */
                    if (empty($repo['envId'])) {
                        continue;
                    }

                    /**
                     *  Build repository URI path
                     */

                    /**
                     *  Case the repo is a deb repo
                     */
                    if ($repo['Package_type'] == 'deb') {
                        $repoUri = '/repo/deb/' . $repo['Name'] . '/' . $repo['Dist'] . '/' . $repo['Section'] . '/' . $repo['Env'];
                    }

                    /**
                     *  Case the repo is a rpm repo
                     */
                    if ($repo['Package_type'] == 'rpm') {
                        $repoUri = '/repo/rpm/' . $repo['Name'] . '/' . $repo['Releasever'] . '/' . $repo['Env'];
                    }

                    /**
                     *  Now if the repo URI is found in the request, it means that the request is made for this repo
                     */
                    if (preg_match('#' . $repoUri . '#', $fullRequest)) {
                        $name = $repo['Name'];
                        $env = $repo['Env'];

                        if ($repo['Package_type'] == 'rpm') {
                            $releasever = $repo['Releasever'];
                        }

                        if ($repo['Package_type'] == 'deb') {
                            $dist = $repo['Dist'];
                            $section = $repo['Section'];
                        }

                        // For debugging
                        // echo 'Date: ' . $date . PHP_EOL;
                        // echo 'Time: ' . $time . PHP_EOL;
                        // echo 'Source IP: ' . $sourceIp . PHP_EOL;
                        // echo 'Source host: ' . $sourceHost . PHP_EOL;
                        // echo 'Request: ' . $fullRequest . PHP_EOL;
                        // echo 'Request result: ' . $requestResult . PHP_EOL;
                        // echo 'Request grabber: ' . $requestGrabber . PHP_EOL;
                        // echo 'Type: ' . $repo['Package_type'] . PHP_EOL;
                        // echo 'Name: ' . $name . PHP_EOL;
                        // if (!empty($dist) and !empty($section)) {
                        //     echo 'Dist: ' . $dist . PHP_EOL;
                        //     echo 'Section: ' . $section . PHP_EOL;
                        // }
                        // echo 'Env: ' . $env . PHP_EOL . PHP_EOL;

                        /**
                         *  Add repo access log to database
                         */
                        if ($repo['Package_type'] == 'rpm') {
                            if (!empty($date) and !empty($time) and !empty($name) and !empty($releasever) and !empty($env) and !empty($sourceHost) and !empty($sourceIp) and !empty($fullRequest) and !empty($requestResult)) {
                                $this->statController->addRpmAccess($date, $time, $name, $releasever, $env, $sourceHost, $sourceIp, $fullRequest, $requestResult);
                            }
                        }

                        if ($repo['Package_type'] == 'deb') {
                            if (!empty($date) and !empty($time) and !empty($name) and !empty($dist) and !empty($section) and !empty($env) and !empty($sourceHost) and !empty($sourceIp) and !empty($fullRequest) and !empty($requestResult)) {
                                $this->statController->addDebAccess($date, $time, $name, $dist, $section, $env, $sourceHost, $sourceIp, $fullRequest, $requestResult);
                            }
                        }

                        /**
                         *  Delete the log line from the queue now that it has been processed
                         */
                        $this->statController->deleteFromQueue($id);

                        /**
                         *  Process the next log line
                         */
                        continue 2;
                    }
                }

                /**
                 *  If the request does not match any of the repo URI patterns, skip it
                 */
                parent::logError('Could not find matching repository for request #' . $id . ': ' . $fullRequest);

                /**
                 *  Delete the log line from the queue
                 */
                $this->statController->deleteFromQueue($id);
            }

            parent::log('Access log queue processing completed');

            sleep(5);
        }
    }
}
