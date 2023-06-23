<?php
/**
 *  v1.0
 */

define("ROOT", dirname(__FILE__, 3));

const HTTP_OK = 200;
const HTTP_BAD_REQUEST = 400;
const HTTP_METHOD_NOT_ALLOWED = 405;

function response($responseCode, $message)
{
    header('Content-Type: application/json');
    http_response_code($responseCode);

    $response = [
        "response_code" => $responseCode,
        "message" => $message
    ];

    echo json_encode($response);

    exit;
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest" and !empty($_POST['controller'])) {
    require_once(ROOT . "/controllers/Autoloader.php");
    new \Controllers\Autoloader();

    /**
     *  Get target controller
     */
    $controller = $_POST['controller'];

    /**
     *  Get target action
     */
    $action = $_POST['action'];

    if (empty($action)) {
        response(HTTP_BAD_REQUEST, 'Unspecified action');
    }

    /**
     *  Check if a controller file exists and include it
     */
    if (!file_exists(ROOT . '/controllers/ajax/' . $controller . '.php')) {
        response(HTTP_BAD_REQUEST, 'Bad controller.');
    }

    include_once(ROOT . '/controllers/ajax/' . $controller . '.php');
}

response(HTTP_METHOD_NOT_ALLOWED, 'Method not allowed');
