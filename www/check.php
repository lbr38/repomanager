<!DOCTYPE html>
<html>
<?php include('common-head.inc.php'); ?>

<?php
  /**
   *  Import des variables et fonctions nécessaires
   */
  require_once('functions/load_common_variables.php');
  require_once('functions/load_display_variables.php');
  require_once('functions/common-functions.php');
  require_once('common.php');
  require_once('class/Repo.php');
  $repo = new Repo();
  if ($DEBUG_MODE == "enabled") { echo 'Mode debug activé : ';	echo '<br>POST '; print_r($_POST); echo '<br>GET ';	print_r($_GET); }
?>

<body>
<?php include('common-header.inc.php');

// On vérifie qu'une action a été demandée
if (empty($_GET['actionId'])) {
    printAlert("Aucune action n'a été demandée");
    return 1;
} else { 
    // et on la récupère si c'est le cas
    $actionId = validateData($_GET['actionId']);
}
?>

<!-- section 'conteneur' principal englobant toutes les sections de droite -->
<!-- On charge la section de droite avant celle de gauche car celle-ci peut mettre plus de temps à charger (si bcp de repos) -->
<section class="mainSectionRight">
  <section class="right">
    <?php

    // Import du fichier de la fonction checkArguments
    include 'operations_prechecks/checkArguments.inc.php';

    // Titre du cadre de droite
    if ($actionId === "newRepo") {
        if ($OS_FAMILY === "Redhat") { echo '<h5>CRÉER UN NOUVEAU REPO</h5>';        }
        if ($OS_FAMILY === "Debian") { echo '<h5>CRÉER UNE NOUVELLE SECTION</h5>';   }
    }
    if ($actionId === "updateRepo") {
        if ($OS_FAMILY === "Redhat") { echo '<h5>METTRE À JOUR UN REPO</h5>';        }
        if ($OS_FAMILY === "Debian") { echo '<h5>METTRE À JOUR UNE SECTION</h5>';    }
    }
    if ($actionId === "changeEnv") {
        if ($OS_FAMILY === "Redhat") { echo '<h5>CHANGEMENT D\'ENVIRONNEMENT D\'UN REPO</h5>';       }
        if ($OS_FAMILY === "Debian") { echo '<h5>CHANGEMENT D\'ENVIRONNEMENT D\'UNE SECTION</h5>';   }
    }
    if ($actionId === "duplicateRepo") {
        echo '<h5>DUPLIQUER UN REPO</h5>';
    }
    if ($actionId === "deleteSection") { // uniquement pour Debian
        if ($OS_FAMILY === "Debian") { echo '<h5>SUPPRIMER UNE SECTION</h5>';    }
    }
    if ($actionId === "deleteDist") { // uniquement pour Debian
        if ($OS_FAMILY === "Debian") { echo '<h5>SUPPRIMER UNE DISTRIBUTION</h5>';    }
    }
    if ($actionId === "deleteRepo") {
        echo '<h5>SUPPRIMER UN REPO</h5>';
    }
    if ($actionId === "deleteOldRepo") {
        if ($OS_FAMILY === "Redhat") { echo '<h5>SUPPRIMER UN REPO ARCHIVÉ</h5>';        }
        if ($OS_FAMILY === "Debian") { echo '<h5>SUPPRIMER UNE SECTION ARCHIVÉE</h5>';   }
    }
    if ($actionId === "restoreOldRepo") {
        if ($OS_FAMILY === "Redhat") { echo '<h5>RESTAURER UN REPO ARCHIVÉ</h5>';        }
        if ($OS_FAMILY === "Debian") { echo '<h5>RESTAURER UNE SECTION ARCHIVÉE</h5>';   }
    }
    ?>

    <form action="check.php" method="get" class="actionform" autocomplete="off">
        <table class="actiontable">
        <?php
            if ($actionId === "newRepo")         { include 'operations_prechecks/precheck_newRepo.inc.php'; precheck_newRepo();               }
            if ($actionId === "updateRepo")      { include 'operations_prechecks/precheck_updateRepo.inc.php'; precheck_updateRepo();         }
            if ($actionId === "changeEnv")       { include 'operations_prechecks/precheck_changeEnv.inc.php'; precheck_changeEnv();           }
            if ($actionId === "duplicateRepo")   { include 'operations_prechecks/precheck_duplicateRepo.inc.php'; precheck_duplicateRepo();   }
            if ($actionId === "deleteSection")   { include 'operations_prechecks/precheck_deleteSection.inc.php'; precheck_deleteSection();   }
            if ($actionId === "deleteDist")      { include 'operations_prechecks/precheck_deleteDist.inc.php'; precheck_deleteDist();         }
            if ($actionId === "deleteRepo")      { include 'operations_prechecks/precheck_deleteRepo.inc.php'; precheck_deleteRepo();         }
            if ($actionId === "deleteOldRepo")   { include 'operations_prechecks/precheck_deleteOldRepo.inc.php'; precheck_deleteOldRepo();   }
            if ($actionId === "restoreOldRepo")  { include 'operations_prechecks/precheck_restoreOldRepo.inc.php'; precheck_restoreOldRepo(); }
        ?>
        </table>
    </form>
  </section>
</section>

<!-- section 'conteneur' principal englobant toutes les sections de gauche -->
<!-- On charge la section de gauche après celle de droite car elle peut mettre plus de temps à charger (si bcp de repos) -->
<section class="mainSectionLeft">
    <section class="left">
        <!-- REPOS ACTIFS -->
        <?php include('common-repos-list.inc.php'); ?>
    </section>
    <section class="left">
        <!-- REPOS ARCHIVÉS-->
        <?php include('common-repos-archive-list.inc.php'); ?>
    </section>
</section>

<?php include('common-footer.inc.php'); ?>

<script>
// Le clic sur le bouton confirmer fait afficher l'icone de chargement (gif) et fait disparaitre le bouton confirmer
$(document).ready(function(){
    $("#confirmButton").click(function(){
        $(".loading").slideToggle(0);
        $(this).toggleClass("open");

        $("#confirmButton").slideToggle(0);
        $(this).toggleClass("open");
    });
});
</script>

</body>
</html>