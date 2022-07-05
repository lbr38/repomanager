<!DOCTYPE html>
<html>
<?php
require_once('../controllers/Autoloader.php');
\Controllers\Autoloader::load();
include_once('../includes/head.inc.php');

/**
 *  Chargement de la BDD stats
 *  - 1ère ouverture en mode rw afin de créer les éventuelles tables si n'existent pas, puis cloture
 *  - 2ème ouverture en mode ro pour la suite du traitement où on ne fera que de la récupération de données
 */
$mystats = new \Controllers\Stat();

$repoError = 0;

/**
 *  Récupération du snapshot et environnement transmis
 */
if (empty($_GET['id'])) {
    $repoError++;
} else {
    $envId = \Controllers\Common::validateData($_GET['id']);
}

/**
 *  Le repo transmis doit être un numéro car il s'agit de l'ID en BDD
 */
if (!is_numeric($envId)) {
    $repoError++;
}

/**
 *  A partir de l'ID fourni, on récupère les infos du repo
 */
if ($repoError == 0) {
    $myrepo = new \Controllers\Repo();
    $myrepo->setEnvId($envId);
    $myrepo->getAllById('', '', $envId);
}

/**
 *  Si un filtre a été sélectionné pour le graphique principal, la page est rechargée en arrière plan par jquery et récupère les données du graphique à partir du filtre sélectionné
 */
if (!empty($_GET['repo_access_chart_filter'])) {
    if (Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "1week") {
        $repo_access_chart_filter = "1week";
    }
    if (Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "1month") {
        $repo_access_chart_filter = "1month";
    }
    if (Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "3months") {
        $repo_access_chart_filter = "3months";
    }
    if (Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "6months") {
        $repo_access_chart_filter = "6months";
    }
}
?>

<body>
<?php include_once('../includes/header.inc.php');?>

<article>
    <section class="main">
        <section class="section-center">
            <h3>STATISTIQUES</h3>

            <?php
            if ($repoError !== 0) {
                echo "<p>Erreur : le repo spécifié n'existe pas.</p>";
            }

            if ($repoError === 0) {
                if ($myrepo->getPackageType() == 'rpm') {
                    echo '<p>Statistiques de <span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>⟶' . \Controllers\Common::envtag($myrepo->getEnv()) . '</p>';
                }
                if ($myrepo->getPackageType() == 'deb') {
                    echo '<p>Statistiques de <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>⟶' . \Controllers\Common::envtag($myrepo->getEnv()) . '</p>';
                }
            }

            echo '<br>';

            if (!file_exists(STATS_LOG_PATH)) {
                echo '<p><span class="yellowtext">Le fichier de log à analyser (' . STATS_LOG_PATH . ') n\'existe pas ou n\'est pas correctement configuré.</span></p>';
            }
            if (!is_readable(STATS_LOG_PATH)) {
                echo '<p><span class="yellowtext">Le fichier de log à analyser (' . STATS_LOG_PATH . ') n\'est pas accessible en lecture.</span></p>';
            }

            /**
             *  Récupération de la liste des derniers logs d'accès au repo, à partir de la BDD
             */
            if ($myrepo->getPackageType() == 'rpm') {
                $lastAccess = $mystats->getLastAccess($myrepo->getName(), '', '', $myrepo->getEnv());
            }
            if ($myrepo->getPackageType() == 'deb') {
                $lastAccess = $mystats->getLastAccess($myrepo->getName(), $myrepo->getDist(), $myrepo->getSection(), $myrepo->getEnv());
            }

            /**
             *  Tri des valeurs par date et heure
             */
            if (!empty($lastAccess)) {
                array_multisort(array_column($lastAccess, 'Date'), SORT_DESC, array_column($lastAccess, 'Time'), SORT_DESC, $lastAccess);
            }
            ?>

            <div id="stats-container">
                <?php

                /**
                 *  Affichage des graphiques uniquement si l'Id du repo est valide
                 */

                if ($repoError === 0) {
                    /**
                     *  Comptage de la taille du repo et du nombre de paquets actuel
                     */
                    if ($myrepo->getPackageType() == 'rpm') {
                        $repoSize = exec("du -hs " . REPOS_DIR . "/" . $myrepo->getDateFormatted() . "_" . $myrepo->getName() . " | awk '{print $1}'");
                        $packagesCount = exec("find " . REPOS_DIR . "/" . $myrepo->getDateFormatted() . "_" . $myrepo->getName() . "/ -type f -name '*.rpm' | wc -l");
                    }
                    if ($myrepo->getPackageType() == 'deb') {
                        $repoSize = exec("du -hs " . REPOS_DIR . "/" . $myrepo->getName() . "/" . $myrepo->getDist() . "/" . $myrepo->getDateFormatted() . "_" . $myrepo->getSection() . " | awk '{print $1}'");
                        $packagesCount = exec("find " . REPOS_DIR . "/" . $myrepo->getName() . "/" . $myrepo->getDist() . "/" . $myrepo->getDateFormatted() . "_" . $myrepo->getSection() . "/ -type f -name '*.deb' | wc -l");
                    }

                    /**
                     *  Affichage de la taille du repo et du nombre de paquets actuel
                     */ ?>
                    <div class="flex-div-15">
                        <p class="center">Propriétés</p>
                        <div class="round-div">
                            <br><p class="lowopacity">Taille du repo</p><br>
                            <div class="round-div-container">
                                <span><?php echo $repoSize;?></span>
                            </div>
                        </div>

                        <div class="round-div">
                            <br><p class="lowopacity">Nombre de paquets</p><br>
                            <div class="round-div-container">
                                <span><?php echo $packagesCount;?></span>
                            </div>
                        </div>
                    </div>

                    <?php
                    /**
                     *  Affichage du nombre d'accès au repo en temps réel et des 5 dernières minutes
                     */
                    echo '<div id="refresh-me" class="flex-div-15">';

                    /**
                     *  Détails des requêtes en temps réel (+/-5 sec)
                     */
                    if ($myrepo->getPackageType() == 'rpm') {
                        $realTimeAccess = $mystats->getRealTimeAccess($myrepo->getName(), '', '', $myrepo->getEnv());
                    }
                    if ($myrepo->getPackageType() == 'deb') {
                        $realTimeAccess = $mystats->getRealTimeAccess($myrepo->getName(), $myrepo->getDist(), $myrepo->getSection(), $myrepo->getEnv());
                    }

                    /**
                     *  Comptage du nombre de requêtes précédemment récupérées
                     */
                    $realTimeAccessCount = count($realTimeAccess);

                    /**
                     *  Détails des requêtes des 5 dernières minutes
                     */
                    if ($myrepo->getPackageType() == 'rpm') {
                        $lastMinutesAccess = $mystats->getLastMinutesAccess($myrepo->getName(), '', '', $myrepo->getEnv());
                    }
                    if ($myrepo->getPackageType() == 'deb') {
                        $lastMinutesAccess = $mystats->getLastMinutesAccess($myrepo->getName(), $myrepo->getDist(), $myrepo->getSection(), $myrepo->getEnv());
                    }

                    /**
                     *  Comptage du nombre de requêtes précédemment récupérées
                     */
                    $lastMinutesAccessCount = count($lastMinutesAccess);

                    /**
                     *  Réorganise le détails des requêtes de la plus récente à la plus ancienne
                     */
                    if (!empty($realTimeAccess)) {
                        array_multisort(array_column($realTimeAccess, 'Date'), SORT_DESC, array_column($realTimeAccess, 'Time'), SORT_DESC, $realTimeAccess);
                    }
                    if (!empty($lastMinutesAccess)) {
                        array_multisort(array_column($lastMinutesAccess, 'Date'), SORT_DESC, array_column($lastMinutesAccess, 'Time'), SORT_DESC, $lastMinutesAccess);
                    }
                    ?>

                        <p class="center">Nombre d'accès au repo</p>
                        <div class="round-div">
                            <br><p class="lowopacity">Temps réel</p><br>
                            <div class="round-div-container pointer">
                                <span><?php echo $realTimeAccessCount;?></span>
                            </div>

                            <?php if (!empty($realTimeAccess)) {
                                echo '<span class="stats-info-requests">';
                                foreach ($realTimeAccess as $line) {
                                    /**
                                     *  Affichage d'une icone verte ou rouge suivant le résultat de la requête
                                     */
                                    if ($line['Request_result'] == "200" or $line['Request_result'] == "304") {
                                        echo "<img src=\"resources/icons/greencircle.png\" class=\"icon-small\" /> ";
                                    } else {
                                        echo "<img src=\"resources/icons/redcircle.png\" class=\"icon-small\" /> ";
                                    }
                                    /**
                                     *  Affichage des détails de la/les requête(s)
                                     */
                                    echo DateTime::createFromFormat('Y-m-d', $line['Date'])->format('d-m-Y') . ' à ' . $line['Time'] . ' - ' . $line['Source'] . '(' . $line['IP'] . ') - ' . $line['Request'];
                                    echo '<br>';
                                }
                                echo '</span>';
                            } ?>
                        </div>

                        <div class="round-div">
                            <br><p class="lowopacity" title="5 dernières minutes">Dernières minutes</p><br>
                            <div class="round-div-container">
                                <span class="pointer"><?php echo $lastMinutesAccessCount;?></span>
                            </div>

                            <?php if (!empty($lastMinutesAccess)) {
                                echo '<span class="stats-info-requests">';
                                foreach ($lastMinutesAccess as $line) {
                                    /**
                                     *  Affichage d'une icone verte ou rouge suivant le résultat de la requête
                                     */
                                    if ($line['Request_result'] == "200" or $line['Request_result'] == "304") {
                                        echo "<img src=\"resources/icons/greencircle.png\" class=\"icon-small\" /> ";
                                    } else {
                                        echo "<img src=\"resources/icons/redcircle.png\" class=\"icon-small\" /> ";
                                    }
                                    /**
                                     *  Affichage des détails de la/les requête(s)
                                     */
                                    echo DateTime::createFromFormat('Y-m-d', $line['Date'])->format('d-m-Y') . ' à ' . $line['Time'] . ' - ' . $line['Source'] . '(' . $line['IP'] . ') - ' . $line['Request'];
                                    echo '<br>';
                                }
                                echo '</span>';
                            } ?>
                        </div>
                    </div>

                    <div id="repo-access-chart-div" class="flex-div-65 div-generic-gray">
                    <?php
                    /**
                     *  Si aucun filtre n'a été sélectionné par l'utilisateur alors on le set à 1 semaine par défaut
                     */
                    if (empty($repo_access_chart_filter)) {
                        $repo_access_chart_filter = "1week";
                    }

                    /**
                     *  Initialisation de la date de départ du graphique, en fonction du filtre choisi
                     */
                    if ($repo_access_chart_filter == "1week") {
                        $dateCounter = date('Y-m-d', strtotime('-1 week', strtotime(DATE_YMD))); // le début du compteur commence à la date actuelle -1 semaine
                    }
                    if ($repo_access_chart_filter == "1month") {
                        $dateCounter = date('Y-m-d', strtotime('-1 month', strtotime(DATE_YMD))); // le début du compteur commence à la date actuelle -1 mois
                    }
                    if ($repo_access_chart_filter == "3months") {
                        $dateCounter = date('Y-m-d', strtotime('-3 months', strtotime(DATE_YMD))); // le début du compteur commence à la date actuelle -3 mois
                    }
                    if ($repo_access_chart_filter == "6months") {
                        $dateCounter = date('Y-m-d', strtotime('-6 months', strtotime(DATE_YMD))); // le début du compteur commence à la date actuelle -6 mois
                    }

                    $repoAccessChartLabels = '';
                    $repoAccessChartData = '';

                    /**
                     *  On traite toutes les dates jusqu'à atteindre la date du jour (qu'on traite aussi)
                     */
                    while ($dateCounter != date('Y-m-d', strtotime('+1 day', strtotime(DATE_YMD)))) {
                        if ($myrepo->getPackageType() == 'rpm') {
                            $dateAccessCount = $mystats->getDailyAccessCount($myrepo->getName(), '', '', $myrepo->getEnv(), $dateCounter);
                        }
                        if ($myrepo->getPackageType() == 'deb') {
                            $dateAccessCount = $mystats->getDailyAccessCount($myrepo->getName(), $myrepo->getDist(), $myrepo->getSection(), $myrepo->getEnv(), $dateCounter);
                        }

                        if (!empty($dateAccessCount)) {
                            $repoAccessChartData .= $dateAccessCount . ', ';
                        } else {
                            $repoAccessChartData .= '0, ';
                        }

                        /**
                         *  Ajout de la date en cours aux labels
                         */
                        $repoAccessChartLabels .= "'$dateCounter', ";

                        /**
                         *  On incrémente de 1 jour pour pouvori traiter la date suivante
                         */
                        $dateCounter = date('Y-m-d', strtotime('+1 day', strtotime($dateCounter)));
                    }

                    /**
                     *  Suppression de la dernière virgule
                     */
                    $repoAccessChartLabels = rtrim($repoAccessChartLabels, ', ');
                    $repoAccessChartData  = rtrim($repoAccessChartData, ', ');

                    if (!empty($repoAccessChartLabels) and !empty($repoAccessChartData)) { ?>
                            <span class="btn-small-blue repo-access-chart-filter-button" filter="1week">1 semaine</span>
                            <span class="btn-small-blue repo-access-chart-filter-button" filter="1month">1 mois</span>
                            <span class="btn-small-blue repo-access-chart-filter-button" filter="3months">3 mois</span>
                            <span class="btn-small-blue repo-access-chart-filter-button" filter="6months">6 mois</span>
                            <?php
                            /**
                             *  On place deux span à l'intérieur du canvas, qui contiennent les valeurs 'labels' et 'data' du chart en cours
                             *  Utilisés par jqeury pour récupérer de nouvelles valeurs en fonction du filtre choisi par l'utilisateur (1week...)
                             */ ?>
                            <canvas id="repo-access-chart">
                                <span id="repo-access-chart-labels" labels="<?php echo $repoAccessChartLabels;?>"></span>
                                <span id="repo-access-chart-data" data="<?php echo $repoAccessChartData;?>"></span>
                            </canvas>
                            <script>
                                var ctx = document.getElementById('repo-access-chart').getContext('2d');
                                var myRepoAccessChart = new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: [<?php echo $repoAccessChartLabels;?>],
                                        datasets: [{
                                            data: [<?php echo $repoAccessChartData;?>],
                                            label: "Nombre d'accès",
                                            borderColor: '#3e95cd',
                                            fill: false
                                        }]
                                    },
                                    options: {
                                        scales: {
                                            x: {
                                                display: true,
                                            },
                                            y: {
                                                beginAtZero: true,
                                                display: true,
                                                ticks: {
                                                    stepSize: 1
                                                }
                                            }
                                        },
                                    }
                                });
                            </script>
                        </div>
                        <?php
                    }

                    /**
                     *  Tableau des derniers logs d'accès
                     */
                    echo '<div class="flex-div-100 div-generic-gray">';
                        echo '<p class="center lowopacity">Dernières requêtes d\'accès</p>';
                        echo '<table class="stats-access-table">';
                    if (!empty($lastAccess)) {
                        echo '<thead>';
                            echo '<tr>';
                            echo '<td class="td-10"></td>';
                            echo '<td class="td-100">Date</td>';
                            echo '<td class="td-100">Source</td>';
                            echo '<td>Cible</td>';
                            echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        foreach ($lastAccess as $access) {
                            /**
                             *  Récupération de la cible (le paquet ou le fichier téléchargé) à partir de la requête
                             *  Ici le preg_match permet de récupérer le nom du paquet ou du fichier ciblé dans l'URL complète
                             *  Il récupère une occurence composée de lettres, de chiffres et de caractères spéciaux et qui commence par un slash '/' et se termine par un espace [[:space:]]
                             *
                             *  Par exemple :
                             *  GET /repo/debian-security/buster/updates/main_test/pool/main/b/bind9/bind9-host_9.11.5.P4%2bdfsg-5.1%2bdeb10u6_amd64.deb HTTP/1.1
                             *                                                                      |                                                   |
                             *                                                                      |_                                                  |_
                             *                                                                        |                                                   |
                             *                                                                preg_match récupère l'occurence située entre un slash et un espace
                             *  Il récupère uniquement une occurence comportant des lettres, des chiffres et certains caractères spéciaux comme - _ . et %
                             */
                            preg_match('#/[a-zA-Z0-9\%_\.-]+[[:space:]]#i', $access['Request'], $accessTarget);
                            $accessTarget[0] = str_replace('/', '', $accessTarget[0]);

                            echo '<tr>';
                            echo '<td class="td-10">';
                            if ($access['Request_result'] == "200" or $access['Request_result'] == "304") {
                                echo '<img src="resources/icons/greencircle.png" class="icon-small" title="' . $access['Request_result'] . '" />';
                            } else {
                                echo '<img src="resources/icons/redcircle.png" class="icon-small" title="' . $access['Request_result'] . '" />';
                            }
                            echo '</td>';
                            echo '<td class="td-100">' . DateTime::createFromFormat('Y-m-d', $access['Date'])->format('d-m-Y') . ' à ' . $access['Time'] . '</td>';
                            echo '<td class="td-100">' . $access['Source'] . ' (' . $access['IP'] . ')</td>';

                            // retrait des double quotes " dans la requête complète :
                            echo '<td><span title="' . str_replace('"', '', $access['Request']) . '">' . $accessTarget[0] . '</span></td>';
                            echo '</tr>';
                        }

                        echo '</tbody>';
                    } else {
                        echo "<tr><td>Aucune requête d'accès récente n'a été trouvée</td></tr>";
                    }
                    echo '</table>';
                    echo '</div>';

                    /**
                     *  Graphique taille du repo et nombre de paquets
                     *  On récupère le contenu de la table stats qui concerne le repo
                     */
                    $stats = $mystats->getAll($myrepo->getEnvId());

                    /**
                     *  Si le résultat n'est pas vide alors on traite
                     */
                    if (!empty($stats)) {
                        $dateLabels = '';
                        $sizeData = '';
                        $countData = '';

                        foreach ($stats as $stat) {
                            $date = DateTime::createFromFormat('Y-m-d', $stat['Date'])->format('d-m-Y');
                            $size = round($stat['Size'] / 1024);
                            $count = $stat['Packages_count'];

                            /**
                             *  On forge les données des graphique
                             *  Un graphique pour la taille du repo
                             *  Un graphique pour le nombre de paquets
                             */
                            $dateLabels .= "'$date', "; // dates
                            $sizeData .= "'$size', ";   // taille du repo
                            $countData .= "'$count', "; // nombre de paquets
                        }

                        /**
                         *  Suppression de la dernière virgule
                         */
                        $dateLabels = rtrim($dateLabels, ', ');
                        $sizeData   = rtrim($sizeData, ', ');
                        $countData  = rtrim($countData, ', ');

                        /**
                         *  Affichage du graphique taille du repo/section
                         */
                        if (!empty($dateLabels) and !empty($sizeData)) { ?>
                            <div class="flex-div-50 div-generic-gray">
                                <canvas id="repoSizeChart"></canvas>
                                <script>
                                    var ctx = document.getElementById('repoSizeChart').getContext('2d');
                                    var myRepoSizeChart = new Chart(ctx, {
                                        type: 'line',
                                        data: {
                                            labels: [<?php echo $dateLabels;?>],    
                                            datasets: [{
                                                data: [<?php echo $sizeData;?>],
                                                label: 'Taille en Mo',
                                                borderColor: '#3e95cd',
                                                fill: false
                                            }]
                                        },
                                        options: {
                                            scales: {
                                                x: {
                                                    display: true,
                                                },
                                                y: {
                                                    display: true,
                                                    ticks: {
                                                        stepSize: 1
                                                    }
                                                }
                                            },
                                            title: {
                                                display: true,
                                                text: 'Taille en Mo'
                                            }
                                        }
                                    });
                                </script>
                            </div>
                            <?php
                        }

                        /**
                         *  Affichage du graphique nombre de paquets du repo/section
                         */
                        if (!empty($dateLabels) and !empty($countData)) { ?>
                            <div class="flex-div-50 div-generic-gray">
                                <canvas id="repoPackagesCountChart"></canvas>
                                <script>
                                    var ctx = document.getElementById('repoPackagesCountChart').getContext('2d');
                                    var myRepoPackagesCountChart = new Chart(ctx, {
                                        type: 'line',
                                        data: {
                                            labels: [<?php echo $dateLabels;?>],    
                                            datasets: [{
                                                data: [<?php echo $countData;?>],
                                                label: 'Nombre de paquets',
                                                borderColor: '#3e95cd',
                                                fill: false
                                            }]
                                        },
                                        options: {
                                            scales: {
                                                x: {
                                                    display: true,
                                                },
                                                y: {
                                                    display: true,
                                                    ticks: {
                                                        stepSize: 1,
                                                    }
                                                }
                                            },
                                            title: {
                                                display: true,
                                                text: 'Nombre de paquets'
                                            }
                                        }
                                    });
                                </script>
                            </div>
                            <?php
                        }
                    }
                } ?>
            </div>
        </section>
    </section>
</article>
<?php
    $mystats->closeConnection();
    include_once('../includes/footer.inc.php');
?>
</body>
</html>