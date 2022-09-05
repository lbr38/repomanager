<!DOCTYPE html>
<html>
<?php
require_once('../controllers/Autoloader.php');
\Controllers\Autoloader::load();
include_once('../includes/head.inc.php');

/**
 *  Instancie un nouvel objet Group en précisant qu'il faut utiliser la BDD repomanager-hosts.db
 */
$group = new \Controllers\Group('host');

/**
 *  Cas où le formulaire de modification des paramètres est validé
 */
if (!empty($_POST['settings-pkgs-considered-outdated']) and !empty($_POST['settings-pkgs-considered-critical'])) {
    $pkgs_considered_outdated = \Controllers\Common::validateData($_POST['settings-pkgs-considered-outdated']);
    $pkgs_considered_critical = \Controllers\Common::validateData($_POST['settings-pkgs-considered-critical']);

    $myhost = new \Controllers\Host();

    $myhost->setSettings($pkgs_considered_outdated, $pkgs_considered_critical);
}

/**
 *  Couleurs disponibles pour les graphiques
 */
$validHexColors = ['rgb(75, 192, 192)', 'rgb(255, 99, 132)', '#5993ec', '#e0b05f', '#24d794']; ?>

<body>
<?php include_once('../includes/header.inc.php');?>

<article>
    <section class="main">
        <section class="section-center">
            <h3>MANAGE HOSTS</h3>

            <p>Manage your hosts updates and check their state.</p>

            <br>

            <div class="hosts-container">
                <?php
                $myhost = new \Controllers\Host();

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
                array_multisort(array_column($kernelList, 'Kernel_count'), SORT_DESC, $kernelList);

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
                $profilesList = $myhost->listCountProfile();
                array_multisort(array_column($profilesList, 'Profile_count'), SORT_DESC, $profilesList);
                ?>

                <div class="flex-div-100 hosts-charts-container">

                <div class="hosts-chart-sub-container div-generic-gray">
                    <span class="hosts-chart-title">Hosts (<?= $totalHosts ?>)</span>
                    <canvas id="hosts-count-chart" class="host-pie-chart"></canvas>
                </div>
    
                <?php
                if (!empty($kernelList)) : ?>
                    <div class="hosts-chart-sub-container div-generic-gray">
                        <span class="hosts-chart-title">Kernels</span>
                    
                        <div class="hosts-charts-list-column-container">
                            <?php
                            foreach ($kernelList as $kernel) {
                                $randomHexColor = array_rand($validHexColors, 1);

                                if (empty($kernel['Kernel'])) {
                                    $kernelName = 'Unknow';
                                } else {
                                    $kernelName = $kernel['Kernel'];
                                } ?>

                                <div class="hosts-charts-list-container">
                                    <div class="hosts-charts-list-label">
                                        <div>
                                            <!-- square figure -->
                                            <span style="background-color: <?= $validHexColors[$randomHexColor] ?>"></span>
                                            <span><?= $kernelName ?></span>
                                        </div>
                                        <span><?= $kernel['Kernel_count'] ?></span>
                                    </div>

                                    <div class="hosts-charts-list-data">
                                        <span></span>
                                    </div>
                                </div>

                                <?php
                            } ?>
                        </div>
                    </div>

                <?php endif;
                if (!empty($profilesList)) : ?>
                    <div class="hosts-chart-sub-container div-generic-gray">
                        <span class="hosts-chart-title">Profiles</span>

                        <div class="hosts-charts-list-column-container">
                            <?php
                            foreach ($profilesList as $profile) {
                                $randomHexColor = array_rand($validHexColors, 1);

                                if (empty($profile['Profile'])) {
                                    $profileName = 'Unknow';
                                } else {
                                    $profileName = $profile['Profile'];
                                } ?>
                                
                                <div class="hosts-charts-list-container">
                                    <div class="hosts-charts-list-label">
                                        <div>
                                            <!-- square figure -->
                                            <span style="background-color: <?= $validHexColors[$randomHexColor] ?>"></span>
                                            <span><?= $profileName ?></span>
                                        </div>
                                        <span><?= $profile['Profile_count'] ?></span>
                                    </div>

                                    <div class="hosts-charts-list-data">
                                        <span></span>
                                    </div>
                                </div>

                                <?php
                            } ?>
                        </div>
                    </div>
                <?php endif;

                if (!empty($osList)) : ?>
                    <div class="hosts-chart-sub-container div-generic-gray">
                        <span class="hosts-chart-title">Operating systems</span>
                        <canvas id="hosts-os-chart" class="host-bar-chart"></canvas>
                    </div>
                <?php endif;

                if (!empty($archList)) : ?>
                    <div class="hosts-chart-sub-container div-generic-gray">
                        <span class="hosts-chart-title">Architectures</span>
                        <canvas id="hosts-arch-chart" class="host-pie-chart"></canvas>
                    </div>
                <?php endif;

                if (!empty($envsList)) : ?>
                    <div class="hosts-chart-sub-container div-generic-gray">
                        <span class="hosts-chart-title">Environments</span>
                        <canvas id="hosts-env-chart" class="host-pie-chart"></canvas>
                    </div>
                <?php endif ?>
                </div>
            </div>
        </section>

        <?php if (\Controllers\Common::isadmin()) { ?>
            <section id="settingsDiv" class="section-center hide">
                <img id="settingsDivCloseButton" title="Close" class="icon-lowopacity float-right" src="resources/icons/close.png" />
                <h3>DISPLAY SETTINGS</h3>
                <div class="div-generic-gray">
                    <form id="hostsSettingsForm" action="hosts.php" method="post" autocomplete="off">
                        <table>
                            <tr>
                                <td>Display a yellow label when total available update is greater than or equal to:</td>
                                <td><input type="number" class="input-small" name="settings-pkgs-considered-outdated" value="<?=$pkgs_count_considered_outdated?>" /></td>
                            </tr>
                            <tr>
                                <td>Display a red label when total available update is greater than or equal to:</td>
                                <td><input type="number" class="input-small" name="settings-pkgs-considered-critical" value="<?=$pkgs_count_considered_critical?>" /></td>
                            </tr>
                        </table>
                        <br>
                        <button class="btn-large-blue">Save</button>
                    </form>
                </div>
            </section>

            <section id="groupsHostDiv" class="section-center hide">
                <img id="groupsDivCloseButton" title="Close" class="icon-lowopacity float-right" src="resources/icons/close.png" />
                <h3>GROUPS</h3>
                <h5>Create a group</h5>
                <form id="newGroupForm" autocomplete="off">
                    <input id="newGroupInput" type="text" class="input-medium" /></td>
                    <button type="submit" class="btn-xxsmall-blue" title="Add">+</button></td>
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
                    echo '<div class="div-generic-gray">';
                    echo '<h5>Current groups</h5>';
                    echo '<div class="groups-list-container">';

                    foreach ($groupsList as $groupName) : ?>
                        <div class="header-container">
                            <div class="header-blue-min">
                                <form class="groupForm" groupname="<?= $groupName ?>" autocomplete="off">
                                    <input type="hidden" name="actualGroupName" value="<?= $groupName ?>" />
                                    <table class="table-large">
                                        <tr>
                                            <td>
                                                <input class="groupFormInput input-medium invisibleInput-blue" groupname="<?= $groupName ?>" type="text" value="<?= $groupName ?>" />
                                            </td>
                                            <td class="td-fit">
                                                <img class="groupConfigurationButton icon-mediumopacity" name="<?= $groupName ?>" title="<?= $groupName ?> group configuration" src="resources/icons/cog.png" />
                                                <img src="resources/icons/bin.png" class="deleteGroupButton icon-lowopacity" name="<?= $groupName ?>" title="Delete <?= $groupName ?> group" />
                                            </td>
                                        </tr>
                                    </table>
                                </form>
                            </div>

                            <div id="groupConfigurationDiv-<?= $groupName ?>" class="hide detailsDiv">
                                <form class="groupHostsForm" groupname="<?= $groupName ?>" autocomplete="off">
                                    <p><b>Hosts</b></p>
                                    <table class="table-large">
                                        <tr>
                                            <td>
                                                <?php $myhost->selectServers($groupName); ?>
                                            </td>
                                            <td class="td-fit">
                                                <button type="submit" class="btn-xxsmall-blue" title="Save">💾</button>
                                            </td>
                                        </tr>
                                    </table>
                                </form>
                            </div>
                        </div>
                        <?php
                    endforeach;
                    echo '</div>';
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
                    <h3>HOSTS</h3>
                    <?php if (\Controllers\Common::isadmin()) { ?>
                        <div>
                            <span id="GroupsListToggleButton" class="pointer" title="Manage hosts groups">Manage groups<img src="resources/icons/folder.png" class="icon"></span>
                            <span id="settingsToggleButton" class="pointer" title="Display settings">Settings<img src="resources/icons/cog.png" class="icon"></span>
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
                                    <p>
                                        <img src="resources/icons/info.png" class="icon-lowopacity" title="You can specify a filter before your search entry:&#13;os:<os name> <search>&#13;os_version:<os version> <search>&#13;os_family:<os family> <search>&#13;type:<virtualization type> <search>&#13;kernel:<kernel> <search>&#13;arch:<architecture> <search>" />Search host:                                            
                                    </p>
                                    <input type="text" id="searchHostInput" onkeyup="searchHost()" class="input-large" autocomplete="off" placeholder="Hostname, IP" />
                                </div>
                                <div>
                                    <p>Search package:</p>
                                    <input type="text" id="searchHostPackageInput" onkeyup="searchHostPackage()" class="input-large" autocomplete="off" placeholder="Package name" />
                                </div>
                            </div>
                        </div>
                        <?php
                    } else {
                        echo '<p>No host configured.</p>';
                    } ?>
                    
                    <div class="groups-container">

                    <?php
                    foreach ($groupsList as $groupName) {
                        /**
                         *  Récupération de la liste des hôtes du groupe
                         */
                        $hostsList = $myhost->listByGroup($groupName);

                        /**
                         *  Si il s'agit du groupe par défaut 'Default' et que celui-ci ne possède aucun hôte alors on ignore son affichage
                         */
                        if ($groupName == "Default" and empty($hostsList)) {
                            continue;
                        }
                        ?>
                        <input type='hidden' name='groupname' value='<?=$groupName?>'>
        
                        <div class="hosts-group-container">
                            <?php
                            /**
                             *  On affiche le nom du groupe sauf si il s'agit du groupe Default
                             */
                            if ($groupName != "Default") {
                                echo '<h3>';
                                echo $groupName;
                                /**
                                 *  Affichage du nombre d'hôtes dans ce groupe
                                 */
                                if (!empty($hostsList)) {
                                    echo ' (' . count($hostsList) . ')';
                                }
                                echo '</h3>';
                            }

                            if (\Controllers\Common::isadmin()) {
                                /**
                                 *  Boutons d'actions sur les checkbox sélectionnées
                                 */ ?>
                                <div class="js-buttons-<?=$groupName?> hide">
                                    
                                    <h5>Request selected host(s) to send informations:</h5>
                                    <button class="hostsActionBtn pointer btn-fit-blue" action="general-status-update" group="<?=$groupName?>" title="Send general informations (OS and state informations)."><img src="resources/icons/update.png" class="icon" /><b>General informations</b></button>
                                    <button class="hostsActionBtn pointer btn-fit-blue" action="packages-status-update" group="<?=$groupName?>" title="Send packages informations (available, installed, updated...)."><img src="resources/icons/update.png" class="icon" /><b>Packages informations</b></button>
                                    <h5>Request selected host(s) to execute an action.</h5>
                                    <button class="hostsActionBtn pointer btn-fit-yellow" action="update" group="<?=$groupName?>" title="Update all available packages using linupdate."><img src="resources/icons/update.png" class="icon" /><b>Update packages</b></button>
                                    
                                    <h5>Delete or reset selected host(s).</h5>
                                    <button class="hostsActionBtn pointer btn-fit-red" action="reset" group="<?=$groupName?>" title="Reset known data."><img src="resources/icons/update.png" class="icon" /><b>Reset</b></button>
                                    <button class="hostsActionBtn pointer btn-fit-red" action="delete" group="<?=$groupName?>" title="Delete."><img src="resources/icons/bin.png" class="icon" /><b>Delete</b></button>
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
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td title="Total installed packages."><span>Inst.</span></td>
                                            <td title="Total available updates."><span>Avail.</span></td>
                                            <td></td>
                                            <?php
                                            if (\Controllers\Common::isadmin()) : ?>
                                                <td>
                                                    <span class='js-select-all-button pointer' group='<?=$groupName?>'>Select all</span>
                                                </td>
                                                <?php
                                            endif ?>
                                            <td></td>
                                            <td></td>
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
                                            if (!empty($host['Hostname'])) {
                                                $hostname   = $host['Hostname'];
                                            } else {
                                                $hostname = 'unknow';
                                            }
                                            if (!empty($host['Ip'])) {
                                                $ip         = $host['Ip'];
                                            } else {
                                                $ip = 'unknow';
                                            }
                                            if (!empty($host['Os'])) {
                                                $os         = $host['Os'];
                                            } else {
                                                $os = 'unknow';
                                            }
                                            if (!empty($host['Os_version'])) {
                                                $os_version = $host['Os_version'];
                                            } else {
                                                $os_version = 'unknow';
                                            }
                                            if (!empty($host['Os_family'])) {
                                                $os_family  = $host['Os_family'];
                                            } else {
                                                $os_family = 'unknow';
                                            }
                                            if (!empty($host['Type'])) {
                                                $type       = $host['Type'];
                                            } else {
                                                $type = 'unknow';
                                            }
                                            if (!empty($host['Kernel'])) {
                                                $kernel     = $host['Kernel'];
                                            } else {
                                                $kernel = 'unknow';
                                            }
                                            if (!empty($host['Arch'])) {
                                                $arch       = $host['Arch'];
                                            } else {
                                                $arch = 'unknow';
                                            }
                                            /**
                                             *  On défini le status de l'agent
                                             *  Ce status peut passer en 'stopped' si l'agent n'a pas donné de nouvelles après 1h
                                             */
                                            $agentStatus = $host['Online_status'];
                                            /**
                                             *  On vérifie que la dernière fois que l'agent a remonté son status est inférieur à 1h (et 10min de "marge")
                                             */
                                            if ($host['Online_status_date'] != DATE_YMD or $host['Online_status_time'] <= date('H:i:s', strtotime(date('H:i:s') . ' - 70 minutes'))) {
                                                $agentStatus = 'seems-stopped';
                                            }
                                            /**
                                             *  Message du dernier état connu
                                             */
                                            $agentLastSendStatusMsg = 'state on ' . DateTime::createFromFormat('Y-m-d', $host['Online_status_date'])->format('d-m-Y') . ' at ' . $host['Online_status_time'];
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
                                            if ($packagesAvailableTotal >= $pkgs_count_considered_outdated) {
                                                $totalNotUptodate++;
                                            } else {
                                                $totalUptodate++;
                                            }
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
                                             */ ?>
                                            <tr class="host-tr" hostid="<?= $id ?>" hostname="<?= $hostname ?>" os="<?= $os ?>" os_version="<?= $os_version ?>" os_family="<?= $os_family ?>" type="<?= $type ?>" kernel="<?= $kernel ?>" arch="<?= $arch ?>">
                                                <td>
                                                    <?php
                                                    /**
                                                     *  Linupdate agent state
                                                     */
                                                    if ($agentStatus == 'running') {
                                                        echo '<img src="resources/icons/greencircle.png" class="icon-small" title="Linupdate agent state on the host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." />';
                                                    }
                                                    if ($agentStatus == "disabled") {
                                                        echo '<img src="resources/icons/yellowcircle.png" class="icon-small" title="Linupdate agent state on the host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." />';
                                                    }
                                                    if ($agentStatus == "stopped") {
                                                        echo '<img src="resources/icons/redcircle.png" class="icon-small" title="Linupdate agent state on the host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." />';
                                                    }
                                                    if ($agentStatus == "seems-stopped") {
                                                        echo '<img src="resources/icons/redcircle.png" class="icon-small" title="Linupdate agent state on the host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." />';
                                                    }
                                                    if ($agentStatus == "unknow") {
                                                        echo '<img src="resources/icons/graycircle.png" class="icon-small" title="Linupdate agent state on the host: unknow." />';
                                                    } ?>
                                                </td>

                                                <td>
                                                    <?php
                                                    if (preg_match('/centos/i', $os)) {
                                                        echo '<img src="resources/icons/centos.png" class="icon" title="' . $os . '" />';
                                                    } elseif (preg_match('/debian/i', $os)) {
                                                        echo '<img src="resources/icons/debian.png" class="icon" title="' . $os . '" />';
                                                    } elseif (preg_match('/ubuntu/i', $os)) {
                                                        echo '<img src="resources/icons/ubuntu.png" class="icon" title="' . $os . '" />';
                                                    } elseif (preg_match('/mint/i', $os)) {
                                                        echo '<img src="resources/icons/ubuntu.png" class="icon" title="' . $os . '" />';
                                                    } else {
                                                        echo '<img src="resources/icons/tux.png" class="icon" title="' . $os . '" />';
                                                    } ?>
                                                </td>

                                                <td>
                                                    <?php
                                                    /**
                                                     *  Nom de l'hôte + ip
                                                     */
                                                    echo '<span title="Hostname and IP address">' . $host['Hostname'] . ' (' . $ip . ')</span>'; ?>
                                                </td>

                                                <td class="hostType-td lowopacity">
                                                    <span title="Type <?=$type?>"><?=$type?></span>
                                                </td>

                                                <td class="packagesCount-td" title="<?=$packagesInstalledTotal . ' installed package(s) on this host.'?>">
                                                    <span><?= $packagesInstalledTotal ?></span>
                                                </td>

                                                <td class="packagesCount-td" title="<?=$packagesAvailableTotal . ' available update(s) on this host.'?>">
                                                    <?php
                                                    if ($packagesAvailableTotal >= $pkgs_count_considered_critical) {
                                                        echo '<span class="bkg-red">' . $packagesAvailableTotal . '</span>';
                                                    } elseif ($packagesAvailableTotal >= $pkgs_count_considered_outdated) {
                                                        echo '<span class="bkg-yellow">' . $packagesAvailableTotal . '</span>';
                                                    } else {
                                                        echo '<span>' . $packagesAvailableTotal . '</span>';
                                                    } ?>
                                                </td>

                                                <td title="See the details for this host.">
                                                    <span class="printHostDetails pointer" host_id="<?=$id?>">Details</span><a href="host.php?id=<?=$id?>" target="_blank" rel="noopener noreferrer"><img src="resources/icons/external-link.png" class="icon-lowopacity" /></a>
                                                </td>

                                                <?php
                                                if (\Controllers\Common::isadmin()) : ?>
                                                    <td title="Select <?=$hostname?>">
                                                        <input type="checkbox" class="js-host-checkbox icon-verylowopacity" name="checkbox-host[]" group="<?=$groupName?>" value="<?=$id?>">
                                                    </td>
                                                    <?php
                                                endif ?>

                                                <td class="host-update-status">
                                                    <?php
                                                    /**
                                                     *  Status de la dernière demande
                                                     */
                                                    if (!empty($lastRequestedUpdate)) {
                                                        if ($lastRequestedUpdate['Type'] == 'packages-update') {
                                                            $updateType = 'Packages update';
                                                        }
                                                        if ($lastRequestedUpdate['Type'] == 'general-status-update') {
                                                            $updateType = 'Sending general info.';
                                                        }
                                                        if ($lastRequestedUpdate['Type'] == 'packages-status-update') {
                                                            $updateType = 'Sending packages state';
                                                        }
                                                        if ($lastRequestedUpdate['Status'] == 'requested') {
                                                            $updateStatus = 'requested';
                                                        }
                                                        if ($lastRequestedUpdate['Status'] == 'running') {
                                                            $updateStatus = 'running<img src="resources/images/loading.gif" class="icon" />';
                                                        }
                                                        if ($lastRequestedUpdate['Status'] == 'done') {
                                                            $updateStatus = 'done';
                                                        }
                                                        if ($lastRequestedUpdate['Status'] == 'error') {
                                                            $updateStatus = 'has failed';
                                                        }
                                                        /**
                                                         *  Si la demande de mise à jour a été faite il y a plusieurs jours ou a été faite il y a +10min alors on affiche le message en jaune, l'hôte distant n'a peut être pas reçu ou traité la demande
                                                         */
                                                        if ($lastRequestedUpdate['Status'] == 'requested' or $lastRequestedUpdate['Status'] == 'running') {
                                                            if ($lastRequestedUpdate['Date'] != DATE_YMD or $lastRequestedUpdate['Time'] <= date('H:i:s', strtotime(date('H:i:s') . ' - 10 minutes'))) {
                                                                echo '<span class="yellowtext" title="The request does not seem to have been taken into account by the host (requested on ' . DateTime::createFromFormat('Y-m-d', $lastRequestedUpdate['Date'])->format('d-m-Y') . ' at ' . $lastRequestedUpdate['Time'] . ')">' . $updateType . ' ' . $updateStatus . '</span>';
                                                            } else {
                                                                echo '<span title="On ' . DateTime::createFromFormat('Y-m-d', $lastRequestedUpdate['Date'])->format('d-m-Y') . ' at ' . $lastRequestedUpdate['Time'] . '">' . $updateType . ' ' . $updateStatus . '</span>';
                                                            }
                                                        }
                                                        if ($lastRequestedUpdate['Status'] == 'error') {
                                                            echo '<span class="redtext" title="On ' . DateTime::createFromFormat('Y-m-d', $lastRequestedUpdate['Date'])->format('d-m-Y') . ' at ' . $lastRequestedUpdate['Time'] . '">' . $updateType . ' ' . $updateStatus . '</span>';
                                                        }
                                                    } ?>
                                                </td>
                                                <td class="host-additionnal-info">
                                                </td>
                                            </tr>
                                            <?php
                                        } ?>
                                    </tbody>
                                </table>
                                <?php
                            } else {
                                echo '<table class="hosts-table-empty"><tr><td class="lowopacity">(empty)</td></tr></table>';
                            } ?>
                        </div>
                        <?php
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
 *  Graph des hôtes
 */
$labels = "'Up to date', 'Need update'";
$datas = "'$totalUptodate', '$totalNotUptodate'";
$backgrounds = "'rgb(75, 192, 192)','rgb(255, 99, 132)'";
$title = '';
$chartId = 'hosts-count-chart';

include('../includes/hosts-pie-chart.inc.php');

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
            $profileNameList .= "'Unknow',";
        } else {
            $profileNameList .= "'" . $profile['Profile'] . "',";
        }
        $profileCountList .= "'" . $profile['Profile_count'] . "',";

        /**
         *  On sélectionne une couleur au hasard dans l'array
         */
        $profileBackgroundColor .= "'" . $validHexColors[$randomHexColor] . "',";
    }
    $labels = rtrim($profileNameList, ',');
    $datas = rtrim($profileCountList, ',');
    $backgrounds = rtrim($profileBackgroundColor, ',');
    $title = '';
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
            $osNameList .= "'Unknow',";
        } else {
            $osNameList .= "'" . ucfirst($os['Os']) . " " . $os['Os_version'] . "',";
        }
        $osCountList .= "'" . $os['Os_count'] . "',";

        /**
         *  On sélectionne une couleur au hasard dans l'array
         */
        $osBackgroundColor .= "'" . $validHexColors[$randomHexColor] . "',";
    }
    $labels = rtrim($osNameList, ',');
    $datas = rtrim($osCountList, ',');
    $backgrounds = rtrim($osBackgroundColor, ',');
    $title = '';
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
            $archNameList .= "'Unknow',";
        } else {
            $archNameList .= "'" . $arch['Arch'] . "',";
        }
        $archCountList .= "'" . $arch['Arch_count'] . "',";

        /**
         *  On sélectionne une couleur au hasard dans l'array
         */
        $archBackgroundColor .= "'" . $validHexColors[$randomHexColor] . "',";
    }
    $labels = rtrim($archNameList, ',');
    $datas = rtrim($archCountList, ',');
    $backgrounds = rtrim($archBackgroundColor, ',');
    $title = '';
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
            $envNameList .= "'Unknow',";
        } else {
            $envNameList .= "'" . $env['Env'] . "',";
        }
        $envCountList .= "'" . $env['Env_count'] . "',";

        /**
         *  Si l'environnement correspond au dernier env de la chaine alors celui-ci sera en rouge
         */
        if ($env['Env'] == LAST_ENV) {
            $envBackgroundColor .= "'rgb(255, 99, 132)',";
        } else {
            /**
             *  On sélectionne une couleur au hasard dans l'array
             */
            $envBackgroundColor .= "'" . $validHexColors[$randomHexColor] . "',";
        }
    }
    $labels = rtrim($envNameList, ',');
    $datas = rtrim($envCountList, ',');
    $backgrounds = rtrim($envBackgroundColor, ',');
    $title = '';
    $chartId = 'hosts-env-chart';

    include('../includes/hosts-pie-chart.inc.php');
} ?>
</html>