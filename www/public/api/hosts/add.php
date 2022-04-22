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
\Controllers\Autoloader::loadFromApi();

/**
 *  Si il y a eu la moindre erreur de chargement lors de l'autoload alors on quitte
 */
if (__LOAD_GENERAL_ERROR != 0) {
    http_response_code(400);
    echo json_encode(["return" => "400", "message" => "Erreur de configuration sur le serveur Repomanager. Contactez l'administrateur du serveur."]);
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
            $message_succes[] = "L'enregistrement a ete effectue.";
            http_response_code(201);
            $authId = $myhost->getAuthId();
            $token  = $myhost->getToken();
            echo json_encode(["return" => "201", "message" => $message_succes, "id" => "$authId", "token" => "$token"]);
            exit;
        }

        if ($register == "2") {
            $message_error[] = "Impossible de déterminer l'adresse IP de l'hote.";
            http_response_code(400);
            echo json_encode(["return" => "400", "message_error" => $message_error]);
            exit;
        }

        if ($register == "3") {
            $message_error[] = "Cet hôte est déjà enregistre.";
            http_response_code(400);
            echo json_encode(["return" => "400", "message_error" => $message_error]);
            exit;
        }

        if ($register == "5") {
            $message_error[] = "Le serveur n'a pas pu finaliser l'enregistrement.";
            http_response_code(400);
            echo json_encode(["return" => "400", "message_error" => $message_error]);
            exit;
        }
    } else {
        $message_error[] = "Les donnees transmises sont invalides.";
        http_response_code(400);
        echo json_encode(["return" => "400", "message_error" => $message_error]);
        exit;
    }

    exit;
}

/**
 *  Cas où on tente d'utiliser une autre méthode que POST
 */
$message_error[] = "La méthode n'est pas autorisée.";
http_response_code(405);
echo json_encode(["return" => "405", "message_error" => $message_error]);
exit;
