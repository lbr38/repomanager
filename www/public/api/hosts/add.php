<?php
// https://github.com/NouvelleTechno/api-rest
// https://nouvelle-techno.fr/articles/live-coding-creer-une-api-rest
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

define("ROOT", dirname(__FILE__, 4));
require_once(ROOT.'/models/Autoloader.php');
Autoloader::loadFromApi();

/**
 *  Si il y a eu la moindre erreur ce chargement lors de l'autoload alors on quitte
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

    if (!empty($datas->ip) AND !empty($datas->hostname)) {

        /**
         *  Instanciation d'un objet Host
         */
        $myhost = new Host();
        $myhost->setIp($datas->ip);
        $myhost->setHostname($datas->hostname);
        $myhost->setFromApi();

        /**
         *  Enregistrement du nouvel hôte en BDD
         */
        $register = $myhost->api_register();
        
        /**
         *  Si l'enregistrement a été effectué, on retourne l'id et le token généré pour cet hôte
         */
        if ($register === true) {
            http_response_code(201);
            $authId = $myhost->getAuthId();
            $token  = $myhost->getToken();
            echo json_encode(["return" => "201", "message" => "L'enregistrement a été effectué.", "id" => "$authId", "token" => "$token"]);
            exit;
        }

        if ($register == "2") {
            http_response_code(400);
            echo json_encode(["return" => "400", "message" => "Impossible de déterminer l'adresse IP de l'hôte."]);
            exit;
        }

        if ($register == "3") {
            http_response_code(400);
            echo json_encode(["return" => "400", "message" => "Cet hôte est déjà enregistré."]);
            exit;
        }

        if ($register == "5") {
            http_response_code(400);
            echo json_encode(["return" => "400", "message" => "Le serveur n'a pas pu finaliser l'enregistrement."]);
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
 *  Cas où on tente d'utiliser une autre méthode que POST
 */
http_response_code(405);
echo json_encode(["return" => "405", "message" => "La méthode n'est pas autorisée."]);
exit;
?>