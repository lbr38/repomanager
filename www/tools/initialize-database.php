<?php
/**
 *  Manually check and initialize main database
 */

define('ROOT', dirname(__FILE__, 2));

require_once(ROOT . '/controllers/Autoloader.php');
\Controllers\Autoloader::loadFromLogin();

try {
    $myconn = new \Models\Connection('main');
} catch (\Exception $e) {
    echo 'There was an error while initializing main database: ' . $e->getMessage() .  PHP_EOL;
    exit(1);
}

echo 'Main database check and initialization successfull' . PHP_EOL;
exit(0);
