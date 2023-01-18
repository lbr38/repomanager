<?php

namespace Controllers\Cve\Tools;

use \Exception;
use \Controllers\Common;

class Import
{
    private $model;
    private $cveController;
    private $feedUrl;
    private $jsonFile;
    private $importId;
    private $hostImportId;
    private $feeds = array(
        'https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2022.json.gz',
        'https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2023.json.gz',
        'https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-modified.json.gz'
    );

    public function __construct()
    {
        $this->model = new \Models\Cve\Tools\Import();
        $this->cveController = new \Controllers\Cve\Cve();
    }

    /**
     *  Set new started import in database
     *  Returns import Id
     */
    private function setStartImport()
    {
        return $this->model->setStartImport();
    }

    /**
     *  Set end CVE import in database
     */
    private function setEndImport(string $importId, string $duration)
    {
        $this->model->setEndImport($importId, $duration);
    }

    /**
     *  Set CVE import status in database
     */
    private function setImportStatus(string $importId, string $status)
    {
        $this->model->setImportStatus($importId, $status);
    }

    /**
     *  Set new started host import in database
     */
    private function setStartHostImport()
    {
        return $this->model->setStartHostImport();
    }

    /**
     *  Set end host import in database
     */
    private function setEndHostImport(string $importId, string $duration)
    {
        $this->model->setEndHostImport($importId, $duration);
    }

    /**
     *  Set host import status in database
     */
    private function setHostImportStatus(string $importId, string $status)
    {
        $this->model->setHostImportStatus($importId, $status);
    }

    /**
     *  Import CVEs JSON feeds into database
     */
    public function import()
    {
        $timeStart = microtime(true);

        /**
         *  Add a new import in database
         */
        $this->importId = $this->setStartImport();

        try {
            /**
             *  Clear database
             */
            $this->clearDatabase();

            foreach ($this->feeds as $feedUrl) {
                /**
                 *  Check that feed URL exist
                 */
                if (\Controllers\Common::urlFileExists($feedUrl) === false) {
                    throw new Exception('Feed URL does not exist');
                }

                /**
                 *  Define target local file
                 */
                $savePath = DATA_DIR . '/cve-feed.gz';
                $gzippedFeed = fopen($savePath, "w");

                /**
                 *  Init curl
                 */
                $ch = curl_init();

                /**
                 *  Download and unzip JSON feed
                 */
                curl_setopt($ch, CURLOPT_URL, $feedUrl);
                curl_setopt($ch, CURLOPT_FILE, $gzippedFeed);   // set output file
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);         // set timeout
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // follow redirect
                curl_setopt($ch, CURLOPT_ENCODING, '');         // use compression if any
                curl_exec($ch);

                if (curl_errno($ch)) {
                    curl_close($ch);
                    fclose($gzippedFeed);

                    throw new Exception('Curl error: ' . curl_error($ch));
                }

                /**
                 *  Check that the http return code is 200 (the file has been downloaded)
                 */
                $status = curl_getinfo($ch);

                if ($status["http_code"] != 200) {
                    /**
                     *  If return code is 404
                     */
                    if ($status["http_code"] == '404') {
                        throw new Exception('File not found');
                    } else {
                        throw new Exception('File could not be downloaded (http return code is: ' . $status["http_code"] . ')');
                    }

                    curl_close($ch);
                    fclose($gzippedFeed);

                    return false;
                }

                fclose($gzippedFeed);
                curl_close($ch);

                /**
                 *  Gunzip feed file
                 */
                \Controllers\Common::gunzip($savePath);

                $jsonFile = str_replace('.gz', '', $savePath);

                if (!file_exists($jsonFile)) {
                    throw new Exception('JSON file does not exist');
                }

                if (!is_readable($jsonFile)) {
                    throw new Exception('JSON file is not readable');
                }

                /**
                 *  Decode JSON file
                 */
                $cveList = json_decode(file_get_contents($jsonFile));

                /**
                 *  Remove files
                 */
                unlink($savePath);
                unlink($jsonFile);

                /**
                 *  Parse each CVEs and insert them in database
                 */
                foreach ($cveList->CVE_Items as $cveItems) {
                    $date = '';
                    $time = '';
                    $cpe23UriGlobal = array();
                    $cpe23UriRaw = array();
                    $cpe23UriRawStr = '';
                    $references = array();
                    $description = '';
                    $updatedDate = '';
                    $updatedTime = '';
                    $cvss2Score = '';
                    $cvss3Score = '';

                    /**
                     *  Continue if no CVE Id found
                     */
                    if (empty($cveItems->cve->CVE_data_meta->ID)) {
                        continue;
                    }

                    $id = \Controllers\Common::validateData($cveItems->cve->CVE_data_meta->ID);

                    /**
                     *  Description
                     */
                    if (!empty($cveItems->cve->description->description_data)) {
                        foreach ($cveItems->cve->description->description_data as $description) {
                            if ($description->lang == 'en') {
                                $description = \Controllers\Common::validateData($description->value);
                            }
                        }
                    }

                    /**
                     *  Date
                     */
                    if (!empty($cveItems->publishedDate)) {
                        $publishedDate = \Controllers\Common::validateData($cveItems->publishedDate);

                        /**
                         *  Parse date and time from retrieved data
                         */
                        $publishedDate = explode('T', $publishedDate);
                        $date = $publishedDate[0];
                        $time = str_replace('Z', '', $publishedDate[1]);
                    }

                    /**
                     *  Updated date
                     */
                    if (!empty($cveItems->lastModifiedDate)) {
                        $lastModifiedDate = \Controllers\Common::validateData($cveItems->lastModifiedDate);

                        /**
                         *  Parse date and time from retrieved data
                         */
                        $lastModifiedDate = explode('T', $lastModifiedDate);
                        $updatedDate = $lastModifiedDate[0];
                        $updatedTime = str_replace('Z', '', $lastModifiedDate[1]);
                    }

                    /**
                     *  cvss2Score
                     */
                    if (!empty($cveItems->impact->baseMetricV2->cvssV2->baseScore)) {
                        $cvss2Score = \Controllers\Common::validateData($cveItems->impact->baseMetricV2->cvssV2->baseScore);
                    }

                    /**
                     *  cvss3Score
                     */
                    if (!empty($cveItems->impact->baseMetricV3->cvssV3->baseScore)) {
                        $cvss3Score = \Controllers\Common::validateData($cveItems->impact->baseMetricV3->cvssV3->baseScore);
                    }

                    /**
                     *  cpe23Uri
                     */
                    if (!empty($cveItems->configurations->nodes[0]->cpe_match)) {
                        foreach ($cveItems->configurations->nodes as $node) {
                            foreach ($node->cpe_match as $cpe) {
                                $part = '';
                                $vendor = '';
                                $product = '';
                                $version = '';

                                if (!empty($cpe->cpe23Uri)) {
                                    // e.g. of cpe23Uri:
                                    // cpe:2.3:o:linux:linux_kernel:*:*:*:*:*:*:*:*

                                    $cpe23UriRaw[] = \Controllers\Common::validateData($cpe->cpe23Uri);

                                    $uriExplode = explode(':', $cpe->cpe23Uri);

                                    /**
                                     *  Retrieve all fields
                                     */
                                    $part = \Controllers\Common::validateData($uriExplode[2]);
                                    $vendor = ucfirst(str_replace('_', ' ', \Controllers\Common::validateData($uriExplode[3])));
                                    $product = ucfirst(str_replace('_', ' ', \Controllers\Common::validateData($uriExplode[4])));
                                    $version = \Controllers\Common::validateData($uriExplode[5]);
                                    $subVersion = \Controllers\Common::validateData($uriExplode[6]);
                                    // $update = $uriExplode[6];
                                    // $edition = $uriExplode[7];
                                    // $language = $uriExplode[8];

                                    /**
                                     *  $cpe23UriGlobal = array(
                                     *     array('vendor' => $vendor, 'product' => $product, 'version' => $version);
                                     *     array('vendor' => $vendor, 'product' => $product, 'version' => $version);
                                     *  )
                                     */
                                    $cpe23UriGlobal[] = array('part' => $part, 'vendor' => $vendor, 'product' => $product, 'version' => $version);
                                }
                            }
                        }
                    }

                    /**
                     *  References (links...)
                     */
                    if (!empty($cveItems->cve->references->reference_data)) {
                        foreach ($cveItems->cve->references->reference_data as $reference) {
                            $name = '';
                            $url = '';
                            $source = '';
                            $tags = array();
                            $tagsStr = '';

                            if (!empty($reference->name)) {
                                $name = \Controllers\Common::validateData($reference->name);
                            }
                            if (!empty($reference->url)) {
                                $url = \Controllers\Common::validateData($reference->url);
                            }
                            if (!empty($reference->refsource)) {
                                $source = \Controllers\Common::validateData($reference->refsource);
                            }
                            if (!empty($reference->tags)) {
                                foreach ($reference->tags as $tag) {
                                    $tags[] = $tag;
                                }
                                $tagsStr = implode(',', $tags);
                                $tagsStr = rtrim($tagsStr, ',');
                            }

                            $references[] = array('name' => $name, 'url' => $url, 'source' => $source, 'tags' => $tagsStr);
                        }
                    }

                    /**
                     *  Format data before inserting it in database
                     */
                    if (!empty($cpe23UriRaw)) {
                        $cpe23UriRawStr = implode(',', $cpe23UriRaw);
                    }

                    /**
                     *  Check if CVE already exist in database
                     *  If so then update CVE, else insert CVE
                     */
                    if (!$this->cveController->nameExists($id)) {
                        $this->cveController->new($id, $date, $time, $updatedDate, $updatedTime, $cpe23UriRawStr, $cpe23UriGlobal, $description, $references, $cvss2Score, $cvss3Score);
                    } else {
                        $this->cveController->update($id, $date, $time, $updatedDate, $updatedTime, $cpe23UriRawStr, $cpe23UriGlobal, $description, $references, $cvss2Score, $cvss3Score);
                    }
                }

                unset($id, $date, $time, $updatedDate, $updatedTime, $cpe23UriRaw, $cpe23UriRawStr, $cpe23UriGlobal, $description, $references, $cvss2Score, $cvss3Score, $cveList);
            }
        } catch (Exception $e) {
            /**
             *  Set import status to error
             */
            $this->setImportStatus($this->importId, 'error');
            $this->setEndImport($this->importId, microtime(true) - $timeStart);

            /**
             *  Add error to log file
             */
            file_put_contents(CVE_IMPORT_LOG_DIR . '/cve-import-' . $this->importId . '.error', 'Error while importing CVEs (import Id ' . $this->importId . '): '. $e->getMessage() . PHP_EOL, FILE_APPEND);

            return false;
        }

        /**
         *  Set import status to done
         */
        $this->setImportStatus($this->importId, 'done');
        $this->setEndImport($this->importId, microtime(true) - $timeStart);

        return true;
    }

    /**
     *  Search for affected hosts in CVEs
     */
    public function importAffectedHosts()
    {
        $myhost = new \Controllers\Host();
        $mycve = new \Controllers\Cve\Cve();

        try {
            /**
             *  Get all active hosts
             */
            $hosts = $myhost->listAll('active');

            /**
             *  Quit if there is no active host
             */
            if (count($hosts) == 0) {
                return;
            }

            $timeStart = microtime(true);

            /**
             *  Add a new import in database
             */
            $this->hostImportId = $this->setStartHostImport();

            /**
             *  Get all CVEs Id
             */
            $cvesId = $this->cveController->getAllId();

            if (empty($cvesId)) {
                throw new Exception('Unable to search for CVEs affected hosts: no CVE found in database');
            }

            if (!is_dir(CVE_IMPORT_HOSTS_DIR)) {
                if (!mkdir(CVE_IMPORT_HOSTS_DIR, 0777, true)) {
                    throw new Exception('Unable to search for CVEs affected hosts: cannot create directory ' . CVE_IMPORT_HOSTS_DIR);
                }
            }

            /**
             *  Export all installed packages on all active hosts into a file
             */
            $hostsArray = array();

            foreach ($hosts as $host) {
                /**
                 *  Retrieve OS version and family
                 */
                $hostId = $host['Id'];
                $hostName = $host['Hostname'];
                $hostOs = $host['Os'];
                $hostOsVersion = $host['Os_version'];
                $hostOsFamily = $host['Os_family'];

                /**
                 *  Open host database
                 */
                $myhost->openHostDb($host['Id']);

                /**
                 *  Get list of all installed packages on this host
                 */
                $installedPackages = $myhost->getPackagesInstalled($host['Id']);
                $installedPackagesArray = array();

                foreach ($installedPackages as $package) {
                    $installedPackagesArray[] = array(
                        'Id' => $package['Id'],
                        'Name' => $package['Name'],
                        'Version' => $package['Version']
                    );
                }

                $hostsArray[$hostId] = array(
                    'Hostname' => $hostName,
                    'Os' => $hostOs,
                    'Os_version' => $hostOsVersion,
                    'Os_family' => $hostOsFamily,
                    'Installed_packages' => $installedPackagesArray
                );

                /**
                 *  Close host database
                 */
                $myhost->closeHostDb();
            }

            /**
             *  Add all hosts and their installed packages to a file
             */
            file_put_contents(DATA_DIR . '/.temp/export-hosts.json', json_encode($hostsArray, JSON_PRETTY_PRINT));

            /**
             *  Search for affected hosts
             */
            $mycve->searchAffectedHosts();

            /**
             *  Send a mail with affected hosts
             */
            $this->cveController->sendMailWithAffectedHosts();
        } catch (Exception $e) {
            /**
             *  Set import status to error
             */
            $this->setHostImportStatus($this->hostImportId, 'error');
            $this->setEndHostImport($this->hostImportId, microtime(true) - $timeStart);

            /**
             *  Add error to log file
             */
            file_put_contents(CVE_LOG_DIR . '/hosts-import-' . $this->hostImportId . '.error', 'Error while importing CVE affected hosts (import Id ' . $this->hostImportId . '): '. $e->getMessage() . PHP_EOL, FILE_APPEND);

            return false;
        }

        /**
         *  Set import status to done
         */
        $this->setHostImportStatus($this->hostImportId, 'done');
        $this->setEndHostImport($this->hostImportId, microtime(true) - $timeStart);

        return true;
    }

    /**
     *  Drop and recreate CVE related tables
     */
    public function clearDatabase()
    {
        $this->model->clearDatabase();
    }
}
