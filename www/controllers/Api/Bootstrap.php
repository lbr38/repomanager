<?php

namespace Controllers\Api;

use JsonException;
use Controllers\Update;
use Controllers\App\Maintenance;

/**
 *  Initialization shared by all API versions: load the application, then check that the request can be served
 *  Errors are thrown as HttpException, each API version returns them in its own format
 */
class Bootstrap
{
    public const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     *  Load the application and check that the request can be served (method, configuration, update, maintenance, authentication)
     */
    public static function start(): void
    {
        new \Controllers\App\Main('api');

        if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', self::METHODS)) {
            throw new HttpException(405, 'Method not allowed');
        }

        if (empty($_SERVER['REQUEST_URI'])) {
            throw new HttpException(400, 'No route specified');
        }

        if (__LOAD_GENERAL_ERROR != 0) {
            throw new HttpException(503, 'Reposerver configuration error. Please contact the administrator.');
        }

        if (Update::running()) {
            throw new HttpException(503, 'Reposerver is currently being updated. Please try again later.');
        }

        if (Maintenance::running()) {
            throw new HttpException(503, 'Reposerver is under maintenance. Please try again later.');
        }

        if (AUTHENTICATED === false) {
            throw new HttpException(401, 'Authentication error' . (defined('AUTHENTICATION_ERROR') ? ': ' . AUTHENTICATION_ERROR : ''));
        }
    }

    /**
     *  Return the decoded JSON body of the request, or an empty array if there is none
     *  (multipart requests have no JSON body, their fields are in $_POST)
     */
    public static function body(): array
    {
        $body = file_get_contents('php://input');

        if ($body === false or trim($body) === '') {
            return [];
        }

        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new HttpException(400, 'Could not parse JSON data: ' . $e->getMessage());
        }

        if (!is_array($data)) {
            throw new HttpException(400, 'Invalid JSON data: an object is expected');
        }

        return $data;
    }
}
