<?php

namespace Controllers\Filesystem;

use Exception;
use Controllers\Process;

class Directory
{
    /**
     *  Return an array with the list of founded directories in specified directory path
     *  Directory name can be filtered with a regex
     */
    public static function findRecursive(string $directoryPath, string $directoryNameRegex = '', bool $returnFullPath = true) : array
    {
        $foundedDirs = [];

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
     *  Copy directory recursively to another location
     */
    public static function copy(string $sourceDir, string $targetDir) : void
    {
        if (!is_dir($sourceDir)) {
            throw new Exception('Recursive copy error: source directory does not exist: ' . $sourceDir);
        }

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                throw new Exception('Recursive copy error: could not create destination directory: ' . $targetDir);
            }
        }

        foreach ($iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item) {
            if ($item->isDir()) {
                if (!mkdir($targetDir . DIRECTORY_SEPARATOR . $iterator->getSubPathname())) {
                    throw new Exception('Recursive copy error: could not create directory: ' . $targetDir . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
                }
            } else {
                if (!copy($item, $targetDir . DIRECTORY_SEPARATOR . $iterator->getSubPathname())) {
                    throw new Exception('Recursive copy error: could not copy file: ' . $targetDir . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
                }
            }
        }

        unset($iterator);
    }

    /**
     *  Delete specified directory recursively
     */
    public static function deleteRecursive(string $directoryPath) : bool
    {
        /**
         *  Return true if there is nothing to delete
         */
        if (!is_dir($directoryPath)) {
            return true;
        }

        $myprocess = new Process('/usr/bin/rm -rf ' . escapeshellarg($directoryPath));
        $myprocess->execute();
        $myprocess->close();

        // TODO: throw an exception instead
        if ($myprocess->getExitCode() != 0) {
            return false;
        }

        return true;
    }

    /**
     *  Move a directory to the target path, replacing the directory that may already be there
     *  The existing directory is first moved to $backupPath: the target path is then missing only between two moves (instead
     *  of during the whole deletion of the existing directory), and the existing directory is restored if the directory cannot
     *  be moved. Deleting the backup is left to the caller. The paths must be on the same filesystem.
     *  Return true if an existing directory has been moved to $backupPath
     */
    public static function replace(string $source, string $target, string $backupPath): bool
    {
        $replaced = false;

        // The result of a previous is_dir() on the target may be cached by PHP
        clearstatcache(true, $target);

        if (is_dir($target)) {
            // Backup left by a previous attempt
            if (file_exists($backupPath) and !self::deleteRecursive($backupPath)) {
                throw new Exception('Cannot delete directory: ' . $backupPath);
            }

            error_clear_last();

            if (!rename($target, $backupPath)) {
                throw new Exception('Could not move directory ' . $target . ' to ' . $backupPath . ': ' . self::lastError());
            }

            $replaced = true;
        }

        error_clear_last();

        if (!rename($source, $target)) {
            $error = self::lastError();

            // Restore the existing directory
            if ($replaced and !rename($backupPath, $target)) {
                throw new Exception('Could not move directory ' . $source . ' to ' . $target . ': ' . $error . ' (the previous directory could not be restored from ' . $backupPath . ')');
            }

            throw new Exception('Could not move directory ' . $source . ' to ' . $target . ': ' . $error);
        }

        return $replaced;
    }

    /**
     *  Return the reason why the last filesystem function failed, from the PHP warning it raised (e.g. 'Directory not empty')
     */
    private static function lastError(): string
    {
        $message = error_get_last()['message'] ?? '';

        // The warning starts with the function and its arguments: rename(/source,/target): Directory not empty
        $message = preg_replace('/^\w+\(.*\): /U', '', $message);

        return !empty($message) ? $message : 'unknown error';
    }

    /**
     *  Delete specified directory if empty
     */
    public static function deleteIfEmpty(array $directories) : void
    {
        foreach ($directories as $directory) {
            // Ignore if directory does not exist
            if (!is_dir($directory)) {
                continue;
            }

            /**
             *  Another process can delete the directory at the same time: ignore it if it no longer exists
             */
            try {
                $empty = self::isEmpty($directory);
            } catch (Exception $e) {
                // The result of the is_dir() above is cached by PHP
                clearstatcache(true, $directory);

                if (!is_dir($directory)) {
                    continue;
                }

                throw $e;
            }

            // Delete directory if empty
            if ($empty) {
                if (!self::deleteRecursive($directory)) {
                    throw new Exception('Cannot delete directory: ' . $directory);
                }
            }
        }
    }

    /**
     *  Get directory size in bytes
     */
    public static function getSize(string $path): string
    {
        $bytestotal = 0;
        $path = realpath($path);

        if (!empty($path) and file_exists($path)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $object) {
                $bytestotal += $object->getSize();
            }
        }

        return $bytestotal;
    }

    /**
     *  Return true if directory is empty
     */
    public static function isEmpty($dir) : bool
    {
        $files = File::recursiveScan($dir);

        if (!empty($files)) {
            return false;
        }

        return true;
    }
}
