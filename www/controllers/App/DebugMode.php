<?php

namespace Controllers\App;

class DebugMode
{
    /**
     *  Return true if debug mode is enabled
     */
    public static function enabled() : bool
    {
        if (file_exists(DATA_DIR . '/.debug')) {
            return true;
        }

        return false;
    }
}
