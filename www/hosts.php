<!DOCTYPE html>
<html>
<?php
require_once('models/Autoloader.php');
Autoloader::load();
include_once('includes/head.inc.php');
require_once('functions/common-functions.php');

/**
 *  Instancie un nouvel objet Group en précisant qu'il faut utiliser la BDD repomanager-hosts.db
 */
$group = new Group(array('useDB' => 'hosts'));

$error = 0;

/**
 *  Enregistrement d'un nouvel hôte
 */
if (!empty($_GET['action']) AND validateData($_GET['action']) == "register" AND !empty($_GET['host'])) {
    $host = validateData($_GET['host']);

    $myhost = new Host();

    /**
     *  Vérif si l'hôte renseigné est un nom d'hôte ou une IP
     */
    if (preg_match('/^[a-zA-Z]/', $host)) {
        $myhost->hostname = $host;
    }
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $myhost->ip = $host;
    }
    
    $myhost->register();
}

/**
 * 	Cas où on souhaite ajouter un nouveau groupe
 */
/*if (!empty($_POST['addGroupName'])) {
    $group->new(validateData($_POST['addGroupName']));
}*/

/**
 * 	Cas où on souhaite supprimer un groupe
 */
/*if (!empty($_GET['action']) AND (validateData($_GET['action']) == "deleteGroup") AND !empty($_GET['groupName'])) {
    $group->delete(validateData($_GET['groupName']));
    $group->cleanServers();
}*/

/**
 * 	Cas où on souhaite renommer un groupe
 */
/*if (!empty($_POST['newGroupName']) AND !empty($_POST['actualGroupName'])) {
    $group->rename(validateData($_POST['actualGroupName']), validateData($_POST['newGroupName']));
}*/

/**
 * 	Cas où on souhaite modifier la liste des hotes d'un groupe
 */
/*if (!empty($_POST['actualGroupName']) AND !empty($_POST['groupAddServerId'])) {
	$mygroup = new Group(array('useDB' => 'hosts', 'groupName' => validateData($_POST['actualGroupName'])));
  	// Pas de validateData sur $_POST['groupAddServerId'], il est opéré dans la fonction addRepo directement :
	$mygroup->addServer($_POST['groupAddServerId']);
	unset($mygroup);
}*/

/**
 *  Mise à jour d'un ou plusieurs hote(s)
 *  La requête peut être en GET (1 seul) ou en POST (plusieurs)
 */
if ((!empty($_GET['action']) AND validateData($_GET['action']) == "update") OR (!empty($_POST['action']) AND validateData($_POST['action']) == "update")) {
    $myhost = new Host();
    
    /**
     *  Mise à jour d'un seul hote
     */
    /*if (!empty($_GET['id'])) {
        $myhost->id = validateData($_GET['id']);
    }*/
    
    /**
     *  Mise à jour de plusieurs hotes (plusieurs checkbox sélectionnées)
     */
    /*if (!empty($_POST['checkbox-host'])) {
        $myhost->idArray = $_POST['checkbox-host'];
    }*/
        
    $myhost->update($_POST['checkbox-host']);
}

/**
 *  Suppression d'un ou plusieurs hôtes
 *  La requete peut être en GET (1 seul) ou en POST (plusieurs)
 */
if (!empty($_POST['action']) AND validateData($_POST['action']) == "delete" AND !empty($_POST['checkbox-host'])) {
    $myhost = new Host();

    /**
     *  Suppression de plusieurs hotes (plusieurs checkbox sélectionnées)
     */
    /*if (!empty($_POST['checkbox-host'])) {
        $myhost->idArray = $_POST['checkbox-host'];
    }*/

    $myhost->unregister($_POST['checkbox-host']);
} 

/**
 *  Cas où un raffraichissement automatique a été demandé 
 */
if (isset($_GET['auto'])) {
    /**
     *  
     */
    $myhost = new Host();

    /**
     *  Récupération de tous les hôtes
     */
    $result = $myhost->db->query("SELECT Id, Ip FROM hosts");

    /**
     *  On traite l'opération uniquement si il y au moins 1 hôte de configuré sur ce serveur
     */
    if (!$myhost->db->isempty($result)) {

        $hosts = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $hosts[] = $row;

        /**
         *  On va tenter de pinguer ces hôtes afin de mettre à jour leur status (joignable ou injoignable)
         */
        foreach ($hosts as $host) {
            $myhost->id = $host['Id'];
            $myhost->ip = $host['Ip'];

            $myhost->update_online_status();
        }
    }
}
?>

<body>
<?php include('includes/header.inc.php');?>

<article>
    <section class="main">
        <section class="section-center">
            <h3>GESTION DU PARC</h3>

            <p>Gérez les mises à jour de vos hôtes et consultez leur état.</p>

            <div class="hosts-container">
                <?php

                $myhost = new Host();

                /**
                 *  Récupération du nombre d'hôtes (liste et compte le nombre de lignes)
                 */
                $totalHosts = count($myhost->listAll('active')); ?>

                <div class="flex-div-15">
                    <p class="center">Propriétés</p>
                    <div class="stats-round-counter">
                        <br><p class="lowopacity">Nombre d'hôtes</p><br>
                        <span class="stats-info-container">
                            <span class="stats-info-counter"><?php echo $totalHosts;?></span>
                        </span>
                    </div>
                    <!--<div class="stats-round-counter">
                        <br><p class="lowopacity"><br>
                        <span class="stats-info-container">
                            <span class="stats-info-counter"></span>
                        </span>
                    </div>-->
                </div>

                <!-- Graphique chartjs doughnut -->
                <div class="flex-div-68">
                    <?php
                    /**
                     *  Initialisation des compteurs du nombre d'hôtes à jour et non à jour pour le graph doughnut
                     */
                    $totalUptodate = 0;
                    $totalNotUptodate = 0; ?>
                    <canvas id="updates-status-chart" class="hosts-stats-chart"></canvas>
                </div>
            </div>
        </section>

        <section id="hostGroupsDiv" class="section-center hide">
            <img id="GroupsListCloseButton" title="Fermer" class="icon-lowopacity" src="ressources/icons/close.png" />
            <h3>GROUPES</h3>
            <p><b>Créer un groupe :</b></p>
            <form action="<?php echo __ACTUAL_URI__;?>" method="post" autocomplete="off">
                <input type="text" class="input-medium" name="addGroupName" /></td>
                <button type="submit" class="btn-xxsmall-blue" title="Ajouter">+</button></td>
            </form>
            <?php
            /**
             *  1. Récupération de tous les noms de groupes (en excluant le groupe par défaut)
             */
            $groupsList = $group->listAllName();

            /**
             *  2. Affichage des groupes si il y en a
             */
            if (!empty($groupsList)) {
                echo '<div class="groups-container">';
                echo '<p><b>Groupes actuels :</b></p>';
                    foreach($groupsList as $groupName) {
                        echo '<div class="header-container">';
                            //echo '<div class="header-blue-min">';
                                /**
                                 *   3. On créé un formulaire pour chaque groupe, car chaque groupe sera modifiable :
                                 */
                            /*    echo "<form action='__ACTUAL_URI__' method='post' autocomplete='off'>";
                                // On veut pouvoir renommer le groupe, ou ajouter des repos à ce groupe, donc il faut transmettre le nom de groupe actuel (actualGroupName) :
                                echo "<input type='hidden' name='actualGroupName' value='${groupName}' />";
                                echo '<table class="table-large">';
                                echo '<tr>';
                                // On affiche le nom actuel du groupe dans un input type=text qui permet de renseigner un nouveau nom si on le souhaite (newGroupeName) :
                                echo "<td><input type='text' value='${groupName}' name='newGroupName' class='input-medium invisibleInput-blue' /></td>";
                            
                                /**
                                 *  Boutons configuration et suppression du groupe
                                 */
                            /*    echo '<td class="td-fit">';
                                echo "<img id=\"groupConfigurationToggleButton-${groupName}\" class=\"icon-mediumopacity\" title=\"Configuration de $groupName\" src=\"ressources/icons/cog.png\" />";
                                echo "<img src=\"ressources/icons/bin.png\" class=\"groupDeleteToggleButton-${groupName} icon-lowopacity\" title=\"Supprimer le groupe ${groupName}\" />";
                                deleteConfirm("Etes-vous sûr de vouloir supprimer le groupe $groupName", "?action=deleteGroup&groupName=${groupName}", "groupDeleteDiv-${groupName}", "groupDeleteToggleButton-${groupName}");
                                echo '</td>';
                                echo '</tr>';
                                echo '</table>';
                                echo '</form>';
                            echo '</div>';*/

                            /**
                             *  4. La liste des repos du groupe est placée dans un div caché
                             */
                            /*echo "<div id=\"groupConfigurationDiv-${groupName}\" class=\"hide detailsDiv\">";
                                // On va récupérer la liste des repos du groupe et les afficher si il y en a (résultat non vide)           
                                echo "<form action=\"__ACTUAL_URI__\" method=\"post\" autocomplete=\"off\">";
                                    // Il faut transmettre le nom du groupe dans le formulaire, donc on ajoute un input caché avec le nom du groupe
                                    echo "<input type=\"hidden\" name=\"actualGroupName\" value=\"${groupName}\" />";
                                    echo '<p>Hôtes :</p>';
                                    echo '<table class="table-large">';
                                    echo '<tr>';
                                    echo '<td>';
                                    $group->selectServers($groupName);
                                    echo '</td>';
                                    echo '<td class="td-fit"><button type="submit" class="btn-xxsmall-blue" title="Enregistrer">💾</button></td>';
                                    echo '</tr>';
                                    echo '</table>';
                                echo '</form>';
                            echo '</div>';*/
                            // Afficher ou masquer la div 'groupConfigurationDiv' :
                            /*echo "<script>";
                            echo "$(document).ready(function(){";
                            echo "$(\"#groupConfigurationToggleButton-${groupName}\").click(function(){";
                                echo "$(\"div#groupConfigurationDiv-${groupName}\").slideToggle(150);";
                                echo '$(this).toggleClass("open");';
                            echo "});";
                            echo "});";
                            echo "</script>";*/
                        echo '</div>';
                    }
                echo '</div>';
            } ?>
        </section>

        <section class="section-center">
            <?php
            /**
             *  Récupération des noms des groupes
             */
            $groupsList = $group->listAllWithDefault(); ?>

            <div>
                <div class="div-flex">
                    <h3>HÔTES</h3>
                    <!--<div>
                        <span id="GroupsListToggleButton" class="pointer" title="Gérer les groupes">Gérer les groupes<img src="ressources/icons/folder.png" class="icon"></span>
                    </div>-->
                </div>

                <br>
                <!--<input id="searchHostInput" type="text" class="input-large" autocomplete="off" placeholder="Rechercher un hôte..." />
                <br>
                <br>-->

                <?php
                if (!empty($groupsList)) {
                    echo '<div class="groups-container">';
                    foreach($groupsList as $groupName) {
                        $group->name = $groupName;

                        /**
                         *  Récupération de la liste des hôtes du groupe
                         */
                        $hostsList = $group->listHosts();

                        /**
                         *  Si il s'agit du groupe par défaut 'Default' et que celui-ci ne possède aucun hôte alors on ignore son affichage
                         */
                        if ($group->name == "Default" AND empty($hostsList)) continue;

                        /**
                         * Formulaire sur le tableau, permet de gérer les checkbox pour effectuer une action commune sur plusieurs hôtes sélectionnés
                         */
                        echo '<form action="hosts.php" method="post" autocomplete="off">';
                        echo "<input type='hidden' name='groupname' value='$group->name'>";
                        echo '<div class="hosts-group-container">';

                        /**
                         *  On affiche le nom du groupe sauf si il s'agit du groupe Default
                         */
                        if ($group->name != "Default") {
                            echo "<h3>$group->name</h3>";
                        }

                        /**
                         *  Boutons d'actions sur les checkbox sélectionnées
                         */
                        echo "<div class='js-buttons-$group->name hide float-right'>";
                        echo "<button name='action' value='delete' class='hide pointer btn-medium-red js-delete-all-button' group='$group->name'><img src='ressources/icons/bin.png' class='icon' /><b>Supprimer</b></button>";
                        echo "<button name='action' value='update' class='hide pointer btn-medium-blue js-update-all-button' group='$group->name'><img src='ressources/icons/update.png' class='icon' /><b>Mettre à jour</b></button>";
                        echo '</div>';

                        /**
                         *  Affichage des hôtes du groupe
                         */
                        if (!empty($hostsList)) { ?>
                            <table class="hosts-table">
                                <thead>
                                    <tr>
                                        <td></td>
                                        <td>Hôte</td>
                                        <td>Status de mise à jour</td>
                                        <td>Paquets disponibles</td>
                                        <td></td>
                                        <td><span class='js-select-all-button pointer' group='<?php echo $group->name; ?>'>Tout sélec.</span></td>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                foreach ($hostsList as $host) {
                                    /**
                                     *  On ouvre la BDD dédiée à l'hôte à partir de son ID, pour pouvoir récupérer des informations supplémentaires.
                                     */
                                    $myhost->openHostDb($host['Id'], 'ro');

                                    /**
                                     *  Récupération des paquets disponibles pour installation
                                     */
                                    $packagesAvailable = $myhost->getPackagesAvailable();
                                    $packagesAvailableTotal = count($packagesAvailable);

                                    /**
                                     *  Si le nombre total de paquets disponibles récupéré précédemment est > 1 alors on incrémente $totalNotUptodate (recense le nombre d'hôtes qui ne sont pas à jour dans le chartjs)
                                     *  Sinon c'est $totalUptodate qu'on incrémente.
                                     */
                                    if ($packagesAvailableTotal > 1) 
                                        $totalNotUptodate++;
                                    else
                                        $totalUptodate++;
                                        
                                    /**
                                     *  Récupération du status de la dernière mise à jour (si il y en a)
                                     */
                                    $lastUpdateStatus = $myhost->getLastUpdateStatus();

                                    echo '<tr>';
                                        /**
                                         *  Status ping
                                         */
                                        if ($host['Online_status'] == "online")
                                            echo '<td><img src="ressources/icons/greencircle.png" class="icon-small" title="En ligne" /></td>';
                                        if ($host['Online_status'] == "unknown")
                                            echo '<td><img src="ressources/icons/redcircle.png" class="icon-small" title="Inconnu" /></td>';
                                        if ($host['Online_status'] == "unreachable")
                                            echo '<td><img src="ressources/icons/redcircle.png" class="icon-small" title="Injoignable" /></td>';
                                        /**
                                         *  Nom de l'hôte + ip
                                         */
                                        if ($host['Os'] == "Centos" OR $host['Os'] == "centos" OR $host['Os'] == "CentOS") {
                                            echo '<td><img src="ressources/icons/centos.png" class="icon" /> '.$host['Hostname'].' ('.$host['Ip'].')</td>';
                                        } elseif ($host['Os'] == "Debian" OR $host['Os'] == "debian") {
                                            echo '<td><img src="ressources/icons/debian.png" class="icon" /> '.$host['Hostname'].' ('.$host['Ip'].')</td>';
                                        } elseif ($host['Os'] == "Ubuntu" OR $host['Os'] == "ubuntu" OR $host['Os'] == "linuxmint") {
                                            echo '<td><img src="ressources/icons/ubuntu.png" class="icon" /> '.$host['Hostname'].' ('.$host['Ip'].')</td>';
                                        } else {
                                            echo '<td><img src="ressources/icons/tux.png" class="icon" /> '.$host['Hostname'].' ('.$host['Ip'].')</td>';
                                        }
                                        /**
                                         *  Status de mise à jour
                                         */
                                        echo '<td>';
                                        if (empty($lastUpdateStatus['Status']))
                                            echo "-";
                                        elseif ($lastUpdateStatus['Status'] == "running")
                                            echo "<span title='".DateTime::createFromFormat('Y-m-d', $lastUpdateStatus['Date'])->format('d-m-Y')." à ".$lastUpdateStatus['Time']."'>En cours</span>";
                                        elseif ($lastUpdateStatus['Status'] == "error")
                                            echo "<span title='".DateTime::createFromFormat('Y-m-d', $lastUpdateStatus['Date'])->format('d-m-Y')." à ".$lastUpdateStatus['Time']."'>En erreur</span>";
                                        elseif ($lastUpdateStatus['Status'] == "done")
                                            echo "<span title='".DateTime::createFromFormat('Y-m-d', $lastUpdateStatus['Date'])->format('d-m-Y')." à ".$lastUpdateStatus['Time']."'>Terminée</span>";
                                        elseif ($lastUpdateStatus['Status'] == "requested") {
                                            /**
                                             *  Si la demande de mise à jour a été faite il y a plusieurs jours ou a été faite il y a +5min alors on affiche le message en jaune, l'hôte distant n'a peut être pas reçu ou traité la demande
                                             */
                                            if ($lastUpdateStatus['Date'] != DATE_YMD OR $lastUpdateStatus['Time'] <= date('H:i:s', strtotime(date('H:i:s').' - 5 minutes'))) {
                                                echo "<span class='yellowtext' title=\"La demande de mise à jour semble ne pas avoir été prise en compte par l'hôte (demandée le ".DateTime::createFromFormat('Y-m-d', $lastUpdateStatus['Date'])->format('d-m-Y')." à ".$lastUpdateStatus['Time'].")\">Mise à jour demandée</span>";
                                            } else {
                                                echo "<span title='".DateTime::createFromFormat('Y-m-d', $lastUpdateStatus['Date'])->format('d-m-Y')." à ".$lastUpdateStatus['Time']."'>Mise à jour demandée</span>";
                                            }
                                        } ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($packagesAvailableTotal < "10") {
                                                echo '<span>'.$packagesAvailableTotal.'</span>';
                                            } elseif ($packagesAvailableTotal >= "10" AND $packagesAvailableTotal < "20") {
                                                echo '<span class="yellowtext">'.$packagesAvailableTotal.'</span>';
                                            } elseif ($packagesAvailableTotal > "20") {
                                                echo '<span class="redtext">'.$packagesAvailableTotal.'</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><span class="printHostDetails pointer" host_id="<?php echo $host['Id']; ?>">Détails</span><a href="host.php?id=<?php echo $host['Id']; ?>" target="_blank" rel="noopener noreferrer"><img src="ressources/icons/external-link.png" class="icon-lowopacity" /></a></td>
                                        <td><input type="checkbox" class="js-host-checkbox icon-verylowopacity" name="checkbox-host[]" group="<?php echo $group->name; ?>" value="<?php echo $host['Id']; ?>"></td>
                                    </tr>
                        <?php   }
                                echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo '<p>Il n\'y a aucun hôte dans ce groupe</p>';
                        }
                                
                        echo '</div>';
                        echo '</form>';
                    }
                    echo '</div>';
                }
            echo '</div>'; ?>
        </section>
    </section>
</article>
<?php include('includes/footer.inc.php'); ?>
</body>
<script>
$(document).ready(function(){
    /**
     *  Graphique chartjs doughnut
     */
    var ctx = document.getElementById('updates-status-chart').getContext('2d');
    var myHostsChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['A jour', 'A mettre à jour'],
            datasets: [{
                label: 'Espace disque utilisé',
                data: [<?php echo "$totalUptodate, $totalNotUptodate";?>],
                backgroundColor: ['rgb(75, 192, 192)','rgb(255, 99, 132)'],
                borderColor: ['gray', 'gray'],
                borderWidth: 0.4
            }]
        },
        options: {
            aspectRatio: 1,
            responsive: false
        }
    });
});
</script>
</html>