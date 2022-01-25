<!DOCTYPE html>
<html>
<?php
require_once('models/Autoloader.php');
Autoloader::load();
include_once('includes/head.inc.php');
require_once('functions/common-functions.php');
require_once('functions/repo.functions.php');
require_once('common.php');
?>

<body>
<?php 
include('includes/header.inc.php');

$action_error = 0;
$id_error = 0;

/**
 *  On vérifie qu'une action a été demandée
 */
if (empty($_GET['action'])) {
    printAlert("Aucune action n'a été demandée", 'error');
    $action_error++;
}

if ($action_error == 0) {
    /**
     *  On récupère le nom de l'action
     */
    $op_action = Common::validateData($_GET['action']);

    /**
     *  Ici on a lancé l'opération à la main (ce n'est pas une planification)
     */
    $op_type = 'manual';

    /**
     *  Instanciation d'une nouvelle opération
     */
    $op = new Operation(compact('op_action', 'op_type'));

    /**
     *  On vérifie qu'un ID de repo a été précisé
     *  Seule l'opération 'new' ne précise pas d'ID puisque le repo à créer n'existe pas en BDD
     */
    if (empty($_GET['id']) AND $op->action != "new") {
        printAlert("Aucun id de repo n'a été précisé", 'error');
        $id_error++;
    }

    /**
     *  A partir de l'ID fourni, on va pouvoir récupérer toutes les infos du repo à traiter
     *  Ignoré si l'action est 'new'
     */
    if ($id_error == 0 AND $op->action != "new") {
        /**
         *  Récupération de l'ID
         */
        $op->repo->id = $_GET['id'];

        if (!is_numeric($op->repo->id)) {
            printAlert("L'id de repo doit être un nombre", 'error');
            $id_error++;
        }

        /**
         *  A partir de l'id, on récupère les infos du repo en BDD
         *  Si l'action renseignée est 'restore' ou 'deleteArchive' alors db_getAllById() devra chercher dans la table des repos archivés
         */
        if ($id_error == 0) {
            if ($op->action == "restore" OR $op->action == "deleteArchive") {
                $op->repo->db_getAllById('archived');
            } else {
                $op->repo->db_getAllById();
            }
        }
    }

    unset($op_action, $op_type);
}
?>

<article>
<!-- section 'conteneur' principal englobant toutes les sections de droite -->
<!-- On charge la section de droite avant celle de gauche car celle-ci peut mettre plus de temps à charger (si bcp de repos) -->
<?php
if ($action_error == 0 AND $id_error == 0) {
    echo '<section class="mainSectionRight">';
        echo '<section class="right">';
            echo '<form action="" method="get" autocomplete="off">';
                /**
                 *  On retransmets l'action et l'ID du repo dans un champ caché du formulaire
                 */
                echo '<input type="hidden" name="action" value="'.$op->action.'" />';
                if ($op->action != "new") {
                    echo '<input type="hidden" name="id" value="'.$op->repo->id.'" />';
                }

                echo '<div id="op_input_container">';
                    /**
                     *  Exécution de l'action souhaitée
                     */
                    if ($op->action === "new")           $op->new();
                    if ($op->action === "update")        $op->update();
                    if ($op->action === "changeEnv")     $op->changeEnv();
                    if ($op->action === "duplicate")     $op->duplicate();
                    if ($op->action === "delete")        $op->delete();
                    if ($op->action === "deleteDist")    $op->deleteDist();
                    if ($op->action === "deleteSection") $op->deleteSection();
                    if ($op->action === "deleteArchive") $op->deleteArchive();
                    if ($op->action === "restore")       $op->restore();
                echo '</div>';
            echo '</form>';
        echo '</section>';
    echo '</section>';
}
?>

<!-- section 'conteneur' principal englobant toutes les sections de gauche -->
<!-- On charge la section de gauche après celle de droite car elle peut mettre plus de temps à charger (si bcp de repos) -->
<section class="mainSectionLeft">
    <section class="left">
        <!-- REPOS ACTIFS -->
        <?php include('includes/repos-list-container.inc.php'); ?>
    </section>
    <section class="left">
        <!-- REPOS ARCHIVÉS-->
        <?php include('includes/repos-archive-list-container.inc.php'); ?>
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