<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

define("ROOT", dirname(__FILE__, 3));
require_once(ROOT.'/models/Autoloader.php');
require_once(ROOT.'/functions/common-functions.php');
Autoloader::loadFromApi();

/**
 *  Si il y a eu la moindre erreur ce chargement lors de l'autoload alors on quitte
 */
if (__LOAD_GENERAL_ERROR != 0) {
    http_response_code(503);
    echo json_encode(["return" => "503", "message" => "Erreur de configuration sur le serveur Repomanager. Contactez l'administrateur du serveur."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {

    /**
     *  On récupère les informations transmises
     */
    $datas = json_decode(file_get_contents("php://input"));

    if (!empty($datas->id) AND !empty($datas->token)) {

        /**
         *  Instanciation d'un objet Host
         */
        $myhost = new Host();
        $myhost->setAuthId($datas->id);
        $myhost->setToken($datas->token);
        $myhost->setFromApi();

        /**
         *  D'abord on vérifie que l'ID et le token transmis sont valides
         */
        if (!$myhost->checkIdToken()) {
            http_response_code(503);
            echo json_encode(["return" => "503", "message" => "L'authentification a échouée."]);
            exit;
        }

        /**
         *  Suppression de l'hôte en BDD
         */
        $unregister = $myhost->unregister();

        if ($unregister === true) {
            http_response_code(201);
            echo json_encode(["return" => "201", "message" => "L'hôte a été supprimé."]);
            exit;
        }

        if ($unregister == "2") {
            http_response_code(503);
            echo json_encode(["return" => "503", "message" => "L'authentification a échouée."]);
            exit;
        }

    } else {
        http_response_code(400);
        echo json_encode(["return" => "400", "message" => "Les données transmises sont invalides."]);
        exit;
    }

    exit;
}

/**
 *  Cas où on tente d'utiliser une autre méthode que DELETE
 */
http_response_code(405);
echo json_encode(["return" => "405", "message" => "La méthode n'est pas autorisée."]);

exit(1);
?>