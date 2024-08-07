<?php

namespace Controllers\Websocket;

/**
 *  Composer autoload
 */
require ROOT . '/libs/vendor/autoload.php';

use Exception;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class WebsocketServer
{
    protected $hostController;
    protected $layoutContainerStateController;
    protected $logFile;

    public function __construct()
    {
        $this->hostController = new \Controllers\Host();
        $this->layoutContainerStateController = new \Controllers\Layout\ContainerState();
    }

    /**
     *  Run the websocket server
     */
    public function run(int $port)
    {
        $socket = new Socket();
        $socket->initialize();

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    $socket
                )
            ),
            $port
        );

        /**
         *  Periodic timer to send requests to target hosts
         */
        $server->loop->addPeriodicTimer(5, function () use ($socket) {
            /**
             *  Process all requests to send to hosts
             */
            $process = new Process();
            $process->requests($socket);
        });

        $this->log('[server] Server started on port ' . $port);
        $server->run();
    }

    /**
     *  Log a message to the log file and to the console
     */
    protected function log($message)
    {
        /**
         *  Always recalculate the log file name, in case the date changes
         */
        $this->logFile = WS_LOGS_DIR . '/' . date('Y-m-d') . '_websocketserver.log';

        /**
         *  Define the message with a timestamp
         */
        $message = '[' . date('D M j H:i:s') . '] ' . $message . PHP_EOL;

        /**
         *  Write the message to the log file
         */
        file_put_contents($this->logFile, $message, FILE_APPEND);

        /**
         *  Print the message to the console
         */
        echo $message;
    }
}
