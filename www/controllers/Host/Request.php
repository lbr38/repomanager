<?php

namespace Controllers\Host;

use Exception;

class Request
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Host\Request();
    }

    /**
     *  Add a new request in database
     */
    public function new(int $hostId, string $request, array $requestData = [])
    {
        /**
         *  Define the request name
         */
        $json['request'] = $request;

        /**
         *  If additional json data is provided, we add it to the request
         */
        if (!empty($requestData)) {
            $json['data'] = $requestData;
        }

        $this->model->new($hostId, json_encode($json));
    }

    /**
     *  Return all requests from database
     *  If a status is specified, only requests with this status will be returned, otherwise all requests will be returned
     */
    public function get(string|null $status = null)
    {
        return $this->model->get($status);
    }

    /**
     *  Update request in database
     */
    public function update(int $id, string $status, string $info, string $responseJson)
    {
        $this->model->update($id, $status, $info, $responseJson);
    }

    /**
     *  Update request status in database
     */
    public function updateStatus(int $id, string $status)
    {
        $this->model->updateStatus($id, $status);
    }

    /**
     *  Update request info message in database
     */
    public function updateInfo(int $id, string $info)
    {
        $this->model->updateInfo($id, $info);
    }

    /**
     *  Update request retry in database
     */
    public function updateRetry(int $id, int $retry)
    {
        $this->model->updateRetry($id, $retry);
    }

    /**
     *  Update request next retry time in database
     */
    public function updateNextRetry(int $id, string $nextRetry)
    {
        $this->model->updateNextRetry($id, $nextRetry);
    }

    /**
     *  Cancel request in database
     */
    public function cancel(int $id)
    {
        $this->model->cancel($id);
    }

    /**
     *  Delete request from database
     */
    public function delete(int $id)
    {
        $this->model->delete($id);
    }

    /**
     *  Get request log details
     *  Request log is a file stored in the websocket-requests logs directory
     */
    public function getRequestLog(int $id) : string
    {
        $logFile = WS_REQUESTS_LOGS_DIR . '/request-' . $id . '.log';

        if (!file_exists($logFile)) {
            throw new Exception('Log file does not exist');
        }

        if (!is_readable($logFile)) {
            throw new Exception('Log file is not readable');
        }

        $content = file_get_contents($logFile);

        if ($content === false) {
            throw new Exception('Error while reading log file');
        }

        return $content;
    }

    /**
     *  Get request package log details
     */
    public function getRequestPackageLog(int $id, string $package, string $status) : string|null
    {
        return $this->model->getRequestPackageLog($id, $package, $status);
    }
}
