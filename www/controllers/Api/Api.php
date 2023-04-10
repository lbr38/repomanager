<?php

namespace Controllers\Api;

use Exception;

class Api
{
    private $method;
    private $uri;
    private $route;
    private $data;
    private $authenticationController;
    private $apiKeyAuthentication = false;
    private $hostAuthentication = false;

    public function __construct()
    {
        $this->authenticationController = new \Controllers\Api\Authentication\Authentication();

        /**
         *  Exit if method is not allowed
         */
        if ($_SERVER['REQUEST_METHOD'] != 'GET' and $_SERVER['REQUEST_METHOD'] != 'POST' and $_SERVER['REQUEST_METHOD'] != 'PUT' and $_SERVER['REQUEST_METHOD'] != 'DELETE') {
            http_response_code(405);
            echo json_encode(["return" => "405", "message_error" => array('Method not allowed.')]);
            exit;
        }

        /**
         *  Get method
         */
        $this->method = $_SERVER['REQUEST_METHOD'];

        /**
         *  Retrieve data
         */
        $this->data = json_decode(file_get_contents("php://input"));

        /**
         *  Quit on error if no data was sent
         */
        if (empty($this->data)) {
            self::returnError(400, 'Missing data.');
        }

        /**
         *  Retrieve URI
         */
        $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->uri = explode('/', $this->uri);

        /**
         *  Get route from URI
         */
        $this->route = $this->uri[3];

        /**
         *  Quit if autoload has encountered any error
         */
        if (__LOAD_GENERAL_ERROR != 0) {
            http_response_code(503);
            echo json_encode(["return" => "503", "message_error" => array('Reposerver configuration error. Please contact the administrator.')]);
            exit;
        }

        /**
         *  Return 403 if an update is running
         */
        if (UPDATE_RUNNING == 'true') {
            http_response_code(403);
            echo json_encode(["return" => "403", "message_error" => array('Reposerver is actually being updated. Please try again later.')]);
            exit;
        }

        /**
         *  Check if authentication is valid from data sent
         */
        if (!$this->authenticationController->valid($this->data)) {
            self::returnError(401, 'Bad credentials.');
        }

        /**
         *  Retrieve valid authentication method
         */
        $this->apiKeyAuthentication = $this->authenticationController->getApiKeyAuthenticationStatus();
        $this->hostAuthentication = $this->authenticationController->getHostAuthenticationStatus();

        /**
         *  Check if method and URI are specified
         */
        if (empty($_SERVER['REQUEST_METHOD'])) {
            throw new Exception('No method specified.');
        }
        if (empty($_SERVER['REQUEST_URI'])) {
            throw new Exception('No route specified.');
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
                throw new Exception('No matching route.');
            }

            $apiControllerPath = '\Controllers\Api\\' . ucfirst($this->route) . '\\' . ucfirst($this->route);

            /**
             *  Call API controller
             */
            $myapiController = new $apiControllerPath($this->method, $this->uri, $this->data);
            $myapiController->setApiKeyAuthentication($this->apiKeyAuthentication);
            $myapiController->setHostAuthentication($this->hostAuthentication);
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
        $returnArray = array('return' => 201);
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
        exit;
    }
}
