<?php

define("ROOT", dirname(__FILE__, 4));

const HTTP_OK = 200;
const HTTP_BAD_REQUEST = 400;
const HTTP_METHOD_NOT_ALLOWED = 405;

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest") {
    require_once(ROOT . "/controllers/Autoloader.php");
    \Controllers\Autoloader::load();

    if (!empty($_POST['action'])) {
        /**
         *  Get all hosts that have the specified kernel
         */
        if ($_POST['action'] == "getHostWithKernel" and !empty($_POST['kernel'])) {
            $myhost = new \Controllers\Host();

            /**
             *  Try to get data
             */
            try {
                $content = json_encode($myhost->getHostWithKernel($_POST['kernel']));
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Return data if there was no error
             */
            response(HTTP_OK, $content);
        }

        /**
         *  Get all hosts that have the specified profile
         */
        if ($_POST['action'] == "getHostWithProfile" and !empty($_POST['profile'])) {
            $myhost = new \Controllers\Host();

            /**
             *  Try to get data
             */
            try {
                $content = json_encode($myhost->getHostWithProfile($_POST['profile']));
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Return data if there was no error
             */
            response(HTTP_OK, $content);
        }

        /**
         *  Rechercher si un paquet est présent sur un hôte (depuis la liste de tous les hôtes sur hosts.php)
         */
        if ($_POST['action'] == "searchHostPackage" and !empty($_POST['hostid']) and !empty($_POST['package'])) {
            $hostid  = \Controllers\Common::validateData($_POST['hostid']);
            $package = \Controllers\Common::validateData($_POST['package']);

            $myhost = new \Controllers\Host();

            /**
             *  Tentative de recherche du paquet
             */
            try {
                $result = $myhost->searchPackage($hostid, $package);
                /**
                 *  Si aucun paquet n'est trouvé
                 */
                if ($result === false) {
                    response(HTTP_BAD_REQUEST, '');
                    exit();
                }
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur, on renvoie $result qui contient la version du paquet trouvée
             */
            response(HTTP_OK, $result);
        }

        /*
         *  Exécuter une action sur le(s) hôte(s) sélectionné(s)
         */
        if ($_POST['action'] == "hostExecAction" and !empty($_POST['exec']) and !empty($_POST['hosts_array'])) {
            $myhost = new \Controllers\Host();

            /**
             *  Tentative d'exécution de l'action
             */
            try {
                $content = $myhost->hostExec($_POST['hosts_array'], $_POST['exec']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur, on renvoie la liste des hôtes qui ont été traités
             */
            response(HTTP_OK, $content);
        }

        /**
         *  Récupérer l'historique d'un paquet
         */
        if ($_POST['action'] == "getPackageTimeline" and !empty($_POST['hostid']) and !empty($_POST['packagename'])) {
            $myhost = new \Controllers\Host();

            /**
             *  Tentative de récupération de l'historique du paquet
             */
            try {
                $content = $myhost->getPackageTimeline($_POST['hostid'], $_POST['packagename']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur, on renvoie l'historique
             */
            response(HTTP_OK, $content);
        }

        /**
         *  Récupérer les détails d'un évènement au survol de la souris (afficher les paquets installé, mis à jour...)
         */
        if ($_POST['action'] == "getEventDetails" and !empty($_POST['hostId']) and !empty($_POST['eventId']) and !empty($_POST['packageState'])) {
            $myhost = new \Controllers\Host();

            /**
             *  Tentative de récupération des informations
             */
            try {
                $content = $myhost->getEventDetails($_POST['hostId'], $_POST['eventId'], $_POST['packageState']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur, on renvoie les informations récupérées
             */
            response(HTTP_OK, $content);
        }

        /**
         *  Si l'action ne correspond à aucune action valide
         */
        response(HTTP_BAD_REQUEST, 'Invalid action.');
    }

    response(HTTP_BAD_REQUEST, 'Missing parameter.');
} else {
    response(HTTP_METHOD_NOT_ALLOWED, 'Method not allowed');
}

function response($response_code, $message)
{
    header('Content-Type: application/json');
    http_response_code($response_code);

    $response = [
        "response_code" => $response_code,
        "message" => $message
    ];

    echo json_encode($response);

    exit;
}
