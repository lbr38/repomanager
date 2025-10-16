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
        $this->taskLogSubStepController->new('getting-repomd', 'GETTING REPOMD.XML', 'From ' . $url . '/repodata/repomd.xml');

        if (!$this->download($url . '/repodata/repomd.xml', $this->workingDir . '/repomd.xml')) {
            throw new Exception('Could not download <code>repomd.xml</code>');
        }

        $this->taskLogSubStepController->completed();
    }

    /**
     *  Download primary.xml packages list file
     */
    private function downloadPrimary(string $url)
    {
        $this->taskLogSubStepController->new('getting-primary', 'GETTING PRIMARY.XML.GZ', 'From ' . $url . '/' . $this->primaryLocation);

        if (!$this->download($url . '/' . $this->primaryLocation, $this->workingDir . '/primary.xml.gz')) {
            throw new Exception('Could not download <code>primary.xml.gz</code>');
        }

        /**
         *  Check that downloaded file checksum is the same as the provided checksum from repomd.xml
         */
        if (!$this->checksum($this->workingDir . '/primary.xml.gz', $this->primaryChecksum)) {
            throw new Exception('<code>primary.xml.gz</code> checksum does not match provided checksum');
        }

        $this->taskLogSubStepController->completed();
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

        $this->taskLogSubStepController->new('getting-comps', 'GETTING COMPS.XML', 'From ' . $url . '/' . $this->compsLocation);

        if (!$this->download($url . '/' . $this->compsLocation, $this->workingDir . '/comps.xml')) {
            throw new Exception('Could not download <code>comps.xml</code>');
        }

        /**
         *  Check that downloaded file checksum is the same as the provided checksum from repomd.xml
         */
        if (!$this->checksum($this->workingDir . '/comps.xml', $this->compsChecksum)) {
            throw new Exception('<code>comps.xml</code> checksum does not match provided checksum');
        }

        $this->taskLogSubStepController->completed();
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

        $this->taskLogSubStepController->new('getting-modules', 'GETTING MODULES', 'From ' . $url . '/' . $this->modulesLocation);

        /**
         *  Get modules file extension
         *  We'll give this modules file a temporary name, to avoid it being included automatically by createrepo_c (it fails every time with modules.yaml file)
         *  It will be renamed and imported by modifyrepo_c later
         */
        if (pathinfo($this->modulesLocation, PATHINFO_EXTENSION) == 'gz') {
            $modulesFileExtension = 'gz';
            $modulesFileTargetName = 'modules-temp.yaml.gz';
        } else if (pathinfo($this->modulesLocation, PATHINFO_EXTENSION) == 'bz2') {
            $modulesFileExtension = 'bz2';
            $modulesFileTargetName = 'modules-temp.yaml.bz2';
        } else if (pathinfo($this->modulesLocation, PATHINFO_EXTENSION) == 'xz') {
            $modulesFileExtension = 'xz';
            $modulesFileTargetName = 'modules-temp.yaml.xz';
        } else if (pathinfo($this->modulesLocation, PATHINFO_EXTENSION) == 'yaml') {
            $modulesFileExtension = 'yaml';
            $modulesFileTargetName = 'modules-temp.yaml';
        } else if (pathinfo($this->modulesLocation, PATHINFO_EXTENSION) == 'zst') {
            $modulesFileExtension = 'zst';
            $modulesFileTargetName = 'modules-temp.yaml.zst';
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
         */
        if (!$this->checksum($this->workingDir . '/' . $modulesFileTargetName, $this->modulesChecksum)) {
            throw new Exception('<code>' . $modulesFileTargetName . '</code> checksum does not match provided checksum');
        }

        /**
         *  If modules file has been downloaded as a .gz file, uncompress it (otherwise it will fail to be included to the metadata)
         */
        if ($modulesFileExtension == 'gz') {
            try {
                \Controllers\Common::gunzip($this->workingDir . '/' . $modulesFileTargetName);
            } catch (Exception $e) {
                throw new Exception('Error while uncompressing <code>' . $modulesFileTargetName . '</code><br><pre class="codeblock">' . $e->getMessage() . '</pre>');
            }

            /**
             *  Delete original .gz file
             */
            if (!unlink($this->workingDir . '/' . $modulesFileTargetName)) {
                throw new Exception('Could not delete <code>' . $modulesFileTargetName . '</code> file');
            }
        }

        if ($modulesFileExtension == 'bz2') {
            try {
                \Controllers\Common::bunzip2($this->workingDir . '/' . $modulesFileTargetName, $this->workingDir . '/modules.yaml');
            } catch (Exception $e) {
                throw new Exception('Error while uncompressing <code>' . $modulesFileTargetName . '</code><br><pre class="codeblock">' . $e->getMessage() . '</pre>');
            }

            /**
             *  Delete original .bz2 file
             */
            if (!unlink($this->workingDir . '/' . $modulesFileTargetName)) {
                throw new Exception('Could not delete <code>' . $modulesFileTargetName . '</code> file');
            }
        }

        if ($modulesFileExtension == 'xz') {
            try {
                \Controllers\Common::xzUncompress($this->workingDir . '/' . $modulesFileTargetName, $this->workingDir . '/modules.yaml');
            } catch (Exception $e) {
                throw new Exception('Error while uncompressing <code>' . $modulesFileTargetName . '</code><br><pre class="codeblock">' . $e->getMessage() . '</pre>');
            }

            /**
             *  Delete original .xz file
             */
            if (!unlink($this->workingDir . '/' . $modulesFileTargetName)) {
                throw new Exception('Could not delete <code>' . $modulesFileTargetName . '</code> file');
            }
        }

        if ($modulesFileExtension == 'zst') {
            try {
                \Controllers\Common::zstdUncompress($this->workingDir . '/' . $modulesFileTargetName);
            } catch (Exception $e) {
                throw new Exception('Error while uncompressing <code>' . $modulesFileTargetName . '</code><br><pre class="codeblock">' . $e->getMessage() . '</pre>');
            }

            /**
             *  Delete original .zst file
             */
            if (!unlink($this->workingDir . '/' . $modulesFileTargetName)) {
                throw new Exception('Could not delete <code>' . $modulesFileTargetName . '</code> file');
            }
        }

        $this->taskLogSubStepController->completed();
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

        $this->taskLogSubStepController->new('getting-updateinfo', 'GETTING UPDATEINFO.XML.GZ', 'From ' . $url . '/' . $this->updateInfoLocation);

        if (pathinfo($this->updateInfoLocation, PATHINFO_EXTENSION) == 'gz') {
            $updateInfoFileExtension = 'gz';
            $updateInfoFileTargetName = 'updateinfo.xml.gz';
        } else if (pathinfo($this->updateInfoLocation, PATHINFO_EXTENSION) == 'bz2') {
            $updateInfoFileExtension = 'bz2';
            $updateInfoFileTargetName = 'updateinfo.xml.bz2';
        } else if (pathinfo($this->updateInfoLocation, PATHINFO_EXTENSION) == 'xz') {
            $updateInfoFileExtension = 'xz';
            $updateInfoFileTargetName = 'updateinfo.xml.xz';
        } else if (pathinfo($this->updateInfoLocation, PATHINFO_EXTENSION) == 'xml') {
            $updateInfoFileExtension = 'xml';
            $updateInfoFileTargetName = 'updateinfo.xml';
        } else if (pathinfo($this->updateInfoLocation, PATHINFO_EXTENSION) == 'zst') {
            $updateInfoFileExtension = 'zst';
            $updateInfoFileTargetName = 'updateinfo.xml.zst';
        } else {
            throw new Exception('Unsupported file extension ' . pathinfo($this->updateInfoLocation, PATHINFO_EXTENSION) . ' for <code>updateinfo</code> file. Please contact the developer to add support for this file extension.');
        }

        if (!$this->download($url . '/' . $this->updateInfoLocation, $this->workingDir . '/' . $updateInfoFileTargetName)) {
            throw new Exception('Could not download <code>updateinfo.xml.gz</code>');
        }

        /**
         *  Check that downloaded file checksum is the same as the provided checksum from repomd.xml
         */
        if (!$this->checksum($this->workingDir . '/' . $updateInfoFileTargetName, $this->updateInfoChecksum)) {
            throw new Exception('<code>' . $updateInfoFileTargetName . '</code> checksum does not match provided checksum');
        }

        /**
         *  If updateinfo file has been downloaded as a .gz file, uncompress it
         */
        if ($updateInfoFileExtension == 'gz') {
            try {
                \Controllers\Common::gunzip($this->workingDir . '/' . $updateInfoFileTargetName);
            } catch (Exception $e) {
                throw new Exception('Could not uncompress <code>' . $updateInfoFileTargetName . '</code><br><pre class="codeblock">' . $e->getMessage() . '</pre>');
            }

            /**
             *  Delete original .gz file
             */
            if (!unlink($this->workingDir . '/' . $updateInfoFileTargetName)) {
                throw new Exception('Could not delete <code>' . $updateInfoFileTargetName . '</code> file');
            }
        }

        if ($updateInfoFileExtension == 'bz2') {
            try {
                \Controllers\Common::bunzip2($this->workingDir . '/' . $updateInfoFileTargetName, $this->workingDir . '/updateinfo.xml');
            } catch (Exception $e) {
                throw new Exception('Could not uncompress <code>' . $updateInfoFileTargetName . '</code><br><pre class="codeblock">' . $e->getMessage() . '</pre>');
            }

            /**
             *  Delete original .bz2 file
             */
            if (!unlink($this->workingDir . '/' . $updateInfoFileTargetName)) {
                throw new Exception('Could not delete <code>' . $updateInfoFileTargetName . '</code> file');
            }
        }

        if ($updateInfoFileExtension == 'xz') {
            try {
                \Controllers\Common::xzUncompress($this->workingDir . '/' . $updateInfoFileTargetName, $this->workingDir . '/updateinfo.xml');
            } catch (Exception $e) {
                throw new Exception('Error while uncompressing <code>' . $updateInfoFileTargetName . '</code><br><pre class="codeblock">' . $e->getMessage() . '</pre>');
            }

            /**
             *  Delete original .xz file
             */
            if (!unlink($this->workingDir . '/' . $updateInfoFileTargetName)) {
                throw new Exception('Could not delete <code>' . $updateInfoFileTargetName . '</code> file');
            }
        }

        if ($updateInfoFileExtension == 'zst') {
            try {
                \Controllers\Common::zstdUncompress($this->workingDir . '/' . $updateInfoFileTargetName);
            } catch (Exception $e) {
                throw new Exception('Error while uncompressing <code>' . $updateInfoFileTargetName . '</code><br><pre class="codeblock">' . $e->getMessage() . '</pre>');
            }

            /**
             *  Delete original .zst file
             */
            if (!unlink($this->workingDir . '/' . $updateInfoFileTargetName)) {
                throw new Exception('Could not delete <code>' . $updateInfoFileTargetName . '</code> file');
            }
        }

        $this->taskLogSubStepController->completed();
    }

    /**
     *  Parsing repomd.xml to get database location
     */
    private function parseRepoMd()
    {
        $this->taskLogSubStepController->new('parsing-repomd', 'PARSING REPOMD.XML');

        if (!file_exists($this->workingDir . '/repomd.xml')) {
            throw new Exception('Could not parse <code>' . $this->workingDir . '/repomd.xml</code>: File not found');
        }

        /**
         *  Convert repomd.xml XML content to JSON to PHP array for a simpler parsing
         */
        try {
            $xml = new SimpleXMLElement(file_get_contents($this->workingDir . '/repomd.xml'), LIBXML_PARSEHUGE);
            $jsonArray = json_decode(json_encode($xml), true);
            unset($xml);
        } catch (Exception $e) {
            throw new Exception('Could not parse ' . $this->workingDir . '/repomd.xml: ' . $e->getMessage());
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
            throw new Exception('Could not find location of the package list file');
        }

        $this->taskLogSubStepController->completed();

        unset($jsonArray, $data);
    }

    /**
     *  Parse primary packages list file to find .rpm packages location and their checksum
     */
    private function parsePrimaryPackagesList(string $primaryFile)
    {
        $error = 0;
        $this->rpmPackagesLocation = array();

        $this->taskLogSubStepController->new('parsing-primary', 'PARSING PACKAGES LIST', 'From ' . $primaryFile);

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
             *  Case mime type is application/zstd (.gz file)
             */
            } elseif ($mime == 'application/zstd') {
                \Controllers\Common::zstdUncompress($primaryFile);
            /**
             *  Case it's another mime type, throw an error
             */
            } else {
                throw new Exception('MIME type not supported: ' . $mime . '. Please contact the developer to add support for this MIME type.');
            }
        } catch (Exception $e) {
            throw new Exception('Error while uncompressing <code>'. end(explode('/', $primaryFile)) . '</code><br><pre class="codeblock">' . $e->getMessage() . '</pre>');
        }

        /**
         *  Primary.xml.gz has been uncompressed to primary.xml
         */
        $primaryFile = $this->workingDir . '/primary.xml';

        /**
         *  Check that primary.xml file exists
         */
        if (!file_exists($primaryFile)) {
            throw new Exception('Could not parse ' . $primaryFile . ': File not found after uncompressing <code>primary.xml.gz</code>');
        }

        /**
         *  Convert primary.xml content from XML to JSON to PHP array for a simpler parsing
         */
        try {
            $xml = new SimpleXMLElement(file_get_contents($primaryFile), LIBXML_PARSEHUGE);
            $jsonArray = json_decode(json_encode($xml), true);
            unset($xml);
        } catch (Exception $e) {
            throw new Exception('Could not parse ' . $primaryFile . ': ' . $e->getMessage());
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
                $this->taskLogSubStepController->warning('Package architecture ' . $jsonArray['package']['arch'] . ' is not matching any of the desired architecture (ignored)');
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
                    throw new Exception('Could not find checksum value for package ' . $packageLocation);
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
                        throw new Exception('Could not find architecture value for package ' . $packageLocation . ' in primary.xml file');
                    }

                    $packageArch = $data['arch'];

                    /**
                     *  If package checksum is not found then it can not be retrieved
                     */
                    if (empty($data['checksum'])) {
                        throw new Exception('Could not find checksum value for package ' . $packageLocation . ' in primary.xml file');
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
            throw new Exception('No package found');
        }

        /**
         *  Print OK if there was no warning
         */
        if ($error == 0) {
            $this->taskLogSubStepController->completed();
        }

        unset($jsonArray, $data);
    }

    /**
     *  Download rpm packages
     */
    private function downloadRpmPackages(string $url)
    {
        $this->taskLogSubStepController->new('downloading-packages', 'DOWNLOADING PACKAGES', 'From ' . $url);

        /**
         *  If GPG signature check is enabled, either use a distant http:// GPG key or use the repomanager keyring
         */
        if ($this->checkSignature == 'true') {
            $mygpg = new \Controllers\GPG();

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
         *  Count total packages to print progression during syncing
         */
        $totalPackages = count($this->rpmPackagesLocation);
        $packageCounter = 0;

        /**
         *  Download each package and check its md5
         */
        foreach ($this->rpmPackagesLocation as $rpmPackage) {
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
            $this->taskLogSubStepController->new('downloading-package-' . $packageCounter, 'DOWNLOADING PACKAGE (' . $packageCounter . '/' . $totalPackages . ')', $url . '/' . $rpmPackageLocation);

            /**
             *  Before downloading package, check if there is enough disk space left (2GB minimum)
             */
            if (disk_free_space(REPOS_DIR) < 2000000000) {
                throw new Exception('Low disk space: repository storage has reached 2GB (minimum) of free space left. Task automatically stopped.');
            }

            /**
             *  If a list of package(s) to include has been provided, check if the package is in the list
             *  If not, skip the package
             */
            if (!empty($this->packagesToInclude)) {
                $isIn = false;

                foreach ($this->packagesToInclude as $packageToInclude) {
                    if (preg_match('/' . $packageToInclude . '/', $rpmPackageName)) {
                        $isIn = true;
                    }
                }

                /**
                 *  If package is not in the list of packages to include, skip it
                 */
                if (!$isIn) {
                    $this->taskLogSubStepController->warning('Not in the list of packages to include (ignoring)');
                    continue;
                }
            }

            /**
             *  If a list of package(s) to exclude has been provided, check if the package is in the list
             *  If so, skip the package
             */
            if (!empty($this->packagesToExclude)) {
                $isIn = false;

                foreach ($this->packagesToExclude as $packageToExclude) {
                    if (preg_match('/' . $packageToExclude . '/', $rpmPackageName)) {
                        $isIn = true;
                    }
                }

                /**
                 *  If package is in the list of packages to exclude, skip it
                 */
                if ($isIn) {
                    $this->taskLogSubStepController->warning('In the list of packages to exclude (ignoring)');
                    continue;
                }
            }

            /**
             *  Check that package architecture is valid
             */
            if (!in_array($rpmPackageArch, RPM_ARCHS)) {
                throw new Exception('Invalid package architecture: ' . $rpmPackageArch . ' for package ' . $rpmPackageLocation);
            }

            /**
             *  If no package arch has been found when parsing primary.xml file, throw an error
             */
            if (empty($rpmPackageArch)) {
                throw new Exception('Invalid package architecture: an empty package architecture has been retrieved from distant repository metadata for package ' . $rpmPackageLocation);
            }

            /**
             *  If package arch is 'src' then package will be downloaded in a 'SRPMS' directory to respect most of the RPM repositories architecture
             */
            if ($rpmPackageArch == 'src') {
                $relativeDir = 'packages/SRPMS';

            /**
             *  Else, package will be downloaded in a directory named after the package arch
             */
            } else {
                $relativeDir = 'packages/' . $rpmPackageArch;
            }

            /**
             *  Define absolute directory in which package will be downloaded
             */
            $absoluteDir = $this->workingDir . '/' . $relativeDir;

            /**
             *  Create directory in which package will be downloaded
             */
            if (!is_dir($absoluteDir)) {
                if (!mkdir($absoluteDir, 0770, true)) {
                    throw new Exception('Cannot create directory: ' . $absoluteDir);
                }
            }

            /**
             *  Check if file does not already exists in the working dir before downloading it (e.g. when a package has multiple possible archs, it can have
             *  been downloaded or linked already from another arch)
             */
            if (file_exists($absoluteDir . '/' . $rpmPackageName)) {
                $this->taskLogSubStepController->completed($absoluteDir . '/' . $rpmPackageName . ' Already exists (ignoring)');
                continue;
            }

            /**
             *  Check if package already exists in the previous snapshot
             */
            if (isset($this->previousSnapshotDirPath)) {
                if (file_exists($this->previousSnapshotDirPath . '/' . $relativeDir . '/' . $rpmPackageName)) {
                    /**
                     *  If deduplication is enabled
                     *  Create a hard link to the package
                     */
                    if (REPO_DEDUPLICATION) {
                        if (!link($this->previousSnapshotDirPath . '/' . $relativeDir . '/' . $rpmPackageName, $absoluteDir . '/' . $rpmPackageName)) {
                            throw new Exception('Cannot create hard link to package: ' . $this->previousSnapshotDirPath . '/' . $relativeDir . '/' . $rpmPackageName);
                        }

                        $this->taskLogSubStepController->completed('Linked to previous snapshot');

                        continue;
                    }

                    /**
                     *  If deduplication is not enabled
                     *  Copy package from the previous snapshot
                     */
                    if (!copy($this->previousSnapshotDirPath . '/' . $relativeDir . '/' . $rpmPackageName, $absoluteDir . '/' . $rpmPackageName)) {
                        throw new Exception('Cannot copy package from previous snapshot: ' . $this->previousSnapshotDirPath . '/' . $relativeDir . '/' . $rpmPackageName);
                    }

                    $this->taskLogSubStepController->completed('Copied from previous snapshot');

                    continue;
                }
            }

            /**
             *  Download package if it does not already exist
             */
            if (!$this->download($url . '/' . $rpmPackageLocation, $absoluteDir . '/' . $rpmPackageName, 3)) {
                throw new Exception('Error while downloading package');
            }

            /**
             *  Check that downloaded rpm package matches the checksum specified by the primary.xml file
             */
            if (!$this->checksum($absoluteDir . '/' . $rpmPackageName, $rpmPackageChecksum)) {
                $message = 'Checksum of the downloaded package does not match the checksum indicated by the source repository metadata';


                // If the MIRRORING_PACKAGE_CHECKSUM_FAILURE setting is set to 'error', then throw an exception
                if (MIRRORING_PACKAGE_CHECKSUM_FAILURE == 'error') {
                    throw new Exception($message);
                }

                // If the MIRRORING_PACKAGE_CHECKSUM_FAILURE setting is set to 'ignore', then we ignore the package (delete it) and continue
                if (MIRRORING_PACKAGE_CHECKSUM_FAILURE == 'ignore') {
                    $this->taskLogSubStepController->warning($message . ', ignoring package (deleting it) and continuing');

                    // Delete the package
                    if (file_exists($absoluteDir . '/' . $rpmPackageName)) {
                        unlink($absoluteDir . '/' . $rpmPackageName);
                    }

                    continue;
                }

                // If the MIRRORING_PACKAGE_CHECKSUM_FAILURE setting is set to 'keep', then we keep the package anyway and continue
                if (MIRRORING_PACKAGE_CHECKSUM_FAILURE == 'keep') {
                    $this->taskLogSubStepController->warning($message . ', keeping package anyway');
                    continue;
                }

                unset($message);
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
                    throw new Exception('Cannot check for package signature because there is no GPG public keys imported in Repomanager\'s keyring');
                }

                /**
                 *  Extract package header
                 */
                $myprocess = new \Controllers\Process('/usr/bin/rpm -qp --qf "%|DSAHEADER?{%{DSAHEADER:pgpsig}}:{%|RSAHEADER?{%{RSAHEADER:pgpsig}}:{(none}|}| %{NVRA}\n" ' . $absoluteDir. '/' . $rpmPackageName);
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
                        throw new Exception('This package has no GPG signature (GPG signing key ID not found in the package header)');
                    }

                    /**
                     *  If RPM_MISSING_SIGNATURE is set to 'ignore', then just ignore the package (delete it because it has been downloaded, and process next package)
                     */
                    if (RPM_MISSING_SIGNATURE == 'ignore') {
                        $this->taskLogSubStepController->warning('This package has no GPG signature (GPG signing key ID not found in the package header) (ignoring package)');

                        /**
                         *  Delete package
                         */
                        if (!unlink($absoluteDir . '/' . $rpmPackageName)) {
                            throw new Exception('Error while deleting package <code>' . $absoluteDir. '/' . $rpmPackageName . '</code>');
                        }

                        continue;
                    }

                    /**
                     *  If RPM_MISSING_SIGNATURE is set to 'download', then download the package anyway
                     */
                    if (RPM_MISSING_SIGNATURE == 'download') {
                        $this->taskLogSubStepController->warning('This package has no GPG signature (GPG signing key ID not found in the package header) (downloaded anyway)');

                        /**
                         *  Add package to the list of packages to sign (if signing is enabled).
                         *  This is the relative patch which is added, because the absolute path is just a temporary path (download-xxxx)
                         */
                        $this->packagesToSign[] = $relativeDir . '/' . $rpmPackageName;

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
                        throw new Exception('GPG signature check failed (unknown GPG signing key ID: ' . $keyId . ')');
                    }

                    /**
                     *  If RPM_INVALID_SIGNATURE is set to 'ignore', then just ignore the package (delete it because it has been downloaded, and process next package)
                     */
                    if (RPM_INVALID_SIGNATURE == 'ignore') {
                        $this->taskLogSubStepController->warning('GPG signature check failed (unknown GPG signing key ID: ' . $keyId . ') (ignoring package)');

                        /**
                         *  Delete package
                         */
                        if (!unlink($absoluteDir. '/' . $rpmPackageName)) {
                            throw new Exception('Error while deleting package <code>' . $absoluteDir. '/' . $rpmPackageName . '</code>');
                        }

                        continue;
                    }

                    /**
                     *  If RPM_INVALID_SIGNATURE is set to 'download', then download the package anyway
                     */
                    if (RPM_INVALID_SIGNATURE == 'download') {
                        $this->taskLogSubStepController->warning('GPG signature check failed (unknown GPG signing key ID: ' . $keyId . ') (downloaded anyway)');

                        /**
                         *  Add package to the list of packages to sign (if signing is enabled).
                         *  This is the relative patch which is added, because the absolute path is just a temporary path (download-xxxx)
                         */
                        $this->packagesToSign[] = $relativeDir . '/' . $rpmPackageName;

                        continue;
                    }
                }
            }

            /**
             *  Add package to the list of packages to sign (if signing is enabled).
             *  This is the relative patch which is added, because the absolute path is just a temporary path (download-xxxx)
             */
            $this->packagesToSign[] = $relativeDir . '/' . $rpmPackageName;

            /**
             *  Print OK if package has been downloaded and verified successfully
             */
            $this->taskLogSubStepController->completed();

            unset($myprocess, $content, $keyId, $matches);
        }

        // Set the main substep as completed
        $this->taskLogSubStepController->completed('', 'downloading-packages');

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
            throw new Exception('RPM binary is not present on the system (searched in <code>/usr/bin/rpm</code>)');
        }

        /**
         *  Delete final slash if exist
         */
        $this->url = rtrim($this->url, '/');

        /**
         *  First start by adding just the base URL to the array, with no arch in the URL (because some distant repos may have no arch in their URL)
         *  replacing '$releasever' with the specified releasever
         */
        $this->archUrls[] = str_replace('$releasever', $this->releasever, $this->url);

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
            $url = $this->url;

            /**
             *  Replace $releasever variable in the URL if exists
             */
            $url = str_replace('$releasever', $this->releasever, $url);

            /**
             *  If there is a $basearch variable in the URL, replace it with 'SRPMS'
             */
            if (preg_match('/\$basearch/i', $url)) {
                $this->archUrls[] = str_replace('$basearch', 'SRPMS', $url);
            /**
             *  Else if there is no $basearch variable in the URL, just append 'SRPMS' to the URL as this could be a possible URL to explore
             */
            } else {
                $this->archUrls[] = $url . '/SRPMS';
            }
        }

        /**
         *  Foreach arch URL, test if it is reachable and got a /repodata/repomd.xml file, else remove the URL from the array
         *  e.g. of $this->archUrls content:
         *  Array
         *  (
         *      [0] => http://nginx.org/packages/centos/7
         *      [1] => http://nginx.org/packages/centos/7/x86_64
         *      [2] => http://nginx.org/packages/centos/7/src
         *      [3] => http://nginx.org/packages/centos/7/SRPMS
         *  )
         */
        foreach ($this->archUrls as $url) {
            try {
                $this->httpRequestController->get([
                    'url' => $url . '/repodata/repomd.xml',
                    'connectTimeout' => 30,
                    'timeout' => 30,
                    'sslCertificatePath' => $this->sslCustomCertificate,
                    'sslPrivateKeyPath' => $this->sslCustomPrivateKey,
                    'sslCaCertificatePath' => $this->sslCustomCaCertificate,
                    'proxy' => PROXY ?? null,
                ]);
            } catch (Exception $e) {
                /**
                 *  If the URL is not reachable, add it to the errorUrls array
                 */
                $errorUrls[] = array('url' => $url, 'error' => $e->getMessage());

                /**
                 *  Remove the unreachable URL from possible URLs array, others URLs will be tested
                 */
                if (($key = array_search($url, $this->archUrls)) !== false) {
                    unset($this->archUrls[$key]);
                }
            }
        }

        $this->taskLogSubStepController->new('retrieve-metadata', 'RETRIEVING METADATA AND PACKAGES FROM URL(s)');

        /**
         *  Remove all empty subarray of $this->archUrls and print an error and quit if no valid/reachable URL has been found
         */
        if (empty(array_filter($this->archUrls))) {
            $errorUrlsString = '';

            /**
             *  For each URL that has been tested and returned an error, add it to the error message
             */
            foreach ($errorUrls as $errorUrl) {
                $errorUrlsString .= '<p> 🠶 <span class="copy wordbreakall">' . $errorUrl['url'] . '</span></p>';
                $errorUrlsString .= '<p class="note">Error details: ' . $errorUrl['error'] . '</p>';
            }

            throw new Exception('No reachable URL found. The source repository URL might be incorrect, unreachable, require SSL authentication, has IP filtering or is non-existent. Tested URLs:<br>' . $errorUrlsString);
        }

        /**
         *  If there was no error, print the URLs that will be used to retrieve packages
         */
        foreach ($this->archUrls as $url) {
            $this->taskLogSubStepController->output(' 🠶 <span class="copy wordbreakall">' . $url . '</span>');
        }

        $this->taskLogSubStepController->completed();

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
