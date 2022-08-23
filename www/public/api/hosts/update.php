<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

define("ROOT", dirname(__FILE__, 4));
require_once(ROOT . '/controllers/Autoloader.php');
\Controllers\Autoloader::api();

/**
 *  Si il y a eu la moindre erreur ce chargement lors de l'autoload alors on quitte
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
            http_response_code(400);
            echo json_encode(["return" => "400", "message_error" => "Unknown host."]);
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
                $message_success[] = "OS update taken into account.";
            } else {
                $message_error[] = "OS update has failed.";
            }
        }

        /**
         *  Si la version d'OS a été transmise alors on la met à jour en BDD
         */
        if (!empty($datas->os_version)) {
            $myhost->setOsVersion($datas->os_version);

            if ($myhost->updateOsVersion()) {
                $message_success[] = "OS version update taken into account.";
            } else {
                $message_error[] = "OS version update has failed.";
            }
        }

        /**
         *  Si la famille d'OS a été transmise alors on la met à jour en BDD
         */
        if (!empty($datas->os_family)) {
            $myhost->setOsFamily($datas->os_family);

            if ($myhost->updateOsFamily()) {
                $message_success[] = "OS family update taken into account.";
            } else {
                $message_error[] = "OS family update has failed.";
            }
        }

        /**
         *  Si le type a été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->type)) {
            $myhost->setType($datas->type);

            if ($myhost->updateType()) {
                $message_success[] = "Virtualization type update taken into account.";
            } else {
                $message_error[] = "Virtualization type update has failed.";
            }
        }

        /**
         *  Si le kernel a été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->kernel)) {
            $myhost->setKernel($datas->kernel);

            if ($myhost->updateKernel()) {
                $message_success[] = "Kernel update taken into account.";
            } else {
                $message_error[] = "Kernel update has failed.";
            }
        }

        /**
         *  Si l'architecture a été transmis alors on la met à jour en BDD
         */
        if (!empty($datas->arch)) {
            $myhost->setArch($datas->arch);

            if ($myhost->updateArch()) {
                $message_success[] = "Arch update taken into account.";
            } else {
                $message_error[] = "Arch update has failed.";
            }
        }

        /**
         *  Si le profil a été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->profile)) {
            $myhost->setProfile($datas->profile);

            if ($myhost->updateProfile()) {
                $message_success[] = "Profile update taken into account.";
            } else {
                $message_error[] = "Profile update has failed.";
            }
        }

        /**
         *  Si l'env a été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->env)) {
            $myhost->setEnv($datas->env);

            if ($myhost->updateEnv()) {
                $message_success[] = "Environment update taken into account.";
            } else {
                $message_error[] = "Environment update has failed.";
            }
        }

        /**
         *  Si le status de l'agent a été transmis
         */
        if (!empty($datas->agent_status)) {
            try {
                $myhost->setAgentStatus($datas->agent_status);
                $message_success[] = "Agent status taken into account.";
            } catch (\Exception $e) {
                $message_error[] = $e->getMessage();
            }
        }

        /**
         *  Si les noms des paquets installés sur l'hôte (inventaire) ont été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->installed_packages)) {
            if ($myhost->setPackagesInventory($datas->installed_packages)) {
                $message_success[] = "Packages informations update taken into account.";
            } else {
                $message_error[] = "Packages informations update has failed.";
            }
        }

        /**
         *  Si la liste des paquets disponibles pour mise à jour a été transmis alors on le met à jour en BDD
         */
        if (!empty($datas->available_packages)) {
            if ($myhost->setPackagesAvailable($datas->available_packages)) {
                $message_success[] = "Available packages informations update taken into account.";
            } else {
                $message_error[] = "Available packages informations update has failed.";
            }
        }

        /**
         *  Ajout en base de données de l'historique des évènements passés sur l'hôte
         */
        if (!empty($datas->events)) {
            if ($myhost->setEventsFullHistory($datas->events) === true) {
                $message_success[] = "Package history update taken into account.";
            } else {
                $message_error[] = "Package history update has failed.";
            }
        }

        /**
         *  Mise à jour du status d'une requête
         */
        if (!empty($datas->set_update_request_type) and !empty($datas->set_update_request_status)) {
            if ($myhost->setUpdateRequestStatus($datas->set_update_request_type, $datas->set_update_request_status) === false) {
                $message_error[] = "Unable to acknowledge the request to the reposerver.";
            } else {
                $message_success[] = "Acknowledge has been taken into account.";
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
        $message_error[] = "Authentication error.";
        http_response_code(400);
        echo json_encode(["return" => "400", "message_error" => $message_error]);
        exit;
    }

    exit;
}

/**
 *  Cas où on tente d'utiliser une autre méthode que PUT
 */
$message_error[] = "Method not allowed.";
http_response_code(405);
echo json_encode(["return" => "405", "message_error" => $message_error]);

exit(1);
