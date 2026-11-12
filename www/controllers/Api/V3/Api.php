<?php

namespace Controllers\Api\V3;

use Exception;
use Throwable;
use Controllers\Api\Bootstrap;
use Controllers\Api\HttpException;
use Controllers\Exception\AppException;

/**
 *  API v3 entry point: route the request to its handler and send the response
 */
class Api
{
    public function run(): never
    {
        try {
            // Load the application and check that the request can be served
            Bootstrap::start();

            $request = Request::fromGlobals();
            $route = (new Router(Routes::get()))->match($request->getMethod(), $request->getSegments());

            self::checkAuthentication($route['auth']);

            $request->setParams($route['params']);

            $handler = new $route['handler']($request);
            $response = $handler->{$route['action']}();

            // Every handler returns a response
            if (!$response instanceof Response) {
                throw new \Error('Handler ' . $route['handler'] . '::' . $route['action'] . '() did not return a response');
            }

            $response->send();
        } catch (HttpException $e) {
            Response::error($e)->send();
        } catch (AppException $e) {
            Response::error(new HttpException(400, self::flatten($e->getDetails()) ?: $e->getMessage(), 'operation_refused'))->send();
        } catch (Exception $e) {
            // Errors raised by the application code shared with the web interface (validation of the tasks, protected environments...)
            Response::error(new HttpException(400, $e->getMessage(), 'operation_refused'))->send();
        } catch (Throwable $e) {
            // Unexpected errors are logged, their details are not returned to the client
            try {
                (new \Controllers\Log\Log())->log('error', 'API', 'Unexpected error while processing API request ' . ($_SERVER['REQUEST_METHOD'] ?? '') . ' ' . ($_SERVER['REQUEST_URI'] ?? ''), $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            } catch (Throwable $logError) {
                error_log('API v3 unexpected error: ' . $e . PHP_EOL);
            }

            Response::error(new HttpException(500, 'Internal error, see Repomanager logs for details'))->send();
        }
    }

    /**
     *  Check that the request is authenticated the way the route requires
     *  Users permissions are only defined with an API key, a host can only access its own data
     */
    private static function checkAuthentication(string $auth): void
    {
        if ($auth == 'user' and !defined('API_KEY')) {
            throw new HttpException(401, 'You must authenticate with an API key');
        }

        if ($auth == 'host' and !defined('HOST_AUTH_ID')) {
            throw new HttpException(401, 'You must authenticate with a host Id and token');
        }
    }

    /**
     *  Return the details of an error as a list of messages
     *  e.g. ['Following packages already exist' => ['a.deb', 'b.deb']] => ['Following packages already exist: a.deb, b.deb']
     */
    private static function flatten(array $details): array
    {
        $messages = [];

        foreach ($details as $key => $value) {
            $prefix = is_string($key) ? $key . ': ' : '';

            if (is_array($value)) {
                $value = implode(', ', array_filter($value, 'is_scalar'));
            }

            if (is_scalar($value)) {
                $messages[] = $prefix . $value;
            }
        }

        return $messages;
    }
}
