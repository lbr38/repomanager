<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if (
    $_SERVER['REQUEST_METHOD'] == 'GET'
    or $_SERVER['REQUEST_METHOD'] == 'POST'
    or $_SERVER['REQUEST_METHOD'] == 'PUT'
    or $_SERVER['REQUEST_METHOD'] == 'DELETE'
) {

    /**
     *  On récupère les informations transmises
     */
    $datas = json_decode(file_get_contents("php://input"));

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        include_once('get.php');
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        include_once('add.php');
    }

    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        include_once('update.php');
    }

    if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
        include_once('delete.php');
    }

    exit;
} else {
    /**
     *  Cas où on tente d'utiliser une autre méthode
     */
    http_response_code(405);
    echo json_encode(["return" => "405", "message_error" => array("Method not allowed.")]);
}

exit;
