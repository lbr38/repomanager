<?php
/**
 *  Manually check and initialize databases
 */

define('ROOT', '/var/www/repomanager');

require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader();
new \Controllers\App\Main('minimal');
use \Controllers\Log\Cli as CliLog;

try {
    $databases = array('main', 'stats', 'hosts', 'ws');

    /**
     *  Open a connection to each database and create tables if they do not exist
     */
    foreach ($databases as $database) {
        $myconn = new \Models\Connection($database, null, false);
    }
} catch (Exception $e) {
    CliLog::error('There was an error while initializing ' . $database . ' database', $e->getMessage());
    exit(1);
}

CliLog::log('Databases check and initialization successful');

exit(0);
