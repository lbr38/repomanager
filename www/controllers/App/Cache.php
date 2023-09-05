<?php

namespace Controllers\App;

use Exception;

class Cache
{
    /**
     *  Generate repos list cache
     */
    public static function generate(string $role)
    {
        ob_start();
        include(ROOT . '/views/includes/repos-list.inc.php');

        $content = ob_get_clean();
        file_put_contents(WWW_CACHE . '/repomanager-repos-list-' . $role . '.html', $content);
    }

    /**
     *  Delete all files starting with 'repomanager-repos-list' in the cache directory
     */
    public static function clear()
    {
        $files = glob(WWW_CACHE . '/repomanager-repos-*');

        if (!empty($files)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}
