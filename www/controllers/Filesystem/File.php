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
    public static function recursiveChmod(string $path, string $type, int $mode) : void
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
    public static function recursiveChown(string $path, string $owner, string $group) : void
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

    /**
     *  Return an array with the list of founded files in specified directory path
     */
    public static function findRecursive(string $path, string $fileExtension = null, bool $absolute = true)
    {
        $foundedFiles = array();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path . '/', \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
        );

        /**
         *  Find files with specified extension
         */
        if (!empty($iterator)) {
            foreach ($iterator as $file) {
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
                 *  By default, return file's fullpath (absolute path)
                 */
                if ($absolute) {
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
     *  Recursively list all files in a directory
     *  Type can be specified: 'file', 'symlink', 'dir' or null (all types will be returned)
     *  Relative path can be specified: true or false
     */
    public static function recursiveScan(string $path, string|null $type = null, $relative = false) : array
    {
        $files = array();

        try {
            if (!file_exists($path)) {
                throw new Exception('path does not exist: ' . $path);
            }

            /**
             *  If a type is specified, check if it is valid
             */
            if (!empty($type) and !in_array($type, ['file', 'symlink', 'dir'])) {
                throw new Exception('specified type is invalid: ' . $type);
            }

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
                 *  If a type is specified
                 */
                if (!empty($type)) {
                    // If $type is file, only add files to array
                    if ($type == 'file') {
                        if ($file->isFile() and !$file->isLink()) {
                            if ($relative) {
                                $files[] = str_replace($path . '/', '', $file->getPathname());
                            } else {
                                $files[] = $file->getPathname();
                            }
                        }
                    }

                    // If $type is symlink, only add symlinks to array
                    if ($type == 'symlink') {
                        if ($file->isLink()) {
                            if ($relative) {
                                $files[] = str_replace($path . '/', '', $file->getPathname());
                            } else {
                                $files[] = $file->getPathname();
                            }
                        }
                    }

                    // If $type is dir, only add directories to array
                    if ($type == 'dir') {
                        if ($file->isDir() and !$file->isLink() and !$file->isFile()) {
                            if ($relative) {
                                $files[] = str_replace($path . '/', '', $file->getPathname());
                            } else {
                                $files[] = $file->getPathname();
                            }
                        }
                    }
                }

                /**
                 *  If no type is specified, include all types of file and indicate the type
                 */
                if (empty($type)) {
                    // Case it is a file
                    if ($file->isFile() and !$file->isLink() and !$file->isDir()) {
                        if ($relative) {
                            $files[] = [
                                'type' => 'file',
                                'path' => str_replace($path . '/', '', $file->getPathname())
                            ];
                        } else {
                            $files[] = [
                                'type' => 'file',
                                'path' => $file->getPathname()
                            ];
                        }
                    }

                    // Case it is a symlink
                    if ($file->isLink()) {
                        if ($relative) {
                            $files[] = [
                                'type' => 'symlink',
                                'path' => str_replace($path . '/', '', $file->getPathname()),
                                'realpath' => realpath($file->getPathname())
                            ];
                        } else {
                            $files[] = [
                                'type' => 'symlink',
                                'path' => $file->getPathname(),
                                'realpath' => realpath($file->getPathname())
                            ];
                        }
                    }

                    // Case it is a directory
                    if ($file->isDir() and !$file->isLink()) {
                        if ($relative) {
                            $files[] = [
                                'type' => 'dir',
                                'path' => str_replace($path . '/', '', $file->getPathname())
                            ];
                        } else {
                            $files[] = [
                                'type' => 'dir',
                                'path' => $file->getPathname()
                            ];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception('Error while recursively listing files: ' . $e->getMessage());
        }

        unset($iterator, $file);

        return $files;
    }
}
