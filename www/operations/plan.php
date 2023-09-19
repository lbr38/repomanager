<?php
/**
 *  Script used for debug only
 */

define('ROOT', '/var/www/repomanager');
require_once(ROOT . "/controllers/Autoloader.php");
new \Controllers\Autoloader('api');

ini_set('memory_limit', '256M');

$getOptions = getopt(null, ["id:"]);

$mylog = new \Controllers\Log\Log();
$myplan = new \Controllers\Planification();

try {
    $myplan->setId($getOptions['id']);
    $myplan->exec();
} catch (\Exception $e) {
    $mylog->log('error', 'Service', 'Error while executing planification: ' . $e->getMessage());
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

exit(0);
