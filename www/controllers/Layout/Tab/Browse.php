<?php

namespace Controllers\Layout\Tab;

class Browse
{
    public static function render()
    {
        $myrepo = new \Controllers\Repo\Repo();
        $myop = new \Controllers\Operation\Operation();

        /**
         *  Cas où on souhaite reconstruire les fichiers de métadonnées du repo
         */
        if (!empty($_POST['action']) and \Controllers\Common::validateData($_POST['action']) === 'reconstruct' and !empty($_POST['snapId'])) {
            $snapId = \Controllers\Common::validateData($_POST['snapId']);

            /**
             *  Récupération de la valeur de GPG Resign
             *  Si on n'a rien transmis alors on set la valeur à 'no'
             *  Si on a transmis quelque chose alors on set la valeur à 'yes'
             */
            if (empty($_POST['repoGpgResign'])) {
                $repoGpgResign = 'no';
            } else {
                $repoGpgResign = 'yes';
            }

            /**
             *  On instancie un nouvel objet Repo avec les infos transmises, on va ensuite pouvoir vérifier que ce repo existe bien
             */
            $myrepo->setSnapId($snapId);

            /**
             *  On vérifie que l'ID de repo transmis existe bien, si c'est le cas alors on lance l'opération en arrière plan
             */
            if ($myrepo->existsSnapId($snapId) === true) {
                /**
                 *  Création d'un fichier json qui défini l'opération à exécuter
                 */
                $params = array();
                $params['action'] = 'reconstruct';
                $params['snapId'] = $snapId;
                $params['targetGpgResign'] = $repoGpgResign;

                $myop->execute(array($params));
            }

            /**
             *  Rafraichissement de la page
             */
            sleep(1);
            header('Location: ' . __ACTUAL_URL__);
            exit;
        }

        $pathError = 0;

        /**
         *  Récupération du repo transmis
         */
        if (empty($_GET['id'])) {
            $pathError++;
        } else {
            $snapId = \Controllers\Common::validateData($_GET['id']);
        }

        /**
         *  Le repo transmis doit être un numéro car il s'agit de l'ID en BDD
         */
        if (!is_numeric($snapId)) {
            $pathError++;
        }

        /**
         *  A partir de l'ID fourni, on récupère les infos du repo
         */
        if ($pathError == 0) {
            $myrepo->setSnapId($snapId);
            $myrepo->getAllById('', $snapId, '');
            $reconstruct = $myrepo->getReconstruct();

            /**
             *  Si on n'a eu aucune erreur lors de la récupération des paramètres, alors on peut construire le chemin complet du repo
             */
            if ($myrepo->getPackageType() == "rpm") {
                $repoPath = REPOS_DIR . "/" . $myrepo->getDateFormatted() . "_" . $myrepo->getName();
            }
            if ($myrepo->getPackageType() == "deb") {
                $repoPath = REPOS_DIR . "/" . $myrepo->getName() . "/" . $myrepo->getDist() . "/" . $myrepo->getDateFormatted() . "_" . $myrepo->getSection();
            }

            /**
             *  Si le chemin construit n'existe pas sur le serveur alors on incrémente pathError qui affichera une erreur et empêchera toute action
             */
            if (!is_dir($repoPath)) {
                $pathError++;
            }
        }

        /**
         *  Cas où on upload un package dans un repo
         */
        if (!empty($_POST['action']) and \Controllers\Common::validateData($_POST['action']) == 'uploadPackage' and !empty($_FILES['packages']) and $pathError === 0 and !empty($repoPath)) {
            /**
             *  On définit le chemin d'upload comme étant le répertoire my_uploaded_packages à l'intérieur du répertoire du repo
             */
            $targetDir = $repoPath . '/my_uploaded_packages';

            /**
             *  Si ce répertoire n'existe pas encore alors on le créé
             */
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0770, true)) {
                    \Controllers\Common::printAlert("Error: cannot create upload directory <b>$target_dir</b>", 'error');
                    return;
                }
            }

            /**
             *  On ré-arrange la liste des fichiers transmis
             */
            $packages = \Controllers\Browse::reArrayFiles($_FILES['packages']);

            $packageExists = ''; // contiendra la liste des paquets ignorés car existent déjà
            $packagesError = ''; // contiendra la liste des paquets uploadé avec une erreur
            $packageEmpty = ''; // contiendra la liste des paquets vides
            $packageInvalid = ''; // contiendra la liste des paquets dont le format est invalide

            foreach ($packages as $package) {
                $uploadError = 0;
                $packageName  = $package['name'];
                $packageType  = $package['type'];
                $packageSize  = $package['size'];
                $packageError = $package['error'];
                $packageTmpName = $package['tmp_name'];

                /**
                 *  Le nom du paquet ne doit pas contenir de caractère spéciaux, sinon on passe au suivant
                 *  On autorise seulement les tirets et les underscores (voir fonction isAlphanumDash), ainsi qu'un caractère supplémentaire : le point (car les nom de paquet contiennent des points)
                 */
                if (!\Controllers\Common::isAlphanumDash($packageName, array('.'))) {
                    $uploadError++;
                    $packageInvalid .= "$packageName, ";
                    continue;
                }

                /**
                 *  Si le paquet est en erreur alors on l'ignore et on passe au suivant
                 */
                if ($packageError != 0) {
                    $uploadError++;
                    $packagesError .= "$packageName, ";
                    continue;
                }

                /**
                 *  Si la taille du paquet est égale à 0 alors on l'ignore et on passe au suivant
                 */
                if ($packageSize == 0) {
                    $uploadError++;
                    $packageEmpty .= "$packageName, ";
                    continue;
                }

                /**
                 *  On vérifie que le paquet n'existe pas déjà, sinon on l'ignore et on l'ajoute à une liste de paquets déjà existants qu'on affichera après
                 */
                if (file_exists($targetDir . '/' . $packageName)) {
                    $uploadError++;
                    $packageExists .= "$packageName, ";
                    continue;
                }

                /**
                 *  On vérifie que le paquet est valide
                 */
                if ($packageType !== 'application/x-rpm' and $packageType !== 'application/vnd.debian.binary-package') {
                    $uploadError++;
                    $packageInvalid .= "$packageName, ";
                }

                /**
                 *  Si on n'a pas eu d'erreur jusque là, alors on peut déplacer le fichier dans son emplacement définitif
                 */
                if ($uploadError === 0 and file_exists($packageTmpName)) {
                    move_uploaded_file($packageTmpName, $targetDir . '/' . $packageName);
                }
            }

            if ($uploadError === 0) {
                \Controllers\Common::printAlert('Files have been uploaded', 'success');
            } else {
                \Controllers\Common::printAlert('Some files have not been uploaded', 'error');
            }
        }

        /**
         *  Cas où on supprime un ou plusieurs paquets d'un repo
         */
        if (!empty($_POST['action']) and \Controllers\Common::validateData($_POST['action']) == 'deletePackages' and !empty($_POST['packageName']) and $pathError === 0 and !empty($repoPath)) {
            $packagesToDeleteNonExists = ''; // contiendra la liste des fichiers qui n'existent pas, si on tente de supprimer un fichier qui n'existe pas
            $packagesDeleted = array();

            foreach ($_POST['packageName'] as $packageToDelete) {
                $packageName = \Controllers\Common::validateData($packageToDelete);
                $packagePath = REPOS_DIR . '/' . $packageName;

                /**
                 *  Le nom du paquet ne doit pas contenir de caractères spéciaux
                 *  On autorise seulement les tirets et les underscores (voir fonction isAlphanumDash), ainsi qu'un caractère supplémentaire : le point (car les nom de paquet contiennent des points)
                 *  On autorise également le slash car le chemin du fichier transmis contient aussi le ou les sous-dossiers vers le paquet à partir de la racine du repo
                 */
                if (!\Controllers\Common::isAlphanumDash($packageName, array('.', '/', '+', '~'))) {
                    continue;
                }

                /**
                 *  On vérifie que le chemin du fichier commence bien par REPOS_DIR et on supprime
                 *  Empeche une personne mal intentionnée de fournir un chemin qui n'a aucun rapport avec le répertoire de repos (par exemple /etc/... )
                 */
                if (preg_match("#^" . REPOS_DIR . "#", $packagePath)) {
                    /**
                     *  On vérifie que le fichier ciblé se termine par .deb ou .rpm sinon on passe au suivant
                     */
                    if (!preg_match("#.deb$#", $packagePath) and !preg_match("#.rpm$#", $packagePath)) {
                        continue;
                    }

                    /**
                     *  Si le fichier n'existe pas, on l'ignore et on passe au suivant
                     */
                    if (!file_exists($packagePath)) {
                        $packagesToDeleteNonExists .= "$packageName, ";
                        continue;
                    }

                    /**
                     *  Suppression
                     */
                    unlink($packagePath);

                    /**
                     *  On stocke le nom du fichier supprimé dans une liste car on va afficher cette liste plus bas pour confirmer à l'utilisateur le(s) paquet(s) supprimé(s)
                     *  Cependant on retire le chemin complet du fichier pour éviter d'afficher l'emplacement des fichiers en clair à l'écran...
                     */
                    $packagesDeleted[] = str_replace($repoPath . '/', '', $packagePath);

                    $myrepo->snapSetReconstruct($snapId, 'needed');
                }
            }

            unset($packageName, $packagePath);
        }

        include_once(ROOT . '/views/browse.template.php');
    }
}
