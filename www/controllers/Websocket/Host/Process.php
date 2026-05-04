<?php

namespace Controllers\Websocket\Host;

use JsonException;
use Exception;

// Class Process extends WebsocketServer to gain access to its methods
class Process extends \Controllers\Websocket\WebsocketServer
{
    private $hostRequestController;

    public function __construct()
    {
        parent::__construct();

        $this->hostRequestController = new \Controllers\Host\Request();
    }

    // Process host authentication
    public function authenticate($conn, $message)
    {
        $this->log('[conn #' . $conn->resourceId . '] Authenticating...');

        try {
            // Check if auth Id and token are set
            if (empty($message['response-to-request']['auth-id']) || empty($message['response-to-request']['token'])) {
                throw new Exception('invalid auth Id or token');
            }

            // Retrieve host auth Id and token
            $authId = $message['response-to-request']['auth-id'];
            $token = $message['response-to-request']['token'];

            // Check that id and token are valid
            if (!$this->hostController->checkIdToken($authId, $token)) {
                throw new Exception('bad credentials');
            }

            try {
                // If authentication is successful, retrieve host Id from database
                $hostId = $this->hostController->getIdByAuth($authId);

                if (empty($hostId)) {
                    throw new Exception('no host Id found for this auth Id');
                }

                // Update connection in database with host Id
                $this->updateWsConnection($conn->resourceId, $hostId, 'true');
            } catch (Exception $e) {
                throw new Exception('could not finish authentication: ' . $e->getMessage());
            }

            // Send a success message to the host
            try {
                $conn->send(json_encode(['info' => 'Authentication successful'], JSON_THROW_ON_ERROR));
            } catch (JsonException $e) {
                throw new Exception('could not encode authentication success message to JSON: ' . $e->getMessage());
            }
        } catch (Exception $e) {
            throw new Exception('Authentication error: ' . $e->getMessage());
        }

        $this->log('[conn #' . $conn->resourceId . '] Authentication successful');
    }

    /**
     *  Process host response to request message
     */
    public function responseFromRequestId($conn, $message)
    {
        $info = '';
        $responseJson = '';

        // Retrieve request Id
        $requestId = $message['response-to-request']['request-id'];

        // Retrieve request status
        $status = $message['response-to-request']['status'];

        // Retrieve error or info message, if any
        if (!empty($message['response-to-request']['error'])) {
            $info = 'Error: ' . strtolower($message['response-to-request']['error']);
        } else if (!empty($message['response-to-request']['info'])) {
            $info = ': ' . strtolower($message['response-to-request']['info']);
        }

        // Retrieve JSON summary, if any
        if (!empty($message['response-to-request']['summary'])) {
            $responseJson = json_encode($message['response-to-request']['summary'], JSON_THROW_ON_ERROR);
        }

        // Retrieve log, if any
        if (!empty($message['response-to-request']['log'])) {
            if (!file_put_contents(WS_REQUESTS_LOGS_DIR . '/request-' . $requestId . '.log', $message['response-to-request']['log'])) {
                $this->logError('[conn #' . $conn->resourceId . '] Error while writing request #' . $requestId . ' log to file ' . WS_REQUESTS_LOGS_DIR . '/request-' . $requestId . '.log');
            }
        }

        $this->log('[conn #' . $conn->resourceId . '] Sended response for request #' . $requestId . ' with status "' . $status . '"');

        // Update request status and response in database
        $this->hostRequestController->update($requestId, $status, $info, $responseJson);

        /**
         *  Send a message to the client to inform that the response was received
         *  Tell the client what kind of data was received (summary, log...), to avoid it to send them again
         */
        $confirmMessage = [
            'info' => 'Request response received',
            'request-id' => $requestId,
            // Tell the client what kind of data was received
            'data' => [
                'status'
            ]
        ];

        // If there was an info message, add it to the data array, to inform the client that it was received
        if (!empty($message['response-to-request']['info'])) {
            $confirmMessage['data'][] = 'info';
        }
        // If there was an error message, add it to the data array, to inform the client that it was received
        if (!empty($message['response-to-request']['error'])) {
            $confirmMessage['data'][] = 'error';
        }
        // If there was a summary, add it to the data array, to inform the client that it was received
        if (!empty($message['response-to-request']['summary'])) {
            $confirmMessage['data'][] = 'summary';
        }
        // If there was a log, add it to the data array, to inform the client that it was received
        if (!empty($message['response-to-request']['log'])) {
            $confirmMessage['data'][] = 'log';
        }

        // Send the confirmation message to the client
        $conn->send(json_encode($confirmMessage), JSON_THROW_ON_ERROR);

        $this->layoutContainerReloadController->reload('hosts/overview');
        $this->layoutContainerReloadController->reload('hosts/list');
        $this->layoutContainerReloadController->reload('host/summary');
        $this->layoutContainerReloadController->reload('host/packages');
        $this->layoutContainerReloadController->reload('host/history');
        $this->layoutContainerReloadController->reload('host/requests');
    }

    /**
     *  Process and send requests to target hosts
     */
    public function requests($socket)
    {
        // Retrieve all 'new' requests from database
        $requests = $this->hostRequestController->get('new');

        // Retrieve all authenticated (true) clients
        $clients = $this->getAuthenticatedWsConnections();

        // If no new requests, quit
        if (empty($requests)) {
            return;
        }

        // For each request, send it to the target host
        foreach ($requests as $request) {
            try {
                // If the request has a 'next_retry' timestamp and it is in the future, skip it for now
                if ($request['Next_retry'] > time()) {
                    continue;
                }

                // Retrieve host hostname, for better logs
                $hostname = $this->hostController->getHostnameById($request['Id_host']);

                if (empty($hostname)) {
                    $hostname = 'unknown';
                }

                // Retrieve request JSON details and decode it
                $requestDetails = json_decode($request['Request'], true);

                // If target host is not authenticated (not in $clients), skip
                if (!in_array($request['Id_host'], array_column($clients, 'Id_host'))) {
                    // If retry count is less than 3, increment it and set new retry date in database
                    if ($request['Retry'] < 3) {
                        $this->hostRequestController->updateRetry($request['Id'], $request['Retry'] + 1);

                        /**
                         *  Calculate timestamp for next retry
                         *  First retry in 1 minute, second in 5 minutes, third in 10 minutes
                         */
                        if ($request['Retry'] == 0) {
                            $nextRetry = strtotime('+1 minute');
                        } elseif ($request['Retry'] == 1) {
                            $nextRetry = strtotime('+5 minutes');
                        } elseif ($request['Retry'] == 2) {
                            $nextRetry = strtotime('+10 minutes');
                        }

                        $this->logError('[server] Request #' . $request['Id'] . ' for host ' . $hostname . ' #' . $request['Id_host']. ' cannot be processed: host is not connected or not authenticated (retry ' . $request['Retry'] . '/3 - next retry ~' . date('H:i:s', $nextRetry) . ')');

                        // Set new retry date in database and add an info message
                        $this->hostRequestController->updateNextRetry($request['Id'], $nextRetry);
                        $this->hostRequestController->updateInfo($request['Id'], 'Host is not connected or not authenticated (retry ' . $request['Retry'] . '/3 - next retry ~' . date('H:i:s', $nextRetry) . ')');
                        $this->layoutContainerReloadController->reload('host/requests');
                        continue;
                    }

                    $this->logError('[server] Request #' . $request['Id'] . ' for host ' . $hostname . ' #' . $request['Id_host']. ' cannot be processed: host is not connected or not authenticated (retry 3/3 - failed, will not retry)');

                    /**
                     *  If all retries failed, update request status to 'failed' in database
                     *  Update request status to 'failed' in database
                     */
                    $this->hostRequestController->updateStatus($request['Id'], 'failed');
                    $this->hostRequestController->updateInfo($request['Id'], 'Host is not connected or not authenticated (retried 3 times)');
                    $this->layoutContainerReloadController->reload('host/requests');
                    continue;
                }

                // First, retrieve websocket connection Id of target host
                $hostWsConnectionId = $this->getWsConnectionIdByHostId($request['Id_host']);

                // If request is 'disconnect', close connection and remove it from database
                if ($requestDetails['request'] == 'disconnect') {
                    foreach ($socket->getClients() as $client) {
                        if ($client->resourceId == $hostWsConnectionId) {
                            // Send a message to the host to inform that the connection will be closed
                            $client->send(json_encode(['info' => 'You will now be disconnected from the server'], JSON_THROW_ON_ERROR));

                            // Close connection
                            $client->close();
                            $this->log('[server] Closed connection with host #' . $request['Id_host'] . ' (connection #' . $client->resourceId . ') as requested by request #' . $request['Id']);
                        }
                    }

                    // Delete the disconnect request from database
                    $this->hostRequestController->delete($request['Id']);
                    continue;
                }

                /**
                 *  Any other request
                 *  Send message to target host through websocket
                 */
                foreach ($socket->getClients() as $client) {
                    if ($client->resourceId == $hostWsConnectionId) {
                        $this->log('[server] Sending request #' . $request['Id'] . ' to host ' . $hostname . ' through connection #' . $client->resourceId);

                        // Send the request to the host
                        try {
                            if (!empty($requestDetails['data'])) {
                                $client->send(json_encode([
                                    'request-id' => $request['Id'],
                                    'request' => $requestDetails['request'],
                                    'data' => $requestDetails['data']
                                ], JSON_THROW_ON_ERROR));
                            } else {
                                $client->send(json_encode([
                                    'request-id' => $request['Id'], 'request' => $requestDetails['request']
                                ], JSON_THROW_ON_ERROR));
                            }
                        } catch (JsonException $e) {
                            throw new Exception('could not encode request data to JSON: ' . $e->getMessage());
                        }

                        // Update request status to 'sent' in database
                        $this->hostRequestController->updateStatus($request['Id'], 'sent');
                        $this->hostRequestController->updateInfo($request['Id'], 'Request sent to the host');
                        $this->layoutContainerReloadController->reload('host/requests');
                    }
                }
            } catch (Exception $e) {
                $this->logError('[server] Error while processing request #' . $request['Id'] . ' for host ' . $hostname . ': ' . $e->getMessage());

                // Update request status to 'failed' in database
                $this->hostRequestController->updateStatus($request['Id'], 'failed');
                $this->hostRequestController->updateInfo($request['Id'], 'Error while processing request: ' . $e->getMessage());
                $this->layoutContainerReloadController->reload('host/requests');
            }
        }
    }
}
