<?php

namespace Controllers\Filesystem;

use Exception;
use Datetime;

class Directory
{
    /**
     *  Copy directory recursively to another location
     */
    public static function copy(string $sourceDir, string $targetDir)
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

        $myprocess = new \Controllers\Process('/usr/bin/rm -rf "' . $directoryPath . '"');
        $myprocess->execute();
        $myprocess->close();

        if ($myprocess->getExitCode() != 0) {
            return false;
        }

        return true;
    }

    /**
     *  Get directory size in bytes
     */
    public static function getSize(string $path)
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
    public static function isEmpty($dir)
    {
        $files = \Controllers\Filesystem\File::recursiveScan($dir);

        if (!empty($files)) {
            return false;
        }

        return true;
    }
}
