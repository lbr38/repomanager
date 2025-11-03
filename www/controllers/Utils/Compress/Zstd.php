<?php

namespace Controllers\Utils\Compress;

use \Controllers\Process;
use Exception;

class Zstd
{
    /**
     *  Uncompress zstd file <file.zst> to <file>
     */
    public static function uncompress(string $filename) : void
    {
        $myprocess = new Process('/usr/bin/unzstd ' . $filename);
        $myprocess->execute();
        $content = trim($myprocess->getOutput());
        $myprocess->close();

        if ($myprocess->getExitCode() != 0) {
            throw new Exception($content);
        }

        unset($myprocess, $content);
    }
}
