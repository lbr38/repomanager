<!DOCTYPE html>
<html>
<?php
require_once('../controllers/Autoloader.php');
\Controllers\Autoloader::load();
include_once('../includes/head.inc.php');

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
    $myrepo = new \Controllers\Repo();
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

        $myop = new \Controllers\Operation();
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
    $myrepo = new \Controllers\Repo();
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
        if (!Controllers\Common::isAlphanumDash($packageName, array('.'))) {
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
            move_uploaded_file($packageTmpName, $targetDir . "/$packageName");
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
        $packagePath = "$repoPath/$packageName";

        /**
         *  Le nom du paquet ne doit pas contenir de caractères spéciaux
         *  On autorise seulement les tirets et les underscores (voir fonction isAlphanumDash), ainsi qu'un caractère supplémentaire : le point (car les nom de paquet contiennent des points)
         *  On autorise également le slash car le chemin du fichier transmis contient aussi le ou les sous-dossiers vers le paquet à partir de la racine du repo
         */
        if (!Controllers\Common::isAlphanumDash($packageName, array('.', '/', '+', '~'))) {
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
            $packagesDeleted[] = str_replace("$repoPath/", '', $packagePath);

            $deleteRepo = new \Controllers\Repo();
            $deleteRepo->snapSetReconstruct($snapId, 'needed');
        }
    }

    unset($packageName, $packagePath, $deleteRepo);
}
?>

<body>
<?php include_once('../includes/header.inc.php');?>

<article>
<?php if (Controllers\Common::isadmin()) { ?>
        <section class="mainSectionRight">
            <section class="right">
                <h3>ACTIONS</h3>
                <?php
                if ($pathError == 0) {
                    /**
                     *  Si une opération est déjà en cours sur ce repo alors on affiche un message
                     */
                    if (!empty($reconstruct) and $reconstruct == 'running') : ?>
                        <p>
                            <img src="resources/images/loading.gif" class="icon" /> 
                            An operation is running on this repo.
                        </p>
                        <?php
                    endif;

                    /**
                     *  Si il n'y a aucune opération en cours, on affiche les boutons permettant d'effectuer des actions sur le repo/section
                     */
                    if (empty($reconstruct) or (!empty($reconstruct) and $reconstruct != 'running')) { ?>
                            <div class="div-generic-gray">
                                <h5><img src="resources/icons/products/package.png" class="icon" />Upload packages</h5>
                                
                                <p>Select package(s) to import into the repo.
                                    <br><span class="lowopacity">Valid MIME types: 'application/x-rpm' and 'application/vnd.debian.binary-package'</span>
                                </p>
                                <br>
                                <form action="" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="uploadPackage" />
                                    <input type="file" name="packages[]" accept="application/vnd.debian.binary-package" multiple />
                                    <button type="submit" class="btn-large-blue">Add</button>
                                </form>

                                <?php
                                /**
                                 *  On affiche les messages d'erreurs issus du script d'upload (plus haut dans ce fichier) si il y en a
                                 */
                                if (!empty($packageExists)) {
                                    echo '<br><span class="redtext">Following packages already exist and have not been uploaded: <b>' . rtrim($packageExists, ', ') . '</b></span>';
                                }
                                if (!empty($packagesError)) {
                                    echo '<br><span class="redtext">Following packages encountered error and have not been uploaded: <b>' . rtrim($packagesError, ', ') . '</b></span>';
                                }
                                if (!empty($packageEmpty)) {
                                    echo '<br><span class="redtext">Following packages are empty and have not been uploaded: <b>' . rtrim($packageEmpty, ', ') . '</b></span>';
                                }
                                if (!empty($packageInvalid)) {
                                    echo '<br><span class="redtext">Following packages are invalid and have not been uploaded: <b>' . rtrim($packageInvalid, ', ') . '</b></span>';
                                }
                                ?>
                            </div>
                            
                            <div class="div-generic-gray">
                                <h5><img src="resources/icons/update.png" class="icon" />Rebuild repo metadata files</h5>
                                <form id="hidden-form" action="" method="post">
                                    <input type="hidden" name="action" value="reconstruct">
                                    <input type="hidden" name="snapId" value="<?= $snapId ?>">
                                    <span>Sign with GPG </span>
                                    <label class="onoff-switch-label">
                                        <?php
                                        $resignChecked = '';

                                        if ($myrepo->getPackageType() == "rpm") {
                                            if (RPM_SIGN_PACKAGES == 'yes') {
                                                $resignChecked = 'checked';
                                            }
                                        }
                                        if ($myrepo->getPackageType() == "deb") {
                                            if (DEB_SIGN_REPO == 'yes') {
                                                $resignChecked = 'checked';
                                            }
                                        }
                                        ?>
                                        <input name="repoGpgResign" type="checkbox" class="onoff-switch-input" value="yes" <?= $resignChecked ?>>
                                        <span class="onoff-switch-slider"></span>
                                    </label>
                                    <span class="graytext">  (Signature can extend the operation duration)</span>
                                    <br><br>
                                    <button type="submit" class="btn-large-red"><img src="resources/icons/rocket.png" class="icon" />Execute</button>
                                </form>
                            </div>
                        <?php
                    }
                } else {
                    echo '<p>You can\'t execute any actions.</p>';
                } ?>
            </section>
        </section>
<?php } ?>

    <section class="mainSectionLeft">
        <section class="left">
            <h3>BROWSE</h3>

            <?php
            if ($pathError !== 0) {
                echo '<p>Error: specified repo does not exist.</p>';
            }

            if ($pathError === 0) {
                if (!empty($myrepo->getName()) and !empty($myrepo->getDist()) and !empty($myrepo->getSection())) {
                    echo '<p>Explore <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span></p>';
                } else {
                    echo '<p>Explore <span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span></p>';
                }

                if ($myrepo->getReconstruct() == 'needed' or is_dir($repoPath . '/my_uploaded_packages')) {
                    if (!Controllers\Common::dirIsEmpty($repoPath . "/my_uploaded_packages")) {
                        echo '<span class="yellowtext">Repo\'s content has been modified. You have to rebuild repo\'s metadata.</span>';
                    }
                }
            }
            ?>

            <br>

            <span id="loading">Generating tree structure<img src="resources/images/loading.gif" class="icon" /></span>

            <div id="explorer" class="hide">

                <?php
                /**
                 *  On appelle la fonction tree permettant de construire l'arborescence de fichiers si on a bien reçu toutes les infos
                 */
                if ($pathError === 0) {
                    echo '<form action="" method="post" />';
                    if (Controllers\Common::isadmin()) : ?>
                        <input type="hidden" name="action" value="deletePackages" />
                        <input type="hidden" name="snapId" value="<?= $snapId ?>" />
                        <span id="delete-packages-btn" class="hide"><button type="submit" class="btn-medium-red">Delete</button></span>
                        <?php
                    endif;

                    /**
                     *  Si des paquets qu'on a tenté de supprimer n'existent pas alors on affiche la liste à cet endroit
                     */
                    if (!empty($packagesToDeleteNonExists)) {
                        echo '<br><span class="redtext">Following packages does not exist and have not been deleted: <b>' . rtrim($packagesToDeleteNonExists, ', ') . '</b></span>';
                    }

                    /**
                     *  Si des paquets ont été supprimés alors on affiche la liste à cet endroit
                     */
                    if (!empty($packagesDeleted)) {
                        echo '<br><span class="greentext">Following packages have been deleted:</span>';
                        foreach ($packagesDeleted as $packageDeleted) {
                            echo '<br><span class="greentext"><b>' . $packageDeleted . '</b></span>';
                        }
                        unset($packagesDeleted, $packageDeleted);
                    }

                    /**
                     *  Appel à la fonction qui construit l'arborescence de fichiers
                     */
                    \Controllers\Browse::tree($repoPath);

                    echo '</form>';
                }

                unset($myrepo); ?>
            </div>
        </section>
    </section>
</article>

<?php include_once('../includes/footer.inc.php'); ?>
</body>
</html>