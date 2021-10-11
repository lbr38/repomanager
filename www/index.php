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
  require_once('class/Group.php');
  require_once('class/Planification.php');
  $repo = new Repo();
  $plan = new Planification();

  // Cas où on souhaite retirer une div ServerInfo de la page d'accueil
  if (!empty($_GET['serverInfoSlideDivClose'])) {
    // On récupère le nom de la div qu'on souhaite retirer
    $divToClose = validateData($_GET['serverInfoSlideDivClose']);
    // On récupère le contenu actuel de display.ini
    $displayConfiguration = parse_ini_file($DISPLAY_CONF, true);
    if ($divToClose === "reposInfo")      $displayConfiguration['serverinfo']['display_serverInfo_reposInfo'] = 'no';
    if ($divToClose === "rootSpace")      $displayConfiguration['serverinfo']['display_serverInfo_rootSpace'] = 'no';
    if ($divToClose === "reposDirSpace")  $displayConfiguration['serverinfo']['display_serverInfo_reposDirSpace'] = 'no';
    if ($divToClose === "planInfo")       $displayConfiguration['serverinfo']['display_serverInfo_planInfo'] = 'no';
    if ($divToClose === "connectionInfo") $displayConfiguration['serverinfo']['display_serverInfo_connectionInfo'] = 'no';

    // On écrit les modifications dans le fichier display.ini
    write_ini_file($DISPLAY_CONF, $displayConfiguration);

    // rechargement de la page pour appliquer les modifications d'affichage
    header('Location: index.php');
    exit;
  }
?>

<body>
<?php include('includes/header.inc.php'); ?>

<article>
<!-- section 'conteneur' principal englobant toutes les sections de droite -->
<!-- On charge la section de droite avant celle de gauche car celle-ci peut mettre plus de temps à charger (si bcp de repos) -->
<section class="mainSectionRight">
    <!-- AJOUTER UN NOUVEAU REPO/SECTION -->
    <section class="right" id="newRepoSlideDiv">
        <img id="newRepoCloseButton" title="Fermer" class="icon-lowopacity" src="icons/close.png" />
        <?php include('includes/create-repo.inc.php'); ?> 
    </section>

    <!-- div cachée, affichée par le bouton "Gérer les groupes" -->
    <!-- GERER LES GROUPES -->
    <section class="right" id="groupsDiv">
        <?php include('includes/manage-groups.inc.php'); ?>
    </section>

    <!-- div cachée, affichée par le bouton "Gérer les repos sources" -->
    <!-- GERER LES SOURCES -->
    <section class="right" id="sourcesDiv">
        <?php include('includes/manage-sources.inc.php'); ?>
    </section>

    <section id="serverInfoContainer">
    <?php
    if ($display_serverInfo_reposInfo == "yes") {
        // Récupération du total des repos actifs et repos archivés
        $totalRepos = $repo->countActive();
        $totalReposArchived = $repo->countArchived();

        echo '<div class="serverInfo">';
        echo '<a href="index.php?serverInfoSlideDivClose=reposInfo" title="Fermer"><img class="icon-invisible float-right" src="icons/close.png" /></a>';
        // nombre de repos/sections sur le serveur
        if ($OS_FAMILY == "Redhat") echo '<p>Repos</p>';
        if ($OS_FAMILY == "Debian") echo '<p>Sections</p>';
        echo "<b>${totalRepos}</b>";

        // nombre de repos/sections archivés sur le serveur
        if ($OS_FAMILY == "Redhat") echo '<p>Repos archivés</p>';
        if ($OS_FAMILY == "Debian") echo '<p>Sections archivées</p>';
        echo "<b>${totalReposArchived}</b>";
        echo '</div>';
    }

    /**
     *  Graphique affichant l'espace utilisé sur le serveur ($path)
     */
    function printSpace(string $path, string $name) {
        echo '<div class="serverInfo">';
        echo "<a href=\"index.php?serverInfoSlideDivClose=${name}\" title=\"Fermer\"><img class=\"icon-invisible float-right\" src=\"icons/close.png\" /></a>";
        
        echo "<p>$path</p>";
    
        $diskTotalSpace = disk_total_space($path);
        $diskFreeSpace = disk_free_space($path);
        $diskUsedSpace = $diskTotalSpace - $diskFreeSpace;
        $diskTotalSpace = $diskTotalSpace / 1073741824;
        $diskUsedSpace = $diskUsedSpace / 1073741824;
        /**
         *  Formattage des données pour avoir un résultat sans virgule et un résultat en pourcentage
         */
        $diskFreeSpace = round(100 - (($diskUsedSpace / $diskTotalSpace) * 100));
        $diskFreeSpacePercent = $diskFreeSpace . '%';
        $diskUsedSpace = round(100 - ($diskFreeSpace));
        $diskUsedSpacePercent = round(100 - ($diskFreeSpace)) . '%';

        unset($diskTotalSpace);
    
        echo "<canvas id=\"diskSpaceChart-${name}\" class=\"chart\"></canvas>";
        echo '<script>';
        echo "var ctx = document.getElementById('diskSpaceChart-${name}').getContext('2d');
            var myDoughnutChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Espace utilisé', 'Espace libre'],
                    datasets: [{
                        label: 'Espace disque utilisé',
                        data: [$diskUsedSpace, $diskFreeSpace],
                        backgroundColor: [";
                            /**
                             *  affichage de différentes couleurs suivant l'espace utilisé
                             */
                            if ($diskUsedSpace > 0 && $diskUsedSpace <= 30) {
                                // vert
                                echo "'rgb(92, 184, 92, 0.80)',";
                            }
                            if ($diskUsedSpace > 30 && $diskUsedSpace <= 50) {
                                // jaune
                                echo "'rgb(240, 173, 78, 0.80)',";
                            }
                            if ($diskUsedSpace > 50 && $diskUsedSpace <= 70) {
                                // orange
                                echo "'rgb(240, 116, 78, 0.80)',";
                            }
                            if ($diskUsedSpace > 70 && $diskUsedSpace <= 100) {
                                // rouge
                                echo "'rgb(217, 83, 79, 0.80)',";
                            }
                            echo "
                            'rgb(247, 247, 247, 0)' // transparent (opacité 0) (espace libre)
                        ],
                        borderColor: [
                            'gray',
                            'gray'
                        ],
                        borderWidth: 0.4
                    }]
                },
                options: {
                    aspectRatio: 1,
                    responsive: false,
                    legend: { // masquer les labels
                        display: false
                    },
                }
            });
            // Afficher le pourcentage utilisé à l'intérieur du graph :
            Chart.pluginService.register({
                beforeDraw: function(chart) {
                    var width = chart.chart.width,
                    height = chart.chart.height,
                    ctx = chart.chart.ctx;
                    ctx.restore();
                    var fontSize = (height / 114).toFixed(2);
                    ctx.font = fontSize + \"em sans-serif\";
                    ctx.fillStyle = \"white\";
                    ctx.textBaseline = \"middle\";
                    var text = \"${diskUsedSpacePercent}\",
                    textX = Math.round((width - ctx.measureText(text).width) / 2),
                    textY = height / 2;
                    ctx.fillText(text, textX, textY);
                    ctx.save();
                }
            });";
        echo '</script>';
        echo '</div>';

        unset($diskUsedSpace, $diskUsedSpacePercent, $diskFreeSpace, $diskFreeSpacePercent);
    }

    if ($display_serverInfo_rootSpace == "yes") printSpace('/', 'rootSpace');
    if ($display_serverInfo_reposDirSpace == "yes") printSpace($REPOS_DIR, 'reposDirSpace');
    ?>
        
    <?php if ($AUTOMATISATION_ENABLED == "yes" AND $display_serverInfo_planInfo == "yes") {
        echo '<div class="serverInfo">';
        echo '<a href="index.php?serverInfoSlideDivClose=planInfo" title="Fermer"><img class="icon-invisible float-right" src="icons/close.png" /></a>';
        echo '<p>Dernière planification</p>';
        $lastPlan = $plan->last();
        if (empty($lastPlan)) {
            echo '<b>-</b>';
        } else {
            $lastPlanDate = DateTime::createFromFormat('Y-m-d', $lastPlan['Date'])->format('d-m-Y');
            $lastPlanTime = $lastPlan['Time'];
            echo "<a href=\"planifications.php\"><b>${lastPlanDate} (${lastPlanTime})</b></a>";
        }
        echo '<p>Prochaine planification</p>';
        $nextPlan = $plan->next();
        if (empty($nextPlan)) {
            echo '<b>-</b>';
        } else {
            $nextPlanDate = DateTime::createFromFormat('Y-m-d', $nextPlan['Date'])->format('d-m-Y');
            $nextPlanTime = $nextPlan['Time'];
            echo "<a href=\"planifications.php\"><b>${nextPlanDate} (${nextPlanTime})</b></a>";
        }
        echo '</div>';
    }
    // Ne fonctionne pas correctement
    /*if ($display_serverInfo_connectionInfo == "yes") {
        echo '<div class="serverInfo">';
        echo '<a href="index.php?serverInfoSlideDivClose=connectionInfo" title="Fermer"><img class="icon-invisible float-right" src="icons/close.png" /></a>';
        echo '<p>Connexions actives</p>';
        $connections = exec("netstat -an | grep ${serverIP}:80 | grep ESTABLISHED | wc -l");
        echo "<b>${connections}</b>";
        echo '</div>';
    }*/
    ?>
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

</body>
</html>