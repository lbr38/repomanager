<?php

define("ROOT", dirname(__FILE__, 4));

const HTTP_OK = 200;
const HTTP_BAD_REQUEST = 400;
const HTTP_METHOD_NOT_ALLOWED = 405;

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest") {
    require_once(ROOT . "/controllers/Autoloader.php");
    \Controllers\Autoloader::load();

    if (!empty($_POST['action'])) {
        /*
         *  Créer un nouveau groupe
         */
        if ($_POST['action'] == "newGroup" and !empty($_POST['name']) and !empty($_POST['type'])) {
            $mygroup = new \Controllers\Group($_POST['type']);

            /**
             *  Tentative de création du nouveau groupe
             */
            try {
                $mygroup->new($_POST['name']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le groupe <b>" . $_POST['name'] . "</b> a été créé");
        }

        /**
         *  Renommer un groupe
         */
        if ($_POST['action'] == "renameGroup" and !empty($_POST['name']) and !empty($_POST['newname']) and !empty($_POST['type'])) {
            $mygroup = new \Controllers\Group($_POST['type']);

            /**
             *  Tentative de renommage du groupe
             */
            try {
                $mygroup->rename($_POST['name'], $_POST['newname']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le groupe <b>" . $_POST['name'] . "</b> a été renommé en <b>" . $_POST['newname'] . "</b>");
        }

        /**
         *  Supprimer un groupe
         */
        if ($_POST['action'] == "deleteGroup" and !empty($_POST['name']) and !empty($_POST['type'])) {
            $mygroup = new \Controllers\Group($_POST['type']);

            /**
             *  Tentative de suppression du groupe
             */
            try {
                $mygroup->delete($_POST['name']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le groupe <b>" . $_POST['name'] . "</b> a été supprimé");
        }

        /**
         *  Ajouter / supprimer des repos d'un groupe
         */
        if ($_POST['action'] == "editGroupRepos" and !empty($_POST['name'])) {
            /**
             *  Si aucun repo n'a été transmis, cela signifie que l'utilisateur souhaite vider le groupe, on set $reposId à vide
             */
            if (empty($_POST['reposId'])) {
                $reposId = array();
            } else {
                $reposId = $_POST['reposId'];
            }

            $myrepo = new \Controllers\Repo('repo');

            /**
             *  Tentative d'ajout/suppression des repos dans le groupe
             */
            try {
                $myrepo->addReposIdToGroup($reposId, $_POST['name']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le groupe <b>" . $_POST['name'] . "</b> a été édité");
        }

        /**
         *  Ajouter / supprimer des hôtes d'un groupe
         */
        if ($_POST['action'] == "editGroupHosts" and !empty($_POST['name'])) {
            $groupName = $_POST['name'];

            /**
             *  Si aucun hote n'a été transmis, cela signifie que l'utilisateur souhaite vider le groupe, on set $hostsId à vide
             */
            if (empty($_POST['hostsId'])) {
                $hostsId = array();
            } else {
                $hostsId = $_POST['hostsId'];
            }

            $myhost = new \Controllers\Host();

            /**
             *  Tentative d'ajout/suppression des hôtes dans le groupe
             */
            try {
                $myhost->addHostsIdToGroup($hostsId, $groupName);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le groupe <b>" . $_POST['name'] . "</b> a été édité");
        }

        /**
         *  Si l'action ne correspond à aucune action valide
         */
        response(HTTP_BAD_REQUEST, 'Action invalide');
    }

    response(HTTP_BAD_REQUEST, 'Il manque un paramètre');
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
