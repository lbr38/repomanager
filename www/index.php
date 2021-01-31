<html>
<?php include('common-head.inc.php'); ?>

<?php
  // Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
  require_once 'vars/common.vars';
  require_once 'common-functions.php';
  require_once 'common.php';
  require_once 'vars/display.vars';
  if ($debugMode == "enabled") { echo "Mode debug activé : "; print_r($_POST); }

  // Cas où on souhaite retirer une div ServerInfo de la page d'accueil
  if (!empty($_GET['serverInfoSlideDivClose'])) {
      // On récupère le nom de la div qu'on souhaite retirer
      $divToClose = validateData($_GET['serverInfoSlideDivClose']);

      if ($divToClose === "reposInfo") {
        exec("sed -i 's/\$display_serverInfo_reposInfo = \"yes\"/\$display_serverInfo_reposInfo = \"no\"/g' ${WWW_DIR}/vars/display.vars");
      }

      if ($divToClose === "rootSpace") {
        exec("sed -i 's/\$display_serverInfo_rootSpace = \"yes\"/\$display_serverInfo_rootSpace = \"no\"/g' ${WWW_DIR}/vars/display.vars");
      }

      if ($divToClose === "reposDirSpace") {
        exec("sed -i 's/\$display_serverInfo_reposDirSpace = \"yes\"/\$display_serverInfo_reposDirSpace = \"no\"/g' ${WWW_DIR}/vars/display.vars");
      }

      if ($divToClose === "planInfo") {
        exec("sed -i 's/\$display_serverInfo_planInfo = \"yes\"/\$display_serverInfo_planInfo = \"no\"/g' ${WWW_DIR}/vars/display.vars");
      }

    // rechargement de la page pour appliquer les modifications d'affichage
    header("Refresh:0; url=index.php");
  }
?>

<body>
<?php include('common-header.inc.php'); ?>

<!-- section 'conteneur' principal englobant toutes les sections de gauche -->
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

<!-- section 'conteneur' principal englobant toutes les sections de droite -->
<section class="mainSectionRight">
    <!-- AJOUTER UN NOUVEAU REPO/SECTION -->
    <section class="right" id="newRepoSlideDiv">
        <a href="#" id="newRepoCloseButton" title="Fermer"><img class="icon-lowopacity" src="icons/close.png" /></a>
        <?php include('common-operations.inc.php'); ?> 
    </section>

    <!--<section class="right" id="serverInfoSlideDiv">-->
    <section id="serverInfoSlideDiv">
    <?php
    if ($display_serverInfo_reposInfo == "yes") {
        // Calcul du total des repos, en supprimant les doublons
        $totalRepos = exec("grep  '^Name=' $REPOS_LIST | awk -F ',' '{print $1}' | cut -d'=' -f2 | sed 's/\"//g' | uniq | wc -l");
        $totalReposArchived = exec("grep  '^Name=' $REPOS_ARCHIVE_LIST | awk -F ',' '{print $1}' | cut -d'=' -f2 | sed 's/\"//g' | uniq | wc -l");
    
        echo '<div class="serverInfo">';
        echo '<a href="index.php?serverInfoSlideDivClose=reposInfo" title="Fermer" class="float-right"><img class="icon-invisible" src="icons/close.png" /></a>';
        // nombre de repos/sections sur le serveur
        if ($OS_FAMILY == "Redhat") { echo '<p>Repos</p>'; }
        if ($OS_FAMILY == "Debian") { echo '<p>Sections</p>'; }
        echo "<b>${totalRepos}</b>";

        // nombre de repos/sections archivés sur le serveur
        if ($OS_FAMILY == "Redhat") { echo '<p>Repos archivés</p>'; }
        if ($OS_FAMILY == "Debian") { echo '<p>Sections archivées</p>'; }
        echo "<b>${totalReposArchived}</b>";
        echo '</div>';
    }

    if ($display_serverInfo_rootSpace == "yes") {
        echo '<div class="serverInfo">';
        echo '<a href="index.php?serverInfoSlideDivClose=rootSpace" title="Fermer" class="float-right"><img class="icon-invisible" src="icons/close.png" /></a>';
        // graphique affichant l'espace utilisé sur le serveur (racine)
        echo '<p>/</p>';
    
        $diskTotalSpace = disk_total_space("/");
        $diskFreeSpace = disk_free_space("/");
        $diskUsedSpace = $diskTotalSpace - $diskFreeSpace;
        $diskTotalSpace = $diskTotalSpace / 1073741824;
        $diskUsedSpace = $diskUsedSpace / 1073741824;
        // Formattage des données pour avoir un résultat sans virgule et un résultat en poucentage
        $diskFreeSpace = round(100 - (($diskUsedSpace / $diskTotalSpace) * 100));
        $diskFreeSpacePercent = $diskFreeSpace . '%';
        $diskUsedSpace = round(100 - ($diskFreeSpace));
        $diskUsedSpacePercent = round(100 - ($diskFreeSpace)) . '%';
    
        echo '<canvas id="diskSpaceChart" class="chart"></canvas>';
        echo '<script>';
        echo "
            var ctx = document.getElementById('diskSpaceChart').getContext('2d');
            var myDoughnutChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Espace utilisé', 'Espace libre'],
                    datasets: [{
                        label: 'Espace disque utilisé',
                        data: [$diskUsedSpace, $diskFreeSpace],
                        backgroundColor: [";
                            // affichage de différente couleur suivant l'espace utilisé
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
    }?>

    <?php
    if ($display_serverInfo_reposDirSpace == "yes") {
        echo '<div class="serverInfo">';
        echo '<a href="index.php?serverInfoSlideDivClose=reposDirSpace" title="Fermer" class="float-right"><img class="icon-invisible" src="icons/close.png" /></a>';
        // graphique affichant l'espace utilisé par le répertoire des repos
        echo "<p>${REPOS_DIR}</p>";
        $diskTotalSpace = disk_total_space("${REPOS_DIR}");
        $diskFreeSpace = disk_free_space("${REPOS_DIR}");
        $diskUsedSpace = $diskTotalSpace - $diskFreeSpace;
        $diskTotalSpace = $diskTotalSpace / 1073741824;
        $diskUsedSpace = $diskUsedSpace / 1073741824;
        // Formattage des données pour avoir un résultat sans virgule et un résultat en poucentage
        $diskFreeSpace = round(100 - (($diskUsedSpace / $diskTotalSpace) * 100));
        $diskFreeSpacePercent = $diskFreeSpace . '%';
        $diskUsedSpace = round(100 - ($diskFreeSpace));
        $diskUsedSpacePercent = round(100 - ($diskFreeSpace)) . '%';

        echo '<canvas id="diskSpaceChart2" class="chart"></canvas>';
        echo '<script>';
        echo "
            var ctx = document.getElementById('diskSpaceChart2').getContext('2d');
            var myDoughnutChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Espace utilisé', 'Espace libre'],
                    datasets: [{
                        label: 'Espace disque utilisé',
                        data: [$diskUsedSpace, $diskFreeSpace],
                        backgroundColor: [";
                            // affichage de différente couleur suivant l'espace utilisé
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
    }
    ?>
        
    <?php if ($AUTOMATISATION_ENABLED == "yes" AND $display_serverInfo_planInfo == "yes") {
        echo '<div class="serverInfo">';
        echo '<a href="index.php?serverInfoSlideDivClose=planInfo" title="Fermer" class="float-right"><img class="icon-invisible" src="icons/close.png" /></a>';
        echo '<p>Dernière planification</p>';
        if (!file_exists("$PLAN_LOG")) {
            echo '<b>N/A</b>';
        } else {
            // Récup du dernier ID de planification dans le fichier de log
            $lastPlanID = exec("grep '\[Plan-' $PLAN_LOG | tail -n1 | sed 's/\[Plan\-//g; s/\]//g'");
            // Récup de toutes les informations et l'état de cette planification en utilisant la fonction planLogExplode
            $plan = planLogExplode($lastPlanID, $PLAN_LOG, $OS_FAMILY); // Le tout est retourné dans un tableau et placé dans $plan
            $planStatus = $plan[0];
            $planError = $plan[1];
            $planDate = $plan[2];
            $planTime = $plan[3];
            // Affichage du status, de la date et heure
            echo '<a href="planifications.php"><b>';
            if ($planStatus === "Error") {
                if (!empty($planDate)) { // Si une date a été retournée on l'affiche
                    echo "$planDate ";
                }
                
                if (!empty($planTime)) { // Si une heure a été retournée on l'affiche
                    echo "à $planTime ";
                }
                echo 'Erreur';
            }
            if ($planStatus === "OK") {
                if (!empty($planDate)) { // Si une date a été retournée on l'affiche
                    echo "$planDate ";
                }
                
                if (!empty($planTime)) { // Si une heure a été retournée on l'affiche
                    echo "à $planTime ";
                }
                echo 'OK';
            }
            echo '</b></a>';
        }

        echo '<p>Prochaine planification</p>';
        if (!file_exists("$PLAN_CONF")) {
            echo '<b>N/A</b>';
        } else {
            // première étape : on récupère toutes les dates dans le fichier de planifications, on trie la liste et on récupère la première de la liste
            $nextPlanDate = exec("grep '^Date=' $PLAN_CONF | cut -d'=' -f2 | sed 's/\"//g' | sort | head -n1");
            // ensuite si cette date apparait plusieurs fois dans le fichier de planifications (plusieurs planifications le même jour) on trie par heure
            $countDate = exec("grep '${nextPlanDate}' $PLAN_CONF | wc -l");
            if ($countDate === "1") {
                // récupération de l'heure et affichage
                $nextPlanTime = exec("sed -n '/${nextPlanDate}/{n;p;}' $PLAN_CONF | cut -d'=' -f2 | sed 's/\"//g'");
                echo "<a href=\"planifications.php\"><b>${nextPlanDate} (${nextPlanTime})</b></a>";
            }
            if ($countDate > "1") {
                // récupération des dates et leur heure et affichage
            }
        }
        echo '</div>';
        // pour debug : var_dump($plan);
    }
    ?>
    </section>
</section>


<!-- divs cachées de base -->
<!-- GERER LES GROUPES -->
<?php include('common-groupslist.inc.php'); ?>

<!-- REPOS/HOTES SOURCES -->
<?php include('common-repos-sources.inc.php'); ?>

<?php include('common-footer.inc.php'); ?>

<?php if ($reloadPage === "yes") {
// Si reloadPage = yes alors il faut rafraichir la page afin d'appliquer certaines modification d'affichage
// Nettoyage du cache navigateur puis rechargement de la page
echo "<script>";
echo "alert('test');";
echo "Clear-Site-Data: \"*\";";
echo "window.location.replace('/index.php');";
echo "</script>";
}
?>

<script> 
    $(document).ready(function(){
        $("#newRepoSlideButton").click(function(){
            // masquage du div contenant les infos serveur
            $("#serverInfoSlideDiv").animate({
                width: 0,
            });
            
            // affichage du div permettant de créer un nouveau repo/section à la place
            $("#newRepoSlideDiv").delay(250).animate({
                width: '97%',
                padding: '10px' //$("#newRepoSlideDiv").css('padding', '10px'); /* lorsqu'on affiche la section cachée, on ajoute un padding de 10 intérieur, voir la suite dans le fichier css pour '#newRepoSlideDiv' */
            });
        });
        
        $("#newRepoCloseButton").click(function(){
            // masquage du div permettant de créer un nouveau repo/section
            $("#newRepoSlideDiv").animate({
                width: 0,
                padding: '0px' //$("#newRepoSlideDiv").css('padding', '0'); /* lorsqu'on masque la section, on retire le padding, afin que la section soit complètement masquée, voir la suite dans le fichier css pour '#newRepoSlideDiv' */
            });

            // affichage du div contenant les infos serveur à la place
            $("#serverInfoSlideDiv").delay(250).animate({
                width: '97%',
            });
        });
    });
</script>
</body>
</html>