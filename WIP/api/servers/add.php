<?php
// https://github.com/NouvelleTechno/api-rest
// https://nouvelle-techno.fr/articles/live-coding-creer-une-api-rest
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    include_once('../../class/Servers.php');

    /**
     *  Instanciation d'un objet Server
     */
    $myserver = new Server();

    /**
     *  On récupère les informations envoyées
     */
    $donnees = json_decode(file_get_contents("php://input"));

    if (!empty($donnees->ip)) {
        $myserver->ip = $donnees->ip;

        if ($myserver->register()) {
            http_response_code(201);
            echo json_encode(["message" => "L'enregistrement a été effectué"]);
            exit(0);
        }

        http_response_code(503);
        echo json_encode(["message" => "L'enregistrement a échoué"]);
        exit(1);
    }
}

// On gère l'erreur
http_response_code(405);
echo json_encode(["message" => "La méthode n'est pas autorisée"]);

exit(1);
?>