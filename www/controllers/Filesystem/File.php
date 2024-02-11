<?php

namespace Controllers\Filesystem;

use Exception;
use Datetime;

class File
{
    /**
     *  Recursively set permissions on a file or directory (chmod)
     *  @param string $path
     *  @param string $type 'file' or 'dir'
     *  @param int $mode
     */
    public static function recursiveChmod(string $path, string $type, int $mode)
    {
        if (!file_exists($path)) {
            throw new Exception('Set permissions error: path does not exist: ' . $path);
        }

        if ($type !== 'file' && $type !== 'dir') {
            throw new Exception('Set permissions error: type must be "file" or "dir": ' . $type);
        }

        $files = array();

        /**
         *  Recursively search for all files in specified path
         */
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path . '/', \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
        );

        /**
         *  Get all files path (file or directory)
         */
        foreach ($iterator as $file) {
            /**
             *  Skip . and .. directories
             */
            if ($file->getFilename() == '.' || $file->getFilename() == '..') {
                continue;
            }

            /**
             *  If $type is file, only add files to array
             */
            if ($type == 'file') {
                if ($file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }

            /**
             *  If $type is dir, only add directories to array
             */
            if ($type == 'dir') {
                if ($file->isDir()) {
                    $files[] = $file->getPathname();
                }
            }
        }

        /**
         *  Process all files in array and set permissions
         */
        foreach ($files as $file) {
            if (!chmod($file, octdec($mode))) {
                throw new Exception('Set permissions error: could not set permissions (mode ' . $mode . ') on file: ' . $file);
            }
        }

        unset($iterator, $files);
    }

    /**
     *  Recursively set owner and group on a file or directory (chown)
     *  @param string $path
     *  @param string $owner
     *  @param string $group
     */
    public static function recursiveChown(string $path, string $owner, string $group)
    {
        if (!file_exists($path)) {
            throw new Exception('Set permissions error: path does not exist: ' . $path);
        }

        $files = array();

        /**
         *  Recursively search for all files in specified path
         */
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path . '/', \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
        );

        /**
         *  Get all files path (file or directory)
         */
        foreach ($iterator as $file) {
            /**
             *  Skip . and .. directories
             */
            if ($file->getFilename() == '.' || $file->getFilename() == '..') {
                continue;
            }

            /**
             *  Add file to array (file or directory)
             */
            if ($file->isFile() or $file->isDir()) {
                $files[] = $file->getPathname();
            }
        }

        /**
         *  Process all files in array and set owner and group
         */
        foreach ($files as $file) {
            if (!chown($file, $owner)) {
                throw new Exception('Set permissions error: could not set owner (' . $owner . ') on file: ' . $file);
            }

            if (!chgrp($file, $group)) {
                throw new Exception('Set permissions error: could not set group (' . $group . ') on file: ' . $file);
            }
        }

        unset($iterator, $files);
    }
}
