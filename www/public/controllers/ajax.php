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
                \Controllers\Common::configureReposListDisplay($_POST['printRepoSize'], $_POST['printRepoType'], $_POST['printRepoSignature'], $_POST['cacheReposList']);
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
