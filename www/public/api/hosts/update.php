<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

define("ROOT", dirname(__FILE__, 4));
require_once(ROOT.'/models/Autoloader.php');
Autoloader::loadFromApi();

/**
 *  Si il y a eu la moindre erreur ce chargement lors de l'autoload alors on quitte
 */
if (__LOAD_GENERAL_ERROR != 0) {
    http_response_code(503);
    echo json_encode(["return" => "503", "message" => "Erreur de configuration sur le serveur Repomanager. Contactez l'administrateur du serveur."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
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
         *  2 arrays qui contiendront les messages de succès/erreur à renvoyer à l'hôte
         */
        $message_success = array();
        $message_error = array();

        /**
         *  D'abord on vérifie que l'ID et le token transmis sont valides
         */
        if (!$myhost->checkIdToken()) {
            $message_error[] = "L'authentification a échouée.";
            http_response_code(503);
            echo json_encode(["return" => "503", "message_error" => $message_error]);
            exit;
        }

        /**
         *  Récupération de l'ID en BDD de l'hôte, il sera utile pour certaines opérations.
         */
        $myhost->db_getId();

        /**
         *  Si l'OS a été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->os)) {
            $myhost->setOS($datas->os);

            if ($myhost->db_updateOS())
                $message_success[] = "Mise à jour de l'OS effectuée.";
            else
                $message_error[] = "Mise à jour de l'OS échouée.";
        }

        /**
         *  Si la version d'OS a été transmise alors on la met à jour en BDD
         */
        if (!empty($datas->os_version)) {
            $myhost->setOS_version($datas->os_version);

            if ($myhost->db_updateOS_version())
                $message_success[] = "Mise à jour de la version d'OS effectuée.";
            else
                $message_error[] = "Mise à jour de la version d'OS échouée.";
        }

        /**
         *  Si le profil a été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->profile)) {
            $myhost->setProfile($datas->profile);

            if ($myhost->db_updateProfile())
                $message_success[] = "Mise à jour du profil effectuée.";
            else
                $message_error[] = "Mise à jour du profile échouée.";
        }

        /**
         *  Si l'env a été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->env)) {
            $myhost->setEnv($datas->env);

            if ($myhost->db_updateEnv())
                $message_success[] = "Mise à jour de l'environnement effectuée.";
            else
                $message_error[] = "Mise à jour de l'environnement échouée.";
        }

        /**
         *  Si les noms des paquets installés sur l'hôte (inventaire) ont été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->packages_installed)) {

            if ($myhost->db_setPackagesInventory($datas->packages_installed))
                $message_success[] = "Mise à jour des informations relatives aux paquets installés effectuée.";
            else
                $message_error[] = "Mise à jour des informations relatives aux paquets installés a échouée.";
        }

        /**
         *  Si la liste des paquets disponibles pour mise à jour a été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->available_packages)) {
            
            if ($myhost->db_setPackagesAvailable($datas->available_packages))
                $message_success[] = "Mise à jour des informations relatives aux paquets disponibles effectuée.";
            else
                $message_error[] = "Mise à jour des informations relatives aux paquets disponibles échouée.";
        }

        /**
         *  Ajout en base de données de l'historique des évènements passés sur l'hôte
         */
        if (!empty($datas->events)) {
            if ($myhost->setEventsFullHistory($datas->events) === true)
                $message_success[] = "Mise à jour de l'historique effectuée.";
            else
                $message_error[] = "Mise à jour de l'historique a échouée.";
        }

        /**
         *  Mise à jour du status d'une requête 
         */
        if (!empty($datas->set_update_request_type) AND !empty($datas->set_update_request_status)) {
            if ($myhost->api_setUpdateRequestStatus($datas->set_update_request_type, $datas->set_update_request_status) === false) {
                $message_error[] = "Impossible d'acquitter la demande auprès du serveur repomanager.";
            }
        }

        /**
         *  Si il y a eu des messages d'erreur alors on retourne un code d'erreur 503, sinon 201
         */
        /**
         *  Cas où il y a eu des erreurs et des success (503)
         */
        if (!empty($message_error) AND !empty($message_success)) {
            http_response_code(503);
            echo json_encode(["return" => "503", "message_success" => $message_success, "message_error" => $message_error]);
            exit;
        /**
         *  Cas où il y a eu des erreurs (503)
         */
        } else if (!empty($message_error)) {
            http_response_code(503);
            echo json_encode(["return" => "503", "message_error" => $message_error]);
            exit;
        /**
         *  Cas où il y a eu des success (201)
         */
        } else {
            http_response_code(201);
            echo json_encode(["return" => "201", "message_success" => $message_success]);
            exit;
        }
 
    } else {
        http_response_code(400);
        $message_error[] = "Les données transmises sont invalides.";
        echo json_encode(["return" => "400", "message_error" => $message_error]);
        exit;
    }

    exit;
}

/**
 *  Cas où on tente d'utiliser une autre méthode que PUT
 */
http_response_code(405);
$message_error[] = "La méthode n'est pas autorisée.";
echo json_encode(["return" => "405", "message_error" => $message_error]);

exit(1);
?>