<?php

namespace Controllers\Utils;

class Convert
{
    /**
     *  Converts a string to a boolean
     *  Possible return values:
     *   Returns TRUE for "1", "true", "on" and "yes"
     *   Returns FALSE for "0", "false", "off" and "no"
     *   Returns NULL on failure if FILTER_NULL_ON_FAILURE is set
     */
    public static function toBool(string $string) : bool|null
    {
        return filter_var($string, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     *  Converts a microtime to a time format
     */
    public static function microtimeToTime(string $microtime) : string
    {
        return date('H:i:s', $microtime);
    }

    /**
     *  Converts a microtime duration to a human readable format HHhMMmSSs
     */
    public static function microtimeToHuman(string $duration) : string
    {
        $time = '';
        $hours = (int)($duration/60/60);
        $minutes = (int)($duration/60)-$hours*60;
        $seconds = (int)$duration-$hours*60*60-$minutes*60;

        if (!empty($hours)) {
            $time = strval($hours) . 'h';
        }
        if (!empty($minutes)) {
            $time .= strval($minutes) . 'm';
        }
        if (!empty($seconds)) {
            $time .= $seconds . 's';
        }

        if (empty($time)) {
            $time = '0s';
        }

        return $time;
    }

    /**
     *  Convert bytes size to the most suitable human readable format (B, MB, GB...)
     */
    public static function sizeToHuman($bytes, $returnFormat = true) : string|int
    {
        $kb = 1024;
        $mb = $kb * 1024;
        $gb = $mb * 1024;
        $tb = $gb * 1024;

        if (($bytes >= 0) && ($bytes < $kb)) {
            $value = $bytes;
            $format = 'B';
        } elseif (($bytes >= $kb) && ($bytes < $mb)) {
            $value = ceil($bytes / $kb);
            $format = 'K';
        } elseif (($bytes >= $mb) && ($bytes < $gb)) {
            $value = ceil($bytes / $mb);
            $format = 'M';
        } elseif (($bytes >= $gb) && ($bytes < $tb)) {
            $value = ceil($bytes / $gb);
            $format = 'G';
        } elseif ($bytes >= $tb) {
            $value = ceil($bytes / $tb);
            $format = 'T';
        } else {
            $value = $bytes;
            $format = 'B';
        }

        if ($value >= 1000 and $value <= 1024) {
            $value = 1;

            if ($format == 'B') {
                $format = 'K';
            } elseif ($format == 'K') {
                $format = 'M';
            } elseif ($format == 'M') {
                $format = 'G';
            } elseif ($format == 'G') {
                $format = 'T';
            } elseif ($format == 'T') {
                $format = 'P';
            }
        }

        if ($returnFormat) {
            return $value . $format;
        }

        return $value;
    }
}
