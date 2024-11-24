<?php

namespace Controllers;

use Exception;

/**
 *  Classe regroupant quelques fonctions communes / génériques
 */

class Common
{
    private $validColors;

    /**
     *  Get content between two patterns strings
     */
    public static function getContentBetween(string $content, string $start, string $end)
    {
        $n = explode($start, $content);
        $result = array();

        foreach ($n as $val) {
            $pos = strpos($val, $end);
            if ($pos !== false) {
                $result[] = substr($val, 0, $pos);
            }
        }

        return $result;
    }

    /**
     *  Fonction de vérification / conversion des données envoyées par formulaire
     */
    public static function validateData($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    /**
     *  Fonction de vérification du format d'une adresse email
     */
    public static function validateMail(string $mail)
    {
        $mail = trim($mail);

        if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        return false;
    }

    /**
     *  Vérifie que la chaine passée ne contient que des chiffres ou des lettres
     */
    public static function isAlphanum(string $data, array $additionnalValidCaracters = [])
    {
        /**
         *  Si on a passé en argument des caractères supplémentaires à autoriser alors on les ignore dans le test en les remplacant temporairement par du vide
         */
        if (!empty($additionnalValidCaracters)) {
            if (!ctype_alnum(str_replace($additionnalValidCaracters, '', $data))) {
                return false;
            }

        /**
         *  Si on n'a pas passé de caractères supplémentaires alors on teste simplement la chaine avec ctype_alnum
         */
        } else {
            if (!ctype_alnum($data)) {
                return false;
            }
        }

        return true;
    }

    /**
     *  Vérifie que la chaine passée ne contient que des chiffres ou des lettres, un underscore ou un tiret
     *  Retire temporairement les tirets et underscore de la chaine passée afin qu'elle soit ensuite testée par la fonction PHP ctype_alnum
     */
    public static function isAlphanumDash(string $data, array $additionnalValidCaracters = [])
    {
        /**
         *  Si une chaine vide a été transmise alors c'est valide
         */
        if (empty($data)) {
            return true;
        }

        /**
         *  array contenant quelques exceptions de caractères valides
         */
        $validCaracters = array('-', '_');

        /**
         *  Si on a passé en argument des caractères supplémentaires à autoriser alors on les ajoute à l'array $validCaracters
         */
        if (!empty($additionnalValidCaracters)) {
            $validCaracters = array_merge($validCaracters, $additionnalValidCaracters);
        }

        if (!ctype_alnum(str_replace($validCaracters, '', $data))) {
            return false;
        }

        return true;
    }

    /**
     *  Get the best contrasting text color for a given background color
     */
    public static function getContrastingTextColor($backgroundColor)
    {
        // Convert hexadecimal color to RGB
        $r = hexdec(substr($backgroundColor, 1, 2));
        $g = hexdec(substr($backgroundColor, 3, 2));
        $b = hexdec(substr($backgroundColor, 5, 2));

        // Calculate YIQ (luma) value
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        // Return white for dark colors and black for light colors
        return ($yiq >= 128) ? '#000000' : '#ffffff';
    }

    /**
     *  Generate environment tag
     */
    public static function envtag(string $name, string $css = null)
    {
        // Default class
        $class = 'env';

        // Default colors
        $backgroundColor = '#ffffff';
        $color = '#000000';

        // Retrieve color from ENVS array
        if (defined('ENVS')) {
            foreach (ENVS as $env) {
                if ($env['Name'] == $name and !empty($env['Color'])) {
                    $backgroundColor = $env['Color'];
                    // Get contrasting text color
                    $color = Common::getContrastingTextColor($backgroundColor);
                }
            }
        }

        if ($css == 'fit') {
            $class = 'env-fit';
        }

        return '<span class="' . $class . '" style="background-color: ' . $backgroundColor . '; color: ' . $color . '">' . $name . '</span>';
    }

    /**
     *  Génère un nombre aléatoire en 10000 et 99999
     */
    public static function generateRandom()
    {
        return mt_rand(10000, 99999);
    }

    /**
     *  Génère une chaine de caractères aléatoires
     */
    public static function randomString(int $length)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return $randomString;
    }

    /**
     *  Generate random strong string
     */
    public static function randomStrongString(int $length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*%-_{}()';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return $randomString;
    }

    /**
     *  Get a random color from a valid hex colors list
     */
    public function randomColor()
    {
        /**
         *  Refill available color list if there are no more available
         */
        if (empty($this->validColors)) {
            $this->validColors = array('rgb(75, 192, 192)', '#5993ec', '#e0b05f', '#24d794', '#EFBDEB', '#F85A3E', '#8EB1C7', '#1AC8ED', '#E9D758');
        }

        $randomColorId = array_rand($this->validColors, 1);
        $randomColor = $this->validColors[$randomColorId];
        unset($this->validColors[$randomColorId]);

        return $randomColor;
    }

    /**
     *  Convertit une durée microtime au format HHhMMmSSs
     */
    public static function convertMicrotime(string $duration)
    {
        $hours = (int)($duration/60/60);
        $minutes = (int)($duration/60)-$hours*60;
        $seconds = (int)$duration-$hours*60*60-$minutes*60;

        $time = '';

        if (!empty($hours)) {
            $time = strval($hours) . 'h';
        }
        if (!empty($minutes)) {
            $time .= strval($minutes) . 'm';
        }
        if (!empty($seconds)) {
            $time .= $seconds . 's';
        }

        return $time;
    }

    /**
     *  Tri un array par la valeur de clé spécifiée
     */
    public static function groupBy($key, $data)
    {
        $result = array();

        foreach ($data as $val) {
            if (array_key_exists($key, $val)) {
                $result[$val[$key]][] = $val;
            } else {
                $result[""][] = $val;
            }
        }

        return $result;
    }

    /**
     *  Return an array with the list of founded files in specified directory path
     */
    public static function findRecursive(string $directoryPath, string $fileExtension = null, bool $returnFullPath = true)
    {
        $foundedFiles = array();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directoryPath . '/', \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
        );

        /**
         *  Find files with specified extension
         */
        if (!empty($iterator)) {
            foreach ($iterator as $file) {
                /**
                 *  Skip '.' and '..' files
                 */
                if ($file->getFilename() == '.' || $file->getFilename() == '..') {
                    continue;
                }

                /**
                 *  Skip if the current file is a directory
                 */
                if ($file->isDir()) {
                    continue;
                }

                /**
                 *  If an extension has been specified, then check that the file has correct extension
                 */
                if (!empty($fileExtension)) {
                    /**
                     *  If extension is incorrect, then ignore the current file and process the next one
                     */
                    if ($file->getExtension() != $fileExtension) {
                        continue;
                    }
                }

                /**
                 *  By default, return file's fullpath
                 */
                if ($returnFullPath === true) {
                    $foundedFiles[] = $file->getPathname();
                /**
                 *  Else only return filename
                 */
                } else {
                    $foundedFiles[] = $file->getFilename();
                }
            }
        }

        return $foundedFiles;
    }

    /**
     *  Return an array with the list of founded directories in specified directory path
     *  Directory name can be filtered with a regex
     */
    public static function findDirRecursive(string $directoryPath, string $directoryNameRegex = null, bool $returnFullPath = true)
    {
        $foundedDirs = array();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directoryPath,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
        );

        /**
         *  Find directories
         */
        if (!empty($iterator)) {
            foreach ($iterator as $file) {
                if (is_file($file->getPathname())) {
                    continue;
                }

                /**
                 *  Skip '.' and '..' files
                 */
                if ($file->getFilename() == '.' || $file->getFilename() == '..') {
                    continue;
                }

                /**
                 *  Skip if the current file is not a directory
                 */
                if (!$file->isDir()) {
                    continue;
                }

                /**
                 *  Skip if the current file is a symlink
                 */
                if ($file->isLink()) {
                    continue;
                }

                /**
                 *  Skip if the dir name does not match the specified regex
                 */
                if (!empty($directoryNameRegex)) {
                    if (!preg_match("/$directoryNameRegex/i", $file->getFilename())) {
                        continue;
                    }
                }

                /**
                 *  By default, return file's fullpath
                 */
                if ($returnFullPath === true) {
                    // trim last '..' and '.' characters
                    $foundedDir = rtrim($file->getPathname(), '.');
                /**
                 *  Else only return filename
                 */
                } else {
                    // trim last '..' and '.' characters
                    $foundedDir = rtrim($file->getFilename(), '.');
                }

                /**
                 *  Add founded directory to the array if not already in
                 */
                if (!in_array($foundedDir, $foundedDirs)) {
                    $foundedDirs[] = $foundedDir;
                }
            }
        }

        return $foundedDirs;
    }

    /**
     *  Find files and copy them to the specified target directory
     */
    public static function findAndCopyRecursive(string $directoryPath, string $targetDirectoryPath, string $fileExtension = null)
    {
        $foundedFiles = Common::findRecursive($directoryPath, $fileExtension, true);

        /**
         *  Copy files if founded
         */
        if (!empty($foundedFiles)) {
            foreach ($foundedFiles as $foundedFile) {
                $filename = preg_split('#/#', $foundedFile);
                $filename = end($filename);

                if (!copy($foundedFile, $targetDirectoryPath . '/' . $filename)) {
                    throw new Exception('Error: could not copy package ' . $foundedFile . ' to ' . $targetDirectoryPath . '/' . $filename);
                }
            }
        }
    }

    /**
     *  Uncompress bzip2 file
     */
    public static function bunzip2(string $filename, string $outputFilename = null)
    {
        /**
         *  If a custom output filename has been specified
         */
        if (!empty($outputFilename)) {
            $myprocess = new \Controllers\Process('/usr/bin/bunzip2 --decompress -k -c ' . $filename . ' > ' . $outputFilename);
        } else {
            $myprocess = new \Controllers\Process('/usr/bin/bunzip2 --decompress -k ' . $filename);
        }

        $myprocess->execute();
        $content = $myprocess->getOutput();
        $myprocess->close();

        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Error while uncompressing bzip2 file ' . $filename . ': ' . $content);
        }

        unset($myprocess, $content);
    }

    /**
     *  Uncompress specified gzip file 'file.gz' to 'file'
     */
    public static function gunzip(string $filename)
    {
        /**
         *  Output file
         */
        $filenameOut = str_replace('.gz', '', $filename);

        /**
         *  Buffer size, read 4kb at a time
         */
        $bufferSize = 4096;

        /**
         *  Open the files (in binary mode)
         */
        $fileOpen = gzopen($filename, 'rb');
        if ($fileOpen === false) {
            throw new Exception('Error while opening gziped file: ' . $filename);
        }

        $fileOut = fopen($filenameOut, 'wb');
        if ($fileOut === false) {
            throw new Exception('Error while opening gunzip output file: ' . $filenameOut);
        }

        /**
         *  Keep repeating until the end of the input file
         */
        while (!gzeof($fileOpen)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            if (!fwrite($fileOut, gzread($fileOpen, $bufferSize))) {
                throw new Exception('Error while reading gziped file content: ' . $filename);
            }
        }

        /**
         *  Close files
         */
        if (!fclose($fileOut)) {
            throw new Exception('Error while closing gunzip output file: ' . $filenameOut);
        }
        if (!gzclose($fileOpen)) {
            throw new Exception('Error while closing gziped file: ' . $filename);
        }

        unset($bufferSize, $fileOpen, $fileOut);
    }

    /**
     *  Uncompress specified xz file 'file.xz' to 'file'
     */
    public static function xzUncompress(string $filename, string $outputFilename = null)
    {
        if (!empty($outputFilename)) {
            $myprocess = new \Controllers\Process('/usr/bin/xz --decompress -k -c ' . $filename . ' > ' . $outputFilename);
        } else {
            $myprocess = new \Controllers\Process('/usr/bin/xz --decompress -k ' . $filename);
        }
        $myprocess->execute();
        $content = $myprocess->getOutput();
        $myprocess->close();

        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Error while uncompressing xz file ' . $filename . ': ' . $content);
        }

        unset($myprocess, $content);
    }

    /**
     *  Return true if distant URL is reachable
     *  The target URL can be a file or a directory
     */
    public static function urlReachable(string $url, int $timeout = 3, string $sslCertificatePath = null, string $sslPrivateKeyPath = null, string $sslCustomCaCertificate = null)
    {
        try {
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

            /**
             *  If a proxy has been specified
             */
            if (!empty(PROXY)) {
                curl_setopt($ch, CURLOPT_PROXY, PROXY);
            }

            /**
             *  If a custom SSL certificate / private key / ca certificate have been specified
             */
            if (!empty($sslCertificatePath)) {
                curl_setopt($ch, CURLOPT_SSLCERT, $sslCertificatePath);
            }
            if (!empty($sslPrivateKeyPath)) {
                curl_setopt($ch, CURLOPT_SSLKEY, $sslPrivateKeyPath);
            }
            if (!empty($sslCustomCaCertificate)) {
                curl_setopt($ch, CURLOPT_CAINFO, $sslCustomCaCertificate);
            }

            /**
             *  If curl fails with an error, try to retrieve the error message and error number and throw an exception
             */
            if (curl_exec($ch) === false) {
                $exception = 'curl error';
                $errorNumber = curl_errno($ch);
                $error = curl_error($ch);

                // Add curl error number
                if (!empty($errorNumber)) {
                    $exception .= ' (' . $errorNumber . ')';
                }

                // Add curl error message
                if (!empty($error)) {
                    $exception .= ': ' . $error;
                }

                throw new Exception($exception);
            }

            /**
             *  If curl execution succeeded, retrieve the HTTP response code
             */
            $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

            if (empty($responseCode)) {
                throw new Exception('could not retrieve HTTP response code');
            }

            /**
             *  If the response code is different from 200, then return the response code
             */
            if ($responseCode != 200) {
                throw new Exception('HTTP response code: ' . $responseCode);
            }
        } catch (Exception $e) {
            throw new Exception('URL reachability check failed: ' . $e->getMessage());
        } finally {
            curl_close($ch);
        }

        return true;
    }

    /**
     *  Convert bytes size to the most suitable human format (B, MB, GB...)
     */
    public static function sizeFormat($bytes, $returnFormat = true)
    {
        $kb = 1024;
        $mb = $kb * 1024;
        $gb = $mb * 1024;
        $tb = $gb * 1024;

        if (($bytes >= 0) && ($bytes < $kb)) {
            $value = $bytes;
            $format = 'B';
        } elseif (($bytes >= $kb) && ($bytes < $mb)) {
            $value = ceil($bytes / $kb);
            $format = 'K';
        } elseif (($bytes >= $mb) && ($bytes < $gb)) {
            $value = ceil($bytes / $mb);
            $format = 'M';
        } elseif (($bytes >= $gb) && ($bytes < $tb)) {
            $value = ceil($bytes / $gb);
            $format = 'G';
        } elseif ($bytes >= $tb) {
            $value = ceil($bytes / $tb);
            $format = 'T';
        } else {
            $value = $bytes;
            $format = 'B';
        }

        if ($value >= 1000 and $value <= 1024) {
            $value = 1;

            if ($format == 'B') {
                $format = 'K';
            } elseif ($format == 'K') {
                $format = 'M';
            } elseif ($format == 'M') {
                $format = 'G';
            } elseif ($format == 'G') {
                $format = 'T';
            } elseif ($format == 'T') {
                $format = 'P';
            }
        }

        if ($returnFormat === true) {
            return $value . $format;
        }

        return $value;
    }

    /**
     *  Print OS icon image
     */
    public static function printOsIcon(string $os = null, string $osFamily = null)
    {
        if (!empty($os)) {
            if (preg_match('/centos/i', $os)) {
                return '<img src="/assets/icons/products/centos.png" class="icon-np" title="' . $os . '" />';
            } elseif (preg_match('/debian|armbian/i', $os)) {
                return '<img src="/assets/icons/products/debian.png" class="icon-np" title="' . $os . '" />';
            } elseif (preg_match('/ubuntu|kubuntu|xubuntu|mint/i', $os)) {
                return '<img src="/assets/icons/products/ubuntu.png" class="icon-np" title="' . $os . '" />';
            }
        }

        /**
         *  If OS could not be found and OS family is specified
         */
        if (!empty($osFamily)) {
            if (preg_match('/debian|ubuntu|kubuntu|xubuntu|armbian|mint/i', $osFamily)) {
                return '<img src="/assets/icons/products/debian.png" class="icon-np" title="' . $os . '" />';
            } elseif (preg_match('/rhel|centos|fedora/i', $osFamily)) {
                return '<img src="/assets/icons/products/redhat.png" class="icon-np" title="' . $os . '" />';
            }
        }

        /**
         *  Else return generic icon
         */
        return '<img src="/assets/icons/products/tux.png" class="icon-np" title="' . $os . '" />';
    }

    /**
     *  Print product icon image
     */
    public static function printProductIcon(string $product)
    {
        if (preg_match('/python/i', $product)) {
            return '<img src="/assets/icons/products/python.png" class="icon-np" />';
        } elseif (preg_match('/^code$/i', $product)) {
            return '<img src="/assets/icons/products/vscode.png" class="icon-np" />';
        } elseif (preg_match('/^firefox/i', $product)) {
            return '<img src="/assets/icons/products/firefox.png" class="icon-np" />';
        } elseif (preg_match('/^chrome-$/i', $product)) {
            return '<img src="/assets/icons/products/chrome.png" class="icon-np" />';
        } elseif (preg_match('/^chromium-$/i', $product)) {
            return '<img src="/assets/icons/products/chromium.png" class="icon-np" />';
        } elseif (preg_match('/^brave-browser$/i', $product)) {
            return '<img src="/assets/icons/products/brave.png" class="icon-np" />';
        } elseif (preg_match('/^filezilla/i', $product)) {
            return '<img src="/assets/icons/products/filezilla.png" class="icon-np" />';
        } elseif (preg_match('/^java/i', $product)) {
            return '<img src="/assets/icons/products/java.png" class="icon-np" />';
        } elseif (preg_match('/^teams$/i', $product)) {
            return '<img src="/assets/icons/products/teams.png" class="icon-np" />';
        } elseif (preg_match('/^teamviewer$/i', $product)) {
            return '<img src="/assets/icons/products/teamviewer.png" class="icon-np" />';
        } elseif (preg_match('/^thunderbird/i', $product)) {
            return '<img src="/assets/icons/products/thunderbird.png" class="icon-np" />';
        } elseif (preg_match('/^vlc/i', $product)) {
            return '<img src="/assets/icons/products/vlc.png" class="icon-np" />';
        } else {
            return '<img src="/assets/icons/package.svg" class="icon-np" />';
        }
    }

    /**
     *  Returns true if string is a valid md5 hash
     */
    public static function isMd5(string $md5)
    {
        return preg_match('/^[a-f0-9]{32}$/', $md5);
    }

    /**
     *  Convert a string to a boolean
     *  Possible return values:
     *   Returns TRUE for "1", "true", "on" and "yes"
     *   Returns FALSE for "0", "false", "off" and "no"
     *   Returns NULL on failure if FILTER_NULL_ON_FAILURE is set
     */
    public static function toBool(string $string)
    {
        return filter_var($string, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
