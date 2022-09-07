<?php

/**
 *  Clean repo statistics older than 1 year
 */

define('ROOT', dirname(__FILE__, 2));
require_once(ROOT . '/controllers/Autoloader.php');
\Controllers\Autoloader::api();

if (STATS_ENABLED == "yes") {
    $mystats = new \Controllers\Stat();

    try {
        $mystats->clean();
    } catch (Exception $e) {
        echo 'Error while executing stats cleaning operation';
    }

    $mystats->closeConnection();
}
