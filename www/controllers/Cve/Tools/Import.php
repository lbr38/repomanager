<?php

namespace Controllers\Cve\Tools;

use \Exception;
use \Controllers\Utils\Validate;
use \Controllers\Utils\Compress\Gzip;

class Import
{
    private $model;
    private $cveController;
    private $feedUrl;
    private $jsonFile;
    private $importId;
    private $hostImportId;
    private $feeds = array(
        'https://nvd.nist.gov/feeds/json/cve/2.0/nvdcve-2.0-2022.json.gz',
        'https://nvd.nist.gov/feeds/json/cve/2.0/nvdcve-2.0-2023.json.gz',
        'https://nvd.nist.gov/feeds/json/cve/2.0/nvdcve-2.0-2024.json.gz',
        'https://nvd.nist.gov/feeds/json/cve/2.0/nvdcve-2.0-2025.json.gz',
        'https://nvd.nist.gov/feeds/json/cve/2.0/nvdcve-2.0-modified.json.gz'
    );

    public function __construct()
    {
        $this->model = new \Models\Cve\Tools\Import();
        $this->cveController = new \Controllers\Cve\Cve();
        $this->httpRequestController = new \Controllers\HttpRequest();
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
        try {
            $timeStart = microtime(true);

            /**
             *  Add a new import in database
             */
            $this->importId = $this->setStartImport();

            /**
             *  Clear database
             */
            $this->clearDatabase();

            foreach ($this->feeds as $feedUrl) {
                // Check if the URL is reachable
                try {
                    \Controllers\Common::urlReachable($feedUrl, 10);
                } catch (Exception $e) {
                    throw new Exception('Feed ' . $feedUrl . ' is unreachable: ' . $e->getMessage());
                }

                // Define target local file
                $savePath = DATA_DIR . '/cve-feed.gz';

                // Download feed file
                try {
                    $this->httpRequestController->get([
                        'url'        => $feedUrl,
                        'outputToFile' => DATA_DIR . '/cve-feed.gz',
                        'timeout'    => 30,
                        'proxy'      => PROXY ?? null,
                    ]);
                } catch (Exception $e) {
                    throw new Exception('error while downloading feed ' . $feedUrl . ': ' . $e->getMessage());
                }

                /**
                 *  Gunzip feed file
                 */
                try {
                    Gzip::uncompress($savePath);
                } catch (Exception $e) {
                    throw new Exception('Error while uncompressing ' . $savePath . ' feed file: ' . $e->getMessage());
                }

                $jsonFile = str_replace('.gz', '', $savePath);

                if (!file_exists($jsonFile)) {
                    throw new Exception('JSON file ' . $jsonFile . ' does not exist');
                }

                if (!is_readable($jsonFile)) {
                    throw new Exception('JSON file ' . $jsonFile . ' is not readable');
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
                    $cpe23UriGlobal = [];
                    $cpe23UriRaw = [];
                    $cpe23UriRawStr = '';
                    $references = [];
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

                    $id = Validate::string($cveItems->cve->CVE_data_meta->ID);

                    /**
                     *  Description
                     */
                    if (!empty($cveItems->cve->description->description_data)) {
                        foreach ($cveItems->cve->description->description_data as $description) {
                            if ($description->lang == 'en') {
                                $description = Validate::string($description->value);
                            }
                        }
                    }

                    /**
                     *  Date
                     */
                    if (!empty($cveItems->publishedDate)) {
                        $publishedDate = Validate::string($cveItems->publishedDate);

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
                        $lastModifiedDate = Validate::string($cveItems->lastModifiedDate);

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
                        $cvss2Score = Validate::string($cveItems->impact->baseMetricV2->cvssV2->baseScore);
                    }

                    /**
                     *  cvss3Score
                     */
                    if (!empty($cveItems->impact->baseMetricV3->cvssV3->baseScore)) {
                        $cvss3Score = Validate::string($cveItems->impact->baseMetricV3->cvssV3->baseScore);
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

                                    $cpe23UriRaw[] = Validate::string($cpe->cpe23Uri);

                                    $uriExplode = explode(':', $cpe->cpe23Uri);

                                    /**
                                     *  Retrieve all fields
                                     */
                                    $part = Validate::string($uriExplode[2]);
                                    $vendor = ucfirst(str_replace('_', ' ', Validate::string($uriExplode[3])));
                                    $product = ucfirst(str_replace('_', ' ', Validate::string($uriExplode[4])));
                                    $version = Validate::string($uriExplode[5]);
                                    $subVersion = Validate::string($uriExplode[6]);
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
                            $tags = [];
                            $tagsStr = '';

                            if (!empty($reference->name)) {
                                $name = Validate::string($reference->name);
                            }
                            if (!empty($reference->url)) {
                                $url = Validate::string($reference->url);
                            }
                            if (!empty($reference->refsource)) {
                                $source = Validate::string($reference->refsource);
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

            /**
             *  Set import status to done
             */
            $this->setImportStatus($this->importId, 'done');
            $this->setEndImport($this->importId, microtime(true) - $timeStart);
        } catch (Exception $e) {
            /**
             *  Set import status to error
             */
            $this->setImportStatus($this->importId, 'error');
            $this->setEndImport($this->importId, microtime(true) - $timeStart);

            throw new Exception('import #' . $this->importId . ' failed: ' . $e->getMessage());
        }
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
            $hosts = $myhost->listAll();

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
                if (!mkdir(CVE_IMPORT_HOSTS_DIR, 0770, true)) {
                    throw new Exception('Unable to search for CVEs affected hosts: cannot create directory ' . CVE_IMPORT_HOSTS_DIR);
                }
            }

            /**
             *  Export all installed packages on all active hosts into a file
             */
            $hostsArray = [];

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
                $hostPackageController = new \Controllers\Host\Package\Package($host['Id']);

                /**
                 *  Get list of all installed packages on this host
                 */
                $installedPackages = $hostPackageController->getInstalled();
                $installedPackagesArray = [];

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

            /**
             *  Set import status to done
             */
            $this->setHostImportStatus($this->hostImportId, 'done');
            $this->setEndHostImport($this->hostImportId, microtime(true) - $timeStart);
        } catch (Exception $e) {
            /**
             *  Set import status to error
             */
            $this->setHostImportStatus($this->hostImportId, 'error');
            $this->setEndHostImport($this->hostImportId, microtime(true) - $timeStart);

            throw new Exception('import affected hosts #' . $this->hostImportId . ' failed: ' . $e->getMessage());
        }
    }

    /**
     *  Drop and recreate CVE related tables
     */
    public function clearDatabase()
    {
        $this->model->clearDatabase();
    }
}
