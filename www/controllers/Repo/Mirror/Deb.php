<?php

namespace Controllers\Repo\Mirror;

use Exception;

class Deb extends \Controllers\Repo\Mirror\Mirror
{
    /**
     *  Download Release file
     */
    private function downloadReleaseFile()
    {
        $this->logOutput(PHP_EOL . 'Getting <code>InRelease</code> / <code>Release</code> file ... ');

        /**
         *  Check that Release.xx file exists before downloading it to prevent error message displaying for nothing
         */
        $releasePossibleNames = array('InRelease', 'Release', 'Release.gpg');

        foreach ($releasePossibleNames as $releaseFile) {
            $urlReachable = \Controllers\Common::urlReachable($this->url . '/dists/' . $this->dist . '/' . $releaseFile, $this->sslCustomCertificate, $this->sslCustomPrivateKey, $this->sslCustomCaCertificate);

            if ($urlReachable === true) {
                $this->download($this->url . '/dists/' . $this->dist . '/' . $releaseFile, $this->workingDir . '/' . $releaseFile);
            }
        }

        /**
         *  Print an error and quit if no Release file has been found
         */
        if (!file_exists($this->workingDir . '/InRelease') and !file_exists($this->workingDir . '/Release') and !file_exists($this->workingDir . '/Release.gpg')) {
            $this->logError('No <code>InRelease</code> or <code>Release</code> file has been found in the source repository <code>' . $this->url . '/dists/' . $this->dist . '/</code> (looked for <code>InRelease</code>, <code>Release</code> and <code>Release.gpg</code>). Is the URL of the repository correct?', '<code>Release</code> file not found');
        }

        $this->logOK();
    }

    /**
     *  Parse Release file to find :
     *   - Packages indices file location  (Packages / Packages.gz)
     *   - Sources packages file location  (Sources / Sources.gz)
     *   - Translation file location       (Translation-en / Translation-en.bz2)
     */
    private function parseReleaseFile()
    {
        /**
         *  Get valid InRelease / Release file content
         */
        $content = file($this->workingDir . '/' . $this->validReleaseFile);

        /**
         *  Process research of Packages indices for each arch
         */
        foreach ($this->arch as $arch) {
            /**
             *  If the arch is 'src' then the indices file is named 'Sources'
             */
            if ($arch == 'src') {
                $this->logOutput(PHP_EOL . 'Searching for <code>Sources</code> indices file location ... ');

                /**
                 *  Sources pattern to search in the Release file
                 *  e.g: main/source/Sources.xx
                 */
                $regex = $this->section . '/source/Sources';
            }

            /**
             *  If the arch is not 'src' then the indices file is named 'Packages'
             */
            if ($arch != 'src') {
                $this->logOutput(PHP_EOL . 'Searching for <code>Packages</code> indices file location for arch <code>' . $arch . '</code> ... ');

                /**
                 *  Packages pattern to search in the Release file
                 *  e.g: main/binary-amd64/Packages.xx
                 */
                $regex = $this->section . '/binary-' . $arch . '/Packages($|.gz$|.xz$)';
            }

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
                         *  Include this Packages.xx/Sources.xx file only if it does really exist on the remote server (sometimes it can be declared in Release but not exists...)
                         */
                        $urlReachable = \Controllers\Common::urlReachable($this->url . '/dists/' . $this->dist . '/' . $location, $this->sslCustomCertificate, $this->sslCustomPrivateKey, $this->sslCustomCaCertificate);

                        if ($urlReachable === true) {
                            if ($arch == 'src') {
                                $this->sourcesIndicesLocation[] = array('location' => $location, 'checksum' => $checksum);
                            }
                            if ($arch != 'src') {
                                $this->packagesIndicesLocation[] = array('location' => $location, 'checksum' => $checksum);
                            }

                            $this->logOK();

                            /**
                             *  Then ignore all next Packages.xx/Sources.xx indices file from the same arch as at least one has been found
                             */
                            continue 2;
                        }
                    }
                }
            }

            /**
             *  If no Packages.xx/Sources.xx file has been found for this arch, throw an error
             */
            if ($arch == 'src') {
                $this->logError('No ' . $arch . ' <code>Sources</code> indices file has been found in the <code>' . $this->validReleaseFile . '</code> file.', 'Cannot retrieve <code>' . $arch . '</code> <code>Packages</code> indices file');
            }
            if ($arch != 'src') {
                $this->logError('No ' . $arch . ' <code>Packages</code> indices file has been found in the <code>' . $this->validReleaseFile . '</code> file.', 'Cannot retrieve <code>' . $arch . '</code> <code>Packages</code> indices file');
            }
        }

        /**
         *  Throw an error if no Packages indices file location has been found
         */
        if (empty($this->packagesIndicesLocation)) {
            $this->logError('No <code>Packages</code> indices file location has been found.', 'Cannot retrieve <code>Packages</code> indices file');
        }
        if (in_array('src', $this->arch) and empty($this->sourcesIndicesLocation)) {
            $this->logError('No <code>Sources</code> indices file location has been found.', 'Cannot retrieve <code>Sources</code> indices file');
        }

        /**
         *  Process research of Translation files for each requested translation language
         */
        if (!empty($this->translation)) {
            $this->logOutput(PHP_EOL . 'Searching for <code>Translation</code> file(s) location ... ');

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
                $this->logError('No <code>Translation</code> file location has been found. There may have no translation available for this repository.', 'Cannot retrieve translations files');
            }

            $this->logOK();
        }

        unset($content);
    }

    /**
     *  Parse Packages indices file to find .deb packages location
     */
    private function parsePackagesIndiceFile()
    {
        $this->logOutput(PHP_EOL . 'Retrieving deb packages list ... ');

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
                $this->logError('Error while downloading <code>' . $packageIndicesName . '</code> indices file: <code>' . $this->url . '/' . $packageIndicesLocation . '</code>', 'Could not download <code>' . $packageIndicesName . '</code> indices file');
            }

            /**
             *  Then check that the Packages.xx file's checksum matches the one that what specified in Release file
             */
            if (hash_file('sha256', $this->workingDir . '/' . $packageIndicesName) !== $packageIndicesChecksum) {
                $this->logError('<code>' . $packageIndicesName . '</code> indices file\'s SHA256 checksum does not match the SHA256 checksum specified in the <code>Release</code> file ' . $packageIndicesChecksum, 'Could not verify <code>Packages</code> indices file');
            }

            /**
             *  Uncompress Packages.xx if it is compressed (.gz or .xz)
             */
            if (preg_match('/.gz$/i', $packageIndicesName)) {
                try {
                    \Controllers\Common::gunzip($this->workingDir . '/' . $packageIndicesName);
                } catch (Exception $e) {
                    $this->logError($e, 'Error while uncompressing <code>' . $packageIndicesName . '</code>');
                }
            }
            if (preg_match('/.xz$/i', $packageIndicesName)) {
                try {
                    \Controllers\Common::xzUncompress($this->workingDir . '/' . $packageIndicesName);
                } catch (Exception $e) {
                    $this->logError($e, 'Error while uncompressing <code>' . $packageIndicesName . '</code>');
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
            $this->logError('No packages found in <code>Packages</code> indices file');
        }

        $this->logOK();
    }

    /**
     *  Parse Sources indices file to find .dsc/tar.gz/tar.xz sources packages location
     */
    private function parseSourcesIndiceFile()
    {
        /**
         *  Ignore this function if no 'src' arch has been specified
         */
        if (!in_array('src', $this->arch)) {
            return;
        }

        $this->logOutput(PHP_EOL . 'Retrieving sources packages list ... ');

        /**
         *  Process research for each Sources file
         */
        foreach ($this->sourcesIndicesLocation as $sourcesIndice) {
            $sourcesIndicesLocation = $sourcesIndice['location'];
            $sourcesIndexChecksum = $sourcesIndice['checksum'];
            $sourcesIndicesName = preg_split('#/#', $sourcesIndicesLocation);
            $sourcesIndicesName = end($sourcesIndicesName);

            /**
             *  Download Sources file using its location
             */
            if (!$this->download($this->url . '/dists/' . $this->dist . '/' . $sourcesIndicesLocation, $this->workingDir . '/' . $sourcesIndicesName)) {
                $this->logError('Error while downloading <code>' . $sourcesIndicesName . '</code> indices file: <code>' . $this->url . '/dists/' . $this->dist . '/' . $sourcesIndicesLocation . '</code>', 'Could not download <code>Sources</code> indices file');
            }

            /**
             *  Then check that the Sources.xx file's checksum matches the one that what specified in Release file
             */
            if (hash_file('sha256', $this->workingDir . '/' . $sourcesIndicesName) !== $sourcesIndexChecksum) {
                $this->logError('<code>' . $sourcesIndicesName . '</code> indices file\'s SHA256 checksum does not match the SHA256 checksum specified in the <code>Release</code> file ' . $packageIndicesChecksum, 'Could not verify <code>Packages</code> indices file');
            }

            /**
             *  Uncompress Sources.xx if it is compressed (.gz or .xz)
             */
            if (preg_match('/.gz$/i', $sourcesIndicesName)) {
                try {
                    \Controllers\Common::gunzip($this->workingDir . '/' . $sourcesIndicesName);
                } catch (Exception $e) {
                    $this->logError($e, 'Error while uncompressing <code>' . $sourcesIndicesName . '</code>');
                }
            }
            if (preg_match('/.xz$/i', $sourcesIndicesName)) {
                try {
                    \Controllers\Common::xzUncompress($this->workingDir . '/' . $sourcesIndicesName);
                } catch (Exception $e) {
                    $this->logError($e, 'Error while uncompressing <code>' . $sourcesIndicesName . '</code>');
                }
            }

            /**
             *  Get all .dsc/tar.gz/tar.xz sources packages location from the Sources file
             */
            $linecount = 0;
            $directory = '';
            $packageLocation = '';
            $packageMd5 = '';
            $handle = fopen($this->workingDir . '/Sources', 'r');

            /**
             *  Read all lines from Sources file
             */
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    /**
                     *  Get .dsc/tar.gz/tar.xz package directory location
                     */
                    if (preg_match('/^Directory:\s+(.*)/im', $line)) {
                        $directory = trim(str_replace('Directory: ', '', $line));
                    }

                    /**
                     *  Get .dsc/tar.gz/tar.xz location
                     */
                    if (preg_match('/^Files:$/im', $line)) {
                        /**
                         *  If line starts with 'Files:' then get the next XX lines that contain the packages md5sum and name
                         *  Start from current $linecount to get the next lines
                         */
                        $spl = new \SplFileObject($this->workingDir . '/Sources');

                        for ($i = 1; $i < 999; $i++) {
                            $spl->seek($linecount + $i);
                            $packageLine = $spl->current();

                            /**
                             *  If the current line does not start with an empty space and a md5sum, then it is the end of the packages list
                             */
                            if (!preg_match('/^ [a-f0-9]{32}/', $packageLine)) {
                                break 1;
                            }

                            /**
                             *  Explode the line to separate md5sum and package location
                             */
                            $packageLine = explode(' ', $packageLine);

                            /**
                             *  If the first part of the line is not empty and is a md5sum, then it is the md5sum of the package
                             */
                            if (!empty($packageLine[1]) and \Controllers\Common::isMd5($packageLine[1])) {
                                $packageMd5 = trim($packageLine[1]);
                            }

                            /**
                             *  If the third part of the line is not empty, then it is the package location
                             */
                            if (!empty($packageLine[3]) and preg_match('/^.*\.(asc|bz2|dsc|gz|xz)$/i', $packageLine[3])) {
                                $packageLocation = trim($packageLine[3]);
                            }

                            /**
                             *  Add founded packages location and md5sum to a global 'packages' array
                             */
                            if (!empty($packageLocation) and !empty($packageMd5)) {
                                $packages[] = array('location' => $packageLocation, 'md5sum' => $packageMd5);
                            }
                        }

                        unset($spl, $packageLocation, $packageMd5);
                    }

                    /**
                     *  If directory and packages have been parsed, had them to the global sources packages list array
                     */
                    if (!empty($directory) and !empty($packages)) {
                        foreach ($packages as $package) {
                            $this->sourcesPackagesLocation[] = array('location' => $directory . '/' . $package['location'], 'md5sum' => $package['md5sum']);
                        }

                        /**
                         *  Then reset $directory and $packages variables to be ready for the next directory
                         */
                        unset($directory, $packages);
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
            $this->logError('No packages found in <code>Sources</code> indices file');
        }

        $this->logOK();
    }

    /**
     *  Check Release file GPG signature
     */
    private function checkReleaseGPGSignature()
    {
        /**
         *  List of possible Release files to check
         */
        $releaseFiles = array(
            array(
                'name' => 'InRelease',
                'signature' => ''
            ),

            array(
                'name' => 'Release',
                'signature' => 'Release.gpg'
            )
        );

        /**
         *  If signature check is disabled, then just set a valid Release file
         */
        if ($this->checkSignature == 'false') {
            foreach ($releaseFiles as $releaseFile) {
                if (!file_exists($this->workingDir . '/' . $releaseFile['name'])) {
                    continue;
                }

                $this->validReleaseFile = $releaseFile['name'];
            }

            /**
             *  If no valid Release file has been found, throw an error
             */
            if (empty($this->validReleaseFile)) {
                $this->logError('No valid <code>InRelease</code> or <code>Release</code> file found. Please ensure that the remote repository is correctly built.', 'Release file check fail');
            }

            return;
        }

        /**
         *  If signature check is enabled, then look for a valid Release file
         */
        foreach ($releaseFiles as $releaseFile) {
            if (!file_exists($this->workingDir . '/' . $releaseFile['name'])) {
                continue;
            }

            $this->logOutput(PHP_EOL . 'Checking <code>' . $releaseFile['name'] . '</code> GPG signature ... ');

            /**
             *  Check that GPG signature is valid (signed with a known key)
             */
            try {
                if (!empty($releaseFile['signature'])) {
                    $this->checkGPGSignature($this->workingDir . '/' . $releaseFile['signature'], $this->workingDir . '/' . $releaseFile['name']);
                } else {
                    $this->checkGPGSignature($this->workingDir . '/' . $releaseFile['name']);
                }

                /**
                 *  If file's signature is valid, then set file as the valid Release file and quit the loop
                 */
                $this->validReleaseFile = $releaseFile['name'];

                $this->logOK();

                break;
            } catch (Exception $e) {
                if (DEB_INVALID_SIGNATURE == 'error') {
                    $this->logError($e->getMessage(), 'GPG signature check failed');
                }

                if (DEB_INVALID_SIGNATURE == 'ignore') {
                    $this->logWarning($e->getMessage());
                    continue;
                }
            }
        }

        /**
         *  If no valid Release file has been found, throw an error
         */
        if (empty($this->validReleaseFile)) {
            $this->logError(PHP_EOL . 'No <code>InRelease</code> or <code>Release</code> file found with a valid GPG signature. Please check that you have imported the GPG key used to sign the repository.', 'GPG signature check failed');
        }
    }

    /**
     *  Check GPG signature of specified file
     */
    private function checkGPGSignature(string $signedFile, string $clearFile = null)
    {
        /**
         *  Check that signature file exists
         */
        if (!file_exists($signedFile)) {
            throw new Exception('No ' . end(explode('/', $signedFile)) . ' signed file found. Are you sure that the remote repository is signed?');
        }

        /**
         *  If a clear file has been specified, check that it exists
         */
        if (!empty($clearFile) and !file_exists($clearFile)) {
            throw new Exception('No ' . end(explode('/', $clearFile)) . ' clear file found. Are you sure that the remote repository is signed?');
        }

        /**
         *  If a clear file exists (e.g. Release) then specify it as second argument, as suggested by gpgv:
         *    Please remember that the signature file (.sig or .asc)
         *    should be the first file given on the command line.
         *  e.g. gpgv --homedir /var/lib/repomanager/.gnupg/ Release.gpg Release
         */
        if (!empty($clearFile)) {
            $myprocess = new \Controllers\Process('/usr/bin/gpgv --homedir ' . GPGHOME . ' ' . $signedFile . ' ' . $clearFile);
        } else {
            $myprocess = new \Controllers\Process('/usr/bin/gpgv --homedir ' . GPGHOME . ' ' . $signedFile);
        }

        $myprocess->execute();
        $output = $myprocess->getOutput();
        $myprocess->close();

        /**
         *  If 'Can't check signature: No public key' is found in the output, then the GPG key is not imported
         */
        if (preg_match("/Can't check signature: No public key/", $output)) {
            throw new Exception('No GPG key could verify the signature of <code>' . end(explode('/', $signedFile)) . '</code> file. Please check that you have imported the GPG key used to sign the repository.');
        }

        /**
         *  If 'BAD signature from' is found in the output, then the signature is invalid / broken
         */
        if (preg_match("/BAD signature from/", $output)) {
            throw new Exception('Invalid signature of <code>' . end(explode('/', $signedFile)) . '</code> file: ' . PHP_EOL . '<pre class="codeblock">' . $output . '</pre>');
        }

        /**
         *  Else if the exit code is not 0, then print the error message
         */
        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Invalid signature or no GPG key could verify the signature of <code>' . end(explode('/', $signedFile)) . '</code> file: ' . PHP_EOL . '<pre class="codeblock">' . $output . '</pre>');
        }
    }

    /**
     *  Download deb packages
     */
    private function downloadDebPackages($url)
    {
        /**
         *  Define package relative dir
         */
        $relativeDir = 'pool/' . $this->section;

        /**
         *  Target directory in which packages will be downloaded
         */
        $absoluteDir = $this->workingDir . '/' . $relativeDir;

        /**
         *  Create directory in which packages will be downloaded
         */
        if (!is_dir($absoluteDir)) {
            if (!mkdir($absoluteDir, 0770, true)) {
                $this->logError('Cannot create directory: <code>' . $absoluteDir . '</code>', 'Error while creating target directory');
            }
        }

        /**
         *  Print URL from which packages are downloaded
         */
        $this->logOutput(PHP_EOL . 'Downloading packages from <span class="copy"><code>' . $url . '</code></span>:' .PHP_EOL);

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
                $this->logError('Repo storage has reached 2GB (minimum) of free space left. Task automatically stopped.', 'Low disk space');
            }

            $debPackageLocation = $debPackage['location'];
            $debPackageChecksum = $debPackage['checksum'];
            $debPackageName = preg_split('#/#', $debPackageLocation);
            $debPackageName = end($debPackageName);
            $packageCounter++;

            /**
             *  Output package to download to log file
             */
            $this->logOutput('<span class="opacity-80-cst">(' . $packageCounter . '/' . $totalPackages . ')  ➙ ' . $debPackageLocation . ' ... </span>');

            /**
             *  Check that package naming respects the Debian package naming convention
             *  It must ends with _<arch>.deb, if not then rename it
             *  e.g. elasticsearch deb repository has a package named 'filebeat-8.0.0-amd64.deb'. In this case arch is incorrect and should use underscore instead of dash.
             */

            /**
             *  First check if package has arch in its name, else ignore it
             */
            if (!preg_match('/(amd64|arm64|armel|armhf|i386|mips|mips64el|mipsel|ppc64el|s390x|all).deb$/', $debPackageName)) {
                $this->logOutput('package does not have arch in its name (ignoring)' . PHP_EOL);
            }

            /**
             *  Rename package if arch is not correctly specified in its name
             */
            $debPackageName = preg_replace('/-amd64.deb$/', '_amd64.deb', $debPackageName);
            $debPackageName = preg_replace('/-arm64.deb$/', '_arm64.deb', $debPackageName);
            $debPackageName = preg_replace('/-armel.deb$/', '_armel.deb', $debPackageName);
            $debPackageName = preg_replace('/-armhf.deb$/', '_armhf.deb', $debPackageName);
            $debPackageName = preg_replace('/-i386.deb$/', '_i386.deb', $debPackageName);
            $debPackageName = preg_replace('/-mips.deb$/', '_mips.deb', $debPackageName);
            $debPackageName = preg_replace('/-mips64el.deb$/', '_mips64el.deb', $debPackageName);
            $debPackageName = preg_replace('/-mipsel.deb$/', '_mipsel.deb', $debPackageName);
            $debPackageName = preg_replace('/-ppc64el.deb$/', '_ppc64el.deb', $debPackageName);
            $debPackageName = preg_replace('/-s390x.deb$/', '_s390x.deb', $debPackageName);
            $debPackageName = preg_replace('/-all.deb$/', '_all.deb', $debPackageName);

            /**
             *  Check if file does not already exists before downloading it (e.g. copied from a previously snapshot)
             */
            if (file_exists($absoluteDir . '/' . $debPackageName)) {
                $this->logOutput('already exists (ignoring)' . PHP_EOL);
                continue;
            }

            /**
             *  Check if package already exists in the previous snapshot
             *  If so, just create a hard link to the package
             */
            if (isset($this->previousSnapshotDirPath)) {
                if (file_exists($this->previousSnapshotDirPath . '/' . $relativeDir . '/' . $debPackageName)) {
                    $this->logOK('(linked to previous snapshot)');

                    /**
                     *  Create hard link to the package
                     */
                    if (!link($this->previousSnapshotDirPath . '/' . $relativeDir . '/' . $debPackageName, $absoluteDir . '/' . $debPackageName)) {
                        $this->logError('Cannot create hard link to package: ' . $this->previousSnapshotDirPath . '/' . $relativeDir . '/' . $debPackageName, 'Error while creating hard link');
                    }

                    continue;
                }
            }

            /**
             *  Download
             */
            if (!$this->download($url . '/' . $debPackageLocation, $absoluteDir . '/' . $debPackageName, 3)) {
                $this->logError('error', 'Error while retrieving packages');
            }

            /**
             *  Check that downloaded deb package's sha256 matches the sha256 specified by the Packages file
             */
            if (hash_file('sha256', $absoluteDir . '/' . $debPackageName) != $debPackageChecksum) {
                $this->logError('checksum of the downloaded package does not match the checksum indicated by the source repository metadata (tested sha256)', 'Error while retrieving packages');
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
     */
    private function downloadDebSourcesPackages($url)
    {
        /**
         *  Ignore this function if no 'src' arch has been specified
         */
        if (!in_array('src', $this->arch)) {
            return;
        }

        /**
         *  Target directory in which packages will be downloaded
         */
        $absoluteDir = $this->workingDir . '/pool/' . $this->section;

        /**
         *  Create directory in which packages will be downloaded
         */
        if (!is_dir($absoluteDir)) {
            if (!mkdir($absoluteDir, 0770, true)) {
                $this->logError('Cannot create directory: <code>' . $absoluteDir . '</code>', 'Error while creating target directory');
            }
        }

        /**
         *  Print URL from which sources packages are downloaded
         */
        $this->logOutput(PHP_EOL . 'Downloading sources packages from <span class="copy"><code>' . $url . '</code></span>:' . PHP_EOL);

        /**
         *  Count total packages to print progression during syncing
         */
        $totalPackages = count($this->sourcesPackagesLocation);
        $packageCounter = 0;

        /**
         *  Download each source package and check its md5
         */
        foreach ($this->sourcesPackagesLocation as $sourcePackage) {
            /**
             *  Before downloading each package, check if there is enough disk space left (2GB minimum)
             */
            if (disk_free_space(REPOS_DIR) < 2000000000) {
                $this->logError('Repo storage has reached 2GB (minimum) of free space left. Task automatically stopped.', 'Low disk space');
            }

            $sourcePackageLocation = $sourcePackage['location'];
            $sourcePackageMd5 = $sourcePackage['md5sum'];
            $sourcePackageName = preg_split('#/#', $sourcePackageLocation);
            $sourcePackageName = end($sourcePackageName);
            $packageCounter++;

            /**
             *  Output source package to download to log file
             */
            $this->logOutput('<span class="opacity-80-cst">(' . $packageCounter . '/' . $totalPackages . ')  ➙ ' . $sourcePackageLocation . ' ... </span>');

            /**
             *  Check if file does not already exists before downloading it (e.g. copied from a previously snapshot)
             */
            if (file_exists($absoluteDir . '/' . $sourcePackageName)) {
                $this->logOutput('already exists (ignoring)' . PHP_EOL);
                continue;
            }

            /**
             *  Download
             */
            if (!$this->download($url . '/' . $sourcePackageLocation, $absoluteDir . '/' . $sourcePackageName)) {
                $this->logError('error', 'Error while retrieving sources packages');
            }

            /**
             *  Check that downloaded source package's md5 matches the md5sum specified by the Sources indices file
             */
            if (md5_file($absoluteDir . '/' . $sourcePackageName) != $sourcePackageMd5) {
                $this->logError('md5 of the file does not match ' . $sourcePackageMd5, 'Error while retrieving sources packages');
            }

            /**
             *  Print OK if source package has been downloaded and verified successfully
             */
            $this->logOK();
        }

        unset($this->sourcesPackagesLocation, $totalPackages, $packageCounter);
    }

    /**
     *  Download translation packages
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
        $this->downloadReleaseFile();

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
