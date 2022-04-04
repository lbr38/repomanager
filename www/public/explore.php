<!DOCTYPE html>
<html>
<?php
require_once('../models/Autoloader.php');
Autoloader::load();
include_once('../includes/head.inc.php');
require_once('../functions/explore.functions.php');

/**
 *  Cas où on souhaite reconstruire les fichiers de métadonnées du repo
 */
if (!empty($_POST['action']) AND Common::validateData($_POST['action']) === 'reconstruct' AND !empty($_POST['repoId'])) {
    $repoId = Common::validateData($_POST['repoId']);

    /**
     *  Récupération de la valeur de GPG Resign
     *  Si on n'a rien transmis alors on set la valeur à 'no'
     *  Si on a transmis quelque chose alors on set la valeur à 'yes'
     */
    if (empty($_POST['repoGpgResign']))
        $repoGpgResign = 'no';
    else
        $repoGpgResign = 'yes';

    /**
     *  On instancie un nouvel objet Repo avec les infos transmises, on va ensuite pouvoir vérifier que ce repo existe bien
     */
    $myrepo = new Repo();
    $myrepo->setId($repoId);
    // $myrepo->setTargetGpgResign($repoGpgResign);

    /**
     *  On vérifie que l'ID de repo transmis existe bien, si c'est le cas alors on lance l'opération en arrière plan
     */
    if ($myrepo->existsId($repoId) === true) {
        /**
         *  Création d'un fichier json qui défini l'opération à exécuter
         */
        $params = array();
        $params['action'] = 'reconstruct';
        $params['repoId'] = $repoId;
        $params['repoStatus'] = 'active';
        $params['targetGpgResign'] = $repoGpgResign;

        $myop = new Operation();
        $myop->execute(array($params));
    }

    /**
     *  Rafraichissement de la page
     */
    sleep(1);
    header('Location: '.__ACTUAL_URL__);
    exit;
}

$pathError = 0;

/**
 *  Récupération du repo transmis
 */
if (empty($_GET['id'])) { 
    $pathError++;
} else {
    $repoId = Common::validateData($_GET['id']);
}

/**
 *  Récupération de l'état du repo passé en argument
 *  Soit il s'agit d'un repo actif, soit d'un repo archivé
 */
if (empty($_GET['state'])) {
    $pathError++;

} else {
    $state = Common::validateData($_GET['state']);

    if ($state != "active" AND $state != "archived") $pathError++;
}

/**
 *  Le repo transmis doit être un numéro car il s'agit de l'ID en BDD
 */
if (!is_numeric($repoId)) $pathError++;

/**
 *  A partir de l'ID fourni, on récupère les infos du repo
 */
if ($pathError == 0) {
    $myrepo = new Repo();
    $myrepo->setId($repoId);

    if ($state == 'active')   $myrepo->db_getAllById();
    if ($state == 'archived') $myrepo->db_getAllById('archived');

    /**
     *  Si on n'a eu aucune erreur lors de la récupération des paramètres, alors on peut construire le chemin complet du repo
     */
    if ($state == 'active') {
        if (OS_FAMILY == "Redhat") $repoPath = REPOS_DIR."/".$myrepo->getName()."_".$myrepo->getEnv();
        if (OS_FAMILY == "Debian") $repoPath = REPOS_DIR."/".$myrepo->getName()."/".$myrepo->getDist()."/".$myrepo->getSection()."_".$myrepo->getEnv();
    }
    if ($state == 'archived') {
        if (OS_FAMILY == "Redhat") $repoPath = REPOS_DIR."/archived_".$myrepo->getDateFormatted()."_".$myrepo->getName();
        if (OS_FAMILY == "Debian") $repoPath = REPOS_DIR."/".$myrepo->getName()."/".$myrepo->getDist()."/archived_".$myrepo->getDateFormatted()."_".$myrepo->getSection();
    }

    /**
     *  Si le chemin construit n'existe pas sur le serveur alors on incrémente pathError qui affichera une erreur et empêchera toute action
     */
    if (!is_dir($repoPath)) $pathError++;
}

/**
 *  Cas où on upload un package dans un repo
 */
if (!empty($_POST['action']) AND Common::validateData($_POST['action']) == 'uploadPackage' AND !empty($_FILES['packages']) AND $pathError === 0 AND !empty($repoPath)) {
    /**
     *  On définit le chemin d'upload comme étant le répertoire my_uploaded_packages à l'intérieur du répertoire du repo
     */
    $targetDir = $repoPath . '/my_uploaded_packages';

    /**
     *  Si ce répertoire n'existe pas encore alors on le créé
     */
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0770, true)) {
            Common::printAlert("Erreur : impossible de créer le répertoire d'upload : <b>$target_dir</b>", 'error');
            return;
        }
    }

    /**
     *  On ré-arrange la liste des fichiers transmis
     */
    $packages = reArrayFiles($_FILES['packages']);

    $packageExists = ''; // contiendra la liste des paquets ignorés car existent déjà
    $packagesError = ''; // contiendra la liste des paquets uploadé avec une erreur
    $packageEmpty = ''; // contiendra la liste des paquets vides
    $packageInvalid = ''; // contiendra la liste des paquets dont le format est invalide

    foreach($packages as $package) {
        $uploadError = 0;
        $packageName  = $package['name'];
        $packageType  = $package['type'];
        $packageSize  = $package['size'];
        $packageError = $package['error'];
        $packageTmpName = $package['tmp_name'];

        /**
         *  Le nom du paquet ne doit pas contenir de caractère spéciaux, sinon on passe au suivant
         *  On autorise seulement les tirets et les underscores (voir fonction is_alphanumdash), ainsi qu'un caractère supplémentaire : le point (car les nom de paquet contiennent des points)
         */
        if (!Common::is_alphanumdash($packageName, array('.'))) { 
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
        if (file_exists("$targetDir/$packageName")) {
            $uploadError++;
            $packageExists .= "$packageName, ";
            continue;
        }

        /**
         *  On vérifie que le paquet est valide
         */
        if (OS_FAMILY == "Redhat") {
            if ($packageType !== 'application/x-rpm') {
                $uploadError++;
                $packageInvalid .= "$packageName, ";
            }
        }

        if (OS_FAMILY == "Debian") {
            if ($packageType !== 'application/vnd.debian.binary-package') {
                $uploadError++;
                $packageInvalid .= "$packageName, ";
            }
        }

        /**
         *  Si on n'a pas eu d'erreur jusque là, alors on peut déplacer le fichier dans son emplacement définitif
         */
        if ($uploadError === 0 AND file_exists($packageTmpName)) move_uploaded_file($packageTmpName, $targetDir ."/$packageName");
    }

    if ($uploadError === 0) {
        Common::printAlert('Les fichiers ont été chargés', 'success');
    } else {
        Common::printAlert("Certains fichiers n'ont pas pu être chargé", 'error');
    }
}

/**
 *  Cas où on supprime un ou plusieurs paquets d'un repo
 */
if (!empty($_POST['action']) AND Common::validateData($_POST['action']) == 'deletePackages' AND !empty($_POST['packageName']) AND $pathError === 0 AND !empty($repoPath)) {

    $packagesToDeleteNonExists = ''; // contiendra la liste des fichiers qui n'existent pas, si on tente de supprimer un fichier qui n'existe pas
    $packagesDeleted = array();

    foreach ($_POST['packageName'] as $packageToDelete) {
        $packageName = Common::validateData($packageToDelete);
        $packagePath = "$repoPath/$packageName";

        /**
         *  Le nom du paquet ne doit pas contenir de caractères spéciaux
         *  On autorise seulement les tirets et les underscores (voir fonction is_alphanumdash), ainsi qu'un caractère supplémentaire : le point (car les nom de paquet contiennent des points)
         *  On autorise également le slash car le chemin du fichier transmis contient aussi le ou les sous-dossiers vers le paquet à partir de la racine du repo
         */
        if (!Common::is_alphanumdash($packageName, array('.', '/', '+', '~'))) {
            continue;
        }

        /**
         *  On vérifie que le chemin du fichier commence bien par REPOS_DIR et on supprime
         *  Empeche une personne mal intentionnée de fournir un chemin qui n'a aucun rapport avec le répertoire de repos (par exemple /etc/... )
         */
        if (preg_match("#^".REPOS_DIR."#", $packagePath)) {
            /**
             *  On vérifie que le fichier ciblé se termine par .deb ou .rpm sinon on passe au suivant
             */
            if (!preg_match("#.deb$#", $packagePath) AND !preg_match("#.rpm$#", $packagePath)) {
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
        }
    }

    unset($packageName, $packagePath);
}
?>

<body>
<?php include_once('../includes/header.inc.php');?>

<article>
    <section class="mainSectionLeft">
        <section class="left">
            <h3>EXPLORER</h3>

            <?php
                if ($pathError !== 0) {
                    if (OS_FAMILY == "Redhat") echo "<p>Erreur : le repo spécifié n'existe pas.</p>";
                    if (OS_FAMILY == "Debian") echo "<p>Erreur : la section de repo spécifiée n'existe pas.</p>";
                }

                if ($pathError === 0) {
                    if (OS_FAMILY == "Redhat" AND !empty($myrepo->getName())) {
                        if ($state == "active")   echo '<p>Explorer le contenu du repo <span class="label-white">'.$myrepo->getName()."</span> ".Common::envtag($myrepo->getEnv()).'</p>';
                        if ($state == "archived") echo '<p>Explorer le contenu du repo archivé <span class="label-white">'.$myrepo->getName().'</span></p>';
                    }

                    if (OS_FAMILY == "Debian" AND !empty($myrepo->getName()) AND !empty($myrepo->getDist()) AND !empty($myrepo->getSection())) {
                        if ($state == "active")   echo '<p>Explorer le contenu de la section <span class="label-white">'.$myrepo->getName().' ❯ '.$myrepo->getDist().' ❯ '.$myrepo->getSection().'</span> '.Common::envtag($myrepo->getEnv()).'</p>';
                        if ($state == "archived") echo '<p>Explorer le contenu de la section archivée <span class="label-white">'.$myrepo->getName().' ❯ '.$myrepo->getDist().' ❯ '.$myrepo->getSection().'</span></p>';
                    }

                    if (is_dir($repoPath.'/my_uploaded_packages')) {
                        if(!Common::dir_is_empty($repoPath."/my_uploaded_packages")) {
                            echo '<span class="yellowtext">Certains paquets uploadés n\'ont pas encore été intégrés au repo. Vous devez reconstruire les fichiers de metadonnées du repo.</span>';
                        }
                    }
                }
            ?>

            <br>

            <span id="loading">Génération de l'arborescence<img src="ressources/images/loading.gif" class="icon" /></span>

            <div id="explorer" class="hide">

                <?php
                /**
                 *  On appelle la fonction tree permettant de construire l'arborescence de fichiers si on a bien reçu toutes les infos
                 */
                if ($pathError === 0) {
                    echo '<form action="" method="post" />';
                    if (Common::isadmin()) {
                        echo '<input type="hidden" name="action" value="deletePackages" />';
                        echo '<span id="delete-packages-btn" class="hide"><button type="submit" class="btn-medium-red">Supprimer</button></span>';
                    }
                    
                    /**
                     *  Si des paquets qu'on a tenté de supprimer n'existent pas alors on affiche la liste à cet endroit
                     */
                    if (!empty($packagesToDeleteNonExists)) {
                        echo "<br><span class=\"redtext\">Les paquets suivants n'existent pas et n'ont pas été supprimés : <b>".rtrim($packagesToDeleteNonExists, ', ')."</b></span>";
                    }

                    /**
                     *  Si des paquets ont été supprimés alors on affiche la liste à cet endroit
                     */
                    if (!empty($packagesDeleted)) {
                        echo "<br><span class=\"greentext\">Les paquets suivants ont été supprimés :</span>";
                        foreach ($packagesDeleted as $packageDeleted) {
                            echo "<br><span class=\"greentext\"><b>$packageDeleted</b></span>";
                        }
                        unset($packagesDeleted, $packageDeleted);
                    }

                    /**
                     *  Appel à la fonction qui construit l'arborescence de fichiers
                     */
                    tree($repoPath);

                    echo '</form>';
                } ?>
            </div>
        </section>
    </section>

    <?php if (Common::isadmin()) { ?>
        <section class="mainSectionRight">
            <section class="right">
                <h3>ACTIONS</h3>
                <?php
                    if ($pathError === 0 AND $state == 'active') {
                        /**
                         *  On vérifie qu'une opération n'est pas déjà en cours sur ce repo (mise à jour ou reconstruction du repo)
                         */
                        try {
                            $stmt = $myrepo->db->prepare("SELECT * FROM operations WHERE Id_repo_target = :id AND Status = 'running'");
                            $stmt->bindValue(':id', $myrepo->getId());
                            $result = $stmt->execute();
                        } catch(Exception $e) {
                            Common::dbError($e);
                        }

                        while ($datas = $result->fetchArray()) $opRunning[] = $datas;

                        if (!empty($opRunning)) {
                            echo '<p>';
                            echo '<img src="ressources/images/loading.gif" class="icon" /> ';
                            if (OS_FAMILY == "Redhat") echo 'Une opération est en cours sur ce repo.';
                            if (OS_FAMILY == "Debian") echo 'Une opération est en cours sur cette section.';
                            echo '</p>';
                        }

                        /**
                         *  Si il n'y a aucune opération en cours, on affiche les boutons permettant d'effectuer des actions sur le repo/section
                         */
                        if (empty($opRunning)) { ?>
                            <h5>Uploader des packages</h5>
                            <form action="" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="uploadPackage" />
                                <input type="file" name="packages[]" accept="application/vnd.debian.binary-package" multiple />
                                <button type="submit" class="btn-medium-blue">Ajouter</button>
                            </form>

                            <?php
                            /**
                             *  On affiche les messages d'erreurs issus du script d'upload (plus haut dans ce fichier) si il y en a
                             */
                            if (!empty($packageExists))  echo "<br><span class=\"redtext\">Les paquets suivants existent déjà et n'ont pas été chargés : <b>".rtrim($packageExists, ', ')."</b></span>";
                            if (!empty($packagesError))  echo "<br><span class=\"redtext\">Les paquets suivants sont en erreur et n'ont pas été chargés : <b>".rtrim($packagesError, ', ')."</b></span>";
                            if (!empty($packageEmpty))   echo "<br><span class=\"redtext\">Les paquets suivants semblent vides et n'ont pas été chargés : <b>".rtrim($packageEmpty, ', ')."</b></span>";
                            if (!empty($packageInvalid)) echo "<br><span class=\"redtext\">Les paquets suivants sont invalides et n'ont pas été chargés : <b>".rtrim($packageInvalid, ', ')."</b></span>";
                            ?>
                            <br>
                            <hr>
                            <br>
                    
                            <h5 id="rebuild-button" class="pointer"><img src="ressources/icons/update.png" class="icon" />Reconstruire les fichiers de metadonnées du repo</h5>
                            <form id="hidden-form" class="hide" action="" method="post">
                                <input type="hidden" name="action" value="reconstruct">
                                <input type="hidden" name="repoId" value="<?php echo $repoId; ?>">
                                <span>Signer avec GPG </span>
                                <label class="onoff-switch-label">
                                <input name="repoGpgResign" type="checkbox" class="onoff-switch-input" value="yes" <?php if (GPG_SIGN_PACKAGES == "yes") { echo 'checked'; } ?> />
                                <span class="onoff-switch-slider"></span>
                                </label>
                                <span class="graytext">  (La signature avec GPG peut rallonger le temps de l'opération)</span>
                                <br><br>
                                <button type="submit" class="btn-medium-red"><img src="ressources/icons/rocket.png" class="icon" />Exécuter</button>
                            </form>
                        <?php
                        }
                    
                    } else {

                        echo '<p>Aucune action possible.</p>';

                    }

                unset($myrepo); ?>
            </section>
        </section>
    <?php } ?>
</article>

<?php include_once('../includes/footer.inc.php'); ?>
</body>
</html>