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
         *  Créer une nouvelle planification
         */
        if ($_POST['action'] == "newPlan") {
            $myplan = new \Controllers\Planification();

            /**
             *  Tentative de création de la planification
             */
            try {
                $myplan->setAction($_POST['planAction']);
                if (!empty($_POST['day'])) {
                    $myplan->setDay($_POST['day']);
                }
                if (!empty($_POST['date'])) {
                    $myplan->setDate($_POST['date']);
                }
                if (!empty($_POST['time'])) {
                    $myplan->setTime($_POST['time']);
                }
                if (!empty($_POST['type'])) {
                    $myplan->setType($_POST['type']);
                }
                if (!empty($_POST['frequency'])) {
                    $myplan->setFrequency($_POST['frequency']);
                }
                if (!empty($_POST['mailRecipient'])) {
                    $myplan->setMailRecipient($_POST['mailRecipient']);
                }
                if (!empty($_POST['reminder'])) {
                    $myplan->setReminder($_POST['reminder']);
                }
                if (!empty($_POST['notificationOnError']) and $_POST['notificationOnError'] == "yes") {
                    $myplan->setNotification('on-error', 'yes');
                } else {
                    $myplan->setNotification('on-error', 'no');
                }
                if (!empty($_POST['notificationOnSuccess']) and $_POST['notificationOnSuccess'] == "yes") {
                    $myplan->setNotification('on-success', 'yes');
                } else {
                    $myplan->setNotification('on-success', 'no');
                }

                /**
                 *  Si l'action est 'update' alors on récupère les paramètres concernant GPG
                 */
                if ($_POST['planAction'] == 'update') {
                    if (!empty($_POST['gpgCheck']) and $_POST['gpgCheck'] == "yes") {
                        $myplan->setTargetGpgCheck('yes');
                    } else {
                        $myplan->setTargetGpgCheck('no');
                    }

                    if (!empty($_POST['gpgResign']) and $_POST['gpgResign'] == "yes") {
                        $myplan->setTargetGpgResign('yes');
                    } else {
                        $myplan->setTargetGpgResign('no');
                    }
                }

                /**
                 *  Cas où c'est un repo seul
                 */
                if (!empty($_POST['snapId'])) {
                    $myplan->setSnapId($_POST['snapId']);
                }

                /**
                 *  Cas où c'est un groupe
                 */
                if (!empty($_POST['groupId'])) {
                    $myplan->setGroupId($_POST['groupId']);
                }

                /**
                 *  Cas où un environnement cible est spécifié
                 */
                if (!empty($_POST['targetEnv'])) {
                    $myplan->setTargetEnv($_POST['targetEnv']);
                }

                /**
                 *  Création de la nouvelle planification
                 */
                $myplan->new();
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "La planification a été créée");
        }

        /**
         *  Supprimer une planification
         */
        if ($_POST['action'] == "deletePlan" and !empty($_POST['id'])) {
            $myplan = new \Controllers\Planification();

            /**
             *  Tentative de suppression du groupe
             */
            try {
                $myplan->remove($_POST['id']);
            } catch (\Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "La planification a été supprimée");
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
