<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

define("ROOT", dirname(__FILE__, 4));
require_once(ROOT . '/controllers/Autoloader.php');
\Controllers\Autoloader::api();

/**
 *  Cas où on a demandé le status du serveur, on renvoi 201
 */
if (isset($datas->status)) {
    http_response_code(201);
    exit;
}

/**
 *  Si il y a eu la moindre erreur de chargement lors de l'autoload alors on quitte
 */
if (__LOAD_GENERAL_ERROR != 0) {
    http_response_code(400);
    echo json_encode(["return" => "400", "message_error" => array("Reposerver configuration error. Please contact the administrator.")]);
    exit;
}

/**
 *  Return 400 if an update is running
 */
if (UPDATE_RUNNING == 'yes') {
    http_response_code(400);
    echo json_encode(["return" => "400", "message_error" => array("Reposerver is actually being updated. Please try again later.")]);
    exit;
}

/**
 *  Récupération de la configuration générale du serveur
 *  L'hôte n'a pas besoin de fournir un Id et token pour recevoir cette configuration
 */
if (!empty($datas->getConfiguration) and $datas->getConfiguration == 'server') {
    try {
        /**
         *  Instanciation d'un objet Profile
         */
        $myprofile = new \Controllers\Profile();
        $configuration = $myprofile->getServerConfiguration();
        echo json_encode(["return" => "201", "configuration" => $configuration]);
        exit;
    } catch (\Exception $e) {
        $message_error[] = "Error while retrieving server configuration.";
        http_response_code(400);
        echo json_encode(["return" => "400", "message_error" => $message_error]);
        exit;
    }
}

if (!empty($datas->id) and !empty($datas->token)) {
    /**
     *  Instanciation d'un objet Host
     */
    $myhost = new \Controllers\Host();
    $myhost->setAuthId($datas->id);
    $myhost->setToken($datas->token);
    // $myhost->setFromApi();

    /**
     *  2 arrays qui contiendront les messages de succès/erreur à renvoyer à l'hôte
     */
    $message_success = array();
    $message_error = array();

    /**
     *  D'abord on vérifie que l'ID et le token transmis sont valides
     */
    if (!$myhost->checkIdToken()) {
        $message_error[] = "Unknown host.";
        http_response_code(400);
        echo json_encode(["return" => "400", "message_error" => $message_error]);
        exit;
    }

    /**
     *  Si l'hôte a demandé de récupérer la configuration d'un profil
     */
    if (!empty($datas->profile)) {
        $profile = \Controllers\Common::validateData($datas->profile);

        /**
         *  Instanciation d'un objet Profile
         */
        $myprofile = new \Controllers\Profile();

        /**
         *  On vérifie que le profil spécifié existe
         */
        if (!$myprofile->exists($profile)) {
            $message_error[] = "Unknown profile $profile";
            http_response_code(400);
            echo json_encode(["return" => "400", "message_error" => $message_error]);
            exit;
        }

        /**
         *  Récupération de la configuration générale d'un profil
         */
        if (!empty($datas->getConfiguration) and $datas->getConfiguration == 'general') {
            try {
                $configuration = $myprofile->getProfileConfiguration($profile);
                echo json_encode(["return" => "201", "configuration" => $configuration]);
                exit;
            } catch (\Exception $e) {
                $message_error[] = "Error while retrieving profile configuration.";
                http_response_code(400);
                echo json_encode(["return" => "400", "message_error" => $message_error]);
                exit;
            }
        }

        /**
         *  Get profile packages configuration
         */
        if (!empty($datas->getConfiguration) and $datas->getConfiguration == 'packages') {
            try {
                $configuration = $myprofile->getProfilePackagesConfiguration($profile);
                echo json_encode(["return" => "201", "configuration" => $configuration]);
                exit;
            } catch (\Exception $e) {
                $message_error[] = "Error while retrieving profile packages configuration.";
                http_response_code(400);
                echo json_encode(["return" => "400", "message_error" => $message_error]);
                exit;
            }
        }

        /**
         *  Récupération de la liste des repos membres d'un profil
         */
        if (!empty($datas->getConfiguration) and $datas->getConfiguration == 'repos') {
            try {
                $configuration = $myprofile->getReposMembersList($profile);
                echo json_encode(["return" => "201", "configuration" => $configuration]);
                exit;
            } catch (\Exception $e) {
                $message_error[] = "Error while retrieving profile repos configuration.";
                http_response_code(400);
                echo json_encode(["return" => "400", "message_error" => $message_error]);
                exit;
            }
        }
    }

    /**
     *  Cas où aucun type de configuration à récupérer n'a été spécifié
     */
    $message_error[] = "Incomplete request";
    echo json_encode(["return" => "400", "message_error" => $message_error]);
    exit;

    /**
     *  Si il y a eu des messages d'erreur alors on retourne un code d'erreur 400, sinon 201
     */
    /**
     *  Cas où il y a eu des erreurs et des success (400)
     */
    if (!empty($message_error) and !empty($message_success)) {
        http_response_code(400);
        echo json_encode(["return" => "400", "message_success" => $message_success, "message_error" => $message_error]);
        exit;

    /**
     *  Cas où il y a eu des erreurs (400)
     */
    } else if (!empty($message_error)) {
        http_response_code(400);
        echo json_encode(["return" => "400", "message_error" => $message_error]);
        exit;

    /**
     *  Cas où il y a eu des success (201)
     */
    } else {
        http_response_code(201);
        echo json_encode(["return" => "201", "message_success" => $message_success]);
        exit;
    }
} else {
    $message_error[] = "Authentication error.";
    http_response_code(400);
    echo json_encode(["return" => "400", "message_error" => $message_error]);
    exit;
}

exit;
