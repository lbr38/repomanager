<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// On vérifie que la méthode utilisée est correcte
if($_SERVER['REQUEST_METHOD'] == 'DELETE'){
    include_once('../../class/Database-servers.php');

    // On instancie la base de données
    $database = new Database_servers();
    $db = $database->getConnection();

    // On récupère l'id du produit
    $donnees = json_decode(file_get_contents("php://input"));

    /*if(!empty($donnees->id)){
        $produit->id = $donnees->id;

        if($produit->supprimer()){
            // Ici la suppression a fonctionné
            // On envoie un code 200
            http_response_code(200);
            echo json_encode(["message" => "La suppression a été effectuée"]);
        }else{
            // Ici la création n'a pas fonctionné
            // On envoie un code 503
            http_response_code(503);
            echo json_encode(["message" => "La suppression n'a pas été effectuée"]);         
        }
    }*/
}

// On gère l'erreur
http_response_code(405);
echo json_encode(["message" => "La méthode n'est pas autorisée"]);

exit(1);
?>