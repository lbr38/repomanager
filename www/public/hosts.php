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

/**
 *  Cas où le formulaire de modification des paramètres est validé
 */
if (!empty($_POST['settings-pkgs-considered-outdated']) and !empty($_POST['settings-pkgs-considered-critical'])) {
    $pkgs_considered_outdated = Common::validateData($_POST['settings-pkgs-considered-outdated']);
    $pkgs_considered_critical = Common::validateData($_POST['settings-pkgs-considered-critical']);

    $myhost = new Host();

    $myhost->setSettings($pkgs_considered_outdated, $pkgs_considered_critical);
} ?>

<body>
<?php include_once('../includes/header.inc.php');?>

<article>
    <section class="main">
        <section class="section-center">
            <h3>GESTION DU PARC</h3>

            <p>Gérez les mises à jour de vos hôtes et consultez leur état.</p>

            <br>

            <div class="hosts-container">
                <?php
                $myhost = new Host();

                /**
                 *  Récupération du nombre d'hôtes (liste et compte le nombre de lignes)
                 */
                $totalHosts = count($myhost->listAll('active')); 
                
                /**
                 *  Récupération des paramètres de seuils en base de données
                 */
                $hosts_settings = $myhost->getSettings(); 
                
                /**
                 *  Seuil du nombre de mises à jour disponibles à partir duquel on considère un hôte comme 'non à jour'
                 */
                $pkgs_count_considered_outdated = $hosts_settings['pkgs_count_considered_outdated'];

                /**
                 *  Seuil du nombre de mises à jour disponibles à partir duquel on considère un hôte comme 'non à jour' (critique)
                 */
                $pkgs_count_considered_critical = $hosts_settings['pkgs_count_considered_critical'];

                /**
                 *  Initialisation des compteurs du nombre d'hôtes à jour et non à jour pour le graph doughnut
                 */
                $totalUptodate = 0;
                $totalNotUptodate = 0;

                /**
                 *  Récupération de la liste de tous les OS des hôtes et comptage (pour le graph bar)
                 */
                $osList = $myhost->listCountOS();
                
                /**
                 *  Récupération de la liste de tous les kernel d'hôtes et comptage
                 */
                $kernelList = $myhost->listCountKernel();

                /**
                 *  Récupération de la liste de toutes les architectures d'hôtes et comptage
                 */
                $archList = $myhost->listCountArch(); 
                
                /**
                 *  Récupération de la liste de tous les environnements d'hôtes et comptage
                 */
                $envsList = $myhost->listCountEnv();

                /**
                 *  Récupération de la liste de tous les profils d'hôtes et comptage
                 */
                $profilesList = $myhost->listCountProfile(); ?>

                <div class="flex-div-100 div-generic-gray hosts-charts-container">
                <?php
                    echo '
                    <div class="hosts-chart-sub-container">
                        <canvas id="hosts-count-chart"></canvas>
                    </div>';
                    if (!empty($kernelList)) {
                        echo '
                        <div class="hosts-chart-sub-container">
                            <canvas id="hosts-kernel-chart"></canvas>
                        </div>';
                    }
                    if (!empty($profilesList)) {
                        echo '
                        <div class="hosts-chart-sub-container">
                            <canvas id="hosts-profile-chart"></canvas>
                        </div>';
                    } 
                    if (!empty($osList)) {
                        echo '
                        <div class="hosts-chart-sub-container">
                            <canvas id="hosts-os-chart"></canvas>
                        </div>';
                    }
                    if (!empty($archList)) {
                        echo '
                        <div class="hosts-chart-sub-container">
                            <canvas id="hosts-arch-chart"></canvas>
                        </div>';
                    }
                    if (!empty($envsList)) {
                        echo '
                        <div class="hosts-chart-sub-container">
                            <canvas id="hosts-env-chart"></canvas>
                        </div>';
                    } ?>
                </div>
            </div>
        </section>

        <?php if (Common::isadmin()) { ?>
            <section id="settingsDiv" class="section-center hide">
                <img id="settingsDivCloseButton" title="Fermer" class="icon-lowopacity float-right" src="ressources/icons/close.png" />
                <h3>PARAMÈTRES</h3>
                <div class="div-generic-gray">
                    <form id="hostsSettingsForm" action="hosts.php" method="post" autocomplete="off">
                        <table>
                            <tr>
                                <td>Afficher un label jaune lorsque le nombre de mises à jour disponible est supérieur ou égal à :</td>
                                <td><input type="number" class="input-small" name="settings-pkgs-considered-outdated" value="<?=$pkgs_count_considered_outdated?>" /></td>
                            </tr>
                            <tr>
                                <td>Afficher un label rouge lorsque le nombre de mises à jour disponible est supérieur ou égal à :</td>
                                <td><input type="number" class="input-small" name="settings-pkgs-considered-critical" value="<?=$pkgs_count_considered_critical?>" /></td>
                            </tr>
                        </table>
                        <br>
                        <button class="btn-large-blue">Enregistrer</button>
                    </form>
                </div>
            </section>

            <section id="groupsHostDiv" class="section-center hide">
                <img id="groupsDivCloseButton" title="Fermer" class="icon-lowopacity float-right" src="ressources/icons/close.png" />
                <h3>GROUPES</h3>
                <h5>Créer un groupe</h5>
                <form id="newGroupForm" autocomplete="off">
                    <input id="newGroupInput" type="text" class="input-medium" /></td>
                    <button type="submit" class="btn-xxsmall-blue" title="Ajouter">+</button></td>
                </form>
                
                <br>

                <?php
                /**
                 *  1. Récupération de tous les noms de groupes (en excluant le groupe par défaut)
                 */
                $groupsList = $group->listAllName();

                /**
                 *  2. Affichage des groupes si il y en a
                 */
                if (!empty($groupsList)) {
                    echo '<h5>Groupes actuels</h5>';
                    echo '<div class="groups-list-container">';
                        foreach ($groupsList as $groupName) { ?>
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
                            <span id="settingsToggleButton" class="pointer" title="Gérer les paramètres">Paramètres<img src="ressources/icons/cog.png" class="icon"></span>
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
                    foreach ($groupsList as $groupName) {
                        $group->name = $groupName;

                        /**
                         *  Récupération de la liste des hôtes du groupe
                         */
                        $hostsList = $group->listHosts();

                        /**
                         *  Si il s'agit du groupe par défaut 'Default' et que celui-ci ne possède aucun hôte alors on ignore son affichage
                         */
                        if ($group->name == "Default" and empty($hostsList)) continue;
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
                                        
                                        <h5>Demander à l'hôte l'envoi d'informations :</h5>
                                        <button class="hostsActionBtn pointer btn-fit-blue" action="general-status-update" group="<?php echo $group->name;?>" title="Demander à l'hôte d'envoyer ses informations générales."><img src="ressources/icons/update.png" class="icon" /><b>Informations générales</b></button>
                                        <button class="hostsActionBtn pointer btn-fit-blue" action="packages-status-update" group="<?php echo $group->name;?>" title="Demander à l'hôte d'envoyer les informations concernant ses paquets (disponibles, installés, mis à jours...)."><img src="ressources/icons/update.png" class="icon" /><b>Informations concernant les paquets</b></button>

                                        <h5>Demander à l'hôte l'exécution d'une action :</h5>
                                        <button class="hostsActionBtn pointer btn-fit-yellow" action="update" group="<?php echo $group->name;?>" title="Demander à l'hôte d'exécuter une mise à jour de ses paquets."><img src="ressources/icons/update.png" class="icon" /><b>Mettre à jour les paquets</b></button>
                                        
                                        <h5>Supprimer ou réinitialiser l'hôte :</h5>
                                        <button class="hostsActionBtn pointer btn-fit-red" action="reset" group="<?php echo $group->name;?>" title="Réinitialiser les données connues de l'hôte. Cette action est irréversible."><img src="ressources/icons/update.png" class="icon" /><b>Réinitialiser</b></button>
                                        <button class="hostsActionBtn pointer btn-fit-red" action="delete" group="<?php echo $group->name;?>" title="Supprimer l'hôte."><img src="ressources/icons/bin.png" class="icon" /><b>Supprimer</b></button>
                                    </div>
                                <?php }
                                /**
                                 *  Affichage des hôtes du groupe
                                 */
                                if (!empty($hostsList)) { ?>
                                    <table class="hosts-table">
                                        <thead>
                                            <tr>
                                                <td class="td-fit"></td>
                                                <td class="td-fit"></td>
                                                <td class="td-10"></td>
                                                <td title="Nombre total de paquets installés"><span>Inst.</span></td>
                                                <td title="Nombre total de mises à jour disponibles pour installation"><span>Disp.</span></td>
                                                <td class="hostDetails-td"></td>
                                                <?php if (Common::isadmin()) { ?>
                                                    <td class="td-fit"><span class='js-select-all-button pointer' group='<?php echo $group->name; ?>'>Tout sélec.</span></td>
                                                <?php } ?>
                                                <td class="td-10"></td>
                                                <td class="td-10"></td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        /**
                                         *  Traitement de la liste des hôtes
                                         *  Ici on va afficher le détails de chaque hôte et on en profites pour récupérer certaines informations supplémentaires en base de données
                                         */
                                        foreach ($hostsList as $host) {
                                            $id = $host['Id'];
                                            if (!empty($host['Hostname']))   $hostname   = $host['Hostname'];   else $hostname = 'unknow';
                                            if (!empty($host['Ip']))         $ip         = $host['Ip'];         else $ip = 'unknow';
                                            if (!empty($host['Os']))         $os         = $host['Os'];         else $os = 'unknow';
                                            if (!empty($host['Os_version'])) $os_version = $host['Os_version']; else $os_version = 'unknow';
                                            if (!empty($host['Os_family']))  $os_family  = $host['Os_family'];  else $os_family = 'unknow';
                                            if (!empty($host['Type']))       $type       = $host['Type'];       else $type = 'unknow';
                                            if (!empty($host['Kernel']))     $kernel     = $host['Kernel'];     else $kernel = 'unknow';
                                            if (!empty($host['Arch']))       $arch       = $host['Arch'];       else $arch = 'unknow';

                                            /**
                                             *  On ouvre la BDD dédiée de l'hôte à partir de son ID pour pouvoir récupérer des informations supplémentaires.
                                             */
                                            $myhost->openHostDb($id);

                                            /**
                                             *  Récupération des paquets disponibles pour installation
                                             */
                                            $packagesAvailableTotal = count($myhost->getPackagesAvailable());

                                            /**
                                             *  Récupération du nombre total de paquets installés
                                             */
                                            $packagesInstalledTotal = count($myhost->getPackagesInstalled());

                                            /**
                                             *  Si le nombre total de paquets disponibles récupéré précédemment est > $pkgs_count_considered_outdated (seuil défini par l'utilisateur) alors on incrémente $totalNotUptodate (recense le nombre d'hôtes qui ne sont pas à jour dans le chartjs)
                                             *  Sinon c'est $totalUptodate qu'on incrémente.
                                             */
                                            if ($packagesAvailableTotal >= $pkgs_count_considered_outdated) 
                                                $totalNotUptodate++;
                                            else
                                                $totalUptodate++;
                                                
                                            /**
                                             *  Récupération du status de la dernière mise à jour (si il y en a)
                                             */
                                            $lastRequestedUpdate = $myhost->getLastRequestedUpdateStatus();

                                            /**
                                             *  Fermeture de la base de données de l'hôte
                                             */
                                            $myhost->closeHostDb();

                                            /**
                                             *  Affichage des informations de l'hôte
                                             *  Ici le <tr> contiendra toutes les informations de l'hôte, ceci afin de pouvoir faire des recherches dessus (input 'rechercher un hôte')
                                             */
                                            echo '<tr class="host-tr" hostid="'.$id.'" hostname="'.$hostname.'" os="'.$os.'" os_version="'.$os_version.'" os_family="'.$os_family.'" type="'.$type.'" kernel="'.$kernel.'" arch="'.$arch.'">';
                                                /**
                                                 *  Status ping
                                                 */
                                                echo '<td class="td-fit">';
                                                    if ($host['Online_status'] == "online")
                                                        echo '<img src="ressources/icons/greencircle.png" class="icon-small" title="En ligne" />';
                                                    if ($host['Online_status'] == "unknown")
                                                        echo '<img src="ressources/icons/redcircle.png" class="icon-small" title="Inconnu" />';
                                                    if ($host['Online_status'] == "unreachable")
                                                        echo '<img src="ressources/icons/redcircle.png" class="icon-small" title="Injoignable" />';
                                                echo '</td>';

                                                /**
                                                 *  Nom de l'hôte + ip
                                                 */
                                                echo '<td class="td-fit" title="Distribution">';
                                                    if (preg_match('/centos/i', $os)) {
                                                        echo '<img src="ressources/icons/centos.png" class="icon" />';
                                                    } elseif (preg_match('/debian/i', $os)) {
                                                        echo '<img src="ressources/icons/debian.png" class="icon" />';
                                                    } elseif (preg_match('/ubuntu/i', $os) or preg_match('/mint/i', $os)) {
                                                        echo '<img src="ressources/icons/ubuntu.png" class="icon" />';
                                                    } else {
                                                        echo '<img src="ressources/icons/tux.png" class="icon" />';
                                                    }
                                                    echo $host['Hostname'].' ('.$ip.')';
                                                echo '</td>'; ?>

                                                <td class="hostType-td td-10 lowopacity">
                                                    <span title="Type <?=$type?>"><?=$type?></span>
                                                </td>
                                                <td class="packagesCount-td" title="<?=$packagesInstalledTotal.' paquet(s) installé(s) sur cet hôte'?>">
                                                    <span><?=$packagesInstalledTotal?></span>
                                                </td>                                           
                                                <td class="packagesCount-td" title="<?=$packagesAvailableTotal.' mise(s) à jour disponible(s) sur cet hôte'?>">
                                                    <?php
                                                    if ($packagesAvailableTotal >= $pkgs_count_considered_critical) {
                                                        echo '<span class="bkg-red">'.$packagesAvailableTotal.'</span>';
                                                    } elseif ($packagesAvailableTotal >= $pkgs_count_considered_outdated) {
                                                        echo '<span class="bkg-yellow">'.$packagesAvailableTotal.'</span>';
                                                    } else {
                                                        echo '<span>'.$packagesAvailableTotal.'</span>';
                                                    } ?>
                                                </td>
                                                <td class="hostDetails-td" title="Voir les détails de cet hôte">
                                                    <span class="printHostDetails pointer" host_id="<?=$id?>">Détails</span><a href="host.php?id=<?=$id?>" target="_blank" rel="noopener noreferrer"><img src="ressources/icons/external-link.png" class="icon-lowopacity" /></a>
                                                </td>
                                                <?php if (Common::isadmin()) { ?>
                                                    <td class="td-fit" title="Sélectionner <?=$hostname?>">
                                                        <input type="checkbox" class="js-host-checkbox icon-verylowopacity" name="checkbox-host[]" group="<?=$group->name?>" value="<?=$id?>">
                                                    </td>
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
                                                            $updateType = 'Envoi des infos. générales';
                                                        }
                                                        if ($lastRequestedUpdate['Type'] == 'packages-status-update') {
                                                            $updateType = 'Envoi de l\'état des paquets';
                                                        }
                                                        if ($lastRequestedUpdate['Status'] == 'requested') {
                                                            $updateStatus = 'demandé(e)';
                                                        }
                                                        if ($lastRequestedUpdate['Status'] == 'running') {
                                                            $updateStatus = 'en cours<img src="ressources/images/loading.gif" class="icon" />';
                                                        }
                                                        if ($lastRequestedUpdate['Status'] == 'done') {
                                                            $updateStatus = 'terminé(e)';
                                                        }
                                                        if ($lastRequestedUpdate['Status'] == 'error') {
                                                            $updateStatus = 'en erreur';
                                                        }

                                                        /**
                                                         *  Si la demande de mise à jour a été faite il y a plusieurs jours ou a été faite il y a +10min alors on affiche le message en jaune, l'hôte distant n'a peut être pas reçu ou traité la demande
                                                         */
                                                        if ($lastRequestedUpdate['Status'] == 'requested' or $lastRequestedUpdate['Status'] == 'running') {
                                                            if ($lastRequestedUpdate['Date'] != DATE_YMD or $lastRequestedUpdate['Time'] <= date('H:i:s', strtotime(date('H:i:s').' - 10 minutes'))) {
                                                                echo '<span class="yellowtext" title="La demande ne semble ne pas avoir été prise en compte par l\'hôte (demandée le '.DateTime::createFromFormat('Y-m-d', $lastRequestedUpdate['Date'])->format('d-m-Y').' à '.$lastRequestedUpdate['Time'].')">'.$updateType.' '.$updateStatus.'</span>';
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
                                    <?php
                                        }
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
<?php
/**
 *  Graphiques ChartJS
 */

/**
 *  Couleurs disponibles pour les graphiques
 */
$validHexColors = ['rgb(75, 192, 192)', 'rgb(255, 99, 132)', '#5993ec', '#e0b05f', '#24d794'];

/**
 *  Graph des hôtes
 */
$labels = "'A jour', 'A mettre à jour'";
$datas = "'$totalUptodate', '$totalNotUptodate'";
$backgrounds = "'rgb(75, 192, 192)','rgb(255, 99, 132)'";
$title = 'Hôtes ('.$totalHosts.')';
$chartId = 'hosts-count-chart';

include('../includes/hosts-pie-chart.inc.php');

/**
 *  Graph des kernels
 */
if (!empty($kernelList)) {
    $kernelNameList = '';
    $kernelCountList = '';
    $kernelBackgroundColor = '';

    foreach ($kernelList as $kernel) {
        $randomHexColor = array_rand($validHexColors, 1);

        /**
         *  Mise en forme du nom de l'OS et son nombre au format ChartJS
         */
        if (empty($kernel['Kernel'])) {
            $kernelNameList .= "'Inconnu',";
        } else {
            $kernelNameList .= "'".$kernel['Kernel']."',";
        }
        $kernelCountList .= "'".$kernel['Kernel_count']."',";
        
        /**
         *  On sélectionne une couleur au hasard dans l'array
         */
        $kernelBackgroundColor .= "'".$validHexColors[$randomHexColor]."',";
    }
    $labels = rtrim($kernelNameList, ',');
    $datas = rtrim($kernelCountList, ',');
    $backgrounds = rtrim($kernelBackgroundColor, ',');
    $title = "Versions de kernel";
    $chartId = 'hosts-kernel-chart';

    include('../includes/hosts-bar-chart.inc.php');
}

/**
 *  Graph des profils
 */
if (!empty($profilesList)) {
    $profileNameList = '';
    $profileCountList = '';
    $profileBackgroundColor = '';

    foreach ($profilesList as $profile) {
        $randomHexColor = array_rand($validHexColors, 1);

        /**
         *  Mise en forme du nom de l'OS et son nombre au format ChartJS
         */
        if (empty($profile['Profile'])) {
            $profileNameList .= "'Inconnu',";
        } else {
            $profileNameList .= "'".$profile['Profile']."',";
        }
        $profileCountList .= "'".$profile['Profile_count']."',";
        
        /**
         *  On sélectionne une couleur au hasard dans l'array
         */
        $profileBackgroundColor .= "'".$validHexColors[$randomHexColor]."',";
    }
    $labels = rtrim($profileNameList, ',');
    $datas = rtrim($profileCountList, ',');
    $backgrounds = rtrim($profileBackgroundColor, ',');
    $title = 'Profils';
    $chartId = 'hosts-profile-chart';

    include('../includes/hosts-bar-chart.inc.php');
}

/**
 *  Graph des OS
 */
if (!empty($osList)) {
    $osNameList = '';
    $osCountList = '';
    $osBackgroundColor = '';

    foreach ($osList as $os) {
        $randomHexColor = array_rand($validHexColors, 1);

        /**
         *  Mise en forme du nom de l'OS et son nombre au format ChartJS
         */
        if (empty($os['Os'])) {
            $osNameList .= "'Inconnu',";
        } else {
            $osNameList .= "'".ucfirst($os['Os'])." ".$os['Os_version']."',";
        }
        $osCountList .= "'".$os['Os_count']."',";
        
        /**
         *  On sélectionne une couleur au hasard dans l'array
         */
        $osBackgroundColor .= "'".$validHexColors[$randomHexColor]."',";
    }
    $labels = rtrim($osNameList, ',');
    $datas = rtrim($osCountList, ',');
    $backgrounds = rtrim($osBackgroundColor, ',');
    $title = "Systèmes d'exploitation";
    $chartId = 'hosts-os-chart';

    include('../includes/hosts-bar-chart.inc.php');
}

/**
 *  Graph des architectures
 */
if (!empty($archList)) {
    $archNameList = '';
    $archCountList = '';
    $archBackgroundColor = '';

    foreach ($archList as $arch) {
        $randomHexColor = array_rand($validHexColors, 1);

        /**
         *  Mise en forme du nom de l'OS et son nombre au format ChartJS
         */
        if (empty($arch['Arch'])) {
            $archNameList .= "'Inconnu',";
        } else {
            $archNameList .= "'".$arch['Arch']."',";
        }
        $archCountList .= "'".$arch['Arch_count']."',";

        /**
         *  On sélectionne une couleur au hasard dans l'array
         */
        $archBackgroundColor .= "'".$validHexColors[$randomHexColor]."',";
    }
    $labels = rtrim($archNameList, ',');
    $datas = rtrim($archCountList, ',');
    $backgrounds = rtrim($archBackgroundColor, ',');
    $title = "Architectures";
    $chartId = 'hosts-arch-chart';

    include('../includes/hosts-pie-chart.inc.php');
}

/**
 *  Graph des environnements
 */
if (!empty($envsList)) {
    $envNameList = '';
    $envCountList = '';
    $envBackgroundColor = '';

    foreach ($envsList as $env) {
        $randomHexColor = array_rand($validHexColors, 1);

        /**
         *  Mise en forme du nom de l'OS et son nombre au format ChartJS
         */
        if (empty($env['Env'])) {
            $envNameList .= "'Inconnu',";
        } else {
            $envNameList .= "'".$env['Env']."',";
        }
        $envCountList .= "'".$env['Env_count']."',";

        /**
         *  Si l'environnement correspond au dernier env de la chaine alors celui-ci sera en rouge
         */
        if ($env['Env'] == LAST_ENV) {
            $envBackgroundColor .= "'rgb(255, 99, 132)',";
        } else {
            /**
             *  On sélectionne une couleur au hasard dans l'array
             */
            $envBackgroundColor .= "'".$validHexColors[$randomHexColor]."',";
        }
    }
    $labels = rtrim($envNameList, ',');
    $datas = rtrim($envCountList, ',');
    $backgrounds = rtrim($envBackgroundColor, ',');
    $title = 'Environnements';
    $chartId = 'hosts-env-chart';

    include('../includes/hosts-pie-chart.inc.php');
} ?>
</html>