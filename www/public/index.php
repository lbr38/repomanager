<!DOCTYPE html>
<html>
<?php
require_once('../models/Autoloader.php');
Autoloader::load();
include_once('../includes/head.inc.php');
require_once('../functions/repo.functions.php');
require_once('../common.php');
?>

<body>
<?php include_once('../includes/header.inc.php'); ?>

<article>
<!-- On charge la section de droite avant celle de gauche car celle-ci peut mettre plus de temps à charger (si bcp de repos) -->
<section class="mainSectionRight">
    <?php if (Common::isadmin()) { ?>
        <!-- GERER L'AFFICHAGE -->
        <?php include_once('../includes/display.inc.php'); ?>

        <!-- AJOUTER UN NOUVEAU REPO/SECTION -->
        <?php include_once('../templates/forms/op-form-new.inc.php'); ?> 
        
        <!-- EXECUTER DES OPERATIONS -->
        <?php include_once('../includes/operation.inc.php'); ?> 

        <!-- GERER LES GROUPES -->
        <?php include_once('../includes/manage-groups.inc.php'); ?>

        <!-- GERER LES SOURCES -->
        <?php include_once('../includes/manage-sources.inc.php'); ?>
    <?php } ?>

    <section class="right">
        <h3>PROPRIÉTÉS</h3>
        <div class="server-properties">
            <?php
            /**
             *  Récupération du total des repos actifs et repos archivés
             */
            $repo = new Repo();
            $totalRepos = $repo->countActive();
            $totalReposArchived = $repo->countArchived(); ?>

            <div class="round-div-medium">
                <p class="lowopacity">Repos</p>
                <div class="round-div-container">
                    <span><?=$totalRepos?></span>
                </div>
            </div>

    <?php   if (AUTOMATISATION_ENABLED == "yes") {
                $plan = new Planification();
                $lastPlan = $plan->listLast();

                if (!empty($lastPlan)) {
                    $planDate = $lastPlan['Date'];
                    $planTime = $lastPlan['Time'];
                    $planStatus = $lastPlan['Status'];
                    $lastPlan = '<a href="planifications.php" title="'.$planDate.' à '.$planTime.'">';
                    if ($planStatus == 'done') {
                        $lastPlan .= 'Terminée';
                    } else {
                        $lastPlan .= 'Erreur';
                    }
                    $lastPlan .= '</a>';
                } else {
                    $lastPlan = '-';
                } ?>

                <div class="<?php if (!empty($planStatus) AND $planStatus == 'error') echo 'round-div-medium-red'; else echo 'round-div-medium';?>">
                    <p class="lowopacity">Dernière planification</p>
                    <div class="round-div-container">
                        <span><?=$lastPlan?></span>
                    </div>
                </div>
    <?php   } ?>
            <div class="donut-chart-container">
                <?php
                $diskTotalSpace = disk_total_space('/');
                $diskFreeSpace = disk_free_space('/');
                $diskUsedSpace = $diskTotalSpace - $diskFreeSpace;
                $diskTotalSpace = $diskTotalSpace / 1073741824;
                $diskUsedSpace = $diskUsedSpace / 1073741824;
                /**
                 *  Formattage des données pour avoir un résultat sans virgule et un résultat en pourcentage
                 */
                $diskFreeSpace = round(100 - (($diskUsedSpace / $diskTotalSpace) * 100));
                $diskFreeSpacePercent = $diskFreeSpace;
                $diskUsedSpace = round(100 - ($diskFreeSpace));
                $diskUsedSpacePercent = round(100 - ($diskFreeSpace)); ?>

           
                <p class="donut-legend-title lowopacity"><b>/</b></p>
                <span class="donut-legend-content"><?=$diskUsedSpace.'%'?></span>
           

                <?php
                $donutChartName = 'donut-chart-1';
                include(ROOT.'/includes/donut.inc.php'); ?>
            </div>

            <div class="round-div-medium">
                <p class="lowopacity">Repos archivés</p>
                <div class="round-div-container">
                    <span><?=$totalReposArchived?></span>
                </div>
            </div>
      
    <?php   if (AUTOMATISATION_ENABLED == "yes") {
                $plan = new Planification();
                $nextPlan = $plan->listNext();

                if (!empty($nextPlan)) {
                    $nextPlan = '<a href="planifications.php">'.$nextPlan['Date'].' ('.$nextPlan['Time'].')</a>';
                } else {
                    $nextPlan = '-';
                } ?>

                <div class="round-div-medium">
                    <p class="lowopacity">Prochaine planification</p>
                    <div class="round-div-container">
                        <span><?=$nextPlan?></span>
                    </div>
                </div>
    <?php   } ?>
            
            <div class="donut-chart-container">
                <?php
                $diskTotalSpace = disk_total_space(REPOS_DIR);
                $diskFreeSpace = disk_free_space(REPOS_DIR);
                $diskUsedSpace = $diskTotalSpace - $diskFreeSpace;
                $diskTotalSpace = $diskTotalSpace / 1073741824;
                $diskUsedSpace = $diskUsedSpace / 1073741824;
                /**
                 *  Formattage des données pour avoir un résultat sans virgule et un résultat en pourcentage
                 */
                $diskFreeSpace = round(100 - (($diskUsedSpace / $diskTotalSpace) * 100));
                $diskFreeSpacePercent = $diskFreeSpace;
                $diskUsedSpace = round(100 - ($diskFreeSpace));
                $diskUsedSpacePercent = round(100 - ($diskFreeSpace));?>

                <p class="donut-legend-title lowopacity"><?=REPOS_DIR?></p>
                <span class="donut-legend-content"><?=$diskUsedSpace.'%'?></span>

                <?php
                $donutChartName = 'donut-chart-2';
                include(ROOT.'/includes/donut.inc.php');
                ?>
            </div>
        </div>
    </section>
</section>

<!-- section 'conteneur' principal englobant toutes les sections de gauche -->
<!-- On charge la section de gauche après celle de droite car elle peut mettre plus de temps à charger (si bcp de repos) -->
<section class="mainSectionLeft">
    <section class="left reposList">
        <!-- REPOS ACTIFS -->
        <?php include_once('../includes/repos-list-container.inc.php'); ?>
    </section>
    <section class="left reposList">
        <!-- REPOS ARCHIVÉS-->
        <?php include_once('../includes/repos-archive-list-container.inc.php'); ?>
    </section>
</section>
</article>

<?php include_once('../includes/footer.inc.php'); ?>

</body>
</html>