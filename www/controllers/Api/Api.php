<?php

namespace Controllers\Api;

use Exception;
use \Controllers\App\Maintenance;
use \Controllers\Update;

class Api
{
    private $method;
    private $uri;
    private $route;
    private $data;

    public function __construct()
    {
        try {
            new \Controllers\App\Main('api');

            /**
             *  Exit if method is not allowed
             */
            if ($_SERVER['REQUEST_METHOD'] != 'GET' and $_SERVER['REQUEST_METHOD'] != 'POST' and $_SERVER['REQUEST_METHOD'] != 'PUT' and $_SERVER['REQUEST_METHOD'] != 'DELETE') {
                http_response_code(405);
                echo json_encode(["return" => "405", "message_error" => array('Method not allowed')]);
                exit;
            }

            /**
             *  Retrieve method
             */
            $this->method = $_SERVER['REQUEST_METHOD'];

            /**
             *  Retrieve data if any
             */
            $this->data = file_get_contents("php://input");

            if (!empty($this->data)) {
                $this->data = json_decode($this->data);

                if ($this->data == null) {
                    self::returnError(400, 'Invalid JSON data');
                }
            }

            /**
             *  Retrieve URI
             */
            $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $this->uri = explode('/', $this->uri);

            /**
             *  Retrieve route from URI
             */
            $this->route = $this->uri[3];

            /**
             *  Quit if app has encountered any error
             */
            if (__LOAD_GENERAL_ERROR != 0) {
                http_response_code(503);
                echo json_encode(["return" => "503", "message_error" => ['Reposerver configuration error. Please contact the administrator.']]);
                exit;
            }

            /**
             *  Return 403 if an update is running
             */
            if (Update::running()) {
                http_response_code(403);
                echo json_encode(["return" => "403", "message_error" => ['Reposerver is currently being updated. Please try again later.']]);
                exit;
            }

            /**
             *  Return 403 if app is in maintenance
             */
            if (Maintenance::running()) {
                http_response_code(403);
                echo json_encode(["return" => "403", "message_error" => ['Reposerver is under maintenance. Please try again later.']]);
                exit;
            }

            /**
             *  Check if authentication is valid
             */
            if (AUTHENTICATED === false) {
                if (defined('AUTHENTICATION_ERROR')) {
                    self::returnError(401, 'Authentication error: ' . AUTHENTICATION_ERROR);
                }

                self::returnError(401, 'Authentication error');
            }

            /**
             *  Check if method and URI are specified
             */
            if (empty($_SERVER['REQUEST_METHOD'])) {
                throw new Exception('No method specified');
            }
            if (empty($_SERVER['REQUEST_URI'])) {
                throw new Exception('No route specified');
            }
        } catch (Exception $e) {
            self::returnError(400, $e->getMessage());
        }
    }

    /**
     *  Run API
     */
    public function run()
    {
        try {
            /**
             *  If this server API status was requested
             */
            if ($this->route == 'status') {
                http_response_code(201);
                echo json_encode(["return" => "201", "status" => 'OK']);
                exit;
            }

            /**
             *  Check if route is valid by checking if corresponding controller exists
             */
            if (!file_exists(ROOT . '/controllers/Api/' . ucfirst($this->route) . '/' . ucfirst($this->route) . '.php')) {
                throw new Exception('No matching route');
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
        } catch (Exception $e) {
            self::returnError(400, $e->getMessage());
            exit;
        }
    }

    /**
     *  Return 201 with specified results
     */
    private static function returnSuccess(array $results)
    {
        $returnArray = ['return' => 201];
        $returnArray = array_merge($returnArray, $results);

        http_response_code(201);
        echo json_encode($returnArray);
        exit;
    }

    /**
     *  Return error
     */
    private static function returnError(int $code, string $message)
    {
        http_response_code($code);
        echo json_encode(['return' => $code, 'message_error' => array($message)]);
        exit(1);
    }
}
