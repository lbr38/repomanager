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
         *  Demande d'un formulaire d'opération
         */
        if ($_POST['action'] == "getForm" and !empty($_POST['operationAction']) and !empty($_POST['repos_array'])) {
            $operation_action = \Controllers\Common::validateData($_POST['operationAction']);
            $repos_array = json_decode($_POST['repos_array'], true);

            $myop = new \Controllers\Operation();

            /**
             *  Récupération du formulaire de l'opération
             */
            try {
                $content = $myop->getForm($operation_action, $repos_array);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, $content);
        }

        /**
         *  Validation et exécution d'un formulaire d'opération
         */
        if ($_POST['action'] == "validateForm" and !empty($_POST['operation_params'])) {
            $operation_params = json_decode($_POST['operation_params'], true);

            $myop = new \Controllers\Operation();

            /**
             *  Vérification des paramètres de l'opération
             */
            try {
                $myop->validateForm($operation_params);
                $myop->execute($operation_params);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, 'Operation is running: <a href="run.php"><b>visualize</b></a>');
        }

        /**
         *  Suppression d'un environnement de repo
         */
        if ($_POST['action'] == "removeEnv" and !empty($_POST['repoId'] and !empty($_POST['snapId']) and !empty($_POST['envId']))) {
            $myrepo = new \Controllers\Repo();
            $myrepo->getAllById(\Controllers\Common::validateData($_POST['repoId']), \Controllers\Common::validateData($_POST['snapId']), \Controllers\Common::validateData($_POST['envId']));

            /**
             *  Vérification des paramètres de l'opération
             */
            try {
                $myrepo->removeEnv();
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, 'Environment has been deleted');
        }

        /**
         *  Relaunch an operation
         */
        if ($_POST['action'] == "relaunchOperation" and !empty($_POST['poolId'])) {
            $myop = new \Controllers\Operation();

            /**
             *  Execute operation
             */
            try {
                $myop->executeId($_POST['poolId']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, 'Operation has been relaunched using the same parameters.');
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
