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
}
