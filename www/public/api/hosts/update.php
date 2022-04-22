<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

define("ROOT", dirname(__FILE__, 4));
require_once(ROOT . '/controllers/Autoloader.php');
\Controllers\Autoloader::loadFromApi();

/**
 *  Si il y a eu la moindre erreur ce chargement lors de l'autoload alors on quitte
 */
if (__LOAD_GENERAL_ERROR != 0) {
    http_response_code(400);
    echo json_encode(["return" => "400", "message" => "Erreur de configuration sur le serveur Repomanager. Contactez l'administrateur du serveur."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
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
         *  2 arrays qui contiendront les messages de succès/erreur à renvoyer à l'hôte
         */
        $message_success = array();
        $message_error = array();

        /**
         *  D'abord on vérifie que l'ID et le token transmis sont valides
         */
        if (!$myhost->checkIdToken()) {
            $message_error[] = "Hôte inconnu.";
            http_response_code(400);
            echo json_encode(["return" => "400", "message_error" => $message_error]);
            exit;
        }

        /**
         *  Récupération de l'ID en BDD de l'hôte, il sera utile pour certaines opérations.
         */
        $myhost->setId($myhost->getIdByAuth());

        /**
         *  Si l'OS a été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->os)) {
            $myhost->setOS($datas->os);

            if ($myhost->updateOS()) {
                $message_success[] = "Mise à jour de l'OS effectuée.";
            } else {
                $message_error[] = "Mise à jour de l'OS échouée.";
            }
        }

        /**
         *  Si la version d'OS a été transmise alors on la met à jour en BDD
         */
        if (!empty($datas->os_version)) {
            $myhost->setOsVersion($datas->os_version);

            if ($myhost->updateOsVersion()) {
                $message_success[] = "Mise à jour de la version d'OS effectuée.";
            } else {
                $message_error[] = "Mise à jour de la version d'OS échouée.";
            }
        }

        /**
         *  Si la famille d'OS a été transmise alors on la met à jour en BDD
         */
        if (!empty($datas->os_family)) {
            $myhost->setOsFamily($datas->os_family);

            if ($myhost->updateOsFamily()) {
                $message_success[] = "Mise à jour de la famille d'OS effectuée.";
            } else {
                $message_error[] = "Mise à jour de la famille d'OS échouée.";
            }
        }

        /**
         *  Si le type a été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->type)) {
            $myhost->setType($datas->type);

            if ($myhost->updateType()) {
                $message_success[] = "Mise à jour du type effectuée.";
            } else {
                $message_error[] = "Mise à jour du type échouée.";
            }
        }

        /**
         *  Si le kernel a été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->kernel)) {
            $myhost->setKernel($datas->kernel);

            if ($myhost->updateKernel()) {
                $message_success[] = "Mise à jour du kernel effectuée.";
            } else {
                $message_error[] = "Mise à jour du kernel échouée.";
            }
        }

        /**
         *  Si l'architecture a été transmis alors on la met à jour en BDD
         */
        if (!empty($datas->arch)) {
            $myhost->setArch($datas->arch);

            if ($myhost->updateArch()) {
                $message_success[] = "Mise à jour de l'arch effectuée.";
            } else {
                $message_error[] = "Mise à jour de l'arch échouée.";
            }
        }

        /**
         *  Si le profil a été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->profile)) {
            $myhost->setProfile($datas->profile);

            if ($myhost->updateProfile()) {
                $message_success[] = "Mise à jour du profil effectuée.";
            } else {
                $message_error[] = "Mise à jour du profile échouée.";
            }
        }

        /**
         *  Si l'env a été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->env)) {
            $myhost->setEnv($datas->env);

            if ($myhost->updateEnv()) {
                $message_success[] = "Mise à jour de l'environnement effectuée.";
            } else {
                $message_error[] = "Mise à jour de l'environnement échouée.";
            }
        }

        /**
         *  Si les noms des paquets installés sur l'hôte (inventaire) ont été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->installed_packages)) {
            if ($myhost->setPackagesInventory($datas->installed_packages)) {
                $message_success[] = "Mise à jour des informations relatives aux paquets installés effectuée.";
            } else {
                $message_error[] = "Mise à jour des informations relatives aux paquets installés a échouée.";
            }
        }

        /**
         *  Si la liste des paquets disponibles pour mise à jour a été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->available_packages)) {
            if ($myhost->setPackagesAvailable($datas->available_packages)) {
                $message_success[] = "Mise à jour des informations relatives aux paquets disponibles effectuée.";
            } else {
                $message_error[] = "Mise à jour des informations relatives aux paquets disponibles échouée.";
            }
        }

        /**
         *  Ajout en base de données de l'historique des évènements passés sur l'hôte
         */
        if (!empty($datas->events)) {
            if ($myhost->setEventsFullHistory($datas->events) === true) {
                $message_success[] = "Mise à jour de l'historique effectuée.";
            } else {
                $message_error[] = "Mise à jour de l'historique a échouée.";
            }
        }

        /**
         *  Mise à jour du status d'une requête
         */
        if (!empty($datas->set_update_request_type) and !empty($datas->set_update_request_status)) {
            if ($myhost->setUpdateRequestStatus($datas->set_update_request_type, $datas->set_update_request_status) === false) {
                $message_error[] = "Impossible d'acquitter la demande auprès du serveur repomanager.";
            } else {
                $message_success[] = "L'acquittement a été pris en compte";
            }
        }

        /**
         *  Si il y a eu des messages d'erreur alors on retourne un code d'erreur 400, sinon 201
         */
        /**
         *  Cas où il y a eu des erreurs et des success (400)
         */
        if (!empty($message_error) and !empty($message_success)) {
            http_response_code(400);
            echo json_encode(["return" => "400", "message_success" => $message_success, "message_error" => $message_error]);
            exit;
        /**
         *  Cas où il y a eu des erreurs (400)
         */
        } else if (!empty($message_error)) {
            http_response_code(400);
            echo json_encode(["return" => "400", "message_error" => $message_error]);
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
        $message_error[] = "Erreur d'authentification.";
        http_response_code(400);
        echo json_encode(["return" => "400", "message_error" => $message_error]);
        exit;
    }

    exit;
}

/**
 *  Cas où on tente d'utiliser une autre méthode que PUT
 */
$message_error[] = "La méthode n'est pas autorisée.";
http_response_code(405);
echo json_encode(["return" => "405", "message_error" => $message_error]);

exit(1);
