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
require_once('class/Operation.php');
?>

<body>
<?php include('includes/header.inc.php');

// On vérifie qu'une action a été demandée
if (empty($_GET['action'])) {
    printAlert("Aucune action n'a été demandée");
} else { 
    // et on la récupère si c'est le cas
    $op_action = validateData($_GET['action']);
    $op_type = 'manual';
    $op = new Operation(compact('op_action', 'op_type'));
    unset($op_action, $op_type);
}
?>

<article>
<!-- section 'conteneur' principal englobant toutes les sections de droite -->
<!-- On charge la section de droite avant celle de gauche car celle-ci peut mettre plus de temps à charger (si bcp de repos) -->
<section class="mainSectionRight">
    <section class="right">
        <form action="" method="get" autocomplete="off">
            <input type="hidden" name="action" value="<?php echo $op->action;?>" />

            <div id="op_input_container">
            <?php
                /**
                 *  Titre de l'action dans le cadre de droite
                 */
                if ($op->action === "new")           { $op->new(); }
                if ($op->action === "update")        { $op->update(); }               
                if ($op->action === "changeEnv")     { $op->changeEnv(); }
                if ($op->action === "duplicate")     { $op->duplicate(); }
                if ($op->action === "delete")        { $op->delete(); }
                if ($op->action === "deleteDist")    { $op->deleteDist(); }    // uniquement pour Debian
                if ($op->action === "deleteSection") { $op->deleteSection(); } // uniquement pour Debian
                if ($op->action === "deleteArchive") { $op->deleteArchive(); }
                if ($op->action === "restore")       { $op->restore(); }
            ?>
            </div>
        </form>
    </section>
</section>

<!-- section 'conteneur' principal englobant toutes les sections de gauche -->
<!-- On charge la section de gauche après celle de droite car elle peut mettre plus de temps à charger (si bcp de repos) -->
<section class="mainSectionLeft">
    <section class="left">
        <!-- REPOS ACTIFS -->
        <?php include('includes/repos-list.inc.php'); ?>
    </section>
    <section class="left">
        <!-- REPOS ARCHIVÉS-->
        <?php include('includes/repos-archive-list.inc.php'); ?>
    </section>
</section>
</article>

<?php include('includes/footer.inc.php'); ?>

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