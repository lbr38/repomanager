<?php

namespace Controllers\Utils\Compress;

use Exception;

class Gzip
{
    /**
     *  Uncompress gzip file <file.gz> to <file>
     */
    public static function uncompress(string $filename) : void
    {
        // Output file
        $filenameOut = str_replace('.gz', '', $filename);

        // Buffer size, read 4kb at a time
        $bufferSize = 4096;

        // Open the files (in binary mode)
        $fileOpen = gzopen($filename, 'rb');

        if ($fileOpen === false) {
            throw new Exception('Error while opening gziped file: ' . $filename);
        }

        // Open output file
        $fileOut = fopen($filenameOut, 'wb');

        if ($fileOut === false) {
            throw new Exception('Error while opening gunzip output file: ' . $filenameOut);
        }

        // Keep repeating until the end of the input file
        while (!gzeof($fileOpen)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            if (!fwrite($fileOut, gzread($fileOpen, $bufferSize))) {
                throw new Exception('Error while reading gziped file content: ' . $filename);
            }
        }

        // Close files
        if (!fclose($fileOut)) {
            throw new Exception('Error while closing gunzip output file: ' . $filenameOut);
        }

        if (!gzclose($fileOpen)) {
            throw new Exception('Error while closing gziped file: ' . $filename);
        }

        unset($filename, $filenameOut, $bufferSize, $fileOpen, $fileOut);
    }
}
