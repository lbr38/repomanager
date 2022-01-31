<?php
define("ROOT", dirname(__FILE__, 2));

const HTTP_OK = 200;
const HTTP_BAD_REQUEST = 400;
const HTTP_METHOD_NOT_ALLOWED = 405;

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest"){

    require_once(ROOT."/models/Autoloader.php");
    Autoloader::load();
    require_once(ROOT."/functions/common-functions.php");

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
         *  Actions relatives aux profils
         * 
         * 
         *  Créer un nouveau profil
         */
        if ($_POST['action'] == "newProfile" AND !empty($_POST['name'])) {
            $myprofile = new Profile();

            /**
             *  Tentative de création du nouveau profil
             */
            try {
                $myprofile->new($_POST['name']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le profil <b>".$_POST['name']."</b> a été créé");
        }

        /**
         *  Supprimer un profil
         */
        if ($_POST['action'] == "deleteProfile" AND !empty($_POST['name'])) {
            $myprofile = new Profile();

            /**
             *  Tentative de suppression du profil
             */
            try {
                $myprofile->delete($_POST['name']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le profil <b>".$_POST['name']."</b> a été supprimé");
        }

        /**
         *  Renommer un profil
         */
        if ($_POST['action'] == "renameProfile" AND !empty($_POST['name']) AND !empty($_POST['newname'])) {
            $myprofile = new Profile();

            /**
             *  Tentative de renommage du profil
             */
            try {
                $myprofile->rename($_POST['name'], $_POST['newname']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le profil <b>".$_POST['name']."</b> a été renommé en <b>".$_POST['newname']."</b>");
        }

        /**
         *  Dupliquer un profil
         */
        if ($_POST['action'] == "duplicateProfile" AND !empty($_POST['name'])) {
            $myprofile = new Profile();

            /**
             *  Tentative de renommage du profil
             */
            try {
                $myprofile->duplicate($_POST['name']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le profil <b>".$_POST['name']."</b> a été dupliqué");
        }

        /**
         *  Configurer un profil
         */
        if ($_POST['action'] == "configureProfile" AND !empty($_POST['name']) AND !empty($_POST['keepCron']) AND !empty($_POST['allowOverwrite']) AND !empty($_POST['allowReposFilesOverwrite'])) {

            $keepCron = $_POST['keepCron'];
            $allowOverwrite = $_POST['allowOverwrite'];
            $allowReposFilesOverwrite = $_POST['allowReposFilesOverwrite'];

            /**
             *  Si aucun repo n'a été transmis, cela signifie que l'utilisateur souhaite vider la liste, on set $reposList à vide
             */
            if (empty($_POST['reposList'])) {
                $reposList = '';
            } else {
                $reposList = $_POST['reposList'];
            }

            /**
             *  Si aucun paquet 'majeur' n'a été transmis, cela signifie que l'utilisateur souhaite vider la liste, on set $packagesMajorExcluded à vide
             */
            if (empty($_POST['packagesMajorExcluded'])) {
                $packagesMajorExcluded = '';
            } else {
                $packagesMajorExcluded = $_POST['packagesMajorExcluded'];
            }

            /**
             *  Si aucun paquet 'toute version' n'a été transmis, cela signifie que l'utilisateur souhaite vider la liste, on set $packagesExcluded à vide
             */
            if (empty($_POST['packagesExcluded'])) {
                $packagesExcluded = '';
            } else {
                $packagesExcluded = $_POST['packagesExcluded'];
            }

            /**
             *  Si aucun service 'à redémarrer' n'a été transmis, cela signifie que l'utilisateur souhaite vider la liste, on set $packagesExcluded à vide
             */
            if (empty($_POST['serviceNeedRestart'])) {
                $serviceNeedRestart = '';
            } else {
                $serviceNeedRestart = $_POST['serviceNeedRestart'];
            }

            $myprofile = new Profile();

            /**
             *  Tentative de configuration du profil
             */
            try {
                $myprofile->configure($_POST['name'], $reposList, $packagesMajorExcluded, $packagesExcluded, $serviceNeedRestart, $keepCron, $allowOverwrite, $allowReposFilesOverwrite);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Configuration du profil <b>".$_POST['name']."</b> enregistrée");
        }

        /**
         *  
         *  Actions relatives aux sources
         * 
         * 
         *  Créer un nouveau repo source
         */
        if ($_POST['action'] == "addSource"
            AND !empty($_POST['name'])
            AND isset($_POST['urlType'])
            AND !empty($_POST['url'])
            AND isset($_POST['existingGpgKey'])
            AND isset($_POST['gpgKeyURL'])
            AND isset($_POST['gpgKeyText'])) {

            $mysource = new Source();

            /**
             *  Tentative de création du nouveau profil
             */
            try {
                $mysource->new($_POST['name'], $_POST['urlType'], $_POST['url'], $_POST['existingGpgKey'], $_POST['gpgKeyURL'], $_POST['gpgKeyText']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le repo source <b>".$_POST['name']."</b> a été créé");
        }

        /**
         *  Supprimer une source
         */
        if ($_POST['action'] == "deleteSource" AND !empty($_POST['name'])) {
            $mysource = new Source();

            /**
             *  Tentative de suppression d'une source
             */
            try {
                $mysource->delete($_POST['name']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le repo source <b>".$_POST['name']."</b> a été retiré");
        }

        /**
         *  Renommer une source
         */
        if ($_POST['action'] == "renameSource" AND !empty($_POST['name']) AND !empty($_POST['newname'])) {
            $mysource = new Source();

            /**
             *  Tentative de renommage du repo source
             */
            try {
                $mysource->rename($_POST['name'], $_POST['newname']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "Le repo source <b>".$_POST['name']."</b> a été renommé en <b>".$_POST['newname']."</b>");
        }

        /**
         *  Modifier l'url d'un repo source (Debian uniquement)
         */
        if ($_POST['action'] == "editSourceUrl" AND !empty($_POST['name']) AND !empty($_POST['url'])) {
            $mysource = new Source();

            /**
             *  Tentative de modification de l'url
             */
            try {
                $mysource->editUrl($_POST['name'], $_POST['url']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "L'URL du repo source <b>".$_POST['name']."</b> a été modifiée");
        }

        /**
         *  Modifier la configuration d'un repo source (Redhat-CentOS uniquement)
         */
        if ($_POST['action'] == "configureSource" AND !empty($_POST['name']) AND !empty($_POST['options_array']) AND isset($_POST['comments'])) {
            $mysource = new Source();

            /**
             *  Tentative de configuration du repo source
             */
            try {
                $mysource->configureSource($_POST['name'], $_POST['options_array'], $_POST['comments']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "La configuration du repo source <b>".$_POST['name']."</b> a été modifiée");
        }

        /**
         *  Supprimer une clé GPG
         */
        if ($_POST['action'] == "deleteGpgKey" AND !empty($_POST['gpgkey'])) {
            $mysource = new Source();

            /**
             *  Tentative de suppression de la clé GPG
             */
            try {
                $mysource->removeGpgKey($_POST['gpgkey']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "La clé GPG <b>".$_POST['gpgkey']."</b> a été supprimée");
        }

        /**
         *  
         *  Actions relatives aux planifications
         * 
         * 
         *  Créer une nouvelle planification
         * 
         */
        if ($_POST['action'] == "newPlan" AND !empty($_POST['type']) AND !empty($_POST['planAction'])) {
            $myplan = new Planification();

            /**
             *  Tentative de création de la planification
             */
            try {
                $myplan->setAction($_POST['planAction']);
                if (!empty($_POST['day'])) $myplan->setDay($_POST['day']);
                if (!empty($_POST['date'])) $myplan->setDate($_POST['date']);
                if (!empty($_POST['time'])) $myplan->setTime($_POST['time']);
                if (!empty($_POST['type'])) $myplan->setType($_POST['type']);
                if (!empty($_POST['frequency'])) $myplan->setFrequency($_POST['frequency']);
                if (!empty($_POST['mailRecipient'])) $myplan->setMailRecipient($_POST['mailRecipient']);
                if (!empty($_POST['reminder'])) $myplan->setReminder($_POST['reminder']);
                if (!empty($_POST['notificationOnError']) AND $_POST['notificationOnError'] == "yes") {
                    $myplan->setNotification('on-error', 'yes');
                } else {
                    $myplan->setNotification('on-error', 'no');
                }
                if (!empty($_POST['notificationOnSuccess']) AND $_POST['notificationOnSuccess'] == "yes") {
                    $myplan->setNotification('on-success', 'yes');
                } else {
                    $myplan->setNotification('on-success', 'no');
                }

                /**
                 *  Si l'action est 'update' alors on récupère les paramètres concernant GPG
                 */
                if ($_POST['planAction'] == 'update') {
                    if (!empty($_POST['gpgCheck']) AND $_POST['gpgCheck'] == "yes") {
                        $myplan->setGpgCheck('yes');
                    } else {
                        $myplan->setGpgCheck('no');
                    }

                    if (!empty($_POST['gpgResign']) AND $_POST['gpgResign'] == "yes") {
                        $myplan->setGpgResign('yes');
                    } else {
                        $myplan->setGpgResign('no');
                    }
                }
            
                /**
                 *  Cas où c'est un repo seul
                 */
                if(!empty($_POST['repo'])) $myplan->setRepoId($_POST['repo']);

                /**
                 *  Cas où c'est un groupe
                 */
                if(!empty($_POST['group'])) $myplan->setGroupId($_POST['group']);

                $myplan->new();

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "La planification a été créé");
        }

        /**
         *  Supprimer une planification
         */
        if ($_POST['action'] == "deletePlan" AND !empty($_POST['id'])) {
            $myplan = new Planification();

            /**
             *  Tentative de suppression du groupe
             */
            try {
                $myplan->remove($_POST['id']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur
             */
            response(HTTP_OK, "La planification a été supprimée");
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
         *  Récupérer les détails d'un évènement au survol de la souris (afficher les paquets installé, mis à jour...)
         */
        if ($_POST['action'] == "getEventDetails" AND !empty($_POST['hostId']) AND !empty($_POST['eventId']) AND !empty($_POST['packageState'])) {
            $myhost = new Host();
            $myhost->setId($_POST['hostId']);

            /**
             *  Tentative de récupération des informations
             */
            try {
                $content = $myhost->getEventDetails($_POST['eventId'], $_POST['packageState']);

            } catch(Exception $e) {
                response(HTTP_BAD_REQUEST, $e->getMessage());
            }

            /**
             *  Si il n'y a pas eu d'erreur, on renvoie les informations récupérées
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