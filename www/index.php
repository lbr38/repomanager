<html>
<?php include('common-head.inc.php'); ?>

<?php
  // Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
  require 'common-vars.php';
  require 'common-functions.php';
  require 'common.php';
  require 'display.php';
  if ($debugMode == "enabled") { echo "Mode debug activé : "; print_r($_POST); }
?>

<body>
<?php include('common-header.inc.php'); ?>


<!-- REPOS ACTIFS -->
<article class="main">
    <article class="left">
        <?php include('common-repos-list.inc.php'); ?>
    </article>

    <article id="serverInfoSlideDiv" class="serverInfoSlideDiv">
    <div id="divContainerArticleRight">
        <h5>DETAILS SERVEUR</h5>
            <p>Espace disque utilisé</p>
            <div class="diskSpaceDiv">
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

            <div class="diskSpaceDiv">
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
        </div>
    </article>
    
    <!-- LISTE DES OPERATIONS -->
    <article id="newRepoSlideDiv" class="newRepoSlideDiv">
    <a href="#" class="button-slide-left" title="Fermer"><img class="icon-lowopacity" src="icons/close.png" /></a>
    <div id="divContainerArticleRight">
        <?php include('common-operations.inc.php'); ?>
    </div>
    </article>
</article>


<!-- REPOS ARCHIVÉS-->
<article class="main">
    <article class="left">
        <?php include('common-repos-archive-list.inc.php'); ?>
    </article>    
</article>

<!-- divs cachées de base -->
<!-- div des groupes de repos -->
<?php include('common-groupslist.inc.php'); ?>

<!-- div des hotes et fichers de repos -->
<?php include('common-repos-sources.inc.php'); ?>

<?php include('common-footer.inc.php'); ?>


<script> 
$(document).ready(function(){
        var boxWidth = $("#newRepoSlideDiv").width();
        $(".button-slide-right").click(function(){
            // masquage du div contenant les infos serveur
            $("#serverInfoSlideDiv").animate({
                width: 0
            });
            $("#serverInfoSlideDiv").css('padding', '0px'); /* lorsqu'on masque l'article contenant les infos serveur, on retire le padding afin qu'il soit complètement masqué, voir la suite dans le fichier css pour '#serverInfoSlideDiv' */

            // affichage du div permettant de créer un nouveau repo/section à la place
            $("#newRepoSlideDiv").animate({
                width: '29%'
            });
            $("#newRepoSlideDiv").css('padding', '10px'); /* lorsqu'on affiche l'article caché, on ajoute un padding de 10 intérieur, voir la suite dans le fichier css pour '#newRepoSlideDiv' */
        });
        
        $(".button-slide-left").click(function(){
            // masquage du div permettant de créer un nouveau repo/section
            $("#newRepoSlideDiv").animate({
                width: 0
            });
            $("#newRepoSlideDiv").css('padding', '0'); /* lorsqu'on masque l'article, on retire le padding, afin que l'article soit complètement masqué, voir la suite dans le fichier css pour '#newRepoSlideDiv' */

            // affichage du div contenant les infos serveur à la place
            $("#serverInfoSlideDiv").animate({
                width: '29%'
            });
            $("#serverInfoSlideDiv").css('padding', '10px'); /* lorsqu'on affiche l'article caché, on ajoute un padding de 10 intérieur, voir la suite dans le fichier css pour '#serverInfoSlideDiv' */
        });
    });
</script>
</body>
</html>