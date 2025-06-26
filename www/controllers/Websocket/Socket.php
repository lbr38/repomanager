<?php

namespace Controllers\Websocket;

/**
 *  Composer autoload
 */
require ROOT . '/libs/vendor/autoload.php';

use Exception;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 *  Class Socker extends WebsocketServer to gain access to its methods
 */
class Socket extends WebsocketServer implements MessageComponentInterface
{
    protected $clients;

    /**
     *  Initialize socket
     *  Basically like a constructor but to avoid conflicts with the parent constructor
     */
    public function initialize()
    {
        /**
         *  Initialize clients storage
         */
        $this->clients = new \SplObjectStorage;

        /**
         *  Clean database from old connections
         *  (e.g. connections that were not removed from database because of a crash or a bug)
         */
        try {
            $this->cleanWsConnections();
        } catch (Exception $e) {
            $this->log('Error while cleaning database from old connections: ' . $e->getMessage());
        }
    }

    /**
     *  Return all websocket clients
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     *  On websocket connection open
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $this->log('[connection #' . $conn->resourceId . '] New connection!');

        /**
         *  Adding connection Id to database, waiting for a message from the host to authenticate
         */
        try {
            $this->newWsConnection($conn->resourceId);
        } catch (Exception $e) {
            $this->log('[connection #' . $conn->resourceId . '] Error while adding connection to database: ' . $e->getMessage());

            /**
             *  Send a message to the host to inform that the connection is not allowed, and close it
             *  TODO: Add an error Id to the message
             */
            $conn->send(json_encode(array('error' => "You've been connected but an error occurred on the server side. Please try again later.")));
            $conn->close();
        }
    }

    /**
     *  On websocket message received
     */
    public function onMessage(ConnectionInterface $conn, $message)
    {
        /**
         *  Decode JSON message
         */
        try {
            $message = json_decode($message, true);
        } catch (Exception $e) {
            $this->log('[connection #' . $conn->resourceId . '] Error while decoding message: ' . $e->getMessage());
            return;
        }

        /**
         *  If the client is sending its connection type
         */
        if (!empty($message['connection-type'])) {
            // Connection type must be either 'host' or 'browser-client'
            if (!in_array($message['connection-type'], array('host', 'browser-client'))) {
                $this->log('[connection #' . $conn->resourceId . '] Invalid connection type: ' . $message['connection-type']);

                // Close connection
                $conn->close();
            }

            // Set connection type in database
            $this->setWsConnectionType($conn->resourceId, $message['connection-type']);

            // If the connection type is 'host'
            if ($message['connection-type'] == 'host') {
                // Ask the host to authenticate
                $conn->send(json_encode(array('request' => 'authenticate')));
            }
        }

        /**
         *  If the host is sending a response to a request
         *  A response can either contain a string request or a request Id
         */
        if (!empty($message['response-to-request'])) {
            try {
                $hostProcessController = new \Controllers\Websocket\Host\Process();

                /**
                 *  If the host is trying to authenticate
                 */
                if (isset($message['response-to-request']['request']) and $message['response-to-request']['request'] == 'authenticate') {
                    $hostProcessController->authenticate($conn, $message);
                }

                /**
                 *  If the host is sending a response to a request, with a request Id
                 */
                if (isset($message['response-to-request']['request-id'])) {
                    $hostProcessController->responseFromRequestId($conn, $message);
                }
            } catch (Exception $e) {
                /**
                 *  Print, send an error message to the host and close connection
                 */
                if (isset($message['response-to-request']['request'])) {
                    $this->log('[connection #' . $conn->resourceId . '] Error while processing host\'s response to request "' . $message['response-to-request']['request'] . '": ' . $e->getMessage());
                    $conn->send(json_encode(array('error' => 'error while processing response to request "' . $message['response-to-request']['request'] . '"')));
                } else if (isset($message['response-to-request']['request-id'])) {
                    $this->log('[connection #' . $conn->resourceId . '] Error while processing host\'s response to request #' . $message['response-to-request']['request-id'] . ': ' . $e->getMessage());
                    $conn->send(json_encode(array('error' => 'error while processing response to request #' . $message['response-to-request']['request-id'])));
                }

                $this->log('[server] Closing connection #' . $conn->resourceId);
                $conn->close();
            }
        }
    }

    /**
     *  On websocket connection close
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        $this->log('[connection #' . $conn->resourceId . '] Connection closed');

        /**
         *  Removing connection Id from database
         */
        try {
            $this->deleteWsConnection($conn->resourceId);
        } catch (Exception $e) {
            $this->log('[connection #' . $conn->resourceId . '] Error while removing connection from database: ' . $e->getMessage());
        }
    }

    /**
     *  On websocket connection error
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->log('[connection #' . $conn->resourceId . '] An error occurred with connection: ' . $e->getMessage());
        $conn->close();
    }
}
