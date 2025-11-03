<?php

namespace Controllers\Cve;

use Exception;

class Cve
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Cve\Cve();
    }

    /**
     *  Return specified CVE details
     */
    public function get(string $id)
    {
        return $this->model->get($id);
    }

    /**
     *  Return CVE Id by its name
     */
    public function getIdByName(string $name)
    {
        return $this->model->getIdByName($name);
    }

    /**
     *  Return CVE cpe details
     */
    public function getCpe(string $cveId)
    {
        return $this->model->getCpe($cveId);
    }

    /**
     *  Return CVE references
     */
    public function getReferences(string $id)
    {
        return $this->model->getReferences($id);
    }

    /**
     *  Return all CVEs Id
     */
    public function getAllId()
    {
        return $this->model->getAllId();
    }

    /**
     *  Return all CVEs
     *  It is possible to add an offset to the request
     */
    public function getAll(bool $withOffset = false, int $offset = 0, string|null $filter = null)
    {
        return $this->model->getAll($withOffset, $offset, $filter);
    }

    /**
     *  Return all CVEs matching search string
     */
    public function getAllIdBySearch(string $search)
    {
        return $this->model->getAllIdBySearch($search);
    }

    /**
     *  Get affected hosts by CVE Id
     */
    public function getAffectedHosts(string $cveId, string $status)
    {
        return $this->model->getAffectedHosts($cveId, $status);
    }

    /**
     *  Check if CVE Id exists in database
     */
    public function exists(string $id)
    {
        return $this->model->exists($id);
    }

    /**
     *  Check if CVE nameId exists in database
     */
    public function nameExists(string $nameId)
    {
        return $this->model->nameExists($nameId);
    }

    /**
     *  Set new CVE
     */
    public function new(string $id, string $date, string $time, string $updatedDate, string $updatedTime, string $cpe23UriRawStr, array $cpe23Uri, string $description, array $references, string $cvss2Score, string $cvss3Score)
    {
        $this->model->new($id, $date, $time, $updatedDate, $updatedTime, $cpe23UriRawStr, $cpe23Uri, $description, $references, $cvss2Score, $cvss3Score);
    }

    /**
     *  Update existing CVE
     */
    public function update(string $id, string $date, string $time, string $updatedDate, string $updatedTime, string $cpe23UriRawStr, array $cpe23Uri, string $description, array $references, string $cvss2Score, string $cvss3Score)
    {
        $this->model->update($id, $date, $time, $updatedDate, $updatedTime, $cpe23UriRawStr, $cpe23Uri, $description, $references, $cvss2Score, $cvss3Score);
    }

    /**
     *  Add a new affected host
     */
    public function setAffectedHost(string $cveId, string $hostId, string $productName, string $productVersion, string $state)
    {
        $this->model->setAffectedHost($cveId, $hostId, $productName, $productVersion, $state);
    }

    public function searchCpeProductVersion(string $product, string $version)
    {
        return $this->model->searchCpeProductVersion($product, $version);
    }

    private function getGenericName(string $package)
    {
        $genericNames = [];

        // kernel
        if (preg_match('/linux-headers|kernel-headers/i', $package)) {
            $genericNames[] = 'kernel';
        }
        // apache2
        if (preg_match('/apache/i', $package)) {
            $genericNames[] = 'apache';
        }
        // php-xx
        if (preg_match('/php/i', $package)) {
            $genericNames[] = 'php';
        }

        return $genericNames;
    }

    /**
     *  Search for affected hosts from a CVE
     */
    public function searchAffectedHosts()
    {
        $myhost = new \Controllers\Host();

        /**
         *  Read list of all hosts and their installed packages
         */
        $hosts = json_decode(file_get_contents(DATA_DIR . '/.temp/export-hosts.json'), true);

        $affectedHosts = [];
        $possibleAffectedHosts = [];

        foreach ($hosts as $hostId => $hostDetails) {
            foreach ($hostDetails['Installed_packages'] as $package) {
                $hostPackageName = $package['Name'];
                $hostPackageVersion = $package['Version'];

                /**
                 *  Skip if package name or version is empty
                 */
                if (empty($hostPackageName) or empty($hostPackageVersion)) {
                    continue;
                }

                /**
                 *  Escape special characters from package name
                 */
                $hostPackageName = str_replace('+', '\\+', $hostPackageName);

                /**
                 *  Remove epoch
                 */
                if (preg_match('/^[0-9]:/', $hostPackageVersion)) {
                    $hostPackageVersion = substr($hostPackageVersion, 2);
                }

                /**
                 *  Define what to search
                 *  Search for more generic names for some packages
                 */
                $searchName = $hostPackageName;

                if (preg_match('/linux-headers|kernel-headers/i', $hostPackageName)) {
                    $searchName = 'kernel';
                }
                if (preg_match('/apache/i', $hostPackageName)) {
                    $searchName = 'apache';
                }
                if (preg_match('/php/i', $hostPackageName)) {
                    $searchName = 'php';
                }

                // echo 'Searching for ' . $searchName . ' ' . $hostPackageVersion . ' on ' . $hostDetails['Hostname'] . '... ';

                /**
                 *  Search for matching CVE CPE
                 */
                $cveSearchAffected = $this->searchCpeProductVersion($searchName, $hostPackageVersion);
                $cveSearchPossibleAffected = $this->searchCpeProductVersion($searchName, '*');

                if (!empty($cveSearchAffected)) {
                    foreach ($cveSearchAffected as $cveId) {
                        $this->setAffectedHost($cveId, $hostId, $hostPackageName, $hostPackageVersion, 'affected');
                    }
                }
                if (!empty($cveSearchPossibleAffected)) {
                    foreach ($cveSearchPossibleAffected as $cveId) {
                        $this->setAffectedHost($cveId, $hostId, $hostPackageName, $hostPackageVersion, 'possible');
                    }
                }
            }

            /**
             *  Also check for OS matching
             */
            // foreach ($cveCpeDetails as $cpeDetails) {
            //     $cpePart = $cpeDetails['Part'];
            //     $cpeVendor = $cpeDetails['Vendor'];
            //     $cpeProductName = $cpeDetails['Product'];
            //     $cpeProductVersion = $cpeDetails['Version'];

            //     if ($cpePart != 'o') {
            //         continue;
            //     }

            //     /**
            //      *  Skip if OS name, version or family is empty
            //      */
            //     if (empty($hostDetails['Os']) or empty($hostDetails['Os_version']) or empty($hostDetails['Os_family'])) {
            //         continue;
            //     }

            //     if (preg_match('#^' . $hostDetails['Os'] . '|' . $hostDetails['Os'] . '$|^' . $hostDetails['Os_family'] . '|' . $hostDetails['Os_family'] . '$#i', $cpeVendor) or
            //         preg_match('#^' . $hostDetails['Os'] . '|' . $hostDetails['Os'] . '$|^' . $hostDetails['Os_family'] . '|' . $hostDetails['Os_family'] . '$#i', $cpeProductName)
            //     ) {
            //         if ($cpeProductVersion != '*' and $cpeProductVersion != '-') {
            //             if (preg_match('#^' . $hostDetails['Os_version'] . '#i', $cpeProductVersion) or preg_match('#^' . $cpeProductVersion . '#i', $hostDetails['Os_version'])) {
            //                 $affectedHosts[] = array('id' => $hostId, 'productName' => $hostDetails['Os'], 'productVersion' => $hostDetails['Os_version']);
            //                 continue;
            //             }
            //         } else {
            //             $possibleAffectedHosts[] = array('id' => $hostId, 'productName' => $hostDetails['Os'], 'productVersion' => $hostDetails['Os_version']);
            //         }
            //     }
            // }
        }

        // unset($cvesId, $cve, $hosts, $host, $hostDetails, $installedPackages, $package, $hostPackageName, $hostPackageVersion, $cpeDetails, $cpePart, $cpeVendor, $cpeProductName, $cpeProductVersion);

        // /**
        //  *  Delete duplicates
        //  */
        // $possibleAffectedHosts = array_unique($possibleAffectedHosts, SORT_REGULAR);
        // $affectedHosts = array_unique($affectedHosts, SORT_REGULAR);

        // /**
        //  *  If an affected host is also in the possible affected hosts list, remove it from the possible affected hosts list
        //  */
        // foreach ($affectedHosts as $affectedHost) {
        //     foreach ($possibleAffectedHosts as $key => $possibleAffectedHost) {
        //         if ($affectedHost['id'] == $possibleAffectedHost['id']) {
        //             unset($possibleAffectedHosts[$key]);
        //         }
        //     }
        // }

        // /**
        //  *  Insert affected hosts in database
        //  */
        // if (!empty($affectedHosts)) {
        //     foreach ($affectedHosts as $affectedHost) {
        //         $this->setAffectedHost($cveId, $affectedHost['id'], $affectedHost['productName'], $affectedHost['productVersion'], 'affected');
        //     }
        // }
        // if (!empty($possibleAffectedHosts)) {
        //     foreach ($possibleAffectedHosts as $possibleAffectedHost) {
        //         $this->setAffectedHost($cveId, $possibleAffectedHost['id'], $possibleAffectedHost['productName'], $possibleAffectedHost['productVersion'], 'possible');
        //     }
        // }
    }

    /**
     *  Send a mail with the affected hosts
     */
    public function sendMailWithAffectedHosts()
    {
        $cveAffectedHostsArray = [];

        $cvesId = $this->getAllId();
        $mailMessage = '';

        /**
         *  Parse all CVEs and retrieve affected hosts
         */
        foreach ($cvesId as $cveId) {
            /**
             *  Get CVE details
             */
            $cveDetails = $this->get($cveId);

            /**
             *  Retrieve list of all affected hosts, if any
             */
            $affectedHosts = $this->getAffectedHosts($cveId, 'affected');

            if (!empty($affectedHosts)) {
                $mailMessage .= '<p><a href="https://' . WWW_HOSTNAME . '/cve?nameid=' . $cveDetails['Name'] . '"><b>' . $cveDetails['Name'] . '</b></a> - Score: ' . $cveDetails['Cvss3_score'] . '<br>';
                $mailMessage .= '<b>Date</b>: ' . $cveDetails['Date'] . ' ' . $cveDetails['Time'] . '<br>';
                $mailMessage .= '<b>Description</b>: ' . $cveDetails['Description'] . '<br>';
                $mailMessage .= '<b>Total affected hosts</b>: ' . count($affectedHosts) . '<br></p><hr>';
            }
        }

        if (!empty($mailMessage)) {
            $mailSubject = 'CVEs affected hosts summary';
            new \Controllers\Mail(implode(',', EMAIL_RECIPIENT), $mailSubject, $mailMessage, '', '');
        }
    }
}
