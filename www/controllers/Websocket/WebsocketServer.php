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
    protected $model;
    protected $hostController;
    protected $layoutContainerReloadController;
    protected $hostProcessController;
    protected $logFile;
    protected $socket;

    public function __construct()
    {
        $this->model = new \Models\Websocket\WebsocketServer();
        $this->hostController = new \Controllers\Host();
        $this->layoutContainerReloadController = new \Controllers\Layout\ContainerReload();
    }

    /**
     *  Run the websocket server
     */
    public function run(int $port)
    {
        $hostProcessController = new \Controllers\Websocket\Host\Process();
        $browserClientProcessController = new \Controllers\Websocket\BrowserClient\Process();

        $this->socket = new Socket();
        $this->socket->initialize();

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    $this->socket
                )
            ),
            $port
        );

        /**
         *  Periodic timer to send requests to target hosts
         */
        $server->loop->addPeriodicTimer(2, function () use ($hostProcessController, $browserClientProcessController) {
            /**
             *  Process all browser clients reloads
             */
            $browserClientProcessController->reload($this->socket);

            /**
             *  Process all requests to send to hosts
             */
            $hostProcessController->requests($this->socket);
        });

        $this->log('[server] Server started on port ' . $port);
        $server->run();
    }

    /**
     *  Clean websocket connections from database
     */
    protected function cleanWsConnections()
    {
        $this->model->cleanWsConnections();
    }

    /**
     *  Add new websocket connection in database
     */
    public function newWsConnection(int $connectionId)
    {
        $this->model->newWsConnection($connectionId);
    }

    /**
     *  Set websocket connection type
     */
    public function setWsConnectionType(int $connectionId, string $type)
    {
        $this->model->setWsConnectionType($connectionId, $type);
    }

    /**
     *  Update websocket connection in database
     */
    public function updateWsConnection(int $connectionId, int $hostId, string $authenticated)
    {
        $this->model->updateWsConnection($connectionId, $hostId, $authenticated);
    }

    /**
     *  Return all authenticated websocket connections from database
     */
    public function getAuthenticatedWsConnections()
    {
        return $this->model->getAuthenticatedWsConnections();
    }

    /**
     *  Return all websocket connections from database
     */
    public function getWsConnections(string $type = null)
    {
        return $this->model->getWsConnections($type);
    }

    /**
     *  Return websocket connection Id by host Id
     */
    public function getWsConnectionIdByHostId(int $hostId)
    {
        return $this->model->getWsConnectionIdByHostId($hostId);
    }

    /**
     *  Delete websocket connection from database
     */
    public function deleteWsConnection(int $connectionId)
    {
        $this->model->deleteWsConnection($connectionId);
    }

    /**
     *  Broadcast a message to all clients
     */
    protected function broadcast($socket, $connectionType, array $message)
    {
        $this->log('[server] Broadcasting message to ' . $connectionType . ' clients: ' . print_r($message, true));

        /**
         *  Retrieve all browser-client connections
         */
        $connections = $this->getWsConnections('browser-client');

        /**
         *  Retrieve all socket connections
         */
        $socketConnections = $socket->getClients();

        foreach ($socketConnections as $socketConnection) {
            // Search in $connections subarrays if a Connection_id corresponds to the current resourceId
            $key = array_search($socketConnection->resourceId, array_column($connections, 'Connection_id'));

            if ($key !== false) {
                $this->log('[server] Sending message to connection #' . $socketConnection->resourceId);
                $socketConnection->send(json_encode($message));
            }
        }
    }

    /**
     *  Log a message to the log file and to the console
     */
    protected function log($message)
    {
        /**
         *  Always recalculate the log file name, in case the date changes
         */
        $this->logFile = WS_LOGS_DIR . '/' . DATE_YMD . '_websocketserver.log';

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
