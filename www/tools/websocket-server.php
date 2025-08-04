<?php
/**
 *  For debugging only
 */
define('ROOT', '/var/www/repomanager');
require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader();
new \Controllers\App\Main('minimal');

$logController = new \Controllers\Log\Log();

try {
    $websockerServer = new \Controllers\Websocket\WebsocketServer();
    $websockerServer->run(8081);
} catch (Exception $e) {
    $logController->log('error', 'Websocket server', "General exception: " . $e->getMessage() . ': ' . $e->getTraceAsString());
    exit(1);
} catch (Error $e) {
    $logController->log('error', 'Websocket server', "General error: " . $e->getMessage() . ': ' . $e->getTraceAsString());
    exit(1);
}

exit(0);
