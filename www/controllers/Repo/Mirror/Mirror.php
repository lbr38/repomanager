<?php

namespace Controllers\Repo\Mirror;

use Exception;

class Mirror
{
    protected $taskId;
    protected $url;
    protected $nonCompliantSource = 'false';
    protected $dist;
    protected $section;
    protected $releasever;
    protected $arch;
    protected $translation;
    protected $checkSignature = 'true';
    protected $gpgKeyUrl;
    protected $primaryLocation;
    protected $primaryChecksum;
    protected $packagesIndicesLocation = [];
    protected $sourcesIndicesLocation = [];
    protected $translationsLocation = [];
    protected $debPackagesLocation = [];
    protected $sourcesPackagesLocation = [];
    protected $rpmPackagesLocation = [];
    protected $packagesToSign = [];
    protected $workingDir;
    protected $outputFile;
    protected $sslCustomCertificate;
    protected $sslCustomPrivateKey;
    protected $sslCustomCaCertificate;
    protected $curlHandle;
    protected $previousSnapshotDirPath;
    protected $packagesToInclude = [];
    protected $packagesToExclude = [];

    protected $taskLogStepController;
    protected $taskLogSubStepController;
    protected $httpRequestController;

    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
        $this->taskLogStepController = new \Controllers\Task\Log\Step($taskId);
        $this->taskLogSubStepController = new \Controllers\Task\Log\SubStep($taskId);
        $this->httpRequestController = new \Controllers\HttpRequest();

        // Set working dir
        $this->workingDir = REPOS_DIR . '/temporary-task-' . $taskId;
    }

    public function setTaskId(string $taskId) : void
    {
        $this->taskId = $taskId;
    }

    public function setUrl(string $url) : void
    {
        $this->url = $url;
    }

    public function setNonCompliantSource(string $nonCompliantSource) : void
    {
        $this->nonCompliantSource = $nonCompliantSource;
    }

    public function setDist(string $dist) : void
    {
        $this->dist = $dist;
    }

    public function setSection(string $section) : void
    {
        $this->section = $section;
    }

    public function setReleasever(string $releasever) : void
    {
        $this->releasever = $releasever;
    }

    public function setArch(array $arch) : void
    {
        $this->arch = $arch;
    }

    public function setCheckSignature(string $checkSignature) : void
    {
        $this->checkSignature = $checkSignature;
    }

    public function setGpgKeyUrl(string $url) : void
    {
        $this->gpgKeyUrl = $url;
    }

    public function setTranslation(array $translation) : void
    {
        $this->translation = $translation;
    }

    public function setSslCustomCertificate(string $path) : void
    {
        $this->sslCustomCertificate = $path;
    }

    public function setSslCustomPrivateKey(string $path) : void
    {
        $this->sslCustomPrivateKey = $path;
    }

    public function setSslCustomCaCertificate(string $path) : void
    {
        $this->sslCustomCaCertificate = $path;
    }

    public function setPreviousSnapshotDirPath(string $path) : void
    {
        $this->previousSnapshotDirPath = $path;
    }

    public function setPackagesToInclude(array $packages) : void
    {
        $this->packagesToInclude = $packages;
    }

    public function setPackagesToExclude(array $packages) : void
    {
        $this->packagesToExclude = $packages;
    }

    public function getPackagesToSign() : array
    {
        return $this->packagesToSign;
    }

    /**
     *  Initialize mirroring task
     */
    public function initialize() : void
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
    public function download(string $url, string $savePath, int $retries = 0) : bool
    {
        $currentRetry = 0;

        while (true) {
            try {
                // Check if the URL is reachable
                try {
                    $this->httpRequestController->reachable([
                        'url'                  => $url,
                        'connectTimeout'       => 15,
                        'timeout'              => MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT,
                        'proxy'                => PROXY ?? null,
                        'sslCertificatePath'   => $this->sslCustomCertificate ?? null,
                        'sslPrivateKeyPath'    => $this->sslCustomPrivateKey ?? null,
                        'sslCaCertificatePath' => $this->sslCustomCaCertificate ?? null,
                        // Use http version to 1.1 to avoid curl error 16 (fix https://github.com/lbr38/repomanager/issues/127)
                        'http1.1'              => true
                    ]);
                } catch (Exception $e) {
                    throw new Exception('URL ' . $url . ' is not reachable: ' . $e->getMessage());
                }

                // Download
                try {
                    $this->httpRequestController->get([
                        'url'                  => $url,
                        'save'                 => $savePath,
                        'connectTimeout'       => 15,
                        'timeout'              => MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT,
                        'proxy'                => PROXY ?? null,
                        'sslCertificatePath'   => $this->sslCustomCertificate ?? null,
                        'sslPrivateKeyPath'    => $this->sslCustomPrivateKey ?? null,
                        'sslCaCertificatePath' => $this->sslCustomCaCertificate ?? null,
                        // Use http version to 1.1 to avoid curl error 16 (fix https://github.com/lbr38/repomanager/issues/127)
                        'http1.1'              => true
                    ]);
                } catch (Exception $e) {
                    throw new Exception('error while downloading file: ' . $e->getMessage());
                }
            } catch (Exception $e) {
                // If download has failed, print a warning and retry if current retry is less than the maximum number of retries
                if ($currentRetry != $retries) {
                    $currentRetry++;
                    $this->taskLogSubStepController->output('Download failed: ' . $e->getMessage(), 'warning');
                    $this->taskLogSubStepController->output('Retrying (' . $currentRetry . '/' . $retries . ') ...', 'note');
                    continue;
                }

                return false;
            }

            // If download was successful, break the loop
            break;
        }

        return true;
    }

    /**
     *  Check if a file has the expected checksum
     */
    protected function checksum(string $file, string $expectedChecksum) : bool
    {
        /**
         *  Check if expected checksum matches the file checksum
         *  Try with sha512, sha256, sha1, then md5
         */
        if (hash_file('sha512', $file) != $expectedChecksum) {
            if (hash_file('sha256', $file) != $expectedChecksum) {
                if (hash_file('sha1', $file) != $expectedChecksum) {
                    if (hash_file('md5', $file) != $expectedChecksum) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     *  Clean remaining files in working directory
     */
    public function clean() : void
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
