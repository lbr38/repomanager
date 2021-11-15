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
require_once('class/Database-stats.php');
require_once('class/Repo.php');

/**
 *  Chargement de la BDD stats
 */
$db_stats = new Database_stats();

$repoError = 0;

/**
 *  Récupération du repo transmis
 */
if (empty($_GET['id']))
    $repoError++;
else
    $repoId = validateData($_GET['id']);

/**
 *  Le repo transmis doit être un numéro car il s'agit de l'ID en BDD
 */
if (!is_numeric($repoId)) $repoError++;

/**
 *  A partir de l'ID fourni, on récupère les infos du repo
 */
if ($repoError == 0) {
    $myrepo = new Repo();
    $myrepo->id = $repoId;

    $myrepo->db_getAllById();
}
?>

<body>
<?php include('includes/header.inc.php');?>

<article>
    <section class="main">
        <section class="section-center">
            <h3>STATISTIQUES</h3>

            <?php
                if ($repoError !== 0) {
                    if ($OS_FAMILY == "Redhat") echo "<p>Erreur : le repo spécifié n'existe pas.</p>";
                    if ($OS_FAMILY == "Debian") echo "<p>Erreur : la section de repo spécifiée n'existe pas.</p>";
                }

                if ($repoError === 0) {
                    if ($OS_FAMILY == "Redhat" AND !empty($myrepo->name)) echo "<p>Statistiques du repo <b>$myrepo->name</b> " . envtag($myrepo->env) . "</p>";
                    if ($OS_FAMILY == "Debian" AND !empty($myrepo->name) AND !empty($myrepo->dist) AND !empty($myrepo->section)) echo "<p>Statistiques de la section <b>$myrepo->section</b> " . envtag($myrepo->env) . " du repo <b>$myrepo->name</b> (distribution <b>$myrepo->dist</b>).</p>";
                }

                echo '<br>';

                if (!file_exists($WWW_STATS_LOG_PATH)) echo "<p><span class=\"yellowtext\">Le fichier de log à analyser ($WWW_STATS_LOG_PATH) n'existe pas ou n'est pas correctement configuré.</span></p>";
                if (!is_readable($WWW_STATS_LOG_PATH)) echo "<p><span class=\"yellowtext\">Le fichier de log à analyser ($WWW_STATS_LOG_PATH) n'est pas accessible en lecture.</span></p>";

                /**
                 *  Récupération de la liste des derniers logs d'accès au repo, à partir de la BDD
                 */
                if ($OS_FAMILY == "Redhat") $lastAccess = $db_stats->get_lastAccess(array('repo' => $myrepo->name, 'env' => $myrepo->env));
                if ($OS_FAMILY == "Debian") $lastAccess = $db_stats->get_lastAccess(array('repo' => $myrepo->name, 'dist' => $myrepo->dist, 'section' => $myrepo->section, 'env' => $myrepo->env));

                /**
                 *  Tri des valeurs par date et heure
                 */
                if (!empty($lastAccess)) array_multisort(array_column($lastAccess, 'Date'), SORT_DESC, array_column($lastAccess, 'Time'), SORT_DESC, $lastAccess);
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
                    if ($OS_FAMILY == "Redhat") {
                        $repoSize = exec("du -hs ${REPOS_DIR}/{$myrepo->dateFormatted}_{$myrepo->name} | awk '{print $1}'");
                        $packagesCount = exec("find ${REPOS_DIR}/{$myrepo->dateFormatted}_{$myrepo->name}/ -type f -name '*.rpm' | wc -l");
                    }
                    if ($OS_FAMILY == "Debian") {
                        $repoSize = exec("du -hs ${REPOS_DIR}/{$myrepo->name}/{$myrepo->dist}/{$myrepo->dateFormatted}_{$myrepo->section} | awk '{print $1}'");
                        $packagesCount = exec("find ${REPOS_DIR}/{$myrepo->name}/{$myrepo->dist}/{$myrepo->dateFormatted}_{$myrepo->section}/ -type f -name '*.deb' | wc -l");
                    }

                    /**
                     *  Affichage de la taille du repo et du nombre de paquets actuel
                     */
                    echo '<div class="stats-div-15">';
                        echo '<p class="center">Propriétés</p>';
                        echo '<div class="stats-round-counter">';
                            echo '<br><p class="lowopacity">Taille du repo</p><br>';
                            echo '<span class="stats-info-container">';
                                echo "<span class=\"stats-info-counter\">$repoSize</span>";
                            echo '</span>';
                        echo '</div>';

                        echo '<div class="stats-round-counter">';
                            echo '<br><p class="lowopacity">Nombre de paquets</p><br>';
                            echo '<span class="stats-info-container">';
                                echo "<span class=\"stats-info-counter\">$packagesCount</span>";
                            echo '</span>';
                        echo '</div>';
                    echo '</div>';

                    /**
                     *  Affichage du nombre d'accès au repo en temps réel et de la dernière minute
                     */
                    echo '<div id="refresh-me" class="stats-div-15">';
                        /**
                         *  Nb de requêtes de la dernière minute
                         */
                        if ($OS_FAMILY == "Redhat") $lastMinuteAccessCount = $db_stats->get_lastMinuteAccess_count(array('repo' => $myrepo->name, 'env' => $myrepo->env));
                        if ($OS_FAMILY == "Debian") $lastMinuteAccessCount = $db_stats->get_lastMinuteAccess_count(array('repo' => $myrepo->name, 'dist' => $myrepo->dist, 'section' => $myrepo->section, 'env' => $myrepo->env));
                        /**
                         *  Détails des requêtes de la dernière minute
                         */
                        if ($lastMinuteAccessCount > 0) {
                            if ($OS_FAMILY == "Redhat") $lastMinuteAccess = $db_stats->get_lastMinuteAccess(array('repo' => $myrepo->name, 'env' => $myrepo->env));
                            if ($OS_FAMILY == "Debian") $lastMinuteAccess = $db_stats->get_lastMinuteAccess(array('repo' => $myrepo->name, 'dist' => $myrepo->dist, 'section' => $myrepo->section, 'env' => $myrepo->env));
                        }
                        /**
                         *  Nb de requêtes temps réel
                         */
                        if ($OS_FAMILY == "Redhat") $realTimeAccessCount = $db_stats->get_realTimeAccess_count(array('repo' => $myrepo->name, 'env' => $myrepo->env));
                        if ($OS_FAMILY == "Debian") $realTimeAccessCount = $db_stats->get_realTimeAccess_count(array('repo' => $myrepo->name, 'dist' => $myrepo->dist, 'section' => $myrepo->section, 'env' => $myrepo->env));

                        if (!empty($lastMinuteAccess)) array_multisort(array_column($lastMinuteAccess, 'Date'), SORT_DESC, array_column($lastMinuteAccess, 'Time'), SORT_DESC, $lastMinuteAccess);

                        /**
                         *  Temps réel
                         */
                        echo '<p class="center">Nombre d\'accès au repo</p>';
                        echo '<div class="stats-round-counter">';
                            echo '<br><p class="lowopacity">Temps réel</p><br>';
                            echo '<span class="stats-info-container pointer">';
                                echo "<span class=\"stats-info-counter\">$realTimeAccessCount</span>";
                            echo '</span>';
                        echo '</div>';

                        /**
                         *  Dernière minute
                         */
                        echo '<div class="stats-round-counter">';
                            echo '<br><p class="lowopacity">Dernière minute</p><br>';
                            echo '<span class="stats-info-container">';
                                echo "<span class=\"stats-info-counter pointer\">$lastMinuteAccessCount</span>";
                                if (!empty($lastMinuteAccess)) {
                                    echo '<span class="stats-info-requests">';
                                        foreach ($lastMinuteAccess as $line) {
                                            /**
                                             *  Affichage d'une icone verte ou rouge suivant le résultat de la requête
                                             */
                                            if ($line['Request_result'] == "200" OR $line['Request_result'] == "304")
                                                echo "<img src=\"icons/greencircle.png\" class=\"icon-small\" /> ";
                                            else
                                                echo "<img src=\"icons/redcircle.png\" class=\"icon-small\" /> ";
                                            /**
                                             *  Affichage des détails de la/les requête(s)
                                             */
                                            echo DateTime::createFromFormat('Y-m-d', $line['Date'])->format('d-m-Y').' à '.$line['Time'].' - '.$line['Source']. '('.$line['IP'].') - '.$line['Request'];
                                            echo '<br>';
                                        }
                                    echo '</span>';
                                }
                            echo '</span>';
                        echo '</div>';
                    echo '</div>';

                    /**
                     *  Graphique accès au repo/section sur la dernière heure
                     */
                    $lastHourLabels = '';
                    $lastHourDatas = '';

                    $dateCounter = date('Y-m-d',strtotime('-3 month',strtotime($DATE_YMD))); // le début du compteur commence à la date actuelle -3 mois  car ce graphique affichera des valeurs sur 3 mois
                    $lastMonth_labels = '';
                    $lastMonth_datas = '';

                    /**
                     *  On traite toutes les dates jusqu'à atteindre la date du jour (qu'on traite aussi)
                     */
                    while ($dateCounter != date('Y-m-d',strtotime('+1 day',strtotime($DATE_YMD)))) {
                        if ($OS_FAMILY == "Redhat") $dateAccessCount = $db_stats->get_dailyAccess_count(array('repo' => $myrepo->name, 'env' => $myrepo->env, 'date' => $dateCounter));
                        if ($OS_FAMILY == "Debian") $dateAccessCount = $db_stats->get_dailyAccess_count(array('repo' => $myrepo->name, 'dist' => $myrepo->dist, 'section' => $myrepo->section, 'env' => $myrepo->env, 'date' => $dateCounter));

                        if (!empty($dateAccessCount))
                            $lastMonth_datas .= $dateAccessCount.', ';
                        else
                            $lastMonth_datas .= '0, ';

                        /**
                         *  Ajout de la date en cours aux labels
                         */
                        $lastMonth_labels .= "'$dateCounter', ";

                        /**
                         *  On incrémente de 1 jour pour pouvori traiter la date suivante
                         */
                        $dateCounter = date('Y-m-d',strtotime('+1 day',strtotime($dateCounter)));
                    }

                    /**
                     *  Suppression de la dernière virgule
                     */
                    $lastMonth_labels = rtrim($lastMonth_labels, ', ');
                    $lastMonth_datas  = rtrim($lastMonth_datas, ', ');

                    if (!empty($lastMonth_labels) AND !empty($lastMonth_datas)) {
                        echo '<div class="stats-div-65">';
                        echo "<canvas id=\"repoAccessChart-$myrepo->name\" class=\"repo-stats-chart\"></canvas>";
                        echo '<script>';
                        echo "var ctx = document.getElementById('repoAccessChart-$myrepo->name').getContext('2d');
                            var myRepoAccessChart = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: [$lastMonth_labels],    
                                    datasets: [{
                                        data: [$lastMonth_datas],
                                        label: 'Nombre de requêtes',
                                        borderColor: '#3e95cd',
                                        fill: false
                                    }]
                                },
                                options: {
                                    scales: {
                                        xAxes: [{
                                                display: true,
                                                scaleLabel: {
                                                    display: true,
                                                    labelString: 'Valeurs sur 3 mois'
                                                }
                                            }],
                                        yAxes: [{
                                                display: true,
                                                ticks: {
                                                    beginAtZero: true,
                                                    steps: 10,
                                                    stepValue: 5,
                                                }
                                            }]
                                    },
                                    title: {
                                        display: true,
                                        text: \"Nombre d'accès sur 3 mois\"
                                    }
                                }
                            });";
                        echo '</script>';
                        echo '</div>';
                    }

                    /**
                     *  Tableau des derniers logs d'accès
                     */
                    echo '<div class="stats-div-100">';
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
                                            if ($access['Request_result'] == "200" OR $access['Request_result'] == "304")
                                                echo '<img src="icons/greencircle.png" class="icon-small" title="'.$access['Request_result'].'" />';
                                            else
                                                echo '<img src="icons/redcircle.png" class="icon-small" title="'.$access['Request_result'].'" />';
                                            echo '</td>';
                                            echo '<td class="td-100">'.DateTime::createFromFormat('Y-m-d', $access['Date'])->format('d-m-Y').' à '.$access['Time'].'</td>';
                                            echo '<td class="td-100">'.$access['Source'].' ('.$access['IP'].')</td>';
                                            // retrait des double quotes " dans la requête complète :
                                            echo '<td><span title="'.str_replace('"', '', $access['Request']).'">'.$accessTarget[0].'</span></td>';
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
                    $stmt = $db_stats->prepare("SELECT * FROM stats WHERE Id_repo=:id_repo");
                    $stmt->bindValue('id_repo', $myrepo->id);
                    $result = $stmt->execute();                
                    
                    /**
                     *  Si le résultat n'est pas vide alors on traite
                     */
                    if ($db_stats->isempty($result) === false) {
                        while ($row = $result->fetchArray()) $results[] = $row;

                        $dateLabels = '';
                        $sizeData = '';
                        $countData = '';

                        foreach ($results as $result) {
                            $date = DateTime::createFromFormat('Y-m-d', $result['Date'])->format('d-m-Y');
                            $size = round($result['Size'] / 1024);
                            $count = $result['Packages_count'];

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
                        if (!empty($dateLabels) AND !empty($sizeData)) {
                            echo '<div class="stats-div-50">';
                            echo "<canvas id=\"repoSizeChart-$myrepo->name\" class=\"repo-stats-chart\"></canvas>";
                            echo '<script>';
                            echo "var ctx = document.getElementById('repoSizeChart-$myrepo->name').getContext('2d');
                                var myRepoSizeChart = new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: [$dateLabels],    
                                        datasets: [{
                                            data: [$sizeData],
                                            label: 'Taille en Mo',
                                            borderColor: '#3e95cd',
                                            fill: false
                                        }]
                                    },
                                    options: {
                                        scales: {
                                            xAxes: [{
                                                    display: true,
                                                    scaleLabel: {
                                                        display: true,
                                                        labelString: 'Valeurs sur 6 mois'
                                                    }
                                                }],
                                            yAxes: [{
                                                    display: true,
                                                    ticks: {
                                                        steps: 10,
                                                        stepValue: 5,
                                                    }
                                                }]
                                        },
                                        title: {
                                            display: true,
                                            text: 'Taille en Mo'
                                        }
                                    }
                                });";
                            echo '</script>';
                            echo '</div>';
                        }

                        /**
                         *  Affichage du graphique nombre de paquets du repo/section
                         */
                        if (!empty($dateLabels) AND !empty($countData)) {
                            echo '<div class="stats-div-50">';
                            echo "<canvas id=\"repoPackagesCountChart-$myrepo->name\" class=\"repo-stats-chart\"></canvas>";
                            echo '<script>';
                            echo "var ctx = document.getElementById('repoPackagesCountChart-$myrepo->name').getContext('2d');
                                var myRepoPackagesCountChart = new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: [$dateLabels],    
                                        datasets: [{
                                            data: [$countData],
                                            label: 'Nombre de paquets',
                                            borderColor: '#3e95cd',
                                            fill: false
                                        }]
                                    },
                                    options: {
                                        scales: {
                                            xAxes: [{
                                                    display: true,
                                                    scaleLabel: {
                                                        display: true,
                                                        labelString: 'Valeurs sur 6 mois'
                                                    }
                                                }],
                                            yAxes: [{
                                                    display: true,
                                                    ticks: {
                                                        steps: 10,
                                                        stepValue: 5,
                                                    }
                                                }]
                                        },
                                        title: {
                                            display: true,
                                            text: 'Nombre de paquets'
                                        }
                                    }
                                });";
                            echo '</script>';
                            echo '</div>';
                        }
                    }
                } ?>
            </div>
        </section>
    </section>
</article>
<?php include('includes/footer.inc.php'); ?>
</body>
<script>
$(document).ready(function(){
	/**
	 *	Autorechargement du journal et des opération en cours (panneau gauche et panneau droit)
	 */
	setInterval(function(){
		$("#refresh-me").load(" #refresh-me > *");
	}, 1000);
});
</script>
</html>