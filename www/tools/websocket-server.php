<?php
/**
 *  For debugging only
 */
define('ROOT', '/var/www/repomanager');
require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader();
new \Controllers\App\Main('minimal');

$logController = new \Controllers\Log\Log();

$port = 8081; // Default port is 8081

/**
 *  If a .wss file exists, read the port from it
 */
if (file_exists(ROOT . '/.wss')) {
    $content = trim(file_get_contents(ROOT . '/.wss'));

    if (!empty($content) && is_numeric($content)) {
        $port = $content;
    }

    unset($content);
}

try {
    $websockerServer = new \Controllers\Websocket\WebsocketServer();
    $websockerServer->run($port);
} catch (Exception $e) {
    $logController->log('error', 'Websocket server', "General exception: " . $e->getMessage() . ': ' . $e->getTraceAsString());
    exit(1);
} catch (Error $e) {
    $logController->log('error', 'Websocket server', "General error: " . $e->getMessage() . ': ' . $e->getTraceAsString());
    exit(1);
}

exit(0);
