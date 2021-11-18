<?php

const HTTP_OK = 200;
const HTTP_BAD_REQUEST = 400;
const HTTP_METHOD_NOT_ALLOWED = 405;

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest"){

    $WWW_DIR = dirname(__FILE__, 2);
    require_once("$WWW_DIR/functions/load_common_variables.php");
    require_once("$WWW_DIR/models/Repo.php");
    require_once("$WWW_DIR/models/Group.php");
    require_once("$WWW_DIR/models/Host.php");

    if (!empty($_POST['action'])) {
        /**
         *  
         *  Actions relatives aux repos
         * 
         * 
         *  Modifier la description d'un repo
         */
        if ($_POST['action'] == "setRepoDescription" AND !empty($_POST['id']) AND !empty($_POST['status']) AND isset($_POST['description'])) {
            $myrepo = new Repo();
            $myrepo->setId($_POST['id']);
            $myrepo->setStatus($_POST['status']);

            /**
             *  Tentative de modification de la description
             */
            try {
                $myrepo->db_setDescription($_POST['description']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "La description a été modifiée");
        }

        /**
         *  
         *  Actions relatives aux groupes
         * 
         * 
         *  Créer un nouveau groupe
         */
        if ($_POST['action'] == "newGroup" AND !empty($_POST['name'])) {
            $mygroup = new Group();

            /**
             *  Tentative de création du nouveau groupe
             */
            try {
                $mygroup->new($_POST['name']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le groupe <b>".$_POST['name']."</b> a été créé");
        }

        /**
         *  Renommer un groupe
         */
        if ($_POST['action'] == "renameGroup" AND !empty($_POST['name']) AND !empty($_POST['newname'])) {
            $mygroup = new Group();

            /**
             *  Tentative de renommage du groupe
             */
            try {
                $mygroup->rename($_POST['name'], $_POST['newname']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le groupe <b>".$_POST['name']."</b> a été renommé en <b>".$_POST['newname']."</b>");
        }

        /**
         *  Supprimer un groupe
         */
        if ($_POST['action'] == "deleteGroup" AND !empty($_POST['name'])) {
            $mygroup = new Group();

            /**
             *  Tentative de suppression du groupe
             */
            try {
                $mygroup->delete($_POST['name']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le groupe <b>".$_POST['name']."</b> a été supprimé");
        }

        /**
         *  Ajouter / supprimer des repos d'un groupe
         */
        if ($_POST['action'] == "editGroupRepos" AND !empty($_POST['name'])) {
            /**
             *  Si aucun repo n'a été transmis, cela signifie que l'utilisateur souhaite vider le groupe, on set $reposList à vide
             */
            if (empty($_POST['reposList'])) {
                $reposList = '';
            } else {
                $reposList = $_POST['reposList'];
            }

            $mygroup = new Group();

            /**
             *  Tentative d'ajout/suppression des repos dans le groupe
             */
            try {
                $mygroup->addRepo($_POST['name'], $reposList);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le groupe <b>".$_POST['name']."</b> a été édité");
        }

        /**
         *  
         *  Actions relatives aux hôtes
         * 
         * 
         *  Récupérer l'historique d'un paquet
         */
        if ($_POST['action'] == "getPackageTimeline" AND !empty($_POST['hostid']) AND !empty($_POST['packagename'])) {
            $myhost = new Host();
            $myhost->setId($_POST['hostid']);

            /**
             *  Tentative de récupération de l'historique du paquet
             */
            try {
                $content = $myhost->getPackageTimeline($_POST['packagename']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur, on renvoie l'historique
             */
            response(HTTP_OK, $content);
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
?>