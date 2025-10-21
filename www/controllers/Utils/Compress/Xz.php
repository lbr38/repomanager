<?php

namespace Controllers\Utils\Compress;

use \Controllers\Process;
use Exception;

class Xz
{
    /**
     *  Uncompress xz file <file.xz> to <file>
     */
    public static function uncompress(string $filename, string|null $outputFilename = null) : void
    {
        if (!empty($outputFilename)) {
            $myprocess = new Process('/usr/bin/xz --decompress -k -c ' . $filename . ' > ' . $outputFilename);
        } else {
            $myprocess = new Process('/usr/bin/xz --decompress -k ' . $filename);
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
