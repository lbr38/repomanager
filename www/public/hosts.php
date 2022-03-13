<!DOCTYPE html>
<html>
<?php
require_once('../models/Autoloader.php');
Autoloader::load();
include_once('../includes/head.inc.php');

/**
 *  Instancie un nouvel objet Group en précisant qu'il faut utiliser la BDD repomanager-hosts.db
 */
$group = new Group('host');

$error = 0;

/**
 * 	Cas où on souhaite modifier la liste des hotes d'un groupe
 */
/*if (!empty($_POST['actualGroupName']) AND !empty($_POST['groupAddServerId'])) {
	$mygroup = new Group(array('useDB' => 'hosts', 'groupName' => Common::validateData($_POST['actualGroupName'])));
  	// Pas de validateData sur $_POST['groupAddServerId'], il est opéré dans la fonction addRepo directement :
	$mygroup->addServer($_POST['groupAddServerId']);
	unset($mygroup);
}*/

/**
 *  Cas où un raffraichissement automatique a été demandé 
 */
if (isset($_GET['auto'])) {
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
} ?>

<body>
<?php include_once('../includes/header.inc.php');?>

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
                    <div class="round-div">
                        <br><p class="lowopacity">Nombre d'hôtes</p><br>
                        <div class="round-div-container">
                            <span><?php echo $totalHosts;?></span>
                        </div>
                    </div>
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

        <?php if (Common::isadmin()) { ?>
            <section id="groupsHostDiv" class="section-center hide">
                <img id="groupsDivCloseButton" title="Fermer" class="icon-lowopacity float-right" src="ressources/icons/close.png" />
                <h3>GROUPES</h3>
                <p><b>Créer un groupe :</b></p>
                <form id="newGroupForm" autocomplete="off">
                    <input id="newGroupInput" type="text" class="input-medium" /></td>
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
                    echo '<p><b>Groupes actuels :</b></p>';
                    echo '<div class="groups-list-container">';
                        foreach($groupsList as $groupName) { ?>
                            <div class="header-container">
                                <div class="header-blue-min">
                                    <form class="groupForm" groupname="<?php echo $groupName;?>" autocomplete="off">
                                        <input type="hidden" name="actualGroupName" value="<?php echo $groupName;?>" />
                                        <table class="table-large">
                                            <tr>
                                                <td>
                                                    <input class="groupFormInput input-medium invisibleInput-blue" groupname="<?php echo $groupName;?>" type="text" value="<?php echo $groupName;?>" />
                                                </td>
                                                <td class="td-fit">
                                                    <img class="groupConfigurationButton icon-mediumopacity" name="<?php echo $groupName;?>" title="Configuration de <?php echo $groupName;?>" src="ressources/icons/cog.png" />
                                                    <img src="ressources/icons/bin.png" class="deleteGroupButton icon-lowopacity" name="<?php echo $groupName;?>" title="Supprimer le groupe <?php echo $groupName;?>" />
                                                </td>
                                            </tr>
                                        </table>
                                    </form>
                                </div>

                                <div id="groupConfigurationDiv-<?php echo $groupName;?>" class="hide detailsDiv">
                                    <form class="groupHostsForm" groupname="<?php echo $groupName;?>" autocomplete="off">
                                        <p><b>Hôtes</b></p>
                                        <table class="table-large">
                                            <tr>
                                                <td>
                                                    <?php $group->selectServers($groupName); ?>
                                                </td>
                                                <td class="td-fit">
                                                    <button type="submit" class="btn-xxsmall-blue" title="Enregistrer">💾</button>
                                                </td>
                                            </tr>
                                        </table>
                                    </form>
                                </div>
                            </div>
                <?php   }
                    echo '</div>';
                } ?>
            </section>
        <?php } ?>

        <section id="hostsDiv" class="section-center">
            <?php
            /**
             *  Récupération des noms des groupes
             */
            $groupsList = $group->listAllWithDefault(); ?>

            <div>
                <div class="div-flex">
                    <h3>HÔTES</h3>
                    <?php if (Common::isadmin()) { ?>
                        <div>
                            <span id="GroupsListToggleButton" class="pointer" title="Gérer les groupes">Gérer les groupes<img src="ressources/icons/folder.png" class="icon"></span>
                        </div>
                    <?php } ?>
                </div>

                <?php
                if (!empty($groupsList)) {
                    /**
                     *  Si il y a au moins 1 hôte actif alors on fait apparaitre les champs de recherche
                     */
                    if ($totalHosts != 0) { ?>
                        <div class="searchInput-container">
                            <div class="searchInput-subcontainer">
                                <div>
                                    <p>Rechercher un hôte :</p>
                                    <input type="text" id="searchHostInput" onkeyup="searchHost()" class="input-large" autocomplete="off" placeholder="Nom d'hôte, IP" />
                                </div>
                                <div>
                                    <p>Rechercher un paquet :</p>
                                    <input type="text" id="searchHostPackageInput" onkeyup="searchHostPackage()" class="input-large" autocomplete="off" placeholder="Nom du paquet" />
                                </div>
                            </div>
                        </div>
            <?php   } else {

                        echo '<p>Il n\'y a aucun hote configuré</p>';

                    } ?>
                    
                    <div class="groups-container">

                    <?php
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
                        ?>
                        <input type='hidden' name='groupname' value='<?php echo $group->name;?>'>
        
                            <div class="hosts-group-container">
                                <?php
                                /**
                                 *  On affiche le nom du groupe sauf si il s'agit du groupe Default
                                 */
                                if ($group->name != "Default") {
                                    echo "<h3>$group->name</h3>";
                                }

                                if (Common::isadmin()) {
                                    /**
                                     *  Boutons d'actions sur les checkbox sélectionnées
                                     */ ?>
                                    <div class="js-buttons-<?php echo $group->name;?> hide">
                                    <button class="hostsActionBtn pointer btn-fit-blue" action="general-status-update" group="<?php echo $group->name;?>" title="Demander à l'hôte d'envoyer ses informations générales."><img src="ressources/icons/update.png" class="icon" /><b>Informations générales</b></button>
                                        <button class="hostsActionBtn pointer btn-fit-blue" action="available-packages-status-update" group="<?php echo $group->name;?>" title="Demander à l'hôte d'envoyer les paquets disponibles pour mise à jour."><img src="ressources/icons/update.png" class="icon" /><b>Paquets disponibles</b></button>
                                        <button class="hostsActionBtn pointer btn-fit-blue" action="installed-packages-status-update" group="<?php echo $group->name;?>" title="Demander à l'hôte d'envoyer la liste des paquets installés."><img src="ressources/icons/update.png" class="icon" /><b>Paquets installés</b></button>
                                        <button class="hostsActionBtn pointer btn-fit-yellow" action="update" group="<?php echo $group->name;?>" title="Demander à l'hôte d'exécuter une mise à jour de ses paquets."><img src="ressources/icons/update.png" class="icon" /><b>Mettre à jour les paquets</b></button>
                                        <br>
                                        <button class="hostsActionBtn pointer btn-fit-red" action="delete" group="<?php echo $group->name;?>" title="Supprimer l'hôte."><img src="ressources/icons/bin.png" class="icon" /><b>Supprimer</b></button>
                                        <button class="hostsActionBtn pointer btn-fit-red" action="reset" group="<?php echo $group->name;?>" title="Réinitialiser les données connues de l'hôte. Cette action est irréversible."><img src="ressources/icons/update.png" class="icon" /><b>Reset</b></button>
                                    </div>
                                <?php }
                                /**
                                 *  Affichage des hôtes du groupe
                                 */
                                if (!empty($hostsList)) { ?>
                                    <table class="hosts-table">
                                        <thead>
                                            <tr>
                                                <td></td>
                                                <td>Hôte</td>
                                                <td title="Paquets disponibles pour installation"><img src="../ressources/icons/products/package.png" class="icon" />Dispo.</td>
                                                <td></td>
                                                <?php if (Common::isadmin()) { ?>
                                                    <td><span class='js-select-all-button pointer' group='<?php echo $group->name; ?>'>Tout sélec.</span></td>
                                                <?php } ?>
                                                <td class="td-fit"></td>
                                                <td class="td-fit"></td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        foreach ($hostsList as $host) {
                                            /**
                                             *  On ouvre la BDD dédiée à l'hôte à partir de son ID, pour pouvoir récupérer des informations supplémentaires.
                                             */
                                            $myhost->openHostDb($host['Id']);

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
                                            $lastRequestedUpdate = $myhost->getLastRequestedUpdateStatus();

                                            echo '<tr class="host-tr" hostid="'.$host['Id'].'" hostname="'.$host['Hostname'].'">';
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
                                                } ?>                                                
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
                                                <?php if (Common::isadmin()) { ?>
                                                    <td><input type="checkbox" class="js-host-checkbox icon-verylowopacity" name="checkbox-host[]" group="<?php echo $group->name; ?>" value="<?php echo $host['Id']; ?>"></td>
                                                <?php } ?>
                                                <td class="host-update-status td-10">
                                                    <?php
                                                    /**
                                                     *  Status de la dernière demande
                                                     */                                                    
                                                    if (!empty($lastRequestedUpdate)) {
                                                        if ($lastRequestedUpdate['Type'] == 'packages-update') {
                                                            $updateType = 'Mise à jour des paquets';
                                                        }
                                                        if ($lastRequestedUpdate['Type'] == 'general-status-update') {
                                                            $updateType = 'Maj des infos. générales';
                                                        }
                                                        if ($lastRequestedUpdate['Type'] == 'available-packages-status-update') {
                                                            $updateType = 'Maj des paquets disponibles';
                                                        }
                                                        if ($lastRequestedUpdate['Type'] == 'installed-packages-status-update') {
                                                            $updateType = 'Maj des paquets installés';
                                                        }
                                                        if ($lastRequestedUpdate['Type'] == 'full-history-update') {
                                                            $updateType = 'Maj de l\'historique des évènements';
                                                        }
                                                        if ($lastRequestedUpdate['Status'] == 'requested') {
                                                            $updateStatus = 'demandée';
                                                        }
                                                        if ($lastRequestedUpdate['Status'] == 'running') {
                                                            $updateStatus = 'en cours<img src="ressources/images/loading.gif" class="icon" />';
                                                        }
                                                        if ($lastRequestedUpdate['Status'] == 'done') {
                                                            $updateStatus = 'terminée';
                                                        }
                                                        if ($lastRequestedUpdate['Status'] == 'error') {
                                                            $updateStatus = 'en erreur';
                                                        }

                                                        /**
                                                         *  Si la demande de mise à jour a été faite il y a plusieurs jours ou a été faite il y a +10min alors on affiche le message en jaune, l'hôte distant n'a peut être pas reçu ou traité la demande
                                                         */
                                                        if ($lastRequestedUpdate['Status'] == 'requested' OR $lastRequestedUpdate['Status'] == 'running') {
                                                            if ($lastRequestedUpdate['Date'] != DATE_YMD OR $lastRequestedUpdate['Time'] <= date('H:i:s', strtotime(date('H:i:s').' - 10 minutes'))) {
                                                                echo '<span class="yellowtext" title="La demande de mise à jour semble ne pas avoir été prise en compte par l\'hôte (demandée le '.DateTime::createFromFormat('Y-m-d', $lastRequestedUpdate['Date'])->format('d-m-Y').' à '.$lastRequestedUpdate['Time'].')">'.$updateType.' '.$updateStatus.'</span>';
                                                            } else {
                                                                echo '<span title="Le '.DateTime::createFromFormat('Y-m-d', $lastRequestedUpdate['Date'])->format('d-m-Y').' à '.$lastRequestedUpdate['Time'].'">'.$updateType.' '.$updateStatus.'</span>';
                                                            }
                                                        } 
                                                        if ($lastRequestedUpdate['Status'] == 'error') {
                                                            echo '<span class="redtext" title="Le '.DateTime::createFromFormat('Y-m-d', $lastRequestedUpdate['Date'])->format('d-m-Y').' à '.$lastRequestedUpdate['Time'].'">'.$updateType.' '.$updateStatus.'</span>';
                                                        }
                                                    } ?>
                                                </td>
                                                <td class="host-additionnal-info td-10">
                                                </td>
                                            </tr>
                                <?php   }
                                        echo '</tbody>';
                                    echo '</table>';
                                } else {
                                    echo '<table class="hosts-table-empty"><tr class="host-tr"><td class="lowopacity">(vide)</td></tr></table>';
                                }
    
                            echo '</div>';
                    }
                    echo '</div>';
                }
            echo '</div>'; ?>
        </section>
    </section>
</article>
<?php include_once('../includes/footer.inc.php'); ?>
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
            responsive: true
        }
    });
});
</script>
</html>