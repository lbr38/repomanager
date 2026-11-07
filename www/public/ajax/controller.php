<?php

define('ROOT', '/var/www/repomanager');

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

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == "xmlhttprequest" and !empty($_POST['controller'])) {
    require_once(ROOT . "/controllers/Autoloader.php");
    new \Controllers\Autoloader();
    new \Controllers\App\Main();

    /**
     *  Get target controller
     */
    $controller = $_POST['controller'];

    /**
     *  Get target action
     */
    $action = $_POST['action'] ?? '';

    if (empty($action) or !is_string($action)) {
        response(HTTP_BAD_REQUEST, 'Unspecified action');
    }

    /**
     *  Controller name must only contain lowercase letters, numbers, hyphens and slashes (for subdirectories)
     *  Prevents path traversal (e.g. '../../') and the inclusion of any file outside the ajax controllers directory
     */
    if (!is_string($controller) or !preg_match('#^[a-z0-9-]+(/[a-z0-9-]+)*$#', $controller)) {
        response(HTTP_BAD_REQUEST, 'Bad controller.');
    }

    /**
     *  Check if a controller file exists and is located inside the ajax controllers directory, then include it
     */
    $controllerFile = realpath(ROOT . '/controllers/ajax/' . $controller . '.php');

    if ($controllerFile === false or !str_starts_with($controllerFile, ROOT . '/controllers/ajax/')) {
        response(HTTP_BAD_REQUEST, 'Bad controller.');
    }

    include_once($controllerFile);
}

response(HTTP_METHOD_NOT_ALLOWED, 'Method not allowed');
