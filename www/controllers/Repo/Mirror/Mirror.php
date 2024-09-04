<?php

namespace Controllers\Repo\Mirror;

use Exception;

class Mirror
{
    protected $url;
    protected $dist;
    protected $section;
    protected $releasever;
    protected $arch;
    protected $translation;
    protected $checkSignature = 'true';
    protected $gpgKeyUrl;
    protected $primaryLocation;
    protected $primaryChecksum;
    protected $packagesIndicesLocation = array();
    protected $sourcesIndicesLocation = array();
    protected $translationsLocation = array();
    protected $debPackagesLocation = array();
    protected $sourcesPackagesLocation = array();
    protected $rpmPackagesLocation = array();
    protected $packagesToSign = array();
    protected $workingDir;
    protected $outputToFile = false;
    protected $outputFile;
    protected $sslCustomCertificate;
    protected $sslCustomPrivateKey;
    protected $sslCustomCaCertificate;
    protected $curlHandle;
    protected $previousSnapshotDirPath;

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

    public function setReleasever(string $releasever)
    {
        $this->releasever = $releasever;
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

    public function setSslCustomCertificate(string $path)
    {
        $this->sslCustomCertificate = $path;
    }

    public function setSslCustomPrivateKey(string $path)
    {
        $this->sslCustomPrivateKey = $path;
    }

    public function setSslCustomCaCertificate(string $path)
    {
        $this->sslCustomCaCertificate = $path;
    }

    public function setPreviousSnapshotDirPath(string $path)
    {
        $this->previousSnapshotDirPath = $path;
    }

    public function getPackagesToSign()
    {
        return $this->packagesToSign;
    }

    /**
     *  Initialize mirroring task
     */
    public function initialize()
    {
        /**
         *  Create working dir if not exist
         */
        if (!is_dir($this->workingDir)) {
            if (!mkdir($this->workingDir, 0770, true)) {
                throw new Exception('Cannot create temporary working directory');
            }
        }

        /**
         *  Initialize shared curl handle
         */
        $this->curlHandle = curl_init();
    }

    /**
     *  Download specified distant file
     */
    public function download(string $url, string $savePath, int $retries = 0)
    {
        $currentRetry = 0;
        $localFile = fopen($savePath, "w");

        /**
         *  Use a shared curl handle '$this->curlHandle' and do not reinitialize it every time to speed up downloads
         */
        curl_setopt($this->curlHandle, CURLOPT_URL, $url);                                   // set remote file url
        curl_setopt($this->curlHandle, CURLOPT_FILE, $localFile);                            // set output file
        curl_setopt($this->curlHandle, CURLOPT_TIMEOUT, MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT); // set timeout
        curl_setopt($this->curlHandle, CURLOPT_FOLLOWLOCATION, true);                        // follow redirect
        curl_setopt($this->curlHandle, CURLOPT_ENCODING, '');                                // use compression if any
        curl_setopt($this->curlHandle, CURLOPT_FAILONERROR, true);                           // fail if http return code is >= 400
        curl_setopt($this->curlHandle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);         // Hardcode http version to 1.1 to avoid curl error 16 (fix https://github.com/lbr38/repomanager/issues/127)
        // Enable verbose on stderr (debug only):
        // curl_setopt($this->curlHandle, CURLOPT_VERBOSE, true);

        /**
         *  If a proxy has been specified
         */
        if (!empty(PROXY)) {
            curl_setopt($this->curlHandle, CURLOPT_PROXY, PROXY);
        }

        /**
         *  If a custom ssl certificate / private key /ca certificate must be used
         */
        if (!empty($this->sslCustomCertificate)) {
            curl_setopt($this->curlHandle, CURLOPT_SSLCERT, $this->sslCustomCertificate);
        }
        if (!empty($this->sslCustomPrivateKey)) {
            curl_setopt($this->curlHandle, CURLOPT_SSLKEY, $this->sslCustomPrivateKey);
        }
        if (!empty($this->sslCustomCaCertificate)) {
            curl_setopt($this->curlHandle, CURLOPT_CAINFO, $this->sslCustomCaCertificate);
        }

        /**
         *  Execute curl request and retry if needed
         */
        while (curl_exec($this->curlHandle) === false) {
            /**
             *  If curl has failed, print a warning and retry if current retry is less than the maximum number of retries
             */
            if ($currentRetry != $retries) {
                $currentRetry++;
                $this->logWarning('Curl error (' . curl_errno($this->curlHandle) . '): ' . curl_error($this->curlHandle));
                $this->logOutput('<span class="opacity-80-cst">Retrying (' . $currentRetry . '/' . $retries . ') ... </span>');
                continue;
            }

            /**
             *  If curl has failed (meaning a curl param might be invalid or timeout has been reached)
             */
            $this->logError('Curl error (' . curl_errno($this->curlHandle) . '): ' . curl_error($this->curlHandle), 'Download error');

            curl_close($this->curlHandle);
            fclose($localFile);
        }

        /**
         *  Check that the http return code is 200 (the file has been downloaded)
         */
        $status = curl_getinfo($this->curlHandle);

        if ($status["http_code"] != 200) {
            /**
             *  If return code is 404
             */
            if ($status["http_code"] == '404') {
                $this->logOutput('File not found (404)' . PHP_EOL);
            } else {
                $this->logOutput('File could not be downloaded (http return code is: ' . $status["http_code"] . ')' . PHP_EOL);
            }

            curl_close($this->curlHandle);
            fclose($localFile);

            return false;
        }

        return true;
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
    public function logOutput(string $message)
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
    public function logOK(string $message = null)
    {
        if (!empty($message)) {
            $this->logOutput('<div class="inline-block"><div class="flex align-item-center column-gap-5"><span class="label-ok">OK</span><span class="opacity-80-cst">' . $message . '</span></div></div>' . PHP_EOL);
        } else {
            $this->logOutput('<div class="inline-block"><span class="label-ok">OK</span></div>' . PHP_EOL);
        }
    }

    /**
     *  Write a red error message to log file and throw an Exception
     */
    public function logError(string $errorMessage, string $exceptionMessage = null)
    {
        /**
         *  If no specific exception message has been specified, then it will be the same as the error message displayed
         */
        if (empty($exceptionMessage)) {
            $exceptionMessage = $errorMessage;
        }

        $this->logOutput('<div class="inline-block"><div class="flex align-item-center column-gap-5"><span class="label-ko">KO</span><span class="redtext">' . $errorMessage . '</span></div></div>' . PHP_EOL);

        throw new Exception($exceptionMessage);
    }

    /**
     *  Write a yellow warning message to log file but do not throw an Exception
     */
    public function logWarning(string $message)
    {
        $this->logOutput('<span class="yellowtext"><img src="/assets/icons/warning.png" class="icon" />' . $message . '</span>' . PHP_EOL);
    }

    /**
     *  Clean remaining files in working directory
     */
    public function clean()
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
}
