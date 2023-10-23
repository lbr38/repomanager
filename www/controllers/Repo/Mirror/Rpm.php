<?php

namespace Controllers\Repo\Mirror;

use Exception;

class Rpm extends \Controllers\Repo\Mirror\Mirror
{
    /**
     *  Get distant repomd.xml file
     *  (RPM mirror)
     */
    private function getRepoMd(string $url)
    {
        $this->logOutput(PHP_EOL . '- Getting <b>repomd.xml</b> from ' . $url . '/repodata/repomd.xml ... ');

        if (!$this->download($url . '/repodata/repomd.xml', $this->workingDir . '/repomd.xml')) {
            $this->logError('error', 'Could not download repomd.xml');
        }

        $this->logOK();
    }

    /**
     *  Get primary packages list file
     *  (RPM mirror)
     */
    private function getPackagesList(string $url, string $checksum)
    {
        $this->logOutput(PHP_EOL . '- Getting <b>primary.xml.gz</b> from ' . $url . ' ... ');

        if (!$this->download($url, $this->workingDir . '/primary.xml.gz')) {
            throw new Exception('Could not download primary.xml.gz');
        }

        /**
         *  Check that downloaded file checksum is the same as the provided checksum from repomd.xml
         *  Try with sha512, sha256 then sha1
         */
        if (hash_file('sha512', $this->workingDir . '/primary.xml.gz') != $checksum) {
            if (hash_file('sha256', $this->workingDir . '/primary.xml.gz') != $checksum) {
                if (hash_file('sha1', $this->workingDir . '/primary.xml.gz') != $checksum) {
                    throw new Exception('Error: primary.xml.gz checksum does not match provided checksum');
                }
            }
        }

        $this->logOK();
    }

    /**
     *  Parsing repomd.xml to get database location
     *  (RPM mirror)
     */
    private function parseRepoMd()
    {
        if (!file_exists($this->workingDir . '/repomd.xml')) {
            $this->logError('Could not parse ' . $this->workingDir . '/repomd.xml: File not found');
        }

        /**
         *  Convert repomd.xml XML content to JSON to PHP array for a simpler parsing
         */
        $xml = simplexml_load_file($this->workingDir . '/repomd.xml');
        $json = json_encode($xml);
        unset($xml);
        $jsonArray = json_decode($json, true);
        unset($json);

        foreach ($jsonArray['data'] as $data) {
            if (isset($data['@attributes'])) {
                /**
                 *  Find an array with attribute 'type' equals to 'primary'
                 *  This array will contains location to the primary package list xml file
                 *  e.g
                 *
                 * Array
                 * (
                 *     [revision] => 1661873831
                 *     [data] => Array
                 *         (
                 *              [1] => Array
                 *                 (
                 *                     [@attributes] => Array
                 *                         (
                 *                             [type] => primary
                 *                         )
                 *                     [checksum] => 247192a75689c2205dafa6665b4dbf114fc641858dcb446c644aa934858108b2
                 *                     [open-checksum] => 5f114045145909f9243d6687e8267c3cabfd3866b7f48a4a0da3d450895a56a5
                 *                     [location] => Array
                 *                         (
                 *                             [@attributes] => Array
                 *                                 (
                 *                                     [href] => repodata/247192a75689c2205dafa6665b4dbf114fc641858dcb446c644aa934858108b2-primary.xml.gz
                 *                                 )
                 *                          )
                 *                     [timestamp] => 1661873832
                 *                     [size] => 31711
                 *                     [open-size] => 493575
                 *                 )
                 *
                 */
                if ($data['@attributes']['type'] == 'primary') {
                    $this->primaryLocation = $data['location']['@attributes']['href'];
                    $this->primaryChecksum = $data['checksum'];

                    /**
                     *  If $data['checksum'] is an array with multiple checksums found (sha, sha256, sha512), then just keep the first of them.
                     */
                    if (is_array($data['checksum'])) {
                        $this->primaryChecksum = $data['checksum'][0];
                    /**
                     *  Else if $data['checksum'] is a string
                     */
                    } else {
                        $this->primaryChecksum = $data['checksum'];
                    }
                }
            }
        }

        /**
         *  If location and checksum could not be find, throw an error
         */
        if (empty($this->primaryLocation) or empty($this->primaryChecksum)) {
            $this->logError('Could not find location of the package list file');
        }
    }

    /**
     *  Parse primary packages list file to find .rpm packages location and their checksum
     *  (RPM mirror)
     */
    private function parsePrimaryPackagesList(string $primaryFile)
    {
        $error = 0;
        $this->rpmPackagesLocation = array();

        $this->logOutput(PHP_EOL . '- Retrieving packages list from ' . $primaryFile . ' ... ');

        /**
         *  Gunzip primary.xml.gz
         */
        try {
            \Controllers\Common::gunzip($primaryFile);
        } catch (Exception $e) {
            $this->logError($e, 'Error while uncompressing primary.xml.gz');
        }

        /**
         *  Read the now gunzipped primary.xml.gz file
         */
        $primaryFile = str_replace('.gz', '', $primaryFile);

        /**
         *  Convert primary.xml content from XML to JSON to PHP array for a simpler parsing
         */
        $xml = simplexml_load_file($primaryFile);
        $json = json_encode($xml);
        unset($xml);
        $jsonArray = json_decode($json, true);
        unset($json);

        /**
         *  First count number of packages because retrieving the packages informations is different if there is only one package or multiple packages
         */
        $packageCount = $jsonArray['@attributes']['packages'];

        /**
         *  Case there is only one package in the target repository
         */
        if ($packageCount == 1) {
            /**
             *  Find package location
             */
            if (!empty($jsonArray['package']['location']['@attributes']['href'])) {
                $packageLocation = $jsonArray['package']['location']['@attributes']['href'];

                /**
                 *  If package checksum is not found then it can not be retrieved
                 */
                if (empty($jsonArray['package']['checksum'])) {
                    $this->logOutput(PHP_EOL . ' <span class="yellowtext"> Could not find checksum value for package ' . $packageLocation . '</span>' . PHP_EOL);
                    $error++;
                } else {
                    $packageChecksum = $jsonArray['package']['checksum'];

                    /**
                     *  If path and checksum have been parsed, had them to the global rpm packages list array
                     */
                    $this->rpmPackagesLocation[] = array('location' => $packageLocation, 'checksum' => $packageChecksum);
                }
            }
        }

        /**
         *  Case there is more than one package in the target repository
         */
        if ($packageCount > 1) {
            foreach ($jsonArray['package'] as $data) {
                /**
                 *  Find package location
                 */
                if (!empty($data['location']['@attributes']['href'])) {
                    $packageLocation = $data['location']['@attributes']['href'];

                    /**
                     *  If package checksum is not found then it can not be retrieved
                     */
                    if (empty($data['checksum'])) {
                        $this->logOutput(PHP_EOL . ' <span class="yellowtext"> Could not find checksum value for package ' . $packageLocation . '</span>' . PHP_EOL);
                        $error++;
                        continue;
                    }

                    $packageChecksum = $data['checksum'];

                    /**
                     *  If path and checksum have been parsed, had them to the global rpm packages list array
                     */
                    $this->rpmPackagesLocation[] = array('location' => $packageLocation, 'checksum' => $packageChecksum);
                }
            }
        }

        /**
         *  Print error if no package location has been found
         */
        if (empty($this->rpmPackagesLocation)) {
            $this->logError('No package found');
        }

        /**
         *  Print OK if there was no warning
         */
        if ($error == 0) {
            $this->logOK();
        }
    }

    /**
     *  Download rpm packages
     *  (RPM mirror)
     */
    private function downloadRpmPackages(string $url)
    {
        /**
         *  Target directory in which packages will be downloaded
         */
        $targetDir = $this->workingDir . '/packages/' . $this->currentArch;

        /**
         *  Create directory in which packages will be downloaded
         */
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0770, true);
        }

        /**
         *  If GPG signature check is enabled, either use a distant http:// GPG key or use the repomanager keyring
         */
        if ($this->checkSignature == 'yes') {
            $mygpg = new \Controllers\GPG();

            /**
             *  If the source repo has a distant http:// gpg signature key, then download it
             */
            if (!empty($this->gpgKeyUrl)) {
                if (!$this->download($this->gpgKeyUrl, TEMP_DIR . '/gpgkey-to-import.gpg')) {
                    $this->logError('Could not retrieve distant GPG signature key: ' . $this->gpgKeyUrl, 'Could not retrieve distant GPG signature key');
                }

                /**
                 *  Import key inside trusted keyring
                 */
                $myprocess = new \Controllers\Process('/usr/bin/gpg --no-default-keyring --keyring ' . GPGHOME . '/trustedkeys.gpg --import ' . TEMP_DIR . '/gpgkey-to-import.gpg');
                $myprocess->execute();

                /**
                 *  Delete temporary GPG key file
                 */
                unlink(TEMP_DIR . '/gpgkey-to-import.gpg');

                /**
                 *  Quits if import has failed
                 */
                if ($myprocess->getExitCode() != 0) {
                    $this->logError('Error while importing distant GPG signature key', 'Could not import distant GPG signature key');
                }

                $myprocess->close();
            }

            /**
             *  Get all known editors GPG public keys imported into repomanager keyring
             */
            $knownPublicKeys = $mygpg->getTrustedKeys();

            /**
             *  Filter to retrieve key Id column only
             */
            $knownPublicKeys = array_column($knownPublicKeys, 'id');
        }

        /**
         *  Print URL from which packages are downloaded
         */
        $this->logOutput(PHP_EOL . '- Downloading packages from: ' . $url . PHP_EOL);

        /**
         *  Count total packages to print progression during syncing
         */
        $totalPackages = count($this->rpmPackagesLocation);
        $packageCounter = 0;

        /**
         *  Download each package and check its md5
         */
        foreach ($this->rpmPackagesLocation as $rpmPackage) {
            /**
             *  Before downloading each package, check if there is enough disk space left (2GB minimum)
             */
            if (disk_free_space(REPOS_DIR) < 2000000000) {
                $this->logError('Repo storage has reached 2GB (minimum) of free space left. Operation automatically stopped.', 'Low disk space');
            }

            $rpmPackageLocation = $rpmPackage['location'];
            $rpmPackageChecksum = $rpmPackage['checksum'];
            $rpmPackageName = preg_split('#/#', $rpmPackageLocation);
            $rpmPackageName = end($rpmPackageName);
            $packageCounter++;

            /**
             *  Output package to download to log file
             */
            $this->logOutput('(' . $packageCounter . '/' . $totalPackages . ')  ➙ ' . $rpmPackageLocation . ' ... ');

            /**
             *  Check if file does not already exists before downloading it (e.g. copied from a previously snapshot)
             */
            if (file_exists($targetDir . '/' . $rpmPackageName)) {
                $this->logOutput('already exists (ignoring)' . PHP_EOL);
                continue;
            }

            /**
             *  Download file if it does not already exist
             */
            if (!$this->download($url . '/' . $rpmPackageLocation, $targetDir . '/' . $rpmPackageName)) {
                $this->logError('error', 'Error while retrieving packages');
            }

            /**
             *  Check that downloaded rpm package's matches the checksum specified by the primary.xml file
             *  Try with sha256 then sha1
             */
            if (hash_file('sha256', $targetDir . '/' . $rpmPackageName) != $rpmPackageChecksum) {
                if (hash_file('sha1', $targetDir . '/' . $rpmPackageName) != $rpmPackageChecksum) {
                    $this->logError('checksum (sha256) does not match (tried sha256 and sha1)', 'Error while retrieving packages');
                }
            }

            /**
             *  Check rpm GPG signature if enabled
             *
             *  https://blog.remirepo.net/post/2020/03/13/Extension-rpminfo-pour-php
             *
             *  using rpm :
             *  rpm -q --qf "%|DSAHEADER?{%{DSAHEADER:pgpsig}}:{%|RSAHEADER?{%{RSAHEADER:pgpsig}}:{(none}|}| %{NVRA}\n" PACKAGE.rpm
             *  rpm --checksig PACKAGE.rpm
             */
            if ($this->checkSignature === 'yes') {
                /**
                 *  Throw an error if there are no known GPG public keys because it is impossible to check for signature then
                 */
                if (empty($knownPublicKeys)) {
                    $this->logError('Cannot check for signature because there is no GPG public keys imported in Repomanager\'s keyring', 'Cannot check packages signature');
                }

                /**
                 *  Extract package header
                 */
                $myprocess = new \Controllers\Process('/usr/bin/rpm -qp --qf "%|DSAHEADER?{%{DSAHEADER:pgpsig}}:{%|RSAHEADER?{%{RSAHEADER:pgpsig}}:{(none}|}| %{NVRA}\n" ' . $targetDir. '/' . $rpmPackageName);
                $myprocess->execute();
                $content = $myprocess->getOutput();
                $myprocess->close();

                /**
                 *  Parse package's GPG signature key Id from header content
                 */
                if (!preg_match('/key ID(.*) /i', $content, $matches)) {
                    $this->logError('GPG signature key ID is not found in the package header', 'Could not verify GPG signatures');
                }

                /**
                 *  Retrieve GPG signature key Id from $matches
                 */
                $keyId = trim($matches[1]);

                /**
                 *  Remove last ':' character
                 */
                $keyId = rtrim($keyId, ':');

                /**
                 *  Now check if that key Id appears in known public keys Id
                 *  If not, throw an error, else, signature is OK
                 */
                if (!preg_grep("/$keyId\$/i", $knownPublicKeys)) {
                    $this->logError('signature is not OK', 'Package has invalid signature');
                }
            }

            /**
             *  Print OK if package has been downloaded and verified successfully
             */
            $this->logOK();
        }

        unset($this->rpmPackagesLocation, $totalPackages, $packageCounter);
    }

    /**
     *  Mirror a rpm repository
     */
    public function mirror()
    {
        $this->initialize();

        /**
         *  Quit if rpm is not present on the system and that signature check is enabled
         */
        if ($this->checkSignature == 'yes' and !(file_exists('/usr/bin/rpm'))) {
            throw new Exception('rpm is not present on the system (searched in /usr/bin/rpm)');
        }

        /**
         *  If source package must be synced, add SRPMS to arch array
         */
        if ($this->syncSource == 'yes') {
            $this->arch[] = 'SRPMS';
        }

        /**
         *  Retrive packages for each arch that have been specified
         */
        foreach ($this->arch as $this->currentArch) {
            $url = $this->url;

            /**
             *  Replace $releasever value
             */
            if (preg_match('/\$releasever/i', $url)) {
                $url = str_replace('$releasever', $this->releasever, $url);
            }

            /**
             *  Replace $basearch value
             */
            if (preg_match('/\$basearch/i', $url)) {
                $url = str_replace('$basearch', $this->currentArch, $url);
            }

            /**
             *  Delete final slash if exist
             */
            $url = rtrim($url, '/');

            /**
             *  Get repomd.xml
             */
            $this->getRepoMd($url);

            /**
             *  Find primary packages list location
             */
            $this->parseRepoMd();

            /**
             *  Get primary packages list file
             */
            $this->getPackagesList($url . '/' . $this->primaryLocation, $this->primaryChecksum);

            /**
             *  Parse primary packages list file
             */
            $this->parsePrimaryPackagesList($this->workingDir . '/primary.xml.gz');

            /**
             *  Download rpm packages
             */
            $this->downloadRpmPackages($url);

            /**
             *  Clean remaining files
             */
            $this->clean();
        }
    }
}
