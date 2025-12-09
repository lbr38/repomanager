<?php

namespace Controllers;

use \Controllers\App\DebugMode;
use Exception;
use JsonException;


class HttpRequest
{
    private $ch;

    public function __construct()
    {
        // Initialize curl handle
        $this->ch = curl_init();
    }

    public function __destruct()
    {
        // Close curl handle
        if (is_resource($this->ch)) {
            curl_close($this->ch);
        }
    }

    /**
     *  Check if a URL is reachable
     */
    public function reachable(array $params) : string|array|null
    {
        return $this->get($params);
    }

    /**
     *  Perform a GET request
     *  Returns the result as a string or saves it to a file (and returns true)
     */
    public function get(array $params, bool $parseJson = false, string $jsonExtract = '') : string|array|null
    {
        try {
            // Set verbose output if debug mode is enabled
            if (DebugMode::enabled()) {
                curl_setopt($this->ch, CURLOPT_VERBOSE, true);
            }

            // Set URL
            curl_setopt($this->ch, CURLOPT_URL, $params['url']);

            // Set max connect timeout (default 10 seconds)
            curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, isset($params['connectTimeout']) ? $params['connectTimeout'] : 10);

            // Set max transfer timeout (default 30 seconds)
            curl_setopt($this->ch, CURLOPT_TIMEOUT, isset($params['timeout']) ? $params['timeout'] : 30);

            // Use compression if supported
            curl_setopt($this->ch, CURLOPT_ENCODING, '');

            // Fail if http return code is >= 400
            curl_setopt($this->ch, CURLOPT_FAILONERROR, true);

            // Follow redirects
            curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);

            // Return the result as a string instead of outputting it directly
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);

            // Set proxy if specified
            if (!empty($params['proxy'])) {
                curl_setopt($this->ch, CURLOPT_PROXY, $params['proxy']);
            }

            // Set headers if specified
            if (!empty($params['headers']) && is_array($params['headers'])) {
                curl_setopt($this->ch, CURLOPT_HTTPHEADER, $params['headers']);
            }

            // If a custom SSL certificate path is specified
            if (!empty($params['sslCertificatePath'])) {
                curl_setopt($this->ch, CURLOPT_SSLCERT, $params['sslCertificatePath']);
            }

            // If a custom SSL private key path is specified
            if (!empty($params['sslPrivateKeyPath'])) {
                curl_setopt($this->ch, CURLOPT_SSLKEY, $params['sslPrivateKeyPath']);
            }

            // If a custom CA certificate path is specified
            if (!empty($params['sslCaCertificatePath'])) {
                curl_setopt($this->ch, CURLOPT_CAINFO, $params['sslCaCertificatePath']);
            }

            // If HTTP version 1.1 is requested
            if (isset($params['http1.1']) && $params['http1.1'] === true) {
                curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            }

            // If the target must be saved as a file directly
            if (isset($params['save'])) {
                // Disable return transfer option in this case
                curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, false);

                // Open the file for writing
                $fp = fopen($params['save'], "w");

                if ($fp === false) {
                    throw new Exception('Unable to open file for writing: ' . $params['save']);
                }

                // Set curl to write output directly to the file
                curl_setopt($this->ch, CURLOPT_FILE, $fp);
            }

            /**
             *  Execute curl request
             */

            // If the output must be returned directly, parsed as JSON, or saved to a file, then capture the output
            if ((isset($params['returnOutput']) && $params['returnOutput'] === true) or $parseJson or !empty($params['outputToFile'])) {
                if (!$output = curl_exec($this->ch)) {
                    throw new Exception('curl failed: ' . curl_error($this->ch));
                }
            } else {
                if (!curl_exec($this->ch)) {
                    throw new Exception('curl failed: ' . curl_error($this->ch));
                }
            }

            // Check that the http return code is 200 (the file has been downloaded)
            $status = curl_getinfo($this->ch);

            // If return code is not 200
            if ($status['http_code'] != 200) {
                // If return code is 404
                if ($status['http_code'] == '404') {
                    throw new Exception('404 file not found');
                // If return code is 403
                } elseif ($status['http_code'] == '403') {
                    throw new Exception('403 access forbidden');
                } else {
                    throw new Exception('HTTP return code is ' . $status['http_code']);
                }
            }

            /**
             *  If the JSON must be parsed to extract specific values
             */
            if ($parseJson) {
                // Decode JSON
                try {
                    $output = json_decode($output, false, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    throw new Exception('JSON decode error: ' . $e->getMessage());
                }

                // If a specific JSON parsing path is specified, extract that part of the JSON
                if (!empty($jsonExtract)) {
                    // Extract the desired part of the JSON
                    $output = array_column($output, $jsonExtract);

                    // For debug purposes, list all extracted values
                    /*foreach ($output as $value) {
                        CliLog::log('Tag: ' . $value);
                    }*/
                }
            }

            // Case the output must be written to a file
            if (!empty($params['outputToFile'])) {
                // If the output is a string, write it directly
                if (is_string($output)) {
                    $write = file_put_contents($params['outputToFile'], trim($output));
                }

                // If the output is an array or object, encode it as JSON
                if (is_array($output) || is_object($output)) {
                    try {
                        $write = file_put_contents($params['outputToFile'], json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
                    } catch (JsonException $e) {
                        throw new Exception('JSON encode error: ' . $e->getMessage());
                    }
                }

                // If writing to the file failed
                if ($write === false) {
                    throw new Exception('unable to write to output file ' . $params['outputToFile']);
                }
            }

            // Case the target must be saved as a file directly
            if (!empty($params['save'])) {
                // If raw output to file was requested, close the file
                if (isset($fp) && is_resource($fp)) {
                    fclose($fp);
                }

                // Reset option for next calls if handle is reused
                curl_setopt($this->ch, CURLOPT_FILE, null);
            }

            /**
             *  If the output must be returned directly
             *  It can be a string (raw output) or an array/object (decoded JSON) or parsed JSON
             */
            if (!empty($output)) {
                return $output;
            }

            return null;
        } catch (Exception $e) {
            throw new Exception('HTTP request error: ' . $e->getMessage());
        }
    }
}
