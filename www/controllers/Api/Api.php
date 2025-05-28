<?php

namespace Controllers\Api;

use Exception;

class Api
{
    private $method;
    private $uri;
    private $route;
    private $authHeader;
    private $data;
    private $userController;
    private $hostController;
    private $apiKeyAuthentication = false;
    private $hostAuthentication = false;
    private $hostId;
    private $hostAuthId;
    private $hostToken;

    public function __construct()
    {
        $this->userController = new \Controllers\User\User();
        $this->hostController = new \Controllers\Host();

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
         *  Quit on error if no data was sent
         */
        // if (empty($this->data)) {
        //     self::returnError(400, 'Missing data.');
        // }

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
        if (UPDATE_RUNNING === true) {
            http_response_code(403);
            echo json_encode(["return" => "403", "message_error" => array('Reposerver is actually being updated. Please try again later.')]);
            exit;
        }

        /**
         *  Check if authentication is valid from data sent
         */
        if (!$this->authenticate()) {
            self::returnError(401, 'Bad credentials');
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
    }

    /**
     *  Check if authentication is valid
     *  It can be an API key authentication or a host authId+token authentication
     */
    public function authenticate()
    {
        $isApiAdmin = false;

        /**
         *  Retrieve authentication header
         */
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $this->authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } else {
            /**
             *  If no authentication header is specified, return false to quit with error
             */
            return false;
        }

        /**
         *  New authentication method
         */

        /**
         *  If API key or host Id+token is specified through the Authorization header
         *  e.g.
         *      "Authorization: Bearer <API_KEY>"
         *      "Authorization: Host <HOST_ID>:<HOST_TOKEN>"
         */
        if (!empty($this->authHeader)) {
            if (strpos($this->authHeader, 'Bearer ') === 0) {
                /**
                 *  Extract the token
                 *  Remove "Bearer " from the header
                 */
                $apiKey = substr($this->authHeader, 7);
            }

            /**
             *  If host Id+token are specified through the Authorization header
             */
            if (strpos($this->authHeader, 'Host ') === 0) {
                /**
                 *  Extract the host Id and token
                 *  Remove "Host " from the header
                 */
                $hostIdToken = substr($this->authHeader, 5);

                /**
                 *  Split the host Id and token
                 */
                $hostIdToken = explode(':', $hostIdToken);

                /**
                 *  Check if host Id and token are specified
                 */
                if (count($hostIdToken) != 2) {
                    return false;
                }

                /**
                 *  Set host authId and token
                 */
                $hostAuthId = $hostIdToken[0];
                $hostToken = $hostIdToken[1];
            }
        }

        /**
         *  If no API key or host authId and token are specified
         */
        if (empty($apiKey) and (empty($hostAuthId) or empty($hostToken))) {
            return false;
        }

        /**
         *  If API key is specified, check that it is valid
         */
        if (!empty($apiKey)) {
            /**
             *  Check if API key exists
             */
            if (!$this->userController->apiKeyValid($apiKey)) {
                return false;
            }

            /**
             *  Set apiKeyAuthentication to true if API key is valid
             */
            $this->apiKeyAuthentication = true;

            /**
             *  Check if API key is an Admin API key
             */
            if ($this->userController->apiKeyIsAdmin($apiKey)) {
                $isApiAdmin = true;
            }
        }

        /**
         *  If a host authId and token have been specified, check if they are valid
         */
        if (!empty($hostAuthId) and !empty($hostToken)) {
            if (!$this->hostController->checkIdToken($hostAuthId, $hostToken)) {
                return false;
            }

            /**
             *  Set hostAuthentication to true if host authId and token are valid
             */
            $this->hostAuthentication = true;
            $this->hostAuthId = $hostAuthId;
            $this->hostToken = $hostToken;
        }

        /**
         *  Define if the API authentication is an admin API authentication
         */
        if (!defined('IS_API_ADMIN')) {
            define('IS_API_ADMIN', $isApiAdmin);
        }

        return true;
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
             *  Set authentication method (true or false)
             */
            $myapiController->setApiKeyAuthentication($this->apiKeyAuthentication);
            $myapiController->setHostAuthentication($this->hostAuthentication);

            if ($this->hostAuthentication) {
                $myapiController->setHostAuthId($this->hostAuthId);
                $myapiController->setHostToken($this->hostToken);
            }

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
