<!DOCTYPE html>
<html>
<?php include('includes/common-head.inc.php'); ?>
<!-- Import de JStree 
https://github.com/vakata/jstree/wiki#include-all-necessary-files -->
<script src="js/jstree/jstree.min.js"></script>
<!--<link rel="stylesheet" type='text/css' href="styles/jstree.css">-->
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/jstree/3.3.8/themes/default/style.min.css" />

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
<?php include('includes/common-header.inc.php');?>

<section class="mainSectionLeft">
    <section class="left">
        <h5>EXPLORER</h5>

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

        if (empty($_GET['env'])) {
            echo '<p>Aucun environnement renseigné</p>';
        } else {
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
            echo "<p>Explorer le contenu du repo <b>$repo</b> <span class=\"env\">$env</span></p>";
        }

        if ($OS_FAMILY == "Debian" AND !empty($repo) AND !empty($dist) AND !empty($section)) {
            echo "<p>Explorer le contenu de la section <b>$section</b> <span class=\"env\">$env</span> du repo <b>$repo</b> (distribution <b>$dist</b>)</p>";
        }
        ?>

        <form id="searchForm" autocomplete="off">
            <input type="search" id="searchInput" />
            <button type="submit" class="button-submit-medium-blue">Rechercher</button>
        </form>

        <br>

        <div id="explorer">
            <!-- Container affichant l'arborescence des fichiers avec JStree -->
        </div>
        
        <!-- JStree -->
        <?php 
        if (($OS_FAMILY == "Redhat" AND !empty($repo)) || ($OS_FAMILY == "Debian" AND !empty($repo) AND !empty($dist) AND !empty($section))) {
            echo "
            <script>
                $(function() {
                    $('#explorer').jstree({
                        \"plugins\" : [\"search\"],
                        'core' : {
                            'data' : {";
                                if ($OS_FAMILY == "Redhat") {
                                    echo "\"url\" : \"tree.php?repo=${repo}&env=${env}&state=${state}\",";
                                }
                                if ($OS_FAMILY == "Debian") {
                                    echo "\"url\" : \"tree.php?repo=${repo}&dist=${dist}&section=${section}&env=${env}&state=${state}\",";
                                }
                                echo "
                                \"data\" : function (node) {
                                return { \"id\" : node.id };
                                }
                            }
                        }
                    });
                });
                $(\"#searchForm\").submit(function(e) {
                    e.preventDefault();
                    $(\"#explorer\").jstree(true).search($(\"#searchInput\").val());
                  });
            </script>";
        }
        ?>
    </section>
</section>

<section class="mainSectionRight">
    <section class="right">
        <h5>ACTIONS</h5>
    </section>
</section>
<?php include('includes/common-footer.inc.php'); ?>
</body>
</html>