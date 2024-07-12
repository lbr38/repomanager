<?php

namespace Controllers\Repo\Mirror;

use Exception;
use SimpleXMLElement;

class Rpm extends \Controllers\Repo\Mirror\Mirror
{
    private $archUrls = array();

    /**
     *  Download repomd.xml file
     */
    private function downloadRepomd(string $url)
    {
        $this->logOutput(PHP_EOL . 'Getting <code>repomd.xml</code> from <span class="copy">' . $url . '/repodata/repomd.xml</span> ... ');

        if (!$this->download($url . '/repodata/repomd.xml', $this->workingDir . '/repomd.xml')) {
            $this->logError('error', 'Could not download <code>repomd.xml</code>');
        }

        $this->logOK();
    }

    /**
     *  Download primary.xml packages list file
     */
    private function downloadPrimary(string $url)
    {
        $this->logOutput(PHP_EOL . 'Getting <code>primary.xml.gz</code> from <span class="copy">' . $url . '/' . $this->primaryLocation . '</span> ... ');

        if (!$this->download($url . '/' . $this->primaryLocation, $this->workingDir . '/primary.xml.gz')) {
            throw new Exception('Could not download <code>primary.xml.gz</code>');
        }

        /**
         *  Check that downloaded file checksum is the same as the provided checksum from repomd.xml
         *  Try with sha512, sha256 then sha1
         */
        if (hash_file('sha512', $this->workingDir . '/primary.xml.gz') != $this->primaryChecksum) {
            if (hash_file('sha256', $this->workingDir . '/primary.xml.gz') != $this->primaryChecksum) {
                if (hash_file('sha1', $this->workingDir . '/primary.xml.gz') != $this->primaryChecksum) {
                    throw new Exception('<code>primary.xml.gz</code> checksum does not match provided checksum');
                }
            }
        }

        $this->logOK();
    }

    /**
     *  Download comps.xml file
     */
    private function downloadComps(string $url)
    {
        /**
         *  Quit if there is no comps.xml file to download
         */
        if (empty($this->compsLocation) or empty($this->compsChecksum)) {
            return;
        }

        $this->logOutput(PHP_EOL . 'Getting <code>comps.xml</code> from <span class="copy">' . $url . '/' . $this->compsLocation . '</span> ... ');

        if (!$this->download($url . '/' . $this->compsLocation, $this->workingDir . '/comps.xml')) {
            throw new Exception('Could not download <code>comps.xml</code>');
        }

        /**
         *  Check that downloaded file checksum is the same as the provided checksum from repomd.xml
         *  Try with sha512, sha256 then sha1
         */
        if (hash_file('sha512', $this->workingDir . '/comps.xml') != $this->compsChecksum) {
            if (hash_file('sha256', $this->workingDir . '/comps.xml') != $this->compsChecksum) {
                if (hash_file('sha1', $this->workingDir . '/comps.xml') != $this->compsChecksum) {
                    throw new Exception('<code>comps.xml</code> checksum does not match provided checksum');
                }
            }
        }

        $this->logOK();
    }

    /**
     *  Download modules.yaml file
     */
    private function downloadModules(string $url)
    {
        /**
         *  Quit if there is no modules.yaml file to download
         */
        if (empty($this->modulesLocation) or empty($this->modulesChecksum)) {
            return;
        }

        $this->logOutput(PHP_EOL . 'Getting <code>modules</code> file from <span class="copy">' . $url . '/' . $this->modulesLocation . '</span> ... ');

        /**
         *  Get modules file extension
         *  We'll give this modules file a temporary name, to avoid it being included automatically by createrepo_c (it fails every time with modules.yaml file)
         *  It will be renamed and imported by modifyrepo_c later
         */
        if (pathinfo($this->modulesLocation, PATHINFO_EXTENSION) == 'gz') {
            $modulesFileExtension = 'gz';
            $modulesFileTargetName = 'modules-temp.yaml.gz';
        } else if (pathinfo($this->modulesLocation, PATHINFO_EXTENSION) == 'yaml') {
            $modulesFileExtension = 'yaml';
            $modulesFileTargetName = 'modules-temp.yaml';
        } else {
            throw new Exception('Unsupported file extension ' . pathinfo($this->modulesLocation, PATHINFO_EXTENSION) . ' for <code>modules</code> file. Please contact the developer to add support for this file extension.');
        }

        /**
         *  Download modules file
         */
        if (!$this->download($url . '/' . $this->modulesLocation, $this->workingDir . '/' . $modulesFileTargetName)) {
            throw new Exception('Could not download <code>' . $modulesFileTargetName . '</code> file');
        }

        /**
         *  Check that downloaded file checksum is the same as the provided checksum from repomd.xml
         *  Try with sha512, sha256 then sha1
         */
        if (hash_file('sha512', $this->workingDir . '/' . $modulesFileTargetName) != $this->modulesChecksum) {
            if (hash_file('sha256', $this->workingDir . '/' . $modulesFileTargetName) != $this->modulesChecksum) {
                if (hash_file('sha1', $this->workingDir . '/' . $modulesFileTargetName) != $this->modulesChecksum) {
                    throw new Exception('<code>' . $modulesFileTargetName . '</code> checksum does not match provided checksum');
                }
            }
        }

        /**
         *  If modules file has been downloaded as a .gz file, uncompress it (otherwise it will fail to be included to the metadata)
         */
        if ($modulesFileExtension == 'gz') {
            try {
                \Controllers\Common::gunzip($this->workingDir . '/' . $modulesFileTargetName);
            } catch (Exception $e) {
                throw new Exception('Could not uncompress <code>' . $modulesFileTargetName . '</code>: ' . $e->getMessage());
            }

            /**
             *  Delete original .gz file
             */
            if (!unlink($this->workingDir . '/' . $modulesFileTargetName)) {
                throw new Exception('Could not delete <code>' . $modulesFileTargetName . '</code> file');
            }
        }

        $this->logOK();
    }

    /**
     *  Download updateinfo.xml.gz file
     */
    private function downloadUpdateInfo(string $url)
    {
        /**
         *  Quit if there is no updateinfo.xml.gz file to download
         */
        if (empty($this->updateInfoLocation) or empty($this->updateInfoChecksum)) {
            return;
        }

        $this->logOutput(PHP_EOL . 'Getting <code>updateinfo.xml.gz</code> from <span class="copy">' . $url . '/' . $this->updateInfoLocation . '</span> ... ');

        if (pathinfo($this->updateInfoLocation, PATHINFO_EXTENSION) == 'gz') {
            $updateInfoFileExtension = 'gz';
            $updateInfoFileTargetName = 'updateinfo.xml.gz';
        } else if (pathinfo($this->updateInfoLocation, PATHINFO_EXTENSION) == 'xml') {
            $updateInfoFileExtension = 'xml';
            $updateInfoFileTargetName = 'updateinfo.xml';
        } else {
            throw new Exception('Unsupported file extension ' . pathinfo($this->updateInfoLocation, PATHINFO_EXTENSION) . ' for <code>updateinfo</code> file. Please contact the developer to add support for this file extension.');
        }

        if (!$this->download($url . '/' . $this->updateInfoLocation, $this->workingDir . '/' . $updateInfoFileTargetName)) {
            throw new Exception('Could not download <code>updateinfo.xml.gz</code>');
        }

        /**
         *  Check that downloaded file checksum is the same as the provided checksum from repomd.xml
         *  Try with sha512, sha256 then sha1
         */
        if (hash_file('sha512', $this->workingDir . '/' . $updateInfoFileTargetName) != $this->updateInfoChecksum) {
            if (hash_file('sha256', $this->workingDir . '/' . $updateInfoFileTargetName) != $this->updateInfoChecksum) {
                if (hash_file('sha1', $this->workingDir . '/' . $updateInfoFileTargetName) != $this->updateInfoChecksum) {
                    throw new Exception('<code>' . $updateInfoFileTargetName . '</code> checksum does not match provided checksum');
                }
            }
        }

        /**
         *  If updateinfo file has been downloaded as a .gz file, uncompress it
         */
        if ($updateInfoFileExtension == 'gz') {
            try {
                \Controllers\Common::gunzip($this->workingDir . '/' . $updateInfoFileTargetName);
            } catch (Exception $e) {
                throw new Exception('Could not uncompress <code>' . $updateInfoFileTargetName . '</code>: ' . $e->getMessage());
            }

            /**
             *  Delete original .gz file
             */
            if (!unlink($this->workingDir . '/' . $updateInfoFileTargetName)) {
                throw new Exception('Could not delete <code>' . $updateInfoFileTargetName . '</code> file');
            }
        }

        $this->logOK();
    }

    /**
     *  Parsing repomd.xml to get database location
     */
    private function parseRepoMd()
    {
        if (!file_exists($this->workingDir . '/repomd.xml')) {
            $this->logError('Could not parse <code>' . $this->workingDir . '/repomd.xml</code>: File not found');
        }

        /**
         *  Convert repomd.xml XML content to JSON to PHP array for a simpler parsing
         */
        try {
            $xml = new SimpleXMLElement(file_get_contents($this->workingDir . '/repomd.xml'), LIBXML_PARSEHUGE);
            $jsonArray = json_decode(json_encode($xml), true);
            unset($xml);
        } catch (Exception $e) {
            $this->logError('Could not parse ' . $this->workingDir . '/repomd.xml: ' . $e->getMessage() . PHP_EOL . $error, 'Could not retrieve package list');
        }

        gc_collect_cycles();

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

                /**
                 *  Retrieve comps.xml location
                 *  Find an attribute 'type' equals to 'group', if exists
                 */
                if ($data['@attributes']['type'] == 'group') {
                    $this->compsLocation = $data['location']['@attributes']['href'];
                    $this->compsChecksum = $data['checksum'];

                    /**
                     *  If $data['checksum'] is an array with multiple checksums found (sha, sha256, sha512), then just keep the first of them.
                     */
                    if (is_array($data['checksum'])) {
                        $this->compsChecksum = $data['checksum'][0];
                    /**
                     *  Else if $data['checksum'] is a string
                     */
                    } else {
                        $this->compsChecksum = $data['checksum'];
                    }
                }

                /**
                 *  Retrieve modules.yaml location
                 *  Find an attribute 'type' equals to 'modules', if exists
                 */
                if ($data['@attributes']['type'] == 'modules') {
                    $this->modulesLocation = $data['location']['@attributes']['href'];
                    $this->modulesChecksum = $data['checksum'];

                    /**
                     *  If $data['checksum'] is an array with multiple checksums found (sha, sha256, sha512), then just keep the first of them.
                     */
                    if (is_array($data['checksum'])) {
                        $this->modulesChecksum = $data['checksum'][0];
                    /**
                     *  Else if $data['checksum'] is a string
                     */
                    } else {
                        $this->modulesChecksum = $data['checksum'];
                    }
                }

                /**
                 *  Retrieve updateinfo.xml.gz location
                 *  Find an attribute 'type' equals to 'updateinfo', if exists
                 */
                if ($data['@attributes']['type'] == 'updateinfo') {
                    $this->updateInfoLocation = $data['location']['@attributes']['href'];
                    $this->updateInfoChecksum = $data['checksum'];

                    /**
                     *  If $data['checksum'] is an array with multiple checksums found (sha, sha256, sha512), then just keep the first of them.
                     */
                    if (is_array($data['checksum'])) {
                        $this->updateInfoChecksum = $data['checksum'][0];
                    /**
                     *  Else if $data['checksum'] is a string
                     */
                    } else {
                        $this->updateInfoChecksum = $data['checksum'];
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

        unset($jsonArray, $data);
    }

    /**
     *  Parse primary packages list file to find .rpm packages location and their checksum
     */
    private function parsePrimaryPackagesList(string $primaryFile)
    {
        $error = 0;
        $this->rpmPackagesLocation = array();

        $this->logOutput(PHP_EOL . 'Retrieving packages list from <span class="copy">' . $primaryFile . '</span> ... ');

        /**
         *  Get primary.xml.gz mime type
         *  Sometimes it can be bzip2 instead of gzip even if the file extension is .gz (e.g. some Remi repos)
         */
        $mime = mime_content_type($primaryFile);

        /**
         *  Uncompress primary.xml.gz
         */
        try {
            /**
             *  Case mime type is application/x-bzip2 (.bz2 file)
             *  Uncompress to 'primary.xml'
             */
            if ($mime == 'application/x-bzip2') {
                \Controllers\Common::bunzip2($primaryFile, $this->workingDir . '/primary.xml');
            /**
             *  Case mime type is application/x-xz (.xz file)
             */
            } elseif ($mime == 'application/x-xz') {
                \Controllers\Common::xzUncompress($primaryFile, $this->workingDir . '/primary.xml');
            /**
             *  Case mime type is application/gzip (.gz file)
             */
            } elseif ($mime == 'application/gzip') {
                \Controllers\Common::gunzip($primaryFile);
            /**
             *  Case it's another mime type, throw an error
             */
            } else {
                throw new Exception('MIME type not supported: ' . $mime . PHP_EOL . 'Please contact the developer to add support for this MIME type');
            }
        } catch (Exception $e) {
            $this->logError($e, 'Error while uncompressing <code>'. end(explode('/', $primaryFile)) . '</code>');
        }

        /**
         *  Primary.xml.gz has been uncompressed to primary.xml
         */
        $primaryFile = $this->workingDir . '/primary.xml';

        /**
         *  Check that primary.xml file exists
         */
        if (!file_exists($primaryFile)) {
            $this->logError('Could not parse ' . $primaryFile . ': File not found after uncompressing <code>primary.xml.gz</code>');
        }

        /**
         *  Convert primary.xml content from XML to JSON to PHP array for a simpler parsing
         */
        try {
            $xml = new SimpleXMLElement(file_get_contents($primaryFile), LIBXML_PARSEHUGE);
            $jsonArray = json_decode(json_encode($xml), true);
            unset($xml);
        } catch (Exception $e) {
            $this->logError('Could not parse ' . $primaryFile . ': ' . $e->getMessage() . PHP_EOL . $error, 'Could not retrieve package list');
        }

        gc_collect_cycles();

        /**
         *  First count number of packages because retrieving the packages informations is different if there is only one package or multiple packages
         */
        $packageCount = $jsonArray['@attributes']['packages'];

        /**
         *  Case there is only one package in the target repository
         */
        if ($packageCount == 1) {
            /**
             *  If package arch is not part of the archs selected by the user then skip it
             */
            if (!in_array($jsonArray['package']['arch'], $this->arch)) {
                $this->logOutput(PHP_EOL . ' <span class="yellowtext"> Package architecture ' . $jsonArray['package']['arch'] . ' is not matching any of the desired architecture (ignored)</span>' . PHP_EOL);
                $error++;
            }

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
                 *  If package arch is not part of the archs selected by the user then skip it
                 */
                if (!in_array($data['arch'], $this->arch)) {
                    continue;
                }

                /**
                 *  Find package location
                 */
                if (!empty($data['location']['@attributes']['href'])) {
                    $packageLocation = $data['location']['@attributes']['href'];

                    /**
                     *  If package arch is not found then it can not be retrieved
                     */
                    if (empty($data['arch'])) {
                        $this->logError('Could not find architecture value for package ' . $packageLocation . ' in primary.xml file', 'Could not find architecture for package');
                    }

                    $packageArch = $data['arch'];

                    /**
                     *  If package checksum is not found then it can not be retrieved
                     */
                    if (empty($data['checksum'])) {
                        $this->logError('Could not find checksum value for package ' . $packageLocation . ' in primary.xml file', 'Could not find checksum for package');
                    }

                    $packageChecksum = $data['checksum'];

                    /**
                     *  If path, arch and checksum have been parsed, had them to the global rpm packages list array
                     */
                    $this->rpmPackagesLocation[] = array('location' => $packageLocation, 'arch' => $packageArch, 'checksum' => $packageChecksum);
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

        unset($jsonArray, $data);
    }

    /**
     *  Download rpm packages
     */
    private function downloadRpmPackages(string $url)
    {
        /**
         *  If GPG signature check is enabled, either use a distant http:// GPG key or use the repomanager keyring
         */
        if ($this->checkSignature == 'true') {
            $mygpg = new \Controllers\GPG();

            /**
             *  If the source repo has a distant http:// gpg signing key, then download it
             */
            if (!empty($this->gpgKeyUrl)) {
                if (!$this->download($this->gpgKeyUrl, TEMP_DIR . '/gpgkey-to-import.gpg')) {
                    $this->logError('Could not retrieve distant GPG signing key: ' . $this->gpgKeyUrl, 'Could not retrieve distant GPG signing key');
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
                    $this->logError('Error while importing distant GPG signing key', 'Could not import distant GPG signing key');
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

            unset($mygpg, $myprocess);
        }

        /**
         *  Print URL from which packages are downloaded
         */
        $this->logOutput(PHP_EOL . 'Downloading packages from <span class="copy">' . $url . '</span>:' . PHP_EOL);

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
                $this->logError('Repo storage has reached 2GB (minimum) of free space left. Task automatically stopped.', 'Low disk space');
            }

            /**
             *  Retrieve package informations from $rpmPackagesLocation array (what has been parsed from primary.xml file)
             */
            $rpmPackageLocation = $rpmPackage['location'];
            $rpmPackageArch     = $rpmPackage['arch'];
            $rpmPackageChecksum = $rpmPackage['checksum'];
            $rpmPackageName     = preg_split('#/#', $rpmPackageLocation);
            $rpmPackageName     = end($rpmPackageName);
            $packageCounter++;

            /**
             *  Output package to download to log file
             */
            $this->logOutput('<span class="opacity-80-cst">(' . $packageCounter . '/' . $totalPackages . ')  ➙ ' . $rpmPackageLocation . ' ... </span>');

            /**
             *  Check that package architecture is valid
             */
            if (!in_array($rpmPackageArch, RPM_ARCHS)) {
                $this->logError('Package architecture is not valid: ' . $rpmPackageArch . ' for package: ' . $rpmPackageLocation, 'Package architecture is not valid');
            }

            /**
             *  If no package arch has been found when parsing primary.xml file, throw an error
             */
            if (empty($rpmPackageArch)) {
                $this->logError('An empty package architecture has been retrieved from distant repository metadata for package: ' . $rpmPackageLocation, 'Package architecture is not valid');
            }

            /**
             *  If package arch is 'src' then package will be downloaded in a 'SRPMS' directory to respect most of the RPM repositories architecture
             */
            if ($rpmPackageArch == 'src') {
                $targetDir = $this->workingDir . '/packages/SRPMS';

            /**
             *  Else, package will be downloaded in a directory named after the package arch
             */
            } else {
                $targetDir = $this->workingDir . '/packages/' . $rpmPackageArch;
            }

            /**
             *  Create directory in which package will be downloaded
             */
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0770, true)) {
                    $this->logError('Cannot create directory: ' . $targetDir, 'Error while creating target directory');
                }
            }

            /**
             *  Check if file does not already exists before downloading it (e.g. copied from a previously snapshot)
             */
            if (file_exists($targetDir . '/' . $rpmPackageName)) {
                $this->logOutput('already exists (ignoring)' . PHP_EOL);
                continue;
            }

            /**
             *  Download package if it does not already exist
             */
            if (!$this->download($url . '/' . $rpmPackageLocation, $targetDir . '/' . $rpmPackageName, 3)) {
                $this->logError('error', 'Error while retrieving packages');
            }

            /**
             *  Check that downloaded rpm package matches the checksum specified by the primary.xml file
             *  Try with sha256 then sha1
             */
            if (hash_file('sha256', $targetDir . '/' . $rpmPackageName) != $rpmPackageChecksum) {
                if (hash_file('sha1', $targetDir . '/' . $rpmPackageName) != $rpmPackageChecksum) {
                    $this->logError('checksum of the downloaded package does not match the checksum indicated by the source repository metadata (tested sha256 and sha1)', 'Error while retrieving packages');
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
            if ($this->checkSignature === 'true') {
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
                 *  Parse package's GPG signing key Id from header content
                 */

                /**
                 *  Case no key ID has been found in the package header (missing signature)
                 */
                if (!preg_match('/key ID(.*) /i', $content, $matches)) {
                    /**
                     *  If RPM_MISSING_SIGNATURE is set to 'error', then throw an error
                     */
                    if (RPM_MISSING_SIGNATURE == 'error') {
                        $this->logError('This package has no GPG signature (GPG signing key ID not found in the package header)', 'GPG signature check failed');
                    }

                    /**
                     *  If RPM_MISSING_SIGNATURE is set to 'ignore', then just ignore the package (delete it because it has been downloaded, and process next package)
                     */
                    if (RPM_MISSING_SIGNATURE == 'ignore') {
                        $this->logWarning('This package has no GPG signature (GPG signing key ID not found in the package header) (ignoring package)');

                        /**
                         *  Delete package
                         */
                        if (!unlink($targetDir. '/' . $rpmPackageName)) {
                            $this->logError('Error while deleting package <code>' . $targetDir. '/' . $rpmPackageName . '</code>', 'Error while deleting package');
                        }

                        continue;
                    }

                    /**
                     *  If RPM_MISSING_SIGNATURE is set to 'download', then download the package anyway
                     */
                    if (RPM_MISSING_SIGNATURE == 'download') {
                        $this->logWarning('This package has no GPG signature (GPG signing key ID not found in the package header) (downloaded anyway)');
                        continue;
                    }
                }

                /**
                 *  If there is a key ID in the package header, check if it is in the known public keys Id
                 */

                /**
                 *  Retrieve GPG signing key Id from $matches
                 */
                $keyId = trim($matches[1]);

                /**
                 *  Remove last ':' character
                 */
                $keyId = rtrim($keyId, ':');

                /**
                 *  Check if that key Id appears in known public keys Id
                 */
                if (!preg_grep("/$keyId\$/i", $knownPublicKeys)) {
                    /**
                     *  If RPM_INVALID_SIGNATURE is set to 'error', then throw an error
                     */
                    if (RPM_INVALID_SIGNATURE == 'error') {
                        $this->logError('GPG signature check failed (unknown GPG signing key ID: ' . $keyId . ')', 'GPG signature check failed');
                    }

                    /**
                     *  If RPM_INVALID_SIGNATURE is set to 'ignore', then just ignore the package (delete it because it has been downloaded, and process next package)
                     */
                    if (RPM_INVALID_SIGNATURE == 'ignore') {
                        $this->logWarning('GPG signature check failed (unknown GPG signing key ID: ' . $keyId . ') (ignoring package)');

                        /**
                         *  Delete package
                         */
                        if (!unlink($targetDir. '/' . $rpmPackageName)) {
                            $this->logError('Error while deleting package <code>' . $targetDir. '/' . $rpmPackageName . '</code>', 'Error while deleting package');
                        }

                        continue;
                    }

                    /**
                     *  If RPM_INVALID_SIGNATURE is set to 'download', then download the package anyway
                     */
                    if (RPM_INVALID_SIGNATURE == 'download') {
                        $this->logWarning('GPG signature check failed (unknown GPG signing key ID: ' . $keyId . ') (downloaded anyway)');
                        continue;
                    }
                }
            }

            /**
             *  Print OK if package has been downloaded and verified successfully
             */
            $this->logOK();

            unset($myprocess, $content, $keyId, $matches);
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
        if ($this->checkSignature == 'true' and !file_exists('/usr/bin/rpm')) {
            throw new Exception('rpm is not present on the system (searched in /usr/bin/rpm)');
        }

        /**
         *  Delete final slash if exist
         */
        $this->url = rtrim($this->url, '/');

        /**
         *  Building all possibly URLs to explore, from the base URL, the releasever and all the archs selected by the user
         *  Loop through all the archs selected by the user to build all the possible URLs to explore
         */
        foreach ($this->arch as $arch) {
            $url = $this->url;

            /**
             *  Replace $releasever variable in the URL if exists
             */
            $url = str_replace('$releasever', $this->releasever, $url);

            /**
             *  If there is a $basearch variable in the URL, replace it with the current arch
             */
            if (preg_match('/\$basearch/i', $url)) {
                $this->archUrls[] = str_replace('$basearch', $arch, $url);
            /**
             *  Else if there is no $basearch variable in the URL, just append the arch to the URL as this could be a possible URL to explore
             */
            } else {
                $this->archUrls[] = $url . '/' . $arch;
            }
        }

        /**
         *  If 'src' exists in the arch array
         */
        if (in_array('src', $this->arch)) {
            /**
             *  If there is a $basearch variable in the URL, replace it with 'SRPMS'
             */
            if (preg_match('/\$basearch/i', $this->url)) {
                $this->archUrls[] = str_replace('$basearch', 'SRPMS', $this->url);
            /**
             *  Else if there is no $basearch variable in the URL, just append 'SRPMS' to the URL as this could be a possible URL to explore
             */
            } else {
                $this->archUrls[] = $this->url . '/SRPMS';
            }
        }

        /**
         *  Foreach arch URL, test if it is reachable and got a /repodata/repomd.xml file, else remove the URL from the array
         *  e.g. of $this->archUrls content:
         *  Array
         *  (
         *      [0] => http://nginx.org/packages/centos/7/x86_64
         *      [1] => http://nginx.org/packages/centos/7/src
         *      [2] => http://nginx.org/packages/centos/7/SRPMS
         *  )
         */
        foreach ($this->archUrls as $url) {
            $urlReachable = \Controllers\Common::urlReachable($url . '/repodata/repomd.xml', $this->sslCustomCertificate, $this->sslCustomPrivateKey, $this->sslCustomCaCertificate);

            /**
             *  If url is not reachable
             */
            if ($urlReachable !== true) {
                /**
                 *  Add URL to errorUrls array with the response code
                 */
                $errorUrls[] = array('url' => $url, 'responseCode' => $urlReachable['responseCode']);

                /**
                 *  Remove the unreachable URL from possible URLs array, others URLs will be tested
                 */
                if (($key = array_search($url, $this->archUrls)) !== false) {
                    unset($this->archUrls[$key]);
                }
            }
        }

        /**
         *  Remove all empty subarray of $this->archUrls and print an error and quit if no valid/reachable URL has been found
         */
        if (empty(array_filter($this->archUrls))) {
            $errorUrlsString = '';

            /**
             *  For each URL that has been tested and returned an error, add it to the error message
             */
            foreach ($errorUrls as $errorUrl) {
                /**
                 *  If response code is 403, add some explanation to the error message
                 */
                if ($errorUrl['responseCode'] == '403') {
                    $errorUrlsString .= ' • <span class="copy">' . $errorUrl['url'] . '</span> (response code: ' . $errorUrl['responseCode'] . ' forbidden. The URL might require authentication, has IP filtering or is non-existent.)' . PHP_EOL;
                } else {
                    $errorUrlsString .= ' • <span class="copy">' . $errorUrl['url'] . '</span> (response code: ' . $errorUrl['responseCode'] . ')' . PHP_EOL;
                }
            }

            $this->logError('No reachable URL found. The source repository URL might be incorrect or unreachable. Tested URLs:' . PHP_EOL . $errorUrlsString, 'No reachable URL found');
        }

        /**
         *  If there was no error, print the URLs that will be used to retrieve packages
         */
        $this->logOutput(PHP_EOL . 'Packages will be retrieved from following URLs:' . PHP_EOL);

        foreach ($this->archUrls as $url) {
            $this->logOutput(' • <span class="copy">' . $url . '</span>' . PHP_EOL);
        }

        /**
         *  Retrieve packages for each URLs
         */
        foreach ($this->archUrls as $url) {
            /**
             *  Download repomd.xml
             */
            $this->downloadRepomd($url);

            /**
             *  Find primary, group and module files location
             */
            $this->parseRepoMd();

            /**
             *  Download primary.xml packages list file
             */
            $this->downloadPrimary($url);

            /**
             *  Download group files
             */
            $this->downloadComps($url);

            /**
             *  Download modules.yaml file
             */
            $this->downloadModules($url);

            /**
             *  Download updateinfo.xml.gz file
             */
            $this->downloadUpdateInfo($url);

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
