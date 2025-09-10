<?php

namespace Controllers\System\Monitoring;

use Exception;

class Disk
{
    /**
     *  Get Disk usage (%)
     */
    public static function getUsage(string $path) : string
    {
        $diskTotalSpace = disk_total_space(REPOS_DIR);
        $diskFreeSpace  = disk_free_space(REPOS_DIR);
        $diskUsedSpace  = $diskTotalSpace - $diskFreeSpace;
        // $diskUsedSpaceHuman = \Controllers\Common::sizeFormat($diskUsedSpace);
        $diskUsedSpacePercent = round(($diskUsedSpace / $diskTotalSpace) * 100);

        return $diskUsedSpacePercent;
    }
}
