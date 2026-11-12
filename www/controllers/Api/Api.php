<?php

namespace Controllers\Api;

use Exception;
use Controllers\Exception\AppException;

class Api
{
    private $method;
    private $uri;
    private $route;
    private $data;

    public function __construct()
    {
        try {
            // Load the application and check that the request can be served
            Bootstrap::start();

            // Retrieve method and JSON data if any
            $this->method = $_SERVER['REQUEST_METHOD'];
            $this->data = Bootstrap::body();

            // Retrieve URI and route from URI
            $this->uri = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
            $this->route = $this->uri[3] ?? '';
        } catch (HttpException $e) {
            self::returnError($e->getStatus(), $e->getMessage());
        } catch (Exception $e) {
            self::returnError(400, $e->getMessage());
        }
    }

    /**
     *  Run API
     */
    public function run(): void
    {
        try {
            /**
             *  If this server API status was requested
             */
            if ($this->route == 'status') {
                http_response_code(201);
                echo json_encode(['return' => '200', 'status' => 'OK']);
                exit;
            }

            /**
             *  Check if route is valid by checking if corresponding controller exists
             */
            if (!file_exists(ROOT . '/controllers/Api/' . ucfirst($this->route) . '/' . ucfirst($this->route) . '.php')) {
                throw new Exception('No matching route');
            }

            /**
             *  Only the routes used by hosts accept the host authentication (Id + token), the other routes act on behalf of a user
             *  and require an API key (users permissions are not defined for a host)
             */
            if (!defined('API_KEY') and !in_array($this->route, ['host', 'profile'])) {
                self::returnError(401, 'You must authenticate with an API key');
            }

            $apiControllerPath = '\Controllers\Api\\' . ucfirst($this->route) . '\\' . ucfirst($this->route);

            /**
             *  Call API controller
             */
            $myapiController = new $apiControllerPath($this->method, $this->uri);

            /**
             *  Set JSON data if any
             */
            if (!empty($this->data)) {
                $myapiController->setJsonData($this->data);
            }

            /**
             *  Execute API controller and return results
             */
            $resultArray = $myapiController->execute();
            self::returnSuccess($resultArray);
        } catch (AppException $e) {
            self::returnError(400, $e->getDetails() ?: $e->getMessage());
        } catch (Exception $e) {
            self::returnError(400, $e->getMessage());
            exit;
        }
    }

    /**
     *  Return 20x with specified results
     */
    private static function returnSuccess(array $results): void
    {
        // If no specific return code is set in results, set it to 200
        if (!array_key_exists('rc', $results)) {
            $results['rc'] = 200;
        }

        // Also keep 'return' key for backward compatibility
        // TODO: remove 'return' key in favor of 'rc' key in future major release
        $results['return'] = $results['rc'];
        $results['note'] = "The 'return' key is deprecated and will be removed in a future major release (replaced by 'rc' key)";

        // If the "normalize" query parameter is set, convert all result keys to lowercase
        if (isset($_GET['normalize'])) {
            $results = Serializer::normalize($results);
        }

        http_response_code((int) $results['rc']);
        echo json_encode($results);
        exit;
    }

    /**
     *  Return error
     *  Error messages may contain HTML formatting intended for the web interface (labels, bold...), API clients get plain text
     */
    private static function returnError(int $code, string|array $message): void
    {
        $toPlainText = function ($value) {
            return is_string($value) ? html_entity_decode(strip_tags($value), ENT_QUOTES) : $value;
        };

        $message = is_array($message) ? array_map($toPlainText, $message) : $toPlainText($message);

        http_response_code($code);
        echo json_encode(['return' => $code, 'message_error' => [$message]]);
        exit(1);
    }
}
