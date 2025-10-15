<?php

namespace Controllers\System\Monitoring;

class Disk
{
    /**
     *  Get Disk usage (%)
     */
    public static function getUsage(string $path) : string
    {
        $diskTotalSpace = disk_total_space($path);
        $diskFreeSpace  = disk_free_space($path);
        $diskUsedSpace  = $diskTotalSpace - $diskFreeSpace;
        // $diskUsedSpaceHuman = \Controllers\Utils\Convert::sizeToHuman($diskUsedSpace);
        $diskUsedSpacePercent = round(($diskUsedSpace / $diskTotalSpace) * 100);

        return $diskUsedSpacePercent;
    }
}
