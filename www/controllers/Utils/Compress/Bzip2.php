<?php

namespace Controllers\Utils\Compress;

use Controllers\Process;
use Exception;

class Bzip2
{
    /**
     *  Uncompress bzip2 file <file.bz2> to <file>
     */
    public static function uncompress(string $filename, string|null $outputFilename = null) : void
    {
        if (!empty($outputFilename)) {
            $myprocess = new Process('/usr/bin/bunzip2 --decompress -k -c ' . escapeshellarg($filename) . ' > ' . escapeshellarg($outputFilename));
        } else {
            $myprocess = new Process('/usr/bin/bunzip2 --decompress -k ' . escapeshellarg($filename));
        }

        $myprocess->execute();
        $content = trim($myprocess->getOutput());
        $myprocess->close();

        if ($myprocess->getExitCode() != 0) {
            throw new Exception($content);
        }

        unset($myprocess, $content);
    }
}
