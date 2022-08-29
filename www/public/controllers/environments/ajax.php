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
         *  Create a new environment
         */
        if ($_POST['action'] == 'newEnv' and !empty($_POST['name'])) {
            $myenv = new \Controllers\Environment();

            try {
                $myenv->new($_POST['name']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, 'Environment <b>' . $_POST['name'] . '</b> created');
        }

        /**
         *  Delete an environment
         */
        if ($_POST['action'] == 'deleteEnv' and !empty($_POST['name'])) {
            $myenv = new \Controllers\Environment();

            try {
                $myenv->delete($_POST['name']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, 'Environment <b>' . $_POST['name'] . '</b> has been deleted');
        }

        /**
         *  Rename / reorder environment(s)
         */
        if ($_POST['action'] == 'renameEnv' and !empty($_POST['envs'])) {
            $myenv = new \Controllers\Environment();

            try {
                $myenv->edit($_POST['envs']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, 'Environment changes taken into account');
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
