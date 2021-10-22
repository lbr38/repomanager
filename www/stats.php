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
require_once('class/Database.php');
require_once('class/Repo.php');

/**
 *  Chargement de la BDD stats
 */
$db_stats = new Database_stats();

/**
 *  A partir du nom du fichier de log principal, on en déduit le répertoire
 */
$WWW_STATS_LOGDIR_PATH = dirname($WWW_STATS_LOG_PATH, 1);

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
                if (!is_dir($WWW_STATS_LOGDIR_PATH)) echo "<p><span class=\"yellowtext\">Impossible de récupérer le chemin du répertoire des logs de ce serveur.</span></p>";

                /**
                 *  Récupération de la liste des fichiers de log à analyser
                 */
                $logFiles = explode("\n", shell_exec("ls -tr1 '${WWW_STATS_LOGDIR_PATH}'/"));
                $logFiles = array_reverse($logFiles);
                /**
                 *  Si la liste de fichiers est longue, on la limite à 5 fichiers maximum pour ne pas plomber la perf lors du parsage
                 */
                if (count($logFiles) > 5) $logFiles = array_slice($logFiles, 0, 5);

                /**
                 *  Récupération de la liste des derniers logs d'accès au repo, à partir de la liste de fichiers de logs précédemment récupérée
                 */
                $lastAccess[] = '';

                if (!empty($logFiles)) {
                    /**
                     *  Pour chaque fichier de log analysé, on ajoute les résultats obtenus dans l'array $lastAccess
                     */
                    foreach ($logFiles as $logFile) {
                        /**
                         *  Récupération des lignes de logs sous forme de tableau, qu'on injecte à la suite du tableau $lastAccess
                         */
                        if ($OS_FAMILY == "Redhat") $lastAccess = array_merge($lastAccess, explode("\n", shell_exec("zgrep -h 'urlgrabber' ${WWW_STATS_LOGDIR_PATH}/${logFile} | grep '/{$myrepo->name}_{$myrepo->env}/'")));
                        if ($OS_FAMILY == "Debian") $lastAccess = array_merge($lastAccess, explode("\n", shell_exec("zgrep -h 'Debian APT-CURL' ${WWW_STATS_LOGDIR_PATH}/${logFile} | grep '/{$myrepo->name}/{$myrepo->dist}/{$myrepo->section}_{$myrepo->env}/'")));
                    }
                }
                /**
                 *  Retrait des valeurs vides dans $lastAccess
                 */
                $lastAccess = array_filter($lastAccess);
            ?>

            <div id="stats-container">
                <?php
                /**
                 *  Affichage des graphiques uniquement si l'Id du repo est valide
                 */
                if ($repoError === 0) {

                    $currentDate = date('d/M/Y');

                    /**
                     *  Compteur en temps réel des accès au repo/section
                     */
                    $currentHourMinuteSecond = date('H:i:s');
                    $currentHourMinute = date('H:i');
                    $countLastMinuteLogOccurences_details = '';

                    /**
                     *  Comptage du nombre d'accès au repo (temps réel et dernière minute)
                     */
                    if ($OS_FAMILY == "Redhat") {
                        $countCurrentLogOccurences = exec("zgrep -h '\[${currentDate}:${currentHourMinuteSecond}.*\]' $WWW_STATS_LOG_PATH | grep 'urlgrabber' | grep '/{$myrepo->name}_{$myrepo->env}/' | wc -l");
                        $countLastMinuteLogOccurences = exec("zgrep -h '\[${currentDate}:${currentHourMinute}.*\]' $WWW_STATS_LOG_PATH | grep 'urlgrabber' | grep '/{$myrepo->name}_{$myrepo->env}/' | wc -l");
                    }
                    if ($OS_FAMILY == "Debian") {
                        $countCurrentLogOccurences = exec("zgrep -h '\[${currentDate}:${currentHourMinuteSecond}.*\]' $WWW_STATS_LOG_PATH | grep 'Debian APT-CURL' | grep '/{$myrepo->name}/{$myrepo->dist}/{$myrepo->section}_{$myrepo->env}/' | wc -l");
                        $countLastMinuteLogOccurences = exec("zgrep -h '\[${currentDate}:${currentHourMinute}.*\]' $WWW_STATS_LOG_PATH | grep 'Debian APT-CURL' | grep '/{$myrepo->name}/{$myrepo->dist}/{$myrepo->section}_{$myrepo->env}/' | wc -l");
                    }
                    /**
                     *  Si le comptage de la dernière minute n'est pas vide, alors on récupère aussi les détails des requêtes (date, source, cible...)
                     */
                    if ($countLastMinuteLogOccurences > 0) {
                        if ($OS_FAMILY == "Redhat") $countLastMinuteLogOccurences_details = explode("\n", trim(shell_exec("zgrep -h '\[${currentDate}:${currentHourMinute}.*\]' $WWW_STATS_LOG_PATH | grep 'urlgrabber' | grep '/{$myrepo->name}_{$myrepo->env}/'")));
                        if ($OS_FAMILY == "Debian") $countLastMinuteLogOccurences_details = explode("\n", trim(shell_exec("zgrep -h '\[${currentDate}:${currentHourMinute}.*\]' $WWW_STATS_LOG_PATH | grep 'Debian APT-CURL' | grep '/{$myrepo->name}/{$myrepo->dist}/{$myrepo->section}_{$myrepo->env}/'")));
                    }

                    /**
                     *  Comptage de la taille du repo et du nombre de paquets actuel
                     */
                    if ($OS_FAMILY == "Redhat") {
                        $repoSize = exec("du -hs ${REPOS_DIR}/{$myrepo->dateFormatted}_{$myrepo->name} | awk '{print $1}'");
                        $packagesCount = exec("find ${REPOS_DIR}/{$myrepo->dateFormatted}_{$myrepo->name}/ -type f -name '*.rpm' | wc -l");
                    }
                    if ($OS_FAMILY == "Debian") {
                        $repoSize = exec("du -hs ${REPOS_DIR}/{$myrepo->name}/{$myrepo->dist}/{$myrepo->date}_{$myrepo->section} | awk '{print $1}'");
                        $packagesCount = exec("find ${REPOS_DIR}/{$myrepo->name}/{$myrepo->dist}/{$myrepo->date}_{$myrepo->section}/ -type f -name '*.deb' | wc -l");
                    }

                    /**
                     *  Affichage de la taille du repo et du nombre de paquets actuel
                     */
                    echo '<div class="stats-div-15">';
                        echo '<p class="center">Propriétés</p>';
                        echo '<div class="stats-round-counter">';
                            echo '<br><p class="lowopacity">Taille du repo</p><br>';
                            echo '<span class="stats-info-container pointer">';
                                echo "<span class=\"stats-info-counter\">$repoSize</span>";
                            echo '</span>';
                        echo '</div>';

                        echo '<div class="stats-round-counter">';
                            echo '<br><p class="lowopacity">Nombre de paquets</p><br>';
                            echo '<span class="stats-info-container">';
                                echo "<span class=\"stats-info-counter pointer\">$packagesCount</span>";
                            echo '</span>';
                        echo '</div>';
                    echo '</div>';

                    /**
                     *  Affichage du nombre d'accès au repo en temps réel et de la dernière minute
                     */
                    echo '<div class="stats-div-15">';
                        echo '<p class="center">Nombre d\'accès au repo</p>';
                        echo '<div class="stats-round-counter">';
                            echo '<br><p class="lowopacity">Temps réel</p><br>';
                            echo '<span class="stats-info-container pointer">';
                                echo "<span class=\"stats-info-counter\">$countCurrentLogOccurences</span>";
                                if (!empty($countCurrentLogOccurences_details)) {
                                    echo '<span class="stats-info-requests">';
                                        foreach ($countCurrentLogOccurences_details as $logline) {
                                            $logSource = exec("echo '$logline'");
                                            echo $logSource . '<br>';
                                        }
                                    echo '</span>';
                                }
                            echo '</span>';
                        echo '</div>';

                        echo '<div class="stats-round-counter">';
                            echo '<br><p class="lowopacity">Dernière minute</p><br>';
                            echo '<span class="stats-info-container">';
                                echo "<span class=\"stats-info-counter pointer\">$countLastMinuteLogOccurences</span>";
                                if (!empty($countLastMinuteLogOccurences_details)) {
                                    $countLastMinuteLogOccurences_details = array_filter($countLastMinuteLogOccurences_details);
                                    echo '<span class="stats-info-requests">';
                                        foreach ($countLastMinuteLogOccurences_details as $logline) {
                                            $logSourceIP = exec("echo '$logline' | awk '{print $1}'");
                                            $logSourceHost = rtrim(exec("dig -x $logSourceIP +short"), '.'); // rtrim : Suppression du point après le nom d'hôte
                                            $logDate = exec("echo '$logline' | awk '{print $4}' | sed 's/\[//g'");
                                            $logRequest = exec("echo '$logline' | awk '{print $6,$7,$8}'");
                                            $logRequestResult = exec("echo '$logline' | awk '{print $9}'");

                                            /**
                                             *  Affichage d'une icone verte ou rouge suivant le résultat de la requête
                                             */
                                            if ($logRequestResult == "200")
                                                echo "<img src=\"icons/greencircle.png\" class=\"icon-small\" /> ";
                                            else
                                                echo "<img src=\"icons/redcircle.png\" class=\"icon-small\" /> ";
                                            /**
                                             *  Affichage des détails de la/les requête(s)
                                             */
                                            echo "$logDate - $logSourceHost ($logSourceIP) - $logRequest";
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

                    $hourCounter = date('H:i',strtotime('-1 hour',strtotime($currentHourMinute))); // le début du compteur commence à (heure actuelle - 1h) car ce graphique affichera des valeurs sur 1h

                    while ($hourCounter != $currentHourMinute) {
                        if ($OS_FAMILY == "Redhat") $countLogOccurences = exec("zgrep -h '\[${currentDate}:${hourCounter}.*\]' $WWW_STATS_LOG_PATH | grep 'urlgrabber' | grep '/{$myrepo->name}_{$myrepo->env}/' | wc -l");
                        if ($OS_FAMILY == "Debian") $countLogOccurences = exec("zgrep -h '\[${currentDate}:${hourCounter}.*\]' $WWW_STATS_LOG_PATH | grep 'Debian APT-CURL' | grep '/{$myrepo->name}/{$myrepo->dist}/{$myrepo->section}_{$myrepo->env}/' | wc -l");

                        if (!empty($countLogOccurences))
                            $lastHourDatas .= trim($countLogOccurences).', ';
                        else
                            $lastHourDatas .= "0, ";

                        $lastHourLabels .= "'$hourCounter', ";

                        $hourCounter = date('H:i',strtotime('+1 minute',strtotime($hourCounter)));
                    }

                    /**
                     *  Suppression de la dernière virgule
                     */
                    $lastHourLabels = rtrim($lastHourLabels, ', ');
                    $lastHourDatas  = rtrim($lastHourDatas, ', ');

                    if (!empty($lastHourLabels) AND !empty($lastHourDatas)) {
                        echo '<div class="stats-div-68">';
                        echo "<canvas id=\"repoAccessChart-$myrepo->name\" class=\"repo-stats-chart\"></canvas>";
                        echo '<script>';
                        echo "var ctx = document.getElementById('repoAccessChart-$myrepo->name').getContext('2d');
                            var myRepoAccessChart = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: [$lastHourLabels],    
                                    datasets: [{
                                        data: [$lastHourDatas],
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
                                                    labelString: 'Valeurs sur 1 heure'
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
                                        text: \"Nombre d'accès sur 1 heure\"
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
                                    echo '<td></td>';
                                    echo '<td>Date</td>';
                                    echo '<td>Source</td>';
                                    echo '<td>Requête</td>';
                                    echo '</tr>';
                                echo '</thead>';
                                echo '<tbody>';
                                    foreach ($lastAccess as $logline) {
                                        $logSourceIP = exec("echo '$logline' | awk '{print $1}'");
                                        $logSourceHost = rtrim(exec("dig -x $logSourceIP +short"), '.'); // rtrim : Suppression du point après le nom d'hôte
                                        $logDate = exec("echo '$logline' | awk '{print $4}' | sed 's/\[//g'");
                                        $logRequest = exec("echo '$logline' | awk '{print $6,$7,$8}'");
                                        $logRequestResult = exec("echo '$logline' | awk '{print $9}'");

                                        echo '<tr>';
                                        if ($logRequestResult == "200")
                                            echo "<td><img src=\"icons/greencircle.png\" class=\"icon-small\" title=\"$logRequestResult\" /></td>";
                                        else
                                            echo "<td><img src=\"icons/redcircle.png\" class=\"icon-small\" title=\"$logRequestResult\" /></td>";
                                        echo "<td>$logDate</td>";
                                        echo "<td>$logSourceHost ($logSourceIP)</td>";
                                        echo "<td>$logRequest</td>";
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
                     *  La fonction isempty est dans la class Database, donc on passe par l'objet $myrepo
                     */
                    if ($myrepo->db->isempty($result) === false) {
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
                        $countData   = rtrim($countData, ', ');

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
</html>