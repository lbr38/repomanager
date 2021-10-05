<!DOCTYPE html>
<html>
<?php include('includes/head.inc.php'); ?>

<?php
/**
 *  Import des variables et fonctions nécessaires
 */
require_once('functions/load_common_variables.php');
require_once('functions/load_display_variables.php');
require_once('functions/common-functions.php');
require_once('common.php');
require_once('class/Repo.php');

/**
 *  Cas où on souhaite reconstruire les fichiers de métadonnées du repo
 */
if (!empty($_POST['action']) AND validateData($_POST['action']) === 'reconstruct' AND !empty($_POST['repoId'])) {
    $repoId = validateData($_POST['repoId']);

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
    $myrepo = new Repo(array('repoId' => $repoId, 'repoGpgResign' => $repoGpgResign));

    /**
     *  On vérifie que l'ID de repo transmis existe bien, si c'est le cas alors on lance l'opération en arrière plan
     */
    if ($myrepo->existsId() === true) exec("php ${WWW_DIR}/operations/execute.php --action='reconstruct' --id='$myrepo->id' --gpgResign='$myrepo->gpgResign' >/dev/null 2>/dev/null &");

    /**
     *  Rafraichissement de la page
     */
    sleep(1);
    header("Location: $actual_url");
    exit;
}

$pathError = 0;

/**
 *  Récupération du repo transmis
 */
if (empty($_GET['id'])) { 
    $pathError++;
} else {
    $repoId = validateData($_GET['id']);
}

/**
 *  Récupération de l'état du repo passé en argument
 *  Soit il s'agit d'un repo actif, soit d'un repo archivé
 */
if (empty($_GET['state'])) {
    $pathError++;

} else {
    $state = validateData($_GET['state']);

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
    $myrepo->id = $repoId;

    if ($state == 'active')   $myrepo->db_getAllById();
    if ($state == 'archived') $myrepo->db_getAllById('archived');

    /**
     *  Si on n'a eu aucune erreur lors de la récupération des paramètres, alors on peut construire le chemin complet du repo
     */
    if ($state == 'active') {
        if ($OS_FAMILY == "Redhat") $repoPath = "${REPOS_DIR}/{$myrepo->name}_{$myrepo->env}";
        if ($OS_FAMILY == "Debian") $repoPath = "${REPOS_DIR}/$myrepo->name/$myrepo->dist/{$myrepo->section}_{$myrepo->env}";
    }
    if ($state == 'archived') {
        if ($OS_FAMILY == "Redhat") $repoPath = "${REPOS_DIR}/archived_{$myrepo->dateFormatted}_{$myrepo->name}";
        if ($OS_FAMILY == "Debian") $repoPath = "${REPOS_DIR}/$myrepo->name/$myrepo->dist/archived_{$myrepo->dateFormatted}_{$myrepo->section}";
    }

    /**
     *  Si le chemin construit n'existe pas sur le serveur alors on incrémente pathError qui affichera une erreur et empêchera toute action
     */
    if (!is_dir($repoPath)) $pathError++;
}

/**
 *  Cas où on upload un package dans un repo
 */
if (!empty($_POST['action']) AND validateData($_POST['action']) == 'uploadPackage' AND !empty($_FILES['packages']) AND $pathError === 0 AND !empty($repoPath)) {
    /**
     *  On définit le chemin d'upload comme étant le répertoire my_uploaded_packages à l'intérieur du répertoire du repo
     */
    $targetDir = $repoPath . '/my_uploaded_packages';

    /**
     *  Si ce répertoire n'existe pas encore alors on le créé
     */
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0770, true)) {
            printAlert("Erreur : impossible de créer le répertoire d'upload : <b>$target_dir</b>", 'error');
            return;
        }
    }

    /**
     *  Fonction permettant de reconstruire l'array $_FILES['packages'] qui est assez mal foutu et donc compliqué à parcourir
     *  https://www.php.net/manual/fr/features.file-upload.multiple.php
     */
    function reArrayFiles(&$file_post) {
        $file_ary = array();
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);
    
        for ($i=0; $i<$file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post[$key][$i];
            }
        }
    
        return $file_ary;
    }
    /**
     *  On ré-arrange la liste des fichiers transmis
     */
    $packages = reArrayFiles($_FILES['packages']);

    // Pour debug :
    /*echo '<pre>';
    print_r($packages);
    echo '</pre>';*/

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
        if (!is_alphanumdash($packageName, array('.'))) { 
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
        if ($OS_FAMILY == "Redhat") {
            if ($packageType !== 'application/x-rpm') {
                $uploadError++;
                $packageInvalid .= "$packageName, ";
            }
        }

        if ($OS_FAMILY == "Debian") {
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
        printAlert('Les fichiers ont été chargés', 'success');
    } else {
        printAlert("Certains fichiers n'ont pas pu être chargé", 'error');
    }
}

/**
 *  Cas où on supprime un ou plusieurs paquets d'un repo
 */
if (!empty($_POST['action']) AND validateData($_POST['action']) == 'deletePackages' AND !empty($_POST['packageName']) AND $pathError === 0 AND !empty($repoPath)) {

    $packagesToDeleteNonExists = ''; // contiendra la liste des fichiers qui n'existent pas, si on tente de supprimer un fichier qui n'existe pas
    $packagesDeleted = array();

    foreach ($_POST['packageName'] as $packageToDelete) {
        $packageName = validateData($packageToDelete);
        $packagePath = "$repoPath/$packageName";

        /**
         *  Le nom du paquet ne doit pas contenir de caractères spéciaux
         *  On autorise seulement les tirets et les underscores (voir fonction is_alphanumdash), ainsi qu'un caractère supplémentaire : le point (car les nom de paquet contiennent des points)
         *  On autorise également le slash car le chemin du fichier transmis contient aussi le ou les sous-dossiers vers le paquet à partir de la racine du repo
         */
        if (!is_alphanumdash($packageName, array('.', '/'))) {
            continue;
        }

        /**
         *  On vérifie que le chemin du fichier commence bien par $REPOS_DIR et on supprime
         */
        if (preg_match("#^${REPOS_DIR}#", $packagePath)) {
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
<?php include('includes/header.inc.php');?>

<article>
    <section class="mainSectionLeft">
        <section class="left">
            <h3>EXPLORER</h3>

            <?php
                if ($pathError !== 0) {
                    if ($OS_FAMILY == "Redhat") echo "<p>Erreur : le repo spécifié n'existe pas.</p>";
                    if ($OS_FAMILY == "Debian") echo "<p>Erreur : la section de repo spécifiée n'existe pas.</p>";
                }

                if ($pathError === 0) {
                    if ($OS_FAMILY == "Redhat" AND !empty($myrepo->name)) {
                        if ($state == "active")   echo "<p>Explorer le contenu du repo <b>$myrepo->name</b> " . envtag($myrepo->env) . "</p>";
                        if ($state == "archived") echo "<p>Explorer le contenu du repo archivé <b>$myrepo->name</b>.</p>";
                    }

                    if ($OS_FAMILY == "Debian" AND !empty($myrepo->name) AND !empty($myrepo->dist) AND !empty($myrepo->section)) {
                        if ($state == "active")   echo "<p>Explorer le contenu de la section <b>$myrepo->section</b> " . envtag($myrepo->env) . " du repo <b>$myrepo->name</b> (distribution <b>$myrepo->dist</b>).</p>";
                        if ($state == "archived") echo "<p>Explorer le contenu de la section archivée <b>$myrepo->section</b> du repo <b>$myrepo->name</b> (distribution <b>$myrepo->dist</b>).</p>";
                    }

                    if (is_dir("$repoPath/my_uploaded_packages")) {
                        if(!dir_is_empty("$repoPath/my_uploaded_packages")) {
                            echo '<span class="yellowtext">Certains paquets uploadés n\'ont pas encore été intégrés au repo. Vous devez reconstruire les fichiers de metadonnées du repo.</span>';
                        }
                    }
                }
            ?>

            <br>

            <div id="explorer">
                <?php
                /**
                 *  Fonctions basées sur : https://phpfog.com/directory-trees-with-php-and-jquery/
                 */
                function tree($path) {
                    global $repoPath;

                    if ($handle = opendir($path)) {
                        echo "<ul>";
                        $queue = array(); // initialisation d'un tableau qui contiendra la liste des fichiers d'un répertoire

                        while (false !== ($file = readdir($handle))) {
                            
                            if (is_dir("$path/$file") && $file != '.' && $file !='..') {
                                printSubDir($file, $path, $queue);
                                
                            } else if ($file != '.' && $file !='..') {
                                /**
                                 *  Si c'est un fichier alors on l'ajoute à l'array queue qui contient toute la liste des fichiers du répertoire ou sous-répertoire en cours
                                 *  On indexe le nom du fichier $file ainsi que son chemin $path/$file auquel on retire le début du chemin complet afin qu'il ne soit pas visible dans le code source
                                 */
                                $queue[$file] = str_replace("$repoPath/", '', "$path/$file");
                            }
                        }

                        printQueue($queue);
                        echo "</ul>";
                    }
                }

                /**
                 *  Affichage de tous les fichiers d'un répertoire
                 */
                function printQueue($queue) {
                    /**
                     *  D'abord on trie la liste par ordre alphabétique
                     */
                    ksort($queue);
                    foreach ($queue as $file => $path) {
                        printFile($file, $path);
                    }
                }

                /**
                 *  Affichage d'un fichier
                 */
                function printFile($file, $path) {
                    /**
                     *  On affiche une checkbox permettant de supprimer le fichier seulement si il s'agit d'un fichier .rpm ou .deb
                     */
                    if (substr($file, -4) == ".rpm" OR substr($file, -4) == ".deb") {
                        echo "<li><span class=\"explorer-file\"><input type=\"checkbox\" class=\"packageName-checkbox\" name=\"packageName[]\" value=\"$path\" /> $file</span></li>";
                    } else {
                        echo "<li><span class=\"explorer-file\"> $file</span></li>";
                    }
                }

                /**
                 *  Affichage d'un sous-dossier
                 */
                function printSubDir($dir, $path) {
                    if ($dir == "my_uploaded_packages") { // Si le nom du répertoire est 'my_uploaded_packages' alors on l'affiche en jaune
                        echo "<li><span class=\"explorer-toggle\"><img src=\"icons/folder.png\" class=\"icon\" /> <span class=\"yellowtext\">$dir</span></span>";
                    } else {
                        echo "<li><span class=\"explorer-toggle\"><img src=\"icons/folder.png\" class=\"icon\" /> $dir</span>";
                    }
                    tree("$path/$dir"); // on rappelle la fonction principale afin d'afficher l'arbsorescence de ce sous-dossier
                    echo "</li>";
                }

                /**
                 *  On appelle la fonction tree permettant de construire l'arbisrescence de fichiers si on a bien reçu toutes les infos
                 */
                if ($pathError === 0) {
                    echo '<form action="" method="post" />';
                    echo '<input type="hidden" name="action" value="deletePackages" />';
                    echo '<button type="submit" class="button-submit-medium-red">Supprimer la sélection</button>';
                    
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

    <section class="mainSectionRight">
        <section class="right">
            <h3>ACTIONS</h3>
            <?php
                if ($pathError === 0 AND $state == 'active') {
                    /**
                     *  On vérifie qu'une opération n'est pas déjà en cours sur ce repo (mise à jour ou reconstruction du repo)
                     */
                    $stmt = $myrepo->db->prepare("SELECT * FROM operations WHERE action = 'update' AND Id_repo_target=:id AND Status = 'running'");
                    $stmt->bindValue(':id', $myrepo->id);
                    $result = $stmt->execute();
                    while ($datas = $result->fetchArray()) { $opRunning_update[] = $datas; }

                    $stmt2 = $myrepo->db->prepare("SELECT * FROM operations WHERE action = 'reconstruct' AND Id_repo_target=:id AND Status = 'running'");
                    $stmt2->bindValue(':id', $myrepo->id);
                    $result2 = $stmt2->execute();
                    while ($datas = $result2->fetchArray()) { $opRunning_reconstruct[] = $datas; }

                    if (!empty($opRunning_update)) {
                        echo '<p>';
                        echo '<img src="images/loading.gif" class="icon" /> ';
                        if ($OS_FAMILY == "Redhat") echo 'Une opération de mise à jour est en cours sur ce repo.';
                        if ($OS_FAMILY == "Debian") echo 'Une opération de mise à jour est en cours sur cette section.';
                        echo '</p>';
                    }

                    if (!empty($opRunning_reconstruct)) {
                        echo '<p>';
                        echo '<img src="images/loading.gif" class="icon" /> ';
                        if ($OS_FAMILY == "Redhat") echo 'Une opération de reconstruction est en cours sur ce repo.';
                        if ($OS_FAMILY == "Debian") echo 'Une opération de reconstruction est en cours sur cette section.';
                        echo '</p>';
                    }

                    /**
                     *  Si il n'y a aucune opération en cours, on affiche les boutons permettant d'effectuer des actions sur le repo/section
                     */
                    if (empty($opRunning_update) AND empty($opRunning_reconstruct)) {
                        echo '<p>Uploader des packages :</p>';
                        echo '<form action="" method="post" enctype="multipart/form-data">';
                        echo '<input type="hidden" name="action" value="uploadPackage" />';
                        echo '<input type="file" name="packages[]" accept="application/vnd.debian.binary-package" multiple />';
                        echo '<button type="submit" class="button-submit-medium-blue">Ajouter</button>';
                        echo '</form>';

                        /**
                         *  On affiche les messages d'erreurs issus du script d'upload (plus haut dans ce fichier) si il y en a
                         */
                        if (!empty($packageExists))  echo "<br><span class=\"redtext\">Les paquets suivants existent déjà et n'ont pas été chargés : <b>".rtrim($packageExists, ', ')."</b></span>";
                        if (!empty($packagesError))  echo "<br><span class=\"redtext\">Les paquets suivants sont en erreur et n'ont pas été chargés : <b>".rtrim($packagesError, ', ')."</b></span>";
                        if (!empty($packageEmpty))   echo "<br><span class=\"redtext\">Les paquets suivants semblent vides et n'ont pas été chargés : <b>".rtrim($packageEmpty, ', ')."</b></span>";
                        if (!empty($packageInvalid)) echo "<br><span class=\"redtext\">Les paquets suivants sont invalides et n'ont pas été chargés : <b>".rtrim($packageInvalid, ', ')."</b></span>";

                        echo '<hr>';
                
                        echo '<p><span id="rebuild-button" class="pointer"><img src="icons/update.png" class="icon" />Reconstruire les fichiers de metadonnées du repo</span></p>';
                        echo '<form id="hidden-form" class="hide" action="" method="post">';
                        echo '<input type="hidden" name="action" value="reconstruct">';
                        echo '<input type="hidden" name="repoId" value="'.$repoId.'">';
                        echo '<span>Signer avec GPG </span>';
                        echo '<label class="onoff-switch-label">';
                        echo '<input name="repoGpgResign" type="checkbox" class="onoff-switch-input" value="yes"'; if ($GPG_SIGN_PACKAGES == "yes") { echo 'checked'; } echo ' />';
                        echo '<span class="onoff-switch-slider"></span>';
                        echo '</label>';
                        echo '<span class="graytext">  (La signature avec GPG peut rallonger le temps de l\'opération)</span>';
                        echo '<br>';
                        echo '<button type="submit" class="button-submit-medium-red"><img src="icons/rocket.png" class="icon" />Exécuter</button>';
                        echo '</form>';
                    }
                
                } else {

                    echo '<p>Aucune action possible.</p>';

                }

            unset($myrepo);

            ?>
        </section>
    </section>
</article>

<?php include('includes/footer.inc.php'); ?>
</body>

<script>
    $(function() {
	// hide all the sub-menus
	$("span.explorer-toggle").next().hide();

	// add a link nudging animation effect to each link
    $("#explorer a, #explorer span.explorer-toggle").hover(
        function() {
            $(this).stop().animate( {
                paddingLeft: '10px',
            }, 200);
        },
        function() {
            $(this).stop().animate( {
                paddingLeft: '0',
            }, 200);
        }
    );

	// set the cursor of the toggling span elements
	$("span.explorer-toggle").css("cursor", "pointer");

	// prepend a plus sign to signify that the sub-menus aren't expanded
	$("span.explorer-toggle").prepend("+ ");

	// add a click function that toggles the sub-menu when the corresponding
	// span element is clicked
	$("span.explorer-toggle").click(function() {
		$(this).next().toggle(200);

		// switch the plus to a minus sign or vice-versa
		var v = $(this).html().substring( 0, 1 );
		if ( v == "+" )
			$(this).html( "-" + $(this).html().substring( 1 ) );
		else if ( v == "-" )
			$(this).html( "+" + $(this).html().substring( 1 ) );
	});
});

$(function() {
    $('#rebuild-button').click(function() {
        $('#hidden-form').toggle(200);
    });
});
</script>
</html>