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
     *  Converts a microtime duration to a time format HHhMMmSSs
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

        if (empty($time)) {
            $time = '0s';
        }

        return $time;
    }

    /**
     *  Converts a microtime to a time format
     */
    public static function microtimeToTime(string $microtime)
    {
        return date('H:i:s', $microtime);
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
            throw new Exception($content);
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
            throw new Exception($content);
        }

        unset($myprocess, $content);
    }

    /**
     *  Uncompress specified zstd file 'file.zst' to 'file'
     */
    public static function zstdUncompress(string $filename)
    {
        $myprocess = new \Controllers\Process('/usr/bin/unzstd ' . $filename);
        $myprocess->execute();
        $content = $myprocess->getOutput();
        $myprocess->close();

        if ($myprocess->getExitCode() != 0) {
            throw new Exception($content);
        }

        unset($myprocess, $content);
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
    public static function printOsIcon(string $os)
    {
        if (preg_match('/centos/i', $os)) {
            return '<img src="/assets/icons/products/centos.png" class="icon-np" title="' . $os . '" />';
        } elseif (preg_match('/rocky/i', $os)) {
            return '<img src="/assets/icons/products/rockylinux.png" class="icon-np" title="' . $os . '" />';
        } elseif (preg_match('/alma/i', $os)) {
            return '<img src="/assets/icons/products/almalinux.png" class="icon-np" title="' . $os . '" />';
        } elseif (preg_match('/oracle/i', $os)) {
            return '<img src="/assets/icons/products/oracle.png" class="icon-np" title="' . $os . '" />';
        } elseif (preg_match('/fedora/i', $os)) {
            return '<img src="/assets/icons/products/fedora.png" class="icon-np" title="' . $os . '" />';
        } elseif (preg_match('/redhat/i', $os)) {
            return '<img src="/assets/icons/products/redhat.png" class="icon-np" title="' . $os . '" />';
        } elseif (preg_match('/debian|armbian/i', $os)) {
            return '<img src="/assets/icons/products/debian.png" class="icon-np" title="' . $os . '" />';
        } elseif (preg_match('/ubuntu|kubuntu|xubuntu|mint/i', $os)) {
            return '<img src="/assets/icons/products/ubuntu.png" class="icon-np" title="' . $os . '" />';
        }

        /**
         *  Else return generic icon
         */
        return '<img src="/assets/icons/products/tux.png" class="icon-np" title="' . $os . '" />';
    }

    /**
     *  Print type icon image
     */
    public static function printTypeIcon(string $type)
    {
        $type = ucfirst($type);

        if (preg_match('/kvm/i', $type)) {
            return '<img src="/assets/icons/products/kvm.png" class="icon-np" title="' . $type . '" />';
        } elseif (preg_match('/lxc/i', $type)) {
            return '<img src="/assets/icons/products/lxc.png" class="icon-np" title="' . $type . '" />';
        } elseif (preg_match('/docker/i', $type)) {
            return '<img src="/assets/icons/products/docker.png" class="icon-np" title="' . $type . '" />';
        }

        /**
         *  Else return generic icon
         */
        return '<img src="/assets/icons/server.svg" class="icon-np" title="' . $type . '" />';
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
