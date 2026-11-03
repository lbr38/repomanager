<?php

namespace Controllers\Download;

use Controllers\Utils\Convert;
use Exception;

/**
 *  Download files in parallel, with retries, size and checksum verification and task log output
 *  Files are written to <target>.part and only renamed to their final name once verified,
 *  so that an existing file is always complete
 */
class Parallel
{
    // Number of simultaneous downloads
    private const PARALLEL_DOWNLOADS = 6;

    // Number of retries for failed downloads
    private const RETRIES = 3;

    // Minimum number of seconds between each progress output
    private const PROGRESS_INTERVAL = 2;

    private $taskLogSubStepController;
    private string|null $sslCertificate = null;
    private string|null $sslPrivateKey = null;
    private string|null $sslCaCertificate = null;
    private string|null $basicAuthUsername = null;
    private string|null $basicAuthPassword = null;
    private int $downloaded = 0;
    private int $downloadedBytes = 0;
    private int $processed = 0;
    private int $substepCounter = 0;
    private array $downloading = [];
    private float $lastProgress = 0;
    private bool $progressDisplayed = false;

    public function __construct(int $taskId)
    {
        $this->taskLogSubStepController = new \Controllers\Task\Log\SubStep($taskId);
    }

    /**
     *  Set SSL authentication material (paths to the certificate, private key and CA certificate files)
     */
    public function setSslAuthentication(string|null $certificate, string|null $privateKey, string|null $caCertificate): void
    {
        $this->sslCertificate = $certificate;
        $this->sslPrivateKey = $privateKey;
        $this->sslCaCertificate = $caCertificate;
    }

    /**
     *  Set basic authentication credentials
     */
    public function setBasicAuthentication(string|null $username, string|null $password): void
    {
        $this->basicAuthUsername = $username;
        $this->basicAuthPassword = $password;
    }

    /**
     *  Return the number of files downloaded by the latest download() call
     */
    public function getDownloadedCount(): int
    {
        return $this->downloaded;
    }

    /**
     *  Return the size (in bytes) of the files downloaded by the latest download() call
     */
    public function getDownloadedBytes(): int
    {
        return $this->downloadedBytes;
    }

    /**
     *  Download files in parallel, verify them and move them to their final path
     *  Each file is an array: ['url' => URL, 'target' => absolute path, 'size' => int|null, 'hash' => string|null, 'algo' => string|null]
     *
     *  $optional: a missing file (404) is not an error, it is just not retrieved
     *  $substepTitle: display each download as a task sub-step with this title (e.g. 'DOWNLOADING PACKAGE'),
     *  otherwise the progress is displayed in the current sub-step
     *  $onDownloaded: called with each downloaded file entry (with its sub-step identifier in 'substep', if any), e.g. to run additional checks
     *
     *  Return the keys of the optional files that were not found (404)
     */
    public function download(array $files, bool $optional = false, string|null $substepTitle = null, callable|null $onDownloaded = null): array
    {
        $missing = [];
        $pending = $files;
        $total = count($files);
        // Position of each file in the list, displayed in the sub-steps titles
        $positions = array_flip(array_keys($files));

        $this->downloaded = 0;
        $this->downloadedBytes = 0;
        $this->processed = 0;
        $this->downloading = [];
        $this->lastProgress = microtime(true);

        for ($round = 0; $round <= self::RETRIES and !empty($pending); $round++) {
            if ($round > 0 and $substepTitle === null) {
                $this->taskLogSubStepController->output('Retrying ' . count($pending) . ' failed download(s) (' . $round . '/' . self::RETRIES . ')', 'warning');
            }

            $failed = [];
            $active = [];
            $keys = array_keys($pending);
            $index = 0;
            $multiHandle = curl_multi_init();

            try {
                while ($index < count($keys) or !empty($active)) {
                    // Fill the download slots
                    while (count($active) < self::PARALLEL_DOWNLOADS and $index < count($keys)) {
                        $key = $keys[$index++];
                        $file = $pending[$key];

                        $this->createParentDir($file['target']);

                        $fp = fopen($file['target'] . '.part', 'w');

                        if ($fp === false) {
                            throw new Exception('Could not open ' . $file['target'] . '.part for writing');
                        }

                        // Sub-steps run in parallel, each one is closed with its identifier
                        if ($substepTitle !== null) {
                            $file['substep'] = 'download-' . ++$this->substepCounter;
                            $this->taskLogSubStepController->new($file['substep'], $substepTitle . ' (' . ($positions[$key] + 1) . '/' . $total . ')', htmlspecialchars($file['url']), false);
                        }

                        $handle = $this->curlInit($file['url'], $fp);
                        curl_multi_add_handle($multiHandle, $handle);
                        $active[spl_object_id($handle)] = ['handle' => $handle, 'fp' => $fp, 'key' => $key, 'file' => $file];
                        $this->downloading[spl_object_id($handle)] = $file['target'];
                    }

                    do {
                        $status = curl_multi_exec($multiHandle, $running);
                    } while ($status == CURLM_CALL_MULTI_PERFORM);

                    if ($status != CURLM_OK) {
                        throw new Exception('Download error: ' . curl_multi_strerror($status));
                    }

                    if (curl_multi_select($multiHandle, 1.0) == -1) {
                        usleep(100000);
                    }

                    // Refresh progress even if no download has finished (large files)
                    if ($substepTitle === null) {
                        $this->printProgress($total);
                    }

                    // Process finished downloads
                    while ($info = curl_multi_info_read($multiHandle)) {
                        $id = spl_object_id($info['handle']);
                        $key = $active[$id]['key'];
                        $file = $active[$id]['file'];
                        $substep = $file['substep'] ?? null;
                        $httpCode = curl_getinfo($info['handle'], CURLINFO_RESPONSE_CODE);

                        fclose($active[$id]['fp']);
                        curl_multi_remove_handle($multiHandle, $info['handle']);
                        curl_close($info['handle']);
                        unset($active[$id], $this->downloading[$id]);

                        if ($info['result'] == CURLE_OK and self::verify($file['target'] . '.part', $file['size'], $file['hash'], $file['algo'])) {
                            if (!rename($file['target'] . '.part', $file['target'])) {
                                throw new Exception('Could not rename ' . $file['target'] . '.part');
                            }

                            $this->downloaded++;
                            $this->downloadedBytes += filesize($file['target']);
                            $this->processed++;

                            if ($onDownloaded !== null) {
                                $onDownloaded($file);
                            }

                            if ($substep !== null) {
                                $this->taskLogSubStepController->completed('', $substep);
                            }
                            continue;
                        }

                        unlink($file['target'] . '.part');

                        if ($optional and $httpCode == 404) {
                            $missing[] = $key;
                            $this->processed++;

                            if ($substep !== null) {
                                $this->taskLogSubStepController->completed('Not found on the source repository (ignoring)', $substep);
                            }
                            continue;
                        }

                        if ($info['result'] != CURLE_OK) {
                            $file['error'] = curl_strerror($info['result']) . ($httpCode > 0 ? ' (HTTP ' . $httpCode . ')' : '');
                        } else {
                            $file['error'] = 'size or checksum mismatch';
                        }

                        if ($substep !== null) {
                            $this->taskLogSubStepController->warning('Download failed: ' . htmlspecialchars($file['error']) . ($round < self::RETRIES ? ', it will be retried' : ''), $substep);
                        }

                        $failed[$key] = $file;
                    }
                }
            } finally {
                // Release the downloads still running if an error occurred
                foreach ($active as $download) {
                    curl_multi_remove_handle($multiHandle, $download['handle']);
                    curl_close($download['handle']);
                    fclose($download['fp']);
                }

                curl_multi_close($multiHandle);
            }

            $pending = $failed;
        }

        // Remove the progress message, the caller outputs the final summary
        if ($this->progressDisplayed) {
            $this->taskLogSubStepController->progress(null);
            $this->progressDisplayed = false;
        }

        if (!empty($pending)) {
            $message = count($pending) . ' file(s) could not be retrieved:';

            foreach (array_slice($pending, 0, 10) as $file) {
                // URLs contain file names coming from upstream metadata
                $message .= '<br> 🠶 <span class="copy wordbreakall">' . htmlspecialchars($file['url']) . '</span>: ' . htmlspecialchars($file['error']);
            }

            throw new Exception($message);
        }

        return $missing;
    }

    /**
     *  Download a single file without verification, return false if it could not be downloaded
     */
    public function downloadFile(string $url, string $savePath): bool
    {
        $this->createParentDir($savePath);

        $fp = fopen($savePath, 'w');

        if ($fp === false) {
            throw new Exception('Could not open ' . $savePath . ' for writing');
        }

        $handle = $this->curlInit($url, $fp);
        $result = curl_exec($handle);
        curl_close($handle);
        fclose($fp);

        if ($result === false) {
            unlink($savePath);
            return false;
        }

        return true;
    }

    /**
     *  Verify file size and checksum, if known
     */
    public static function verify(string $path, int|null $size, string|null $hash, string|null $algo): bool
    {
        if (!is_null($size) and filesize($path) != $size) {
            return false;
        }

        if (!empty($hash) and !empty($algo)) {
            return hash_equals(strtolower($hash), hash_file($algo, $path));
        }

        return true;
    }

    /**
     *  Output progress periodically in the current sub-step: number of processed files and files being downloaded
     *  It replaces the previous progress message
     */
    private function printProgress(int $total): void
    {
        if (microtime(true) - $this->lastProgress < self::PROGRESS_INTERVAL) {
            return;
        }

        $message = $this->processed . ' / ' . $total . ' file(s) downloaded';

        if ($this->downloadedBytes > 0) {
            $message .= ' (' . Convert::sizeToHuman($this->downloadedBytes) . ')';
        }

        // File names come from upstream metadata
        foreach ($this->downloading as $path) {
            $message .= '<br> 🠶 Downloading <span class="wordbreakall">' . htmlspecialchars(basename($path)) . '</span>';
        }

        $this->taskLogSubStepController->progress($message);
        $this->progressDisplayed = true;
        $this->lastProgress = microtime(true);
    }

    /**
     *  Initialize a curl handle writing to the specified file pointer
     */
    private function curlInit(string $url, $fp)
    {
        $handle = curl_init();

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_FILE, $fp);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($handle, CURLOPT_TIMEOUT, MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT);
        curl_setopt($handle, CURLOPT_FAILONERROR, true);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        // Use http version to 1.1 to avoid curl error 16 (fix https://github.com/lbr38/repomanager/issues/127)
        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handle, CURLOPT_USERAGENT, 'Repomanager (+https://github.com/lbr38/repomanager)');

        if (!empty(PROXY)) {
            curl_setopt($handle, CURLOPT_PROXY, PROXY);
        }
        if (!empty($this->sslCertificate)) {
            curl_setopt($handle, CURLOPT_SSLCERT, $this->sslCertificate);
        }
        if (!empty($this->sslPrivateKey)) {
            curl_setopt($handle, CURLOPT_SSLKEY, $this->sslPrivateKey);
        }
        if (!empty($this->sslCaCertificate)) {
            curl_setopt($handle, CURLOPT_CAINFO, $this->sslCaCertificate);
        }
        // Basic authentication credentials are not sent to other hosts on redirect
        if (!empty($this->basicAuthUsername)) {
            curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($handle, CURLOPT_USERNAME, $this->basicAuthUsername);
            curl_setopt($handle, CURLOPT_PASSWORD, $this->basicAuthPassword ?? '');
        }

        return $handle;
    }

    /**
     *  Create the parent directory of a file if it does not exist
     */
    private function createParentDir(string $path): void
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0770, true) and !is_dir($dir)) {
                throw new Exception('Could not create directory ' . $dir);
            }
        }
    }
}
