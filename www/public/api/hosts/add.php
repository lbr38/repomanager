<?php

// https://github.com/NouvelleTechno/api-rest
// https://nouvelle-techno.fr/articles/live-coding-creer-une-api-rest
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

define("ROOT", dirname(__FILE__, 4));
require_once(ROOT . '/controllers/Autoloader.php');
\Controllers\Autoloader::api();

/**
 *  Si il y a eu la moindre erreur de chargement lors de l'autoload alors on quitte
 */
if (__LOAD_GENERAL_ERROR != 0) {
    http_response_code(400);
    echo json_encode(["return" => "400", "message_error" => array("Reposerver configuration error. Please contact the administrator.")]);
    exit;
}

/**
 *  Return 400 if an update is running
 */
if (UPDATE_RUNNING == 'yes') {
    http_response_code(400);
    echo json_encode(["return" => "400", "message_error" => array("Reposerver is actually being updated. Please try again later.")]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    /**
     *  On récupère les informations transmises
     */
    $datas = json_decode(file_get_contents("php://input"));

    if (!empty($datas->ip) and !empty($datas->hostname)) {

        /**
         *  Instanciation d'un objet Host
         */
        $myhost = new \Controllers\Host();
        $myhost->setIp($datas->ip);
        $myhost->setHostname($datas->hostname);
        $myhost->setFromApi();

        /**
         *  Enregistrement du nouvel hôte en BDD
         */
        $register = $myhost->register();

        /**
         *  Si l'enregistrement a été effectué, on retourne l'id et le token généré pour cet hôte
         */
        if ($register === true) {
            $message_success[] = "Register is done.";
            http_response_code(201);
            $authId = $myhost->getAuthId();
            $token  = $myhost->getToken();
            echo json_encode(["return" => "201", "message_success" => $message_success, "id" => "$authId", "token" => "$token"]);
            exit;
        }

        if ($register == "2") {
            $message_error[] = "Cannot determine host IP address.";
            http_response_code(400);
            echo json_encode(["return" => "400", "message_error" => $message_error]);
            exit;
        }

        if ($register == "3") {
            $message_error[] = "This host is already registered.";
            http_response_code(400);
            echo json_encode(["return" => "400", "message_error" => $message_error]);
            exit;
        }

        if ($register == "5") {
            $message_error[] = "The server could not finalize registering.";
            http_response_code(400);
            echo json_encode(["return" => "400", "message_error" => $message_error]);
            exit;
        }
    } else {
        $message_error[] = "Sended data are invalid.";
        http_response_code(400);
        echo json_encode(["return" => "400", "message_error" => $message_error]);
        exit;
    }

    exit;
}

/**
 *  Cas où on tente d'utiliser une autre méthode que POST
 */
$message_error[] = "Method not allowed.";
http_response_code(405);
echo json_encode(["return" => "405", "message_error" => $message_error]);
exit;
