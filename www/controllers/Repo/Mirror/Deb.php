<?php

namespace Controllers\Repo\Mirror;

use Exception;

class Deb extends \Controllers\Repo\Mirror\Mirror
{
    /**
     *  Download Release file
     *  (DEB mirror)
     */
    private function getReleaseFile()
    {
        $this->logOutput(PHP_EOL . '- Getting <b>Release</b> file ... ');

        /**
         *  Check that Release.xx file exists before downloading it to prevent error message displaying for nothing
         */
        if (\Controllers\Common::urlFileExists($this->url . '/dists/' . $this->dist . '/InRelease', $this->sslCustomCertificate, $this->sslCustomPrivateKey)) {
            $this->download($this->url . '/dists/' . $this->dist . '/InRelease', $this->workingDir . '/InRelease');
        }
        if (\Controllers\Common::urlFileExists($this->url . '/dists/' . $this->dist . '/Release', $this->sslCustomCertificate, $this->sslCustomPrivateKey)) {
            $this->download($this->url . '/dists/' . $this->dist . '/Release', $this->workingDir . '/Release');
        }
        if (\Controllers\Common::urlFileExists($this->url . '/dists/' . $this->dist . '/Release.gpg', $this->sslCustomCertificate, $this->sslCustomPrivateKey)) {
            $this->download($this->url . '/dists/' . $this->dist . '/Release.gpg', $this->workingDir . '/Release.gpg');
        }

        /**
         *  Print an error and quit if no Release file has been found
         */
        if (!file_exists($this->workingDir . '/InRelease') and !file_exists($this->workingDir . '/Release') and !file_exists($this->workingDir . '/Release.gpg')) {
            $this->logError('No Release file has been found in the source repository ' . $this->url . '/dists/' . $this->dist . '/ (looked for InRelease, Release and Release.gpg)', 'Release file not found');
        }

        $this->logOK();
    }

    /**
     *  Parse Release file to find :
     *   - Packages indices file location  (Packages / Packages.gz)
     *   - Sources packages file location  (Sources / Sources.gz)
     *   - Translation file location       (Translation-en / Translation-en.bz2)
     *
     *  (DEB mirror)
     */
    private function parseReleaseFile()
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
                        if (\Controllers\Common::urlFileExists($this->url . '/dists/' . $this->dist . '/' . $location, $this->sslCustomCertificate, $this->sslCustomPrivateKey)) {
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
                    \Controllers\Common::gunzip($this->workingDir . '/' . $packageIndicesName);
                } catch (Exception $e) {
                    $this->logError($e, 'Error while uncompressing ' . $packageIndicesName);
                }
            }
            if (preg_match('/.xz$/i', $packageIndicesName)) {
                try {
                    \Controllers\Common::xzUncompress($this->workingDir . '/Packages.xz');
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
        $output = $myprocess->getOutput();
        $myprocess->close();

        /**
         *  If gpgv returned an error then signature is invalid
         */
        if ($myprocess->getExitCode() != 0) {
            $this->logError('No GPG key could verify the signature of downloaded file ' . $file . ': ' . PHP_EOL . $output, 'Error while checking GPG signature');
        }
    }

    /**
     *  Download deb packages
     *  (DEB mirror)
     */
    private function downloadDebPackages($url)
    {
        /**
         *  Target directory in which packages will be downloaded
         */
        $targetDir = $this->workingDir . '/packages';

        /**
         *  Create directory in which packages will be downloaded
         */
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0770, true);
        }

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
            /**
             *  Before downloading each package, check if there is enough disk space left (2GB minimum)
             */
            if (disk_free_space(REPOS_DIR) < 2000000000) {
                $this->logError('Repo storage has reached 2GB (minimum) of free space left. Operation automatically stopped.', 'Low disk space');
            }

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
             *  Check if file does not already exists before downloading it (e.g. copied from a previously snapshot)
             */
            if (file_exists($targetDir . '/' . $debPackageName)) {
                $this->logOutput('already exists (ignoring)' . PHP_EOL);
                continue;
            }

            /**
             *  Download
             */
            if (!$this->download($url . '/' . $debPackageLocation, $targetDir . '/' . $debPackageName)) {
                $this->logError('error', 'Error while retrieving packages');
            }

            /**
             *  Check that downloaded deb package's sha256 matches the sha256 specified by the Packages file
             */
            if (hash_file('sha256', $targetDir . '/' . $debPackageName) != $debPackageChecksum) {
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
        if ($this->syncSource != 'yes') {
            return;
        }

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
     *  Mirror a deb repository
     */
    public function mirror()
    {
        $this->initialize();

        /**
         *  Try to download distant Release / InRelease file
         */
        $this->getReleaseFile();

        /**
         *  Check Release GPG signature if enabled
         */
        $this->checkReleaseGPGSignature();

        /**
         *  Parse Release file to find Packages source files location
         */
        $this->parseReleaseFile();

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
}
