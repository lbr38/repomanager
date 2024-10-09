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
    protected $outputFile;
    protected $sslCustomCertificate;
    protected $sslCustomPrivateKey;
    protected $sslCustomCaCertificate;
    protected $curlHandle;
    protected $previousSnapshotDirPath;
    protected $packagesToInclude = [];
    protected $packagesToExclude = [];

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

    public function setPackagesToInclude(array $packages)
    {
        $this->packagesToInclude = $packages;
    }

    public function setPackagesToExclude(array $packages)
    {
        $this->packagesToExclude = $packages;
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
     *  Write specified message to log file
     */
    public function logOutput(string $message)
    {
        file_put_contents($this->outputFile, '<p>' . $message . '</p>', FILE_APPEND);
    }

    /**
     *  Write a title to log file
     */
    public function logTitle(string $message, string $endMessage = null)
    {
        $this->logOutput('<div class="flex justify-space-between align-flex-end"><h6>' . $message . '</h6><p title="Running time" class="lowopacity-cst">' . date('H:i:s') . '</p></div>');
    }

    /**
     *  Write a note to log file
     */
    public function logNote(string $message)
    {
        $this->logOutput('<span class="note">' . $message . '</span>');
    }

    /**
     *  Write a green 'OK' to log file
     */
    public function logOK(string $message = null)
    {
        if (!empty($message)) {
            $this->logOutput('<img src="/assets/icons/check.svg" class="icon margin-right-5 vertical-align-text-top" />' . $message . '<br>');
        } else {
            $this->logOutput('<img src="/assets/icons/check.svg" class="icon vertical-align-text-top" /><br>');
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

        $this->logOutput('<img src="/assets/icons/error.svg" class="icon margin-right-5 vertical-align-text-top" /><span class="redtext">' . $errorMessage . '</span><br>');

        throw new Exception($exceptionMessage);
    }

    /**
     *  Write a yellow warning message to log file but do not throw an Exception
     */
    public function logWarning(string $message)
    {
        $this->logOutput('<img src="/assets/icons/warning.svg" class="icon margin-right-5 vertical-align-text-top" /><span class="yellowtext">' . $message . '</span><br>');
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
