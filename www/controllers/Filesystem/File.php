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
        try {
            if (!file_exists($path)) {
                throw new Exception('path does not exist: ' . $path);
            }

            if (!in_array($type, ['file', 'dir'])) {
                throw new Exception('type must be "file" or "dir": ' . $type);
            }

            $files = [];

            // Add the specified path to the array of files to process if it is a directory to make sure it is included
            if ($type == 'dir' and is_dir($path)) {
                $files[] = $path;
            }

            // If the specified path is a directory, then search for all files in this directory and sub-directories
            if (is_dir($path)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path . '/', \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST,
                    \RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
                );

                foreach ($iterator as $file) {
                    // If $type is file, only add files to array
                    if ($type == 'file') {
                        if ($file->isDir()) {
                            continue;
                        }

                        if ($file->isLink()) {
                            continue;
                        }

                        if ($file->isFile()) {
                            $files[] = $file->getPathname();
                        }
                    }

                    // If $type is dir, only add directories to array
                    if ($type == 'dir') {
                        if ($file->isDir()) {
                            $files[] = $file->getPathname();
                        }
                    }
                }
            }

            /**
             *  Process all files in array and set permissions
             */
            foreach ($files as $file) {
                if (!chmod($file, octdec($mode))) {
                    throw new Exception('could not set permissions (mode ' . $mode . ') on file: ' . $file);
                }
            }
        } catch (Exception $e) {
            throw new Exception('Set permissions error: ' . $e->getMessage());
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

        // Initialize array of files with the specified path
        $files = [$path];

        // If the specified path is a directory, then search for all files in this directory and sub-directories
        if (is_dir($path)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path . '/', \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST,
                \RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
            );

            foreach ($iterator as $file) {
                if ($file->isLink()) {
                    continue;
                }

                // Add file/directory to array
                if ($file->isFile() or $file->isDir()) {
                    $files[] = $file->getPathname();
                }
            }
        }

        /**
         *  Process all files in array and set owner and group
         */
        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

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
    public static function findRecursive(string $path, array $fileExtension = [], bool $absolute = true) : array
    {
        $foundedFiles = [];

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
                 *  If one or more extension(s) have been specified, then check that the file has correct extension
                 *  Otherwise, ignore it
                 */
                if (!empty($fileExtension)) {
                    if (!in_array($file->getExtension(), $fileExtension)) {
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
        $files = [];

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
