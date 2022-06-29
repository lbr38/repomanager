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
         *  Ajouter un nouveau repo source
         */
        if (
            $_POST['action'] == "addSource"
            and !empty($_POST['repoType'])
            and !empty($_POST['name'])
            and isset($_POST['urlType'])
            and !empty($_POST['url'])
            and isset($_POST['existingGpgKey'])
            and isset($_POST['gpgKeyURL'])
            and isset($_POST['gpgKeyText'])
        ) {
            $mysource = new \Models\Source();

            /**
             *  Tentative d'ajout du nouveau repo source
             */
            try {
                $mysource->new($_POST['repoType'], $_POST['name'], $_POST['urlType'], $_POST['url'], $_POST['existingGpgKey'], $_POST['gpgKeyURL'], $_POST['gpgKeyText']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le repo source <b>" . $_POST['name'] . "</b> a été créé");
        }

        /**
         *  Supprimer un repo source
         */
        if ($_POST['action'] == "deleteSource" and !empty($_POST['repoType']) and !empty($_POST['name'])) {
            $mysource = new \Models\Source();

            /**
             *  Tentative de suppression
             */
            try {
                $mysource->delete($_POST['repoType'], $_POST['name']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le repo source <b>" . $_POST['name'] . "</b> a été retiré");
        }

        /**
         *  Renommer un repo source
         */
        if ($_POST['action'] == "renameSource" and !empty($_POST['repoType']) and !empty($_POST['name']) and !empty($_POST['newname'])) {
            $mysource = new \Models\Source();

            /**
             *  Tentative de renommage du repo source
             */
            try {
                $mysource->rename($_POST['repoType'], $_POST['name'], $_POST['newname']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le repo source <b>" . $_POST['name'] . "</b> a été renommé en <b>" . $_POST['newname'] . "</b>");
        }

        /**
         *  Modifier l'url d'un repo source (repo source de type deb uniquement)
         */
        if ($_POST['action'] == "editSourceUrl" and !empty($_POST['name']) and !empty($_POST['url'])) {
            $mysource = new \Models\Source();

            /**
             *  Tentative de modification de l'url
             */
            try {
                $mysource->editUrl($_POST['name'], $_POST['url']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "L'URL du repo source <b>" . $_POST['name'] . "</b> a été modifiée");
        }

        /**
         *  Modifier la configuration d'un repo source (repo source de type rpm uniquement)
         */
        if ($_POST['action'] == "configureSource" and !empty($_POST['name']) and !empty($_POST['options_array']) and isset($_POST['comments'])) {
            $mysource = new \Models\Source();

            /**
             *  Tentative de configuration du repo source
             */
            try {
                $mysource->configureSource($_POST['name'], $_POST['options_array'], $_POST['comments']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "La configuration du repo source <b>" . $_POST['name'] . "</b> a été modifiée");
        }

        /**
         *  Supprimer une clé GPG
         */
        if ($_POST['action'] == "deleteGpgKey" and !empty($_POST['repoType']) and !empty($_POST['gpgkey'])) {
            $mysource = new \Models\Source();

            /**
             *  Tentative de suppression de la clé GPG
             */
            try {
                $mysource->removeGpgKey($_POST['repoType'], $_POST['gpgkey']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "La clé GPG <b>" . $_POST['gpgkey'] . "</b> a été supprimée");
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
