<!DOCTYPE html>
<html>
<?php
require_once('../models/Autoloader.php');
Autoloader::load();
include_once('../includes/head.inc.php');
require_once('../functions/repo.functions.php');
require_once('../common.php');

/**
 *  Destruction de certains cookies (temporaire)
 */
setcookie('operation_action', '', time() - 3600);
setcookie('operation_repos', '', time() - 3600);

/**
 *  Cas où on souhaite retirer une div ServerInfo de la page d'accueil
 */
if (!empty($_GET['serverInfoSlideDivClose'])) {
    /**
     *  On récupère le nom de la div qu'on souhaite retirer
     */
    $divToClose = Common::validateData($_GET['serverInfoSlideDivClose']);

    /**
     *  On récupère le contenu actuel de display.ini
     */
    $displayConfiguration = parse_ini_file(DISPLAY_CONF, true);
    if ($divToClose === "reposInfo")      $displayConfiguration['serverinfo']['display_serverInfo_reposInfo'] = 'no';
    if ($divToClose === "rootSpace")      $displayConfiguration['serverinfo']['display_serverInfo_rootSpace'] = 'no';
    if ($divToClose === "reposDirSpace")  $displayConfiguration['serverinfo']['display_serverInfo_reposDirSpace'] = 'no';
    if ($divToClose === "planInfo")       $displayConfiguration['serverinfo']['display_serverInfo_planInfo'] = 'no';
    if ($divToClose === "connectionInfo") $displayConfiguration['serverinfo']['display_serverInfo_connectionInfo'] = 'no';

    /**
     *  On écrit les modifications dans le fichier display.ini
     */
    Common::write_ini_file(DISPLAY_CONF, $displayConfiguration);

    /**
     *  Rechargement de la page pour appliquer les modifications d'affichage
     */
    header('Location: index.php');
    exit;
} ?>

<body>
<?php include_once('../includes/header.inc.php'); ?>

<article>
<!-- On charge la section de droite avant celle de gauche car celle-ci peut mettre plus de temps à charger (si bcp de repos) -->
<section class="mainSectionRight">
    <?php if (Common::isadmin()) { ?>
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

            <div class="round-div-small">
                <p class="lowopacity">Repos</p>
                <div class="round-div-container">
                    <span><?=$totalRepos?></span>
                </div>
            </div>

    <?php   if (AUTOMATISATION_ENABLED == "yes") {
                $plan = new Planification();
                $lastPlan = $plan->listLast();
                $planDate = $lastPlan['Date'];
                $planTime = $lastPlan['Time'];
                $planStatus = $lastPlan['Status'];

                if (!empty($lastPlan)) {
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

                <div class="<?php if ($planStatus == 'done') echo 'round-div-small'; else echo 'round-div-small-red';?>">
                    <p class="lowopacity">Dernière<br>planification</p>
                    <div class="round-div-container">
                        <span><?=$lastPlan?></span>
                    </div>
                </div>
    <?php   } ?>
            <div>
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
                $diskUsedSpacePercent = round(100 - ($diskFreeSpace));

                if ($diskUsedSpace > 0 && $diskUsedSpace <= 30) {
                    $donutColor = "rgb(92, 184, 92, 0.80)";
                }
                if ($diskUsedSpace > 30 && $diskUsedSpace <= 50) {
                    $donutColor = "rgb(240, 173, 78, 0.80)";
                }
                if ($diskUsedSpace > 50 && $diskUsedSpace <= 70) {
                    $donutColor = "rgb(240, 116, 78, 0.80)";
                }
                if ($diskUsedSpace > 70 && $diskUsedSpace <= 100) {
                    $donutColor = "rgb(217, 83, 79, 0.80)";
                }

                include(ROOT.'/includes/donut.inc.php'); ?>
            </div>

            <div class="round-div-small">
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

                <div class="round-div-small">
                    <p class="lowopacity">Prochaine<br>planification</p>
                    <div class="round-div-container">
                        <span><?=$nextPlan?></span>
                    </div>
                </div>
    <?php   } ?>
            
            <div>
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
                $diskUsedSpacePercent = round(100 - ($diskFreeSpace));

                include(ROOT.'/includes/donut.inc.php');
                ?>
            </div>
        </div>
    </section>


    <!-- <section id="serverInfoContainer"> -->
    <?php
    

    /**
     *  Graphique affichant l'espace utilisé sur le serveur ($path)
     */
    // function printSpace(string $path, string $name) {
    //     echo '<div class="serverInfo">';
    //     echo "<a href=\"index.php?serverInfoSlideDivClose=${name}\" title=\"Fermer\"><img class=\"icon-invisible float-right\" src=\"ressources/icons/close.png\" /></a>"; 
    //     echo "<p>$path</p>";    
    //     $diskTotalSpace = disk_total_space($path);
    //     $diskFreeSpace = disk_free_space($path);
    //     $diskUsedSpace = $diskTotalSpace - $diskFreeSpace;
    //     $diskTotalSpace = $diskTotalSpace / 1073741824;
    //     $diskUsedSpace = $diskUsedSpace / 1073741824;
    //     /**
    //      *  Formattage des données pour avoir un résultat sans virgule et un résultat en pourcentage
    //      */
    //     $diskFreeSpace = round(100 - (($diskUsedSpace / $diskTotalSpace) * 100));
    //     $diskFreeSpacePercent = $diskFreeSpace . '%';
    //     $diskUsedSpace = round(100 - ($diskFreeSpace));
    //     $diskUsedSpacePercent = round(100 - ($diskFreeSpace)) . '%';

    //     unset($diskTotalSpace);
    
    //     echo "<canvas id=\"diskSpaceChart-${name}\" class=\"chart\"></canvas>";
    //     echo '<script>';
    //     echo "var ctx = document.getElementById('diskSpaceChart-${name}').getContext('2d');
    //         var myDoughnutChart = new Chart(ctx, {
    //             type: 'doughnut',
    //             data: {
    //                 labels: ['Espace utilisé', 'Espace libre'],
    //                 datasets: [{
    //                     label: 'Espace disque utilisé',
    //                     data: [$diskUsedSpace, $diskFreeSpace],
    //                     backgroundColor: [";
                            /**
                             *  affichage de différentes couleurs suivant l'espace utilisé
                             */
                            // if ($diskUsedSpace > 0 && $diskUsedSpace <= 30) {
                            //     // vert
                            //     echo "'rgb(92, 184, 92, 0.80)',";
                            // }
                            // if ($diskUsedSpace > 30 && $diskUsedSpace <= 50) {
                            //     // jaune
                            //     echo "'rgb(240, 173, 78, 0.80)',";
                            // }
                            // if ($diskUsedSpace > 50 && $diskUsedSpace <= 70) {
                            //     // orange
                            //     echo "'rgb(240, 116, 78, 0.80)',";
                            // }
                            // if ($diskUsedSpace > 70 && $diskUsedSpace <= 100) {
                            //     // rouge
                            //     echo "'rgb(217, 83, 79, 0.80)',";
                            // }
    //                         echo "
    //                         'rgb(247, 247, 247, 0)' // transparent (opacité 0) (espace libre)
    //                     ],
    //                     borderColor: [
    //                         'gray',
    //                         'gray'
    //                     ],
    //                     borderWidth: 0.4
    //                 }]
    //             },
    //             options: {
    //                 aspectRatio: 1,
    //                 responsive: false,
    //                 legend: { // masquer les labels
    //                     display: false
    //                 },
    //             }
    //         });
    //         // Afficher le pourcentage utilisé à l'intérieur du graph :
    //         Chart.pluginService.register({
    //             beforeDraw: function(chart) {
    //                 var width = chart.chart.width,
    //                 height = chart.chart.height,
    //                 ctx = chart.chart.ctx;
    //                 ctx.restore();
    //                 var fontSize = (height / 114).toFixed(2);
    //                 ctx.font = fontSize + \"em sans-serif\";
    //                 ctx.fillStyle = \"white\";
    //                 ctx.textBaseline = \"middle\";
    //                 var text = \"${diskUsedSpacePercent}\",
    //                 textX = Math.round((width - ctx.measureText(text).width) / 2),
    //                 textY = height / 2;
    //                 ctx.fillText(text, textX, textY);
    //                 ctx.save();
    //             }
    //         });";
    //     echo '</script>';
    //     echo '</div>';

    //     unset($diskUsedSpace, $diskUsedSpacePercent, $diskFreeSpace, $diskFreeSpacePercent);
    // }

    // if (DISPLAY_SERVERINFO_ROOTSPACE == "yes") printSpace('/', 'rootSpace');
    // if (DISPLAY_SERVERINFO_REPOSDIRSPACE == "yes") printSpace(REPOS_DIR, 'reposDirSpace');
    ?>
        
    
    <!-- </section> -->
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