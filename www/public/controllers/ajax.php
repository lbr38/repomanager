<?php

define("ROOT", dirname(__FILE__, 3));

const HTTP_OK = 200;
const HTTP_BAD_REQUEST = 400;
const HTTP_METHOD_NOT_ALLOWED = 405;

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest") {
    require_once(ROOT . "/controllers/Autoloader.php");
    \Controllers\Autoloader::load();

    if (!empty($_POST['action'])) {
        /**
         *
         *  Actions relatives aux repos
         *
         *
         *  Modifier la description d'un repo
         */
        if ($_POST['action'] == "setRepoDescription" and !empty($_POST['envId']) and isset($_POST['description'])) {
            $myrepo = new \Controllers\Repo();

            /**
             *  Tentative de modification de la description
             */
            try {
                $myrepo->envSetDescription($_POST['envId'], $_POST['description']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "La description a été modifiée");
        }

        /**
         *
         *  Actions relatives aux sources
         *
         *
         *  Créer un nouveau repo source
         */
        if (
            $_POST['action'] == "addSource"
            and !empty($_POST['name'])
            and isset($_POST['urlType'])
            and !empty($_POST['url'])
            and isset($_POST['existingGpgKey'])
            and isset($_POST['gpgKeyURL'])
            and isset($_POST['gpgKeyText'])
        ) {
            $mysource = new \Models\Source();

            /**
             *  Tentative de création du nouveau profil
             */
            try {
                $mysource->new($_POST['name'], $_POST['urlType'], $_POST['url'], $_POST['existingGpgKey'], $_POST['gpgKeyURL'], $_POST['gpgKeyText']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le repo source <b>" . $_POST['name'] . "</b> a été créé");
        }

        /**
         *  Supprimer une source
         */
        if ($_POST['action'] == "deleteSource" and !empty($_POST['name'])) {
            $mysource = new \Models\Source();

            /**
             *  Tentative de suppression d'une source
             */
            try {
                $mysource->delete($_POST['name']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le repo source <b>" . $_POST['name'] . "</b> a été retiré");
        }

        /**
         *  Renommer une source
         */
        if ($_POST['action'] == "renameSource" and !empty($_POST['name']) and !empty($_POST['newname'])) {
            $mysource = new \Models\Source();

            /**
             *  Tentative de renommage du repo source
             */
            try {
                $mysource->rename($_POST['name'], $_POST['newname']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le repo source <b>" . $_POST['name'] . "</b> a été renommé en <b>" . $_POST['newname'] . "</b>");
        }

        /**
         *  Modifier l'url d'un repo source (Debian uniquement)
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
         *  Modifier la configuration d'un repo source (Redhat-CentOS uniquement)
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
        if ($_POST['action'] == "deleteGpgKey" and !empty($_POST['gpgkey'])) {
            $mysource = new \Models\Source();

            /**
             *  Tentative de suppression de la clé GPG
             */
            try {
                $mysource->removeGpgKey($_POST['gpgkey']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "La clé GPG <b>" . $_POST['gpgkey'] . "</b> a été supprimée");
        }

        /**
         *  Modifier les paramètres d'affichage de la liste des repos
         */
        if (
            $_POST['action'] == "configureReposListDisplay"
            and !empty($_POST['printRepoSize'])
            and !empty($_POST['printRepoType'])
            and !empty($_POST['printRepoSignature'])
            and !empty($_POST['cacheReposList'])
        ) {

            /**
             *  Tentative de modification des paramètres d'affichage
             */
            try {
                \Models\Common::configureReposListDisplay($_POST['printRepoSize'], $_POST['printRepoType'], $_POST['printRepoSignature'], $_POST['cacheReposList']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Les paramètres d'affichage ont été pris en compte");
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
