<?php
/**
 *  Manually apply all releases' SQL queries update
 */

define('ROOT', dirname(__FILE__, 2));
define('DATA_DIR', '/var/lib/repomanager');

require_once(ROOT . '/controllers/Autoloader.php');
\Controllers\Autoloader::loadFromLogin();

$myupdate = new \Controllers\Update();

try {
    echo PHP_EOL . 'Enabling maintenance page.' . PHP_EOL;
    $myupdate->setMaintenance('on');

    echo PHP_EOL . 'Executing SQL queries if there are...' . PHP_EOL;
    $myupdate->updateDB();
} catch (Exception $e) {
    echo 'There was an error while executing update: ' . $e->getMessage();
}

echo PHP_EOL . 'Disabling maintenance page.' . PHP_EOL;

$myupdate->setMaintenance('off');
