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
?>

<body>
<?php include('includes/header.inc.php');?>

<article>
    <section class="mainSectionLeft">
        <section class="left">
            <h3>EXPLORER</h3>

            <?php
            if (empty($_GET['repo'])) { 
                echo '<p>Aucun repo renseigné</p>';
            } else {
                $repo = validateData($_GET['repo']);
            }

            if ($OS_FAMILY == "Debian") {
                if (empty($_GET['dist'])) {
                    echo '<p>Aucune distribution renseignée</p>';
                } else {
                    $dist = validateData($_GET['dist']);
                }
                if (empty($_GET['section'])) {
                    echo '<p>Aucune section renseignée</p>';
                } else {
                    $section = validateData($_GET['section']);
                }
            }

            if (empty($_GET['date'])) {
                echo '<p>Aucune date renseignée</p>';
            } else {
                $date = validateData($_GET['date']);
            } 

            /**
             *  Env : optionnel, on récupère si existe
             */
            if (!empty($_GET['env'])) {
                $env = validateData($_GET['env']);
            } 
            
            if (empty($_GET['state'])) {
                echo '<p>Etat non renseigné</p>';
                return;
            } else {
                $state = validateData($_GET['state']);
                if ($state != "active" AND $state != "archived") {
                    echo '<p>Erreur : Etat invalide</p>';
                    return;
                }
            } ?>

            <?php
            if ($OS_FAMILY == "Redhat" AND !empty($repo)) {
                if ($state == "active") {
                    echo "<p>Explorer le contenu du repo <b>$repo</b> " . envtag($env);
                }
                if ($state == "archived") {
                    echo "<p>Explorer le contenu du repo archivé <b>$repo</b></p>";
                }
            }

            if ($OS_FAMILY == "Debian" AND !empty($repo) AND !empty($dist) AND !empty($section)) {
                if ($state == "active") {
                    echo "<p>Explorer le contenu de la section <b>$section</b> " . envtag($env) . " du repo <b>$repo</b> (distribution <b>$dist</b>)</p>";
                }
                if ($state == "archived") {
                    echo "<p>Explorer le contenu de la section archivée <b>$section</b> du repo <b>$repo</b> (distribution <b>$dist</b>)</p>";
                }
            }
            ?>
    <!--
            <form id="searchForm" autocomplete="off">
                <input type="search" id="searchInput" />
                <button type="submit" class="button-submit-medium-blue">Rechercher</button>
            </form>-->

            <br>

            <div id="explorer">
                <?php

                /**
                 *  Fonctions basées sur : https://phpfog.com/directory-trees-with-php-and-jquery/
                 */
                function tree($path) {
                    if ($handle = opendir($path)) {

                        echo "<ul>";
                        $queue = array(); // initialisation d'un tableau qui contiendra la liste des fichiers d'un répertoire

                        while (false !== ($file = readdir($handle))) {
                            if (is_dir("$path/$file") && $file != '.' && $file !='..') {
                                printSubDir($file, $path, $queue);
                                
                            } else if ($file != '.' && $file !='..') {
                                $queue[] = $file;
                            }
                        }
                
                        printQueue($queue, $path);
                        echo "</ul>";
                    }
                }
                
                /**
                 *  Affichage de tous les fichiers d'un répertoire
                 */
                function printQueue($queue, $path) {
                    foreach ($queue as $file) {
                        printFile($file, $path);
                    }
                }
                
                /**
                 *  Affichage d'un fichier
                 */
                function printFile($file, $path) {
                    echo "<li><span class=\"explorer-file\">$file</span></li>";
                }
                
                /**
                 *  Affichage d'un sous-dossier
                 */
                function printSubDir($dir, $path) {
                    echo "<li><span class=\"explorer-toggle\"><img src=\"icons/folder.png\" class=\"icon\" />$dir</span>";
                    tree("$path/$dir"); // on rappelle la fonction principale afin d'afficher l'arbsorescence de ce sous-dossier
                    echo "</li>";
                }
                
                /**
                 *  On appelle la fonction tree permettant de construire l'arbisrescence de fichiers si on a bien reçu toutes les infos
                 */
                if ($OS_FAMILY == "Redhat" AND !empty($repo) AND !empty($env)) {
                    tree("${REPOS_DIR}/${repo}_${env}");
                } 
                
                if ($OS_FAMILY == "Debian" AND !empty($repo) AND !empty($dist) AND !empty($section) AND !empty($env)) {
                    tree("${REPOS_DIR}/$repo/$dist/${section}_${env}");
                } ?>
            </div>
        </section>
    </section>

    <section class="mainSectionRight">
        <section class="right">
            <h3>ACTIONS (à venir)</h3>
            <p>Uploader un package</p>
            <p>Construire les fichiers de metadonnées du repo</p>
            <p>Signer le repo</p>
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
</script>
</html>