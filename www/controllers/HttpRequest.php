<?php

namespace Controllers;

use Exception;
use JsonException;
use \Controllers\Log\Cli as CliLog;

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
     *  Perform a GET request
     *  Returns the result as a string or saves it to a file (and returns true)
     */
    public function get(array $params, bool $parseJson = false, string $jsonExtract = null) : bool|string|array
    {
        try {
            /**
             *  Set curl options
             */

            // Set URL
            curl_setopt($this->ch, CURLOPT_URL, $params['url']);

            // Set max connect timeout (default 10 seconds)
            curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, isset($params['connectTimeout']) ? $params['connectTimeout'] : 10);

            // Set max transfer timeout (default 30 seconds)
            curl_setopt($this->ch, CURLOPT_TIMEOUT, isset($params['timeout']) ? $params['timeout'] : 30);

            // Set proxy if specified
            if (!empty($params['proxy'])) {
                curl_setopt($this->ch, CURLOPT_PROXY, $params['proxy']);
            }

            // Set headers if specified
            if (!empty($params['headers']) && is_array($params['headers'])) {
                curl_setopt($this->ch, CURLOPT_HTTPHEADER, $params['headers']);
            }

            // Return the result as a string instead of outputting it directly
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);

            /**
             *  Execute curl request
             */
            $output = curl_exec($this->ch);

            // If curl_exec() returns false, an error has occurred
            if (!$output) {
                throw new Exception('curl failed: ' . curl_error($this->ch));
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

            /**
             *  If an output file is specified, write the result to the file
             */
            if (!empty($params['outputFile'])) {
                // If the output is a string, write it directly
                if (is_string($output)) {
                    $write = file_put_contents($params['outputFile'], trim($output));
                }

                // If the output is an array or object, encode it as JSON
                if (is_array($output) || is_object($output)) {
                    try {
                        $write = file_put_contents($params['outputFile'], json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
                    } catch (JsonException $e) {
                        throw new Exception('JSON encode error: ' . $e->getMessage());
                    }
                }

                // If writing to the file failed
                if ($write === false) {
                    throw new Exception('unable to write to output file ' . $params['outputFile']);
                }

                // Return true to indicate success
                return true;
            }

            /**
             *  Otherwise, return the result directly
             *  It can be a string (raw output) or an array/object (decoded JSON) or parsed JSON
             */
            return $output;
        } catch (Exception $e) {
            throw new Exception('HTTP request error: ' . $e->getMessage());
        }
    }
}
