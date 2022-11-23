<?php

namespace Controllers;

use Exception;

class Mirror
{
    private $type;
    private $url;
    private $dist;
    private $section;
    private $arch;
    private $translation;
    private $syncSource;
    private $checkSignature = 'yes';
    private $gpgKeyUrl;
    private $primaryLocation;
    private $primaryChecksum;
    private $packagesIndicesLocation = array();
    private $sourcesIndicesLocation = array();
    private $translationsLocation = array();
    private $debPackagesLocation = array();
    private $sourcesPackagesLocation = array();
    private $rpmPackagesLocation = array();
    private $workingDir;
    private $outputToFile = false;
    private $outputFile;
    private $sslCustomCertificate;
    private $sslCustomPrivateKey;

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    public function setDist(string $dist)
    {
        $this->dist = $dist;
    }

    public function setSection(string $section)
    {
        $this->section = $section;
    }

    public function setArch(array $arch)
    {
        $this->arch = $arch;
    }

    public function setCheckSignature(string $checkSignature)
    {
        $this->checkSignature = $checkSignature;
    }

    public function setGpgKeyUrl(string $url)
    {
        $this->gpgKeyUrl = $url;
    }

    public function setTranslation(array $translation)
    {
        $this->translation = $translation;
    }

    public function setWorkingDir(string $dir)
    {
        $this->workingDir = $dir;
    }

    public function setSyncSource(string $syncSource)
    {
        $this->syncSource = $syncSource;
    }

    public function setSslCustomCertificate(string $path)
    {
        $this->sslCustomCertificate = $path;
    }

    public function setSslCustomPrivateKey(string $path)
    {
        $this->sslCustomPrivateKey = $path;
    }

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
         *  Try with sha256 then sha1
         */
        if (hash_file('sha256', $this->workingDir . '/primary.xml.gz') != $checksum) {
            if (hash_file('sha1', $this->workingDir . '/primary.xml.gz') != $checksum) {
                throw new Exception('Error: primary.xml.gz checksum does not match provided checksum');
            }
        }

        $this->logOK();
    }

    /**
     *  Download Release indices file
     *  (DEB mirror)
     */
    private function getReleaseFile()
    {
        $this->logOutput(PHP_EOL . '- Getting <b>Release</b> indices file ... ');

        /**
         *  Check that Release.xx file exists before downloading it to prevent error message displaying for nothing
         */
        if (Common::urlFileExists($this->url . '/dists/' . $this->dist . '/InRelease', $this->sslCustomCertificate, $this->sslCustomPrivateKey)) {
            $this->download($this->url . '/dists/' . $this->dist . '/InRelease', $this->workingDir . '/InRelease');
        }
        if (Common::urlFileExists($this->url . '/dists/' . $this->dist . '/Release', $this->sslCustomCertificate, $this->sslCustomPrivateKey)) {
            $this->download($this->url . '/dists/' . $this->dist . '/Release', $this->workingDir . '/Release');
        }
        if (Common::urlFileExists($this->url . '/dists/' . $this->dist . '/Release.gpg', $this->sslCustomCertificate, $this->sslCustomPrivateKey)) {
            $this->download($this->url . '/dists/' . $this->dist . '/Release.gpg', $this->workingDir . '/Release.gpg');
        }

        /**
         *  Print an error and quit if no Release file has been found
         */
        if (!file_exists($this->workingDir . '/InRelease') and !file_exists($this->workingDir . '/Release') and !file_exists($this->workingDir . '/Release.gpg')) {
            $this->logError('Error', 'Could not download Release indices file');
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

        foreach ($jsonArray['package'] as $data) {
            /**
             *  Find package location is set
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
     *  Parse Release file to find :
     *   - Packages indices file location  (Packages / Packages.gz)
     *   - Sources packages file location  (Sources / Sources.gz)
     *   - Translation file location       (Translation-en / Translation-en.bz2)
     *
     *  (DEB mirror)
     */
    private function parseReleaseIndiceFile()
    {
        if (file_exists($this->workingDir . '/InRelease')) {
            $content = file($this->workingDir . '/InRelease');
        } elseif (file_exists($this->workingDir . '/Release')) {
            $content = file($this->workingDir . '/Release');
        }

        $this->logOutput(PHP_EOL . '- Searching for <b>Packages</b> indices file location ... ');

        /**
         *  Process research of Packages indices for each arch
         */
        foreach ($this->arch as $arch) {
            /**
             *  Packages pattern to search in the Release file
             *  e.g: main/binary-amd64/Packages
             */
            $regex = $this->section . '/binary-' . $arch . '/Packages($|.gz$|.xz$)';

            /**
             *  Parse the whole file, searching for the desired lines
             */
            foreach ($content as $line) {
                if (preg_match("#$regex#", $line)) {
                    /**
                     *  Explode the line to separate hashes and location
                     */
                    $splittedLine = explode(' ', trim($line));

                    /**
                     *  We only need the location with its SHA256 (64 caracters long)
                     *  e.g: bd29d2ec28c10fec66a139d8e9a88ca01ff0f2533ca3fab8dc33c13b533059c1  1279885 main/binary-amd64/Packages
                     */
                    if (strlen($splittedLine[0]) == '64') {
                        $location = end($splittedLine);
                        $checksum = $splittedLine[0];

                        /**
                         *  Include this Package.xx file only if it does really exist on the remote server (sometimes it can be declared in Release but not exists...)
                         */
                        if (Common::urlFileExists($this->url . '/dists/' . $this->dist . '/' . $location, $this->sslCustomCertificate, $this->sslCustomPrivateKey)) {
                            $this->packagesIndicesLocation[] = array('location' => $location, 'checksum' => $checksum);

                            /**
                             *  Then ignore all next Package.xx indices file from the same arch as at least one has been found
                             */
                            break 1;
                        }
                    }
                }
            }
        }

        /**
         *  Throw an error if no Packages indices file location has been found
         */
        if (empty($this->packagesIndicesLocation)) {
            $this->logError('No Packages indices file location has been found.', 'Cannot retrieve Packages indices file');
        }

        $this->logOK();

        /**
         *  Process research of Sources files for the current section
         */
        if ($this->syncSource == 'yes') {
            $this->logOutput(PHP_EOL . '- Searching for <b>Sources</b> indices file location ... ');

            /**
             *  Sources pattern to search in the Release file
             *  e.g: main/source/Sources
             */
            $regex = $this->section . '/source/Sources';

            /**
             *  Parse the whole file, searching for the desired lines
             */
            foreach ($content as $line) {
                if (preg_match("#$regex$#", $line)) {
                    /**
                     *  Explode the line to separate hashes and location
                     */
                    $splittedLine = explode(' ', trim($line));

                    /**
                     *  We only need the location with its md5sum (32 caracters long)
                     *  e.g: 1440dd54895a24684cdbb39ddc54ea22 40470389 main/source/Sources
                     */
                    if (strlen($splittedLine[0]) == '32') {
                        $this->sourcesIndicesLocation[] = array('location' => end($splittedLine), 'md5sum' => $splittedLine[0]);
                    }
                }
            }

            /**
             *  Throw an error if no Sources indices file location has been found
             */
            if (empty($this->packagesIndicesLocation)) {
                $this->logError('No Sources indices file location has been found. Check that specified distribution and section names are correct.', 'Cannot retrieve Sources indices file');
            }

            $this->logOK();
        }

        /**
         *  Process research of Translation files for each requested translation language
         */
        if (!empty($this->translation)) {
            $this->logOutput(PHP_EOL . '- Searching for <b>Translation</b> file(s) location ... ');

            foreach ($this->translation as $translation) {
                /**
                 *  Translation pattern to search in the Release file
                 *  e.g: main/i18n/Translation-fr.bz2
                 */
                $regex = $this->section . '/i18n/Translation-' . $translation . '.bz2';

                /**
                 *  Parse the whole file, searching for the desired lines
                 */
                foreach ($content as $line) {
                    if (preg_match("#$regex$#", $line)) {
                        /**
                         *  Explode the line to separate hashes and location
                         */
                        $splittedLine = explode(' ', trim($line));

                        /**
                         *  We only need the location with its md5sum (32 caracters long)
                         *  e.g: 35e89f49cdfaa179e552aee1d67c5cdb  2478327 main/i18n/Translation-fr.bz2
                         */
                        if (strlen($splittedLine[0]) == '32') {
                            $this->translationsLocation[] = array('location' => end($splittedLine), 'md5sum' => $splittedLine[0]);
                        }
                    }
                }
            }

            /**
             *  Throw an error if no Translation file location has been found
             */
            if (empty($this->translationsLocation)) {
                $this->logError('No Translation file location has been found. There may have no translation available for this repository.', 'Cannot retrieve translations files');
            }

            $this->logOK();
        }

        unset($content);
    }

    /**
     *  Parse Packages indices file to find .deb packages location
     *  (DEB mirror)
     */
    private function parsePackagesIndiceFile()
    {
        $this->logOutput('- Retrieving deb packages list ... ');

        /**
         *  Process research for each Package file (could have multiple if multiple archs have been specified)
         */
        foreach ($this->packagesIndicesLocation as $packageIndice) {
            $packageIndicesLocation = $packageIndice['location'];
            $packageIndicesChecksum = $packageIndice['checksum'];
            $packageIndicesName = preg_split('#/#', $packageIndicesLocation);
            $packageIndicesName = end($packageIndicesName);

            /**
             *  Download Packages.xx file using its location
             */
            if (!$this->download($this->url . '/dists/' . $this->dist . '/' . $packageIndicesLocation, $this->workingDir . '/' . $packageIndicesName)) {
                $this->logError('Error while downloading ' . $packageIndicesName . ' indices file: ' . $this->url . '/' . $packageIndicesLocation, 'Could not download ' . $packageIndicesName . ' indices file');
            }

            /**
             *  Then check that the Packages.xx file's checksum matches the one that what specified in Release file
             */
            if (hash_file('sha256', $this->workingDir . '/' . $packageIndicesName) !== $packageIndicesChecksum) {
                $this->logError($packageIndicesName . ' indices file\'s SHA256 checksum does not match the SHA256 checksum specified in the Release file ' . $packageIndicesChecksum, 'Could not verify Packages indices file');
            }

            /**
             *  Uncompress Packages.xx if it is compressed (.gz or .xz)
             */
            if (preg_match('/.gz$/i', $packageIndicesName)) {
                try {
                    Common::gunzip($this->workingDir . '/' . $packageIndicesName);
                } catch (Exception $e) {
                    $this->logError($e, 'Error while uncompressing ' . $packageIndicesName);
                }
            }
            if (preg_match('/.xz$/i', $packageIndicesName)) {
                try {
                    Common::xzUncompress($this->workingDir . '/Packages.xz');
                } catch (Exception $e) {
                    $this->logError($e, 'Error while uncompressing Packages.xz');
                }
            }

            /**
             *  Get all .deb packages location from the uncompressed Packages file
             */
            $packageLocation = '';
            $packageChecksum = '';
            $handle = fopen($this->workingDir . '/Packages', 'r');

            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    /**
                     *  Get deb location
                     */
                    if (preg_match('/^Filename:\s+(.*)/im', $line)) {
                        $packageLocation = trim(str_replace('Filename: ', '', $line));
                    }

                    /**
                     *  Get deb SHA256
                     */
                    if (preg_match('/^SHA256:\s+(.*)/im', $line)) {
                        $packageChecksum = trim(str_replace('SHA256: ', '', $line));
                    }

                    /**
                     *  If location and checksum have been parsed, had them to the global deb packages list array
                     */
                    if (!empty($packageLocation) and !empty($packageChecksum)) {
                        $this->debPackagesLocation[] = array('location' => $packageLocation, 'checksum' => $packageChecksum);

                        unset($packageLocation, $packageChecksum);
                    }
                }

                fclose($handle);
            }
        }

        /**
         *  Quit if no packages have been found
         */
        if (empty($this->debPackagesLocation)) {
            $this->logError('No packages found in Packages indices file');
        }

        $this->logOK();
    }

    /**
     *  Parse Sources indices file to find .dsc/tar.gz/tar.xz sources packages location
     *  (DEB mirror)
     */
    private function parseSourcesIndiceFile()
    {
        if ($this->syncSource != 'yes') {
            return;
        }

        $this->logOutput('- Retrieving sources packages list ... ');

        /**
         *  Process research for each Sources file
         */
        foreach ($this->sourcesIndicesLocation as $sourcesIndice) {
            $sourcesIndicesLocation = $sourcesIndice['location'];
            $sourcesIndexMd5 = $sourcesIndice['md5sum'];

            /**
             *  Download Source file using its location
             */
            if (!$this->download($this->url . '/dists/' . $this->dist . '/' . $sourcesIndicesLocation, $this->workingDir . '/Sources')) {
                $this->logError('Error while downloading Sources indices file: ' . $this->url . '/' . $sourcesIndicesLocation, 'Could not download Sources indices file');
            }

            /**
             *  Gunzip Sources.gz
             */
            // try {
            //     \Controllers\Common::gunzip($this->workingDir . '/Sources.gz');
            // } catch(Exception $e) {
            //     $this->logError($e, 'Error while uncompressing Sources.gz');
            // }

            /**
             *  Then check that the gunzip Sources file's md5 is the same as the one that what specified in Release file
             */
            if (md5_file($this->workingDir . '/Sources') !== $sourcesIndexMd5) {
                $this->logError('Sources indices file\'s md5 (' . md5_file($this->workingDir . '/Sources') . ') does not match the md5 specified in the Release file ' . $sourcesIndexMd5, 'Could not verify Packages indices file');
            }

            /**
             *  Get all .dsc/tar.gz/tar.xz sources packages location from the Sources file
             */
            $directory = '';
            $packageLocation = '';
            $packageMd5 = '';
            $linecount = 0;
            $handle = fopen($this->workingDir . '/Sources', 'r');

            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    /**
                     *  Get .dsc/tar.gz/tar.xz directory location
                     */
                    if (preg_match('/^Directory:\s+(.*)/im', $line)) {
                        $directory = trim(str_replace('Directory: ', '', $line));
                    }

                    /**
                     *  Get .dsc/tar.gz/tar.xz location
                     */
                    if (preg_match('/^Files:$/im', $line)) {
                        /**
                         *  If line starts with 'Files:' then get the next 3 lines that contain the packages name and md5sum
                         *  Use current $linecount to get the next 3 lines
                         */
                        $spl = new \SplFileObject($this->workingDir . '/Sources');

                        for ($i = 1; $i < 4; $i++) {
                            $spl->seek($linecount + $i);
                            $packageLine = $spl->current();
                            $packageLine = explode(' ', $packageLine);
                            $packageMd5 = trim($packageLine[1]);
                            $packageLocation = trim($packageLine[3]);

                            /**
                             *  Add founded packages to the global array
                             */
                            if (!empty($directory) and !empty($packageLocation) and !empty($packageMd5)) {
                                $this->sourcesPackagesLocation[] = array('location' => $directory . '/' . $packageLocation, 'md5sum' => $packageMd5);
                            }
                        }

                        unset($spl, $packageLocation, $packageMd5);
                    }

                    $linecount++;
                }

                fclose($handle);
            }
        }

        /**
         *  Quit if no sources packages have been found
         */
        if (empty($this->sourcesPackagesLocation)) {
            $this->logError('No packages found in Packages indices file');
        }

        $this->logOK();
    }

    /**
     *  Check Release file GPG signature
     *  (DEB mirror)
     */
    private function checkReleaseGPGSignature()
    {
        /**
         *  Quit if signature check is disabled
         */
        if ($this->checkSignature === 'no') {
            return;
        }

        $this->logOutput('- Checking Release GPG signature ... ');

        /**
         *  Check signature from InRelease file in priority, else from Release.gpg file
         */
        if (file_exists($this->workingDir . '/InRelease')) {
            $this->checkGPGSignature($this->workingDir . '/InRelease');
        } elseif (file_exists($this->workingDir . '/Release.gpg')) {
            $this->checkGPGSignature($this->workingDir . '/Release.gpg');
        }

        $this->logOK();
    }

    /**
     *  Check GPG signature of specified file
     *  (DEB mirror)
     */
    private function checkGPGSignature(string $file)
    {
        $myprocess = new \Controllers\Process('gpgv --homedir ' . GPGHOME . ' ' . $file);
        $myprocess->execute();
        $myprocess->getOutput();
        $myprocess->close();

        /**
         *  If gpgv returned an error then signature is invalid
         */
        if ($myprocess->getExitCode() != 0) {
            $this->logError('Signature could not verify file ' . $file, 'Error while checking GPG signature');
        }
    }

    /**
     *  Create working directories
     */
    private function initialize()
    {
        if (!is_dir($this->workingDir)) {
            if (!mkdir($this->workingDir, 0770, true)) {
                throw new Exception('Cannot create temporary working directory');
            }
        }
    }

    /**
     *  Download specified distant file
     */
    private function download(string $url, string $savePath)
    {
        $curlError = 0;
        $localFile = fopen($savePath, "w");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);            // set remote file url
        curl_setopt($ch, CURLOPT_FILE, $localFile);     // set output file
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);         // set timeout
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // follow redirect

        /**
         *  If a custom ssl certificate and private key must be used
         */
        if (!empty($this->sslCustomCertificate)) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->sslCustomCertificate);
        }
        if (!empty($this->sslCustomPrivateKey)) {
            curl_setopt($ch, CURLOPT_SSLKEY, $this->sslCustomPrivateKey);
        }

        /**
         *  Execute curl
         */
        curl_exec($ch);

        /**
         *  If curl has failed (meaning a curl param might be invalid)
         */
        if (curl_errno($ch)) {
            curl_close($ch);
            fclose($localFile);

            $this->logError('Curl error: ' . curl_error($ch), 'Download error');
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
                $this->logOutput('File not found ');
            } else {
                $this->logOutput('File could not be downloaded (http return code is: ' . $status["http_code"] . ') ');
            }

            curl_close($ch);
            fclose($localFile);

            return false;
        }

        return true;
    }

    /**
     *  Download rpm packages
     *  (RPM mirror)
     */
    private function downloadRpmPackages(string $url)
    {
        /**
         *  Create directory in which packages will be downloaded
         */
        if (!is_dir($this->workingDir . '/packages')) {
            mkdir($this->workingDir . '/packages', 0770, true);
        }

        /**
         *  If GPG signature check is enabled, either use a distant http:// GPG key or use the repomanager keyring
         */
        if ($this->checkSignature === 'yes') {
            /**
             *  If the source repo has a distant http:// gpg signature key, then download it
             */
            if (!empty($this->gpgKeyUrl)) {
                if (!$this->download($this->gpgKeyUrl, TEMP_DIR . '/gpgkey-to-import.gpg')) {
                    $this->logError('Could not download distant GPG signature key: ' . $this->gpgKeyUrl, 'Could not retrieve GPG signature key');
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
                    $this->logError('Error while importing distant GPG key', 'Could not retrieve GPG signature key');
                }

                $myprocess->close();
            }

            /**
             *  Get all known editors GPG public keys imported into repomanager keyring
             */
            $knownPublicKeys = \Controllers\Common::getGpgTrustedKeys();

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
             *  Download
             */
            if (!$this->download($url . '/' . $rpmPackageLocation, $this->workingDir . '/packages/' . $rpmPackageName)) {
                $this->logError('error', 'Error while retrieving packages');
            }

            /**
             *  Check that downloaded rpm package's matches the checksum specified by the primary.xml file
             *  Try with sha256 then sha1
             */
            if (hash_file('sha256', $this->workingDir . '/packages/' . $rpmPackageName) != $rpmPackageChecksum) {
                if (hash_file('sha1', $this->workingDir . '/packages/' . $rpmPackageName) != $rpmPackageChecksum) {
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
                $myprocess = new \Controllers\Process('/usr/bin/rpm -qp --qf "%|DSAHEADER?{%{DSAHEADER:pgpsig}}:{%|RSAHEADER?{%{RSAHEADER:pgpsig}}:{(none}|}| %{NVRA}\n" ' . $this->workingDir . '/packages/' . $rpmPackageName);
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
     *  Download deb packages
     *  (DEB mirror)
     */
    private function downloadDebPackages($url)
    {
        /**
         *  Create directory in which packages will be downloaded
         */
        mkdir($this->workingDir . '/packages', 0770, true);

        /**
         *  Print URL from which packages are downloaded
         */
        $this->logOutput(PHP_EOL . '- Downloading packages from: ' . $url . PHP_EOL);

        /**
         *  Count total packages to print progression during syncing
         */
        $totalPackages = count($this->debPackagesLocation);
        $packageCounter = 0;

        /**
         *  Download each package and check its md5
         */
        foreach ($this->debPackagesLocation as $debPackage) {
            $debPackageLocation = $debPackage['location'];
            $debPackageChecksum = $debPackage['checksum'];
            $debPackageName = preg_split('#/#', $debPackageLocation);
            $debPackageName = end($debPackageName);
            $packageCounter++;

            /**
             *  Output package to download to log file
             */
            $this->logOutput('(' . $packageCounter . '/' . $totalPackages . ')  ➙ ' . $debPackageLocation . ' ... ');

            /**
             *  Download
             */
            if (!$this->download($url . '/' . $debPackageLocation, $this->workingDir . '/packages/' . $debPackageName)) {
                $this->logError('error', 'Error while retrieving packages');
            }

            /**
             *  Check that downloaded deb package's sha256 matches the sha256 specified by the Packages file
             */
            if (hash_file('sha256', $this->workingDir . '/packages/' . $debPackageName) != $debPackageChecksum) {
                $this->logError('SHA256 does not match', 'Error while retrieving packages');
            }

            /**
             *  Print OK if package has been downloaded and verified successfully
             */
            $this->logOK();
        }

        unset($this->debPackagesLocation, $totalPackages, $packageCounter);
    }

    /**
     *  Download deb sources packages
     *  (DEB mirror)
     */
    private function downloadDebSourcesPackages($url)
    {
        /**
         *  Create directory in which sources packages will be downloaded
         */
        mkdir($this->workingDir . '/sources', 0770, true);

        /**
         *  Print URL from which sources packages are downloaded
         */
        $this->logOutput(PHP_EOL . '- Downloading sources packages from: ' . $url . PHP_EOL);

        /**
         *  Download each source package and check its md5
         */
        foreach ($this->sourcesPackagesLocation as $sourcePackage) {
            $sourcePackageLocation = $sourcePackage['location'];
            $sourcePackageMd5 = $sourcePackage['md5sum'];
            $sourcePackageName = preg_split('#/#', $sourcePackageLocation);
            $sourcePackageName = end($sourcePackageName);

            /**
             *  Output source package to download to log file
             */
            $this->logOutput('  ➙ ' . $sourcePackageLocation . ' ... ');

            /**
             *  Download
             */
            if (!$this->download($url . '/' . $sourcePackageLocation, $this->workingDir . '/sources/' . $sourcePackageName)) {
                $this->logError('error', 'Error while retrieving sources packages');
            }

            /**
             *  Check that downloaded source package's md5 matches the md5sum specified by the Sources indices file
             */
            if (md5_file($this->workingDir . '/sources/' . $sourcePackageName) != $sourcePackageMd5) {
                $this->logError('md5 does not match', 'Error while retrieving sources packages');
            }

            /**
             *  Print OK if source package has been downloaded and verified successfully
             */
            $this->logOK();
        }

        unset($this->sourcesPackagesLocation);
    }

    /**
     *  Download translation packages
     *  (DEB mirror)
     */
    private function downloadTranslation()
    {
        if (empty($this->translationsLocation)) {
            return;
        }

        /**
         *  Create directory in which packages will be downloaded
         */
        mkdir($this->workingDir . '/translations', 0770, true);

        /**
         *  Download each package and check its md5
         */
        foreach ($this->translationsLocation as $translation) {
            $translationLocation = $translation['location'];
            $translationMd5 = $translation['md5sum'];
            $translationName = preg_split('#/#', $translationLocation);
            $translationName = end($translationName);
            $translationUrl = $this->url . '/dists/' . $this->dist . '/' . $translationLocation;

            /**
             *  Output package to download to log file
             */
            $this->logOutput('Downloading translation: ' . $translationUrl . ' ... ');

            /**
             *  Download
             */
            if (!$this->download($translationUrl, $this->workingDir . '/translations/' . $translationName)) {
                $this->logError('error', 'Error while retrieving packages');
            }

            /**
             *  Check that downloaded deb package's md5 matches the md5sum specified by the Release file
             */
            if (md5_file($this->workingDir . '/translations/' . $translationName) != $translationMd5) {
                $this->logError('md5 does not match', 'Error while retrieving packages');
            }

            /**
             *  Print OK if package has been downloaded and verified successfully
             */
            $this->logOK();
        }

        unset($this->translationsLocation);
    }

    /**
     *  Set log file to output to
     */
    public function setOutputFile(string $file)
    {
        $this->outputFile = $file;

        /**
         *  If file does not exist, try to create it to be sure it is writeable
         */
        if (!file_exists($file)) {
            if (!touch($file)) {
                throw new Exception('Cannot create output log file: ' . $file);
            }
        }
    }

    /**
     *  Enable output to log file
     */
    public function outputToFile(bool $enable = false)
    {
        if ($enable == true) {
            $this->outputToFile = true;
        }
    }

    /**
     *  Write specified message to log file
     */
    private function logOutput(string $message)
    {
        /**
         *  Only write if logging is enabled
         */
        if ($this->outputToFile === true and !empty($this->outputFile)) {
            file_put_contents($this->outputFile, $message, FILE_APPEND);
        }
    }

    /**
     *  Write a green 'OK' to log file
     */
    private function logOK()
    {
        $this->logOutput('<span class="greentext">OK</span>' . PHP_EOL);
    }

    /**
     *  Write a red error message to log file and throw an Exception
     */
    private function logError(string $errorMessage, string $exceptionMessage = null)
    {
        /**
         *  If no specific exception message has been specified, then it will be the same as the error message displayed
         */
        if (empty($exceptionMessage)) {
            $exceptionMessage = $errorMessage;
        }

        $this->logOutput('<span class="redtext">' . $errorMessage . '</span>' . PHP_EOL);
        throw new Exception($exceptionMessage);
    }

    /**
     *  Clean remaining files in working directory
     */
    private function clean()
    {
        if (file_exists($this->workingDir . '/primary.xml')) {
            unlink($this->workingDir . '/primary.xml');
        }
        if (file_exists($this->workingDir . '/primary.xml.gz')) {
            unlink($this->workingDir . '/primary.xml.gz');
        }
        if (file_exists($this->workingDir . '/repomd.xml')) {
            unlink($this->workingDir . '/repomd.xml');
        }
        if (file_exists($this->workingDir . '/InRelease')) {
            unlink($this->workingDir . '/InRelease');
        }
        if (file_exists($this->workingDir . '/Release')) {
            unlink($this->workingDir . '/Release');
        }
        if (file_exists($this->workingDir . '/Release.gpg')) {
            unlink($this->workingDir . '/Release.gpg');
        }
        if (file_exists($this->workingDir . '/Packages.gz')) {
            unlink($this->workingDir . '/Packages.gz');
        }
        if (file_exists($this->workingDir . '/Packages')) {
            unlink($this->workingDir . '/Packages');
        }
        if (file_exists($this->workingDir . '/Sources')) {
            unlink($this->workingDir . '/Sources');
        }
    }

    /**
     *  Mirror a rpm repository
     */
    private function mirrorRpm()
    {
        /**
         *  Quit if rpm is not present on the system and that signature check is enabled
         */
        if ($this->checkSignature == 'yes' and !(file_exists('/usr/bin/rpm'))) {
            throw new Exception('rpm is not present on the system (searched in /usr/bin/rpm)');
        }

        /**
         *  Retrive packages for each arch that have been specified
         */
        foreach ($this->arch as $arch) {
            $url = $this->url;

            /**
             *  Replace $releasever value
             */
            if (preg_match('/\$releasever/i', $url)) {
                $url = str_replace('$releasever', RELEASEVER, $url);
            }

            /**
             *  Replace $basearch value
             */
            if (preg_match('/\$basearch/i', $url)) {
                $url = str_replace('$basearch', $arch, $url);
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

    /**
     *  Mirror a deb repository
     */
    private function mirrorDeb()
    {
        /**
         *  Try to download distant Release / InRelease file
         */
        $this->getReleaseFile();

        /**
         *  Check Release GPG signature if enabled
         */
        $this->checkReleaseGPGSignature();

        /**
         *  Parse Release indices file to find Packages source files location
         */
        $this->parseReleaseIndiceFile();

        /**
         *  Parse Packages indices file to find packages location
         */
        $this->parsePackagesIndiceFile();

        /**
         *  Parse Sources indices file to find sources packages location
         */
        $this->parseSourcesIndiceFile();

        /**
         *  Download deb packages
         */
        $this->downloadDebPackages($this->url);

        /**
         *  Download sources packages
         */
        $this->downloadDebSourcesPackages($this->url);

        /**
         *  Download translations
         */
        $this->downloadTranslation();

        /**
         *  Clean remaining files
         */
        $this->clean();
    }

    /**
     *  Mirror a repository
     */
    public function mirror()
    {
        $this->initialize();

        if ($this->type == 'rpm') {
            $this->mirrorRpm();
        }

        if ($this->type == 'deb') {
            $this->mirrorDeb();
        }
    }
}
