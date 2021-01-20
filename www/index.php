<html>
<?php include('common-head.inc.php'); ?>

<?php
  // Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
  require 'vars/common.vars';
  require 'common-functions.php';
  require 'common.php';
  require 'vars/display.vars';
  if ($debugMode == "enabled") { echo "Mode debug activé : "; print_r($_POST); }
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

    <section class="right" id="serverInfoSlideDiv">
    <?php
        // Calcul du total des repos, en supprimant les doublons
        $totalRepos = exec("grep  '^Name=' $REPOS_LIST | awk -F ',' '{print $1}' | cut -d'=' -f2 | sed 's/\"//g' | uniq | wc -l");
        $totalReposArchived = exec("grep  '^Name=' $REPOS_ARCHIVE_LIST | awk -F ',' '{print $1}' | cut -d'=' -f2 | sed 's/\"//g' | uniq | wc -l");
    ?>
        <table>
            <tr>
                <td>Total repos</td>
                <td><?php echo "<b>${totalRepos}</b>"; ?></td>
            </tr>
            <tr>
                <td>Total repos archivés</td>
                <td><?php echo "<b>${totalReposArchived}</b>"; ?></td>
            </tr>
        </table>
        <p>Espace disque</p>
        <div class="serverInfoDiskSpace">
            <!-- graphique affichant l'espace utilisé sur le serveur (racine) -->
            <p>/</p>
            <?php
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
            ?>
            <canvas id="diskSpaceChart" class="chart"></canvas>
            <script>
            var ctx = document.getElementById('diskSpaceChart').getContext('2d');
            var myDoughnutChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Espace utilisé', 'Espace libre'],
                    datasets: [{
                        label: 'Espace disque utilisé',
                        data: [<?php echo "$diskUsedSpace, $diskFreeSpace";?>],
                        backgroundColor: [ // affichage de différente couleur suivant l'espace utilisé
                            <?php 
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
                            ?>
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
                    ctx.font = fontSize + "em sans-serif";
                    ctx.fillStyle = "white";
                    ctx.textBaseline = "middle";
                    var text = "<?php echo "${diskUsedSpacePercent}"; ?>",
                    textX = Math.round((width - ctx.measureText(text).width) / 2),
                    textY = height / 2;
                    ctx.fillText(text, textX, textY);
                    ctx.save();
                }
            });
            </script>
        </div>

        <div class="serverInfoDiskSpace">
            <!-- graphique affichant l'espace utilisé par le répertoire des repos -->
            <p><?php echo "${REPOS_DIR}";?></p>
            <?php
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
            ?>
            <canvas id="diskSpaceChart2" class="chart"></canvas>
            <script>
            var ctx = document.getElementById('diskSpaceChart2').getContext('2d');
            var myDoughnutChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Espace utilisé', 'Espace libre'],
                    datasets: [{
                        label: 'Espace disque utilisé',
                        data: [<?php echo "$diskUsedSpace, $diskFreeSpace";?>],
                        backgroundColor: [ // affichage de différente couleur suivant l'espace utilisé
                            <?php 
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
                            ?>
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
                    ctx.font = fontSize + "em sans-serif";
                    ctx.fillStyle = "white";
                    ctx.textBaseline = "middle";

                    var text = "<?php echo "${diskUsedSpacePercent}"; ?>",
                    textX = Math.round((width - ctx.measureText(text).width) / 2),
                    textY = height / 2;
                    ctx.fillText(text, textX, textY);
                    ctx.save();
                }
            });
            </script>
        </div>
    </section>
</section>


<!-- divs cachées de base -->
<!-- GERER LES GROUPES -->
<?php include('common-groupslist.inc.php'); ?>

<!-- REPOS/HOTES SOURCES -->
<?php include('common-repos-sources.inc.php'); ?>

<?php include('common-footer.inc.php'); ?>


<script> 
    $(document).ready(function(){
        $("#newRepoSlideButton").click(function(){
            // masquage du div contenant les infos serveur
            $("#serverInfoSlideDiv").animate({
                width: 0,
                padding: '0px' //$("#serverInfoSlideDiv").css('padding', '0px'); /* lorsqu'on masque la section contenant les infos serveur, on retire le padding afin qu'il soit complètement masqué, voir la suite dans le fichier css pour '#serverInfoSlideDiv' */
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
                padding: '10px' /* lorsqu'on affiche la section cachée, on ajoute un padding de 10 intérieur, voir la suite dans le fichier css pour '#serverInfoSlideDiv' */
            });
        });
    });
</script>
</body>
</html>