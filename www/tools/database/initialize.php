<?php
/**
 *  Manually check and initialize databases
 */

define('ROOT', '/var/www/repomanager');

require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader('minimal');

try {
    $databases = array('main', 'stats', 'hosts', 'ws');

    /**
     *  Open a connection to each database and create tables if they do not exist
     */
    foreach ($databases as $database) {
        $myconn = new \Models\Connection($database, null, false);
    }
} catch (\Exception $e) {
    echo '[' . date('D M j H:i:s') . '] There was an error while initializing ' . $database . ' database: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo '[' . date('D M j H:i:s') . '] Databases check and initialization successful' . PHP_EOL;
exit(0);
