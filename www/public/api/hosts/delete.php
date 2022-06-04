<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
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

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {

    /**
     *  On récupère les informations transmises
     */
    $datas = json_decode(file_get_contents("php://input"));

    if (!empty($datas->id) and !empty($datas->token)) {

        /**
         *  Instanciation d'un objet Host
         */
        $myhost = new \Controllers\Host();
        $myhost->setAuthId($datas->id);
        $myhost->setToken($datas->token);
        $myhost->setFromApi();

        /**
         *  D'abord on vérifie que l'ID et le token transmis sont valides
         */
        if (!$myhost->checkIdToken()) {
            $message_error[] = "Hôte inconnu.";
            http_response_code(400);
            echo json_encode(["return" => "400", "message" => $message_error]);
            exit;
        }

        /**
         *  Suppression de l'hôte en BDD
         */
        $unregister = $myhost->unregister();

        if ($unregister === true) {
            $message_success[] = "L'hote a ete supprime.";
            http_response_code(201);
            echo json_encode(["return" => "201", "message" => $message_success]);
            exit;
        }

        if ($unregister == "2") {
            $message_error[] = "L'authentification a echoue.";
            http_response_code(400);
            echo json_encode(["return" => "400", "message" => $message_error]);
            exit;
        }
    } else {
        $message_error[] = "Erreur d'authentification.";
        http_response_code(400);
        echo json_encode(["return" => "400", "message_error" => $message_error]);
        exit;
    }

    exit;
}

/**
 *  Cas où on tente d'utiliser une autre méthode que DELETE
 */
$message_error[] = "La méthode n'est pas autorisée.";
http_response_code(405);
echo json_encode(["return" => "405", "message_error" => $message_error]);

exit(1);
