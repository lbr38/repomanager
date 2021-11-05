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
require_once('class/Database-servers.php');
require_once('class/Server.php');

/**
 *  Chargement de la BDD servers
 */
$db_servers = new Database_servers();

$error = 0;

/**
 *  Enregistrement d'un nouveau serveur
 */
if (!empty($_GET['action']) AND validateData($_GET['action']) == "register" AND !empty($_GET['host'])) {
    $host = validateData($_GET['host']);

    /**
     *  Vérif si l'hôte renseigné est un nom d'hôte ou une IP
     */
    if (preg_match('/^[a-zA-Z]/', $host)) {
        $myserver = new Server(array('serverHostname' => $host));
    }
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $myserver = new Server(array('serverIp' => $host));
    }
    
    $myserver->register();
}

/**
 *  Suppression d'un serveur
 */
if (!empty($_GET['action']) AND validateData($_GET['action']) == "unregister" AND !empty($_GET['id'])) {
    $serverId = validateData($_GET['id']);

    $myserver = new Server(array('serverId' => $serverId));
    $myserver->unregister();
}

/**
 *  Mise à jour d'un serveur
 */
if (!empty($_GET['action']) AND validateData($_GET['action']) == "update" AND !empty($_GET['id'])) {
    $serverId = validateData($_GET['id']);

    $myserver = new Server(array('serverId' => $serverId));
    $myserver->update();
}
?>

<body>
<?php include('includes/header.inc.php');?>

<article>
    <section class="main">
        <section class="section-center">
            <h3>GESTION DU PARC</h3>

            <p>Gérez les mises à jour de vos machines et consultez leur état.</p>

            <div id="servers-container">
                <?php

                    $myserver = new Server();  

                    /**
                     *  Affichage de la taille du repo et du nombre de paquets actuel
                     */
                    $totalServers = $myserver->totalServers();

                    echo '<div class="stats-div-15">';
                        echo '<p class="center">Propriétés</p>';
                        echo '<div class="stats-round-counter">';
                            echo '<br><p class="lowopacity">Nombre de serveurs</p><br>';
                            echo '<span class="stats-info-container">';
                                echo "<span class=\"stats-info-counter\">$totalServers</span>";
                            echo '</span>';
                        echo '</div>';

                        echo '<div class="stats-round-counter">';
                            echo '<br><p class="lowopacity">qlq chose</p><br>';
                            echo '<span class="stats-info-container">';
                                echo "<span class=\"stats-info-counter\">tutu</span>";
                            echo '</span>';
                        echo '</div>';
                    echo '</div>';

                    /**
                     *  Affichage du nombre d'accès au repo en temps réel et de la dernière minute
                     */
                    echo '<div id="refresh-me" class="stats-div-15">';
                        
                        /**
                         *  Temps réel
                         */
                        echo '<p class="center">Titre</p>';
                        echo '<div class="stats-round-counter">';
                            echo '<br><p class="lowopacity">qlq chose</p><br>';
                            echo '<span class="stats-info-container pointer">';
                                echo "<span class=\"stats-info-counter\">toto</span>";
                            echo '</span>';
                        echo '</div>';

                        /**
                         *  Dernière minute
                         */
                        echo '<div class="stats-round-counter">';
                            echo '<br><p class="lowopacity">qlq chose</p><br>';
                            echo '<span class="stats-info-container">';
                                echo "<span class=\"stats-info-counter pointer\">titi</span>";
                                /*if (!empty($lastMinuteAccess)) {
                                    echo '<span class="stats-info-requests">';
                                        foreach ($lastMinuteAccess as $line) {
                                          
                                            if ($line['Request_result'] == "200")
                                                echo "<img src=\"icons/greencircle.png\" class=\"icon-small\" /> ";
                                            else
                                                echo "<img src=\"icons/redcircle.png\" class=\"icon-small\" /> ";
                                       
                                            echo DateTime::createFromFormat('Y-m-d', $line['Date'])->format('d-m-Y').' à '.$line['Time'].' - '.$line['Source']. '('.$line['IP'].') - '.$line['Request'];
                                            echo '<br>';
                                        }
                                    echo '</span>';
                                }*/
                            echo '</span>';
                        echo '</div>';
                    echo '</div>';

                    /**
                     *  Affichage d'un graphique (doughnut) du nombre de serveurs à jour, pas à jour ou inconnu
                     */
                    $totalUptodate = $myserver->totalUptodate();
                    $totalNotUptodate = $myserver->totalNotUptodate();
                    $totalUptodate_unknown = $myserver->totalUptodate_unknown();

                    echo '<div class="stats-div-68">';
                    if (!empty($totalUptodate) AND !empty($totalNotUptodate) AND !empty($totalUptodate_unknown)) {
                        echo "<canvas id=\"updates-status-chart\" class=\"repo-stats-chart\"></canvas>";
                        echo '<script>';
                        echo "var ctx = document.getElementById('updates-status-chart').getContext('2d');
                            var myRepoAccessChart = new Chart(ctx, {
                                type: 'doughnut',
                                data: {
                                    labels: ['A jour', 'A mettre à jour', 'Inconnu'],
                                    datasets: [{
                                        label: 'Espace disque utilisé',
                                        data: [$totalUptodate, $totalNotUptodate, $totalUptodate_unknown],
                                        backgroundColor: ['rgb(255, 99, 132)','rgb(75, 192, 192)','rgb(255, 205, 86)','rgb(201, 203, 207)','rgb(54, 162, 235)'],
                                        borderColor: ['gray', 'gray'],
                                        borderWidth: 0.4
                                    }]
                                }
                            });";
                        echo '</script>';
                    }
                    echo '</div>';

                    /**
                     *  Tableau des serveurs gérés, triés par groupes
                     */
                    $serversList = $myserver->listAll();

                    echo '<div class="stats-div-100">';
                        echo '<p class="center lowopacity">Serveurs</p>';
                        echo '<form action="servers.php" method="get" autocomplete="off">';
                        echo '<p>Ajouter un serveur :</p>';
                        echo '<input type="hidden" name="action" value="register" />';
                        echo '<input type="text" name="host" class="input-medium" placeholder="IP ou hostname" required />';
                        echo '<button type="submit" class="button-submit-xxsmall-blue" title="Ajouter">+</button>';
                        echo '</form>';

                        /**
                         * Formulaire sur le tableau, permet de gérer les checkbox pour effectuer une action commune sur plusieurs serveurs sélectionnés
                         */
                        echo '<form action="servers.php" method="post" autocomplete="off">';
                            echo '<table class="stats-access-table">';
                                if (!empty($serversList)) {
                                    echo '<thead>';
                                        echo '<tr>';
                                        echo '<td></td>';
                                        //echo '<td>Date</td>';
                                        echo '<td>Serveur</td>';
                                        echo '<td>Status de mise à jour</td>';
                                        echo '</tr>';
                                    echo '</thead>';
                                    echo '<tbody>';
                                        foreach ($serversList as $server) {
                                            echo '<tr>';
                                                if ($server['Online_status'] == "online") {
                                                    echo '<td><img src="icons/greencircle.png" class="icon-small" title="En ligne" /></td>';
                                                }
                                                if ($server['Online_status'] == "unknown") {
                                                    echo '<td><img src="icons/redcircle.png" class="icon-small" title="Inconnu" /></td>';
                                                }
                                                if ($server['Online_status'] == "unreachable") {
                                                    echo '<td><img src="icons/redcircle.png" class="icon-small" title="Injoignable" /></td>';
                                                }
                                                
                                                //echo '<td>'.DateTime::createFromFormat('Y-m-d', $server['Status_date'])->format('d-m-Y').' à '.$server['Status_time'].'</td>';
                                                echo '<td>'.$server['Hostname'].' ('.$server['Ip'].')</td>';
                                            
                                                /**
                                                 *  Status de mise à jour
                                                 */
                                                echo '<td>';
                                                if ($server['Last_update_status'] == "none")
                                                    echo "-";
                                                if ($server['Last_update_status'] == "running")
                                                    echo 'En cours';
                                                if ($server['Last_update_status'] == "error")
                                                    echo 'En erreur';
                                                if ($server['Last_update_status'] == "done")
                                                    echo 'Terminée';
                                                echo '</td>';

                                                echo '<td>';
                                                echo '<a href="servers.php?action=update&id='.$server['Id'].'"><span><img src="icons/update.png" class="icon" />Mettre à jour </span></a>';
                                                echo '<a href="servers.php?action=unregister&id='.$server['Id'].'"><span><img src="icons/bin.png" class="icon" />Supprimer </span></a>';
                                                echo '</td>';

                                            echo '</tr>';
                                        }
                                    echo '</tbody>';
                                }
                            echo '</table>';
                        echo '</form>';
                    echo '</div>';
                 
               // } ?>
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
	/*setInterval(function(){
		$("#refresh-me").load(" #refresh-me > *");
	}, 1000);
});*/
</script>
</html>