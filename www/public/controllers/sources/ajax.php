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
         *  Add a new source repo
         */
        if (
            $_POST['action'] == "addSource"
            and !empty($_POST['repoType'])
            and !empty($_POST['name'])
            and !empty($_POST['url'])
            and isset($_POST['gpgKeyURL'])
            and isset($_POST['gpgKeyText'])
        ) {
            $mysource = new \Controllers\Source();

            try {
                $mysource->new($_POST['repoType'], $_POST['name'], $_POST['url'], $_POST['gpgKeyURL'], $_POST['gpgKeyText']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  If there was no error
             */
            response(HTTP_OK, 'Source repo <b>' . $_POST['name'] . '</b> has been added');
        }

        /**
         *  Delete a source repo
         */
        if ($_POST['action'] == "deleteSource" and !empty($_POST['sourceId'])) {
            $mysource = new \Controllers\Source();

            try {
                $mysource->delete($_POST['sourceId']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  If there was no error
             */
            response(HTTP_OK, "Source repo has been deleted");
        }

        /**
         *  Rename a source repo
         */
        if ($_POST['action'] == "renameSource" and !empty($_POST['type']) and !empty($_POST['name']) and !empty($_POST['newname'])) {
            $mysource = new \Controllers\Source();

            try {
                $mysource->rename($_POST['type'], $_POST['name'], $_POST['newname']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  If there was no error
             */
            response(HTTP_OK, "Source repo <b>" . $_POST['name'] . "</b> has been renamed to <b>" . $_POST['newname'] . "</b>");
        }

        /**
         *  Edit source repo URL
         */
        if ($_POST['action'] == "editSourceUrl" and !empty($_POST['type']) and !empty($_POST['name']) and !empty($_POST['url'])) {
            $mysource = new \Controllers\Source();

            try {
                $mysource->editUrl($_POST['type'], $_POST['name'], $_POST['url']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  If there was no error
             */
            response(HTTP_OK, "Source repo URL <b>" . $_POST['name'] . "</b> has been saved");
        }

        /**
         *  Edit source repo GPG key URL
         */
        if ($_POST['action'] == "editGpgKey" and !empty($_POST['sourceId']) and isset($_POST['gpgkey'])) {
            $mysource = new \Controllers\Source();

            try {
                $mysource->editGpgKey($_POST['sourceId'], $_POST['gpgkey']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  If there was no error
             */
            response(HTTP_OK, "GPG key URL has been saved");
        }

        /**
         *  Edit source repo SSL certificate path
         */
        if ($_POST['action'] == "editSslCertificatePath" and !empty($_POST['sourceId']) and isset($_POST['sslCertificatePath'])) {
            $mysource = new \Controllers\Source();

            try {
                $mysource->editSslCertificatePath($_POST['sourceId'], $_POST['sslCertificatePath']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  If there was no error
             */
            response(HTTP_OK, "SSL Certificate file path has been saved");
        }

        /**
         *  Edit source repo SSL private key path
         */
        if ($_POST['action'] == "editSslPrivateKeyPath" and !empty($_POST['sourceId']) and isset($_POST['sslPrivateKeyPath'])) {
            $mysource = new \Controllers\Source();

            try {
                $mysource->editSslPrivateKeyPath($_POST['sourceId'], $_POST['sslPrivateKeyPath']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  If there was no error
             */
            response(HTTP_OK, "SSL Private key file path has been saved");
        }

        /**
         *  Delete a GPG key
         */
        if ($_POST['action'] == "deleteGpgKey" and !empty($_POST['gpgKeyId'])) {
            $mysource = new \Controllers\Source();

            /**
             *  Tentative de suppression de la clé GPG
             */
            try {
                $mysource->deleteGpgKey($_POST['gpgKeyId']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  If there was no error
             */
            response(HTTP_OK, "GPG key <b>" . $_POST['gpgKeyId'] . "</b> has been deleted");
        }

        /**
         *  Import a new GPG key
         */
        if ($_POST['action'] == "importGpgKey" and !empty($_POST['gpgkey'])) {
            $mysource = new \Controllers\Source();

            try {
                $mysource->importGpgKey($_POST['gpgkey']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  If there was no error
             */
            response(HTTP_OK, "GPG key has been imported");
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
