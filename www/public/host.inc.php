<?php

if (!defined('ROOT')) {
    define('ROOT', dirname(__FILE__, 2));
}
require_once(ROOT.'/models/Autoloader.php');
Autoloader::load();

$idError = 0;

if (!empty($_GET['id'])) {
    /**
     *  A partir de l'ID fourni, on instancie un Host
     */
    $myhost = new Host();
    $myhost->setId($_GET['id']);

    /**
     *  Récupération de toutes les informations de base concernant cet hôte
     */
    $hostProperties = $myhost->db_getAll();

    if (!empty($hostProperties)) {
        $id               = $hostProperties['Id'];
        $hostname         = $hostProperties['Hostname'];
        $ip               = $hostProperties['Ip'];
        $os               = $hostProperties['Os'];
        $os_version       = $hostProperties['Os_version'];
        $profile          = $hostProperties['Profile'];
        $env              = $hostProperties['Env'];
        $onlineStatus     = $hostProperties['Online_status'];
        $onlineStatusDate = $hostProperties['Online_status_date'];
        $onlineStatusTime = $hostProperties['Online_status_time'];
        $status           = $hostProperties['Status'];

        /**
         *  Si l'hôte est en status 'deleted' alors on ne l'affiche pas 
         */
        if ($status == 'deleted') {
            $idError++;

        } else {
            /**
             *  On ouvre la base de données de l'hôte
             */
            $myhost->openHostDb($id);
        }

    } else {
        $idError++;
    }

} else {
    $idError++;
}

if ($idError != 0) {
    echo '<span class="yellowtext">L\'Id d\'hôte renseigné est invalide</span>';
    die();
}

/**
 *  Récupération d'informations dans la BDD dédiée de l'hôte
 */
/**
 *  Récupération de la liste des paquets installés sur l'hôte et le total
 */
$packagesInventored = $myhost->getPackagesInventory();
$packagesInstalledCount = count($myhost->getPackagesInstalled());
/**
 *  Récupération de la liste des paquets disponibles pour installation sur l'hôte et le total
 */
$packagesAvailable = $myhost->getPackagesAvailable();
$packagesAvailableCount = count($packagesAvailable);
/**
 *  Récupération de la liste des mises à jour demandées par repomanager à l'hôte
 */
$updatesRequestsList = $myhost->getUpdatesRequests();
/**
 *  Récupération de la liste de toutes les opérations de mises à jour qui ont été exécutées sur l'hôte
 */
$eventsList = $myhost->getEventsHistory(); 
/**
 *  On merge les demandes de mises à jour et les évènement dans un même tableau et on les trie par date et heure
 */
$allEventsList = array_merge($eventsList, $updatesRequestsList);
array_multisort(array_column($allEventsList, 'Date'), SORT_DESC, array_column($allEventsList, 'Time'), SORT_DESC, $allEventsList); 

/**
 *  Génération des valeurs pour le Chart 'line'
 */

/**
 *  D'abord on crée une liste de dates sur une période de 15 jours
 */
$dates = array();
$dateStart = date_create(date('Y-m-d'))->modify("-15 days")->format('Y-m-d');
$dateEnd = date_create(date('Y-m-d'))->modify("+1 days")->format('Y-m-d');
$period = new DatePeriod(
    new DateTime($dateStart),
    new DateInterval('P1D'),
    new DateTime($dateEnd)
);
/**
 *  On peuple l'array à partir de la période de dates précédemment générée, on initialise chaque date à 0
 */
foreach ($period as $key => $value) {
    $dates[$value->format('Y-m-d')] = 0;
}

/**
 *  Récupération du nombre de paquet installés ces 15 derniers jours, triés par date
 */
$lastInstalledPackagesArray = $myhost->getLastPackagesStatusCount('installed', '15');
/**
 *  Récupération du nombre de paquets mis à jour ces 15 derniers jours, triés par date
 */
$lastUpgradedPackagesArray = $myhost->getLastPackagesStatusCount('upgraded', '15');
/**
 *  Récupération du nombre de paquets supprimés ces 15 derniers jours, triés par date
 */
$lastRemovedPackagesArray = $myhost->getLastPackagesStatusCount('removed', '15');

/**
 *  On se sert de l'array de dates initialisés à 0 avec l'array retourné par getLastInstalledPackagesCount();
 */
$lastInstalledPackagesArray = array_merge($dates, $lastInstalledPackagesArray);
$lastUpgradedPackagesArray  = array_merge($dates, $lastUpgradedPackagesArray);
$lastRemovedPackagesArray   = array_merge($dates, $lastRemovedPackagesArray);

/**
 *  Formattage des valeurs retournées au format ChartJS
 *  Formattage de l'array de dates au format ChartJS
 */
$lineChartInstalledPackagesCount = "'".implode("','",$lastInstalledPackagesArray)."'";
$lineChartUpgradedPackagesCount  = "'".implode("','",$lastUpgradedPackagesArray)."'";
$lineChartRemovedPackagesCount   = "'".implode("','",$lastRemovedPackagesArray)."'";
$lineChartDates = "'".implode("','",array_keys($dates))."'";

echo '<h3>'.strtoupper($hostname).'</h3>';

if (Common::isadmin()) { ?>
<div class="hostActionBtn-container">
    <span class="btn-large-blue"><img src="../ressources/icons/rocket.png" class="icon-lowopacity" />Actions</span>
    <span class="hostActionBtn btn-large-blue" hostid="<?php echo $id?>" action="general-status-update" title="Rafraichir les informations générales">Rafraichir les informations générales</span>
    <span class="hostActionBtn btn-large-blue" hostid="<?php echo $id?>" action="available-packages-status-update" title="Rafraichir les paquets disponibles">Rafraichir les paquets disponibles</span>
    <span class="hostActionBtn btn-large-blue" hostid="<?php echo $id?>" action="installed-packages-status-update" title="Rafraichir les paquets installés">Rafraichir les paquets installés</span>
    <span class="hostActionBtn btn-large-blue" hostid="<?php echo $id?>" action="full-history-update" title="Rafraichir l'historique des évènements">Rafraichir l'historique des évènements</span>
    <span class="hostActionBtn btn-large-red"  hostid="<?php echo $id?>" action="update" title="Mettre à jour tous les paquets de l'hôte">Mettre à jour les paquets</span>
    <span class="hostActionBtn btn-large-red"  hostid="<?php echo $id?>" action="reset" title="Réinitialiser cet hôte">Réinitialiser cet hôte</span>
    <span class="hostActionBtn btn-large-red"  hostid="<?php echo $id?>" action="delete" title="Supprimer cet hôte">Supprimer cet hôte</span>
</div>
<?php } ?>

            <div class="div-flex">
                <div class="flex-div-100 div-generic-gray">

                    <table class="table-generic table-small host-table opacity-80">
                        <tr>
                            <td>IP</td>
                            <td><?php echo $ip; ?></td>
                        </tr>
                        <tr>
                            <td>STATUS</td>
                            <td>
                                <?php
                                if ($onlineStatus == "online")
                                    echo '<span><img src="ressources/icons/greencircle.png" class="icon-small" title="En ligne (ping)" />Online</span>';
                                if ($onlineStatus == "unknown")
                                    echo '<span><img src="ressources/icons/redcircle.png" class="icon-small" title="Inconnu" />Inconnu</span>';
                                if ($onlineStatus == "unreachable")
                                    echo '<span><img src="ressources/icons/redcircle.png" class="icon-small" title="Injoignable (ping)" />Injoignable</span>';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td>OS</td>
                            <?php
                            if (!empty($os) AND !empty($os_version)) {
                                echo '<td>';
                                if ($os == "Centos" OR $os == "centos" OR $os == "CentOS") {
                                    echo '<img src="ressources/icons/centos.png" class="icon" />';
                                } elseif ($os == "Debian" OR $os == "debian") {
                                    echo '<img src="ressources/icons/debian.png" class="icon" />';
                                } elseif ($os == "Ubuntu" OR $os == "ubuntu" OR $os == "linuxmint") {
                                    echo '<img src="ressources/icons/ubuntu.png" class="icon" />';
                                } else {
                                    echo '<img src="ressources/icons/tux.png" class="icon" />';
                                }
                                echo ucfirst($os).' '.$os_version;
                                echo '</td>';
                            } else {
                                echo '<td>Inconnu</td>';
                            } ?>
                        </tr>
                        <tr>
                            <td>PROFIL</td>
                            <?php
                            if (!empty($profile)) {
                                echo "<td>$profile</td>";
                            } else {
                                echo '<td>Inconnu</td>';
                            } ?>
                        </tr>
                        <tr>
                            <td>ENVIRONNEMENT</td>
                            <?php
                            if (!empty($env)) {
                                echo "<td>".Common::envtag($env)."</td>";
                            } else {
                                echo '<td>Inconnu</td>';
                            } ?>
                        </tr>
                    </table>

                    <div class="host-line-chart-container">
                        <canvas id="packages-status-chart"></canvas>
                    </div>
                </div>
            </div>
            <div class="div-flex">
                <div class="flex-div-50 div-generic-gray">
                                   
                    <h4>ETATS DES PAQUETS</h4>

                    <table class="hosts-table">
                        <thead>
                            <tr>
                                <td></td>
                                <th>À mettre à jour</th>
                                <th>Total installés</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td></td>
                                <td>
                                <?php 
                                if ($packagesAvailableCount < "10") {
                                    echo '<span>'.$packagesAvailableCount.'</span>';
                                } elseif ($packagesAvailableCount >= "10" AND $packagesAvailableCount < "20") {
                                    echo '<span class="yellowtext">'.$packagesAvailableCount.'</span>';
                                } elseif ($packagesAvailableCount > "20") {
                                    echo '<span class="redtext">'.$packagesAvailableCount.'</span>';
                                } 
                                /**
                                 *  Affichage d'un bouton 'Détails' si il y a au moins 1 paquet disponible
                                 */
                                if ($packagesAvailableCount > 0) {
                                    echo ' <img src="ressources/icons/search.png" id="packagesAvailableButton" class="icon-lowopacity" />';
                                }
                                ?>
                                </td>
                                <td>
                                    <?php 
                                    echo '<span>'.$packagesInstalledCount.'</span>';
                                    /**
                                     *  Affichage d'un bouton 'Détails' si il y a au moins 1 paquet installé
                                     */
                                    if ($packagesInstalledCount > 0) {
                                        echo ' <img src="ressources/icons/search.png" id="packagesInstalledButton" class="icon-lowopacity" />';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div id="packagesContainer">

                        <span id="packagesContainerLoader">Chargement <img src="../ressources/images/loading.gif" class="icon" /></span>

                        <div id="packagesAvailableDiv" class="hide">
                            <table class="packages-table">
                                <thead>
                                    <tr>
                                        <td>Nom</td>
                                        <td>Version</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!empty($packagesAvailable)) {
                                        foreach ($packagesAvailable as $package) {
                                            echo '<tr>';
                                                echo '<td>';
                                                if (preg_match('/python/i', $package['Name'])) {
                                                    echo '<img src="../ressources/icons/products/python.png" class="icon" />';
                                                } elseif (preg_match('/^code$/i', $package['Name'])) { 
                                                    echo '<img src="../ressources/icons/products/vscode.png" class="icon" />';
                                                } elseif (preg_match('/^firefox/i', $package['Name'])) { 
                                                    echo '<img src="../ressources/icons/products/firefox.png" class="icon" />';
                                                } elseif (preg_match('/^chrome-$/i', $package['Name'])) { 
                                                    echo '<img src="../ressources/icons/products/chrome.png" class="icon" />';
                                                } elseif (preg_match('/^chromium-$/i', $package['Name'])) { 
                                                    echo '<img src="../ressources/icons/products/chromium.png" class="icon" />';
                                                } elseif (preg_match('/^brave-browser$/i', $package['Name'])) {
                                                    echo '<img src="../ressources/icons/products/brave.png" class="icon" />';
                                                } elseif (preg_match('/^filezilla/i', $package['Name'])) { 
                                                    echo '<img src="../ressources/icons/products/filezilla.png" class="icon" />';
                                                } elseif (preg_match('/^java/i', $package['Name'])) { 
                                                    echo '<img src="../ressources/icons/products/java.png" class="icon" />';
                                                } elseif (preg_match('/^fonts-/i', $package['Name'])) { 
                                                    echo '<img src="../ressources/icons/products/fonts.png" class="icon" />';
                                                } elseif (preg_match('/^teams$/i', $package['Name'])) { 
                                                    echo '<img src="../ressources/icons/products/teams.png" class="icon" />';
                                                } elseif (preg_match('/^teamviewer$/i', $package['Name'])) { 
                                                    echo '<img src="../ressources/icons/products/teamviewer.png" class="icon" />';
                                                } elseif (preg_match('/^thunderbird/i', $package['Name'])) { 
                                                    echo '<img src="../ressources/icons/products/thunderbird.png" class="icon" />';
                                                } elseif (preg_match('/^vlc/i', $package['Name'])) { 
                                                    echo '<img src="../ressources/icons/products/vlc.png" class="icon" />';
                                                } else {
                                                    echo '<img src="../ressources/icons/products/package.png" class="icon" />';
                                                }
                                                echo $package['Name'];
                                                echo '</td>';
                                                echo '<td>'.$package['Version'].'</td>';
                                            echo '</tr>';
                                        }
                                    } ?>
                                </tbody>
                            </table>
                        </div>

                        <div id="packagesInstalledDiv" class="hide">
                            <h4>Inventaire des paquets de l'hôte</h4>

                            <input type="text" id="packagesIntalledSearchInput" onkeyup="filterPackage()" autocomplete="off" placeholder="Rechercher...">
                            <table id="packagesIntalledTable" class="packages-table">
                                <thead>
                                    <tr>
                                        <td>Nom</td>
                                        <td>Version</td>
                                        <td></td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!empty($packagesInventored)) {
                                        foreach ($packagesInventored as $package) {
                                            echo '<tr class="pkg-row">';
                                            echo '<td>';
                                            if (preg_match('/python/i', $package['Name'])) {
                                                echo '<img src="../ressources/icons/products/python.png" class="icon" />';
                                            } elseif (preg_match('/^code$/i', $package['Name'])) { 
                                                echo '<img src="../ressources/icons/products/vscode.png" class="icon" />';
                                            } elseif (preg_match('/^firefox/i', $package['Name'])) { 
                                                echo '<img src="../ressources/icons/products/firefox.png" class="icon" />';
                                            } elseif (preg_match('/^chrome-/i', $package['Name'])) { 
                                                echo '<img src="../ressources/icons/products/chrome.png" class="icon" />';
                                            } elseif (preg_match('/^chromium-/i', $package['Name'])) { 
                                                echo '<img src="../ressources/icons/products/chromium.png" class="icon" />';
                                            } elseif (preg_match('/^brave-/i', $package['Name'])) {
                                                echo '<img src="../ressources/icons/products/brave.png" class="icon" />';
                                            } elseif (preg_match('/^filezilla/i', $package['Name'])) { 
                                                echo '<img src="../ressources/icons/products/filezilla.png" class="icon" />';
                                            } elseif (preg_match('/^java/i', $package['Name'])) { 
                                                echo '<img src="../ressources/icons/products/java.png" class="icon" />';
                                            } elseif (preg_match('/^teams$/i', $package['Name'])) { 
                                                echo '<img src="../ressources/icons/products/teams.png" class="icon" />';
                                            } elseif (preg_match('/^teamviewer$/i', $package['Name'])) { 
                                                echo '<img src="../ressources/icons/products/teamviewer.png" class="icon" />';
                                            } elseif (preg_match('/^thunderbird/i', $package['Name'])) { 
                                                echo '<img src="../ressources/icons/products/thunderbird.png" class="icon" />';
                                            } elseif (preg_match('/^vlc/i', $package['Name'])) { 
                                                echo '<img src="../ressources/icons/products/vlc.png" class="icon" />';
                                            } else {
                                                echo '<img src="../ressources/icons/products/package.png" class="icon" />';
                                            }
                                            if ($package['State'] == "inventored" OR $package['State'] == "installed" OR $package['State'] == "reinstalled" OR $package['State'] == "upgraded") {
                                                echo $package['Name'];
                                            }
                                            if ($package['State'] == "removed" OR $package['State'] == "purged") {
                                                echo '<span class="redtext">'.$package['Name'].' (désinstallé)</span>';
                                            }
                                            echo '</td>';
                                            echo '<td>'.$package['Version'].'</td>';
                                            echo '<td><span class="getPackageTimeline pointer" hostid="'.$id.'" packagename="'.$package['Name'].'">Historique</span></td>';
                                            echo '</tr>';
                                        }
                                    } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>               

                <div class="flex-div-50 div-generic-gray">
                    <h4>HISTORIQUE</h4>

                    <div id="eventsContainer">
                        
                        <div id="eventsDiv">
                            <?php
                            if (empty($allEventsList)) {
                                echo '<p>Aucun historique</p>';
                
                            } else { ?>
                                <span>Afficher les demandes de transfert</span>
                                <label class="onoff-switch-label">
                                    <input id="showUpdateRequests" type="checkbox" name="" class="onoff-switch-input" <?php if (!empty($_COOKIE['showUpdateRequests']) AND $_COOKIE['showUpdateRequests'] == "yes") echo 'checked';?> />
                                    <span class="onoff-switch-slider"></span>
                                </label>

                                <?php
                                /**
                                 * 	Nombre maximal d'évènements qu'on souhaite afficher par défaut, le reste est masqué et affichable par un bouton "Afficher tout"
                                 * 	Lorsque $i a atteint le nombre maximal $printMaxItems, on commence à masquer les opérations
                                 */
                                $i = 0;
                                $printMaxItems = 10;

                                foreach ($allEventsList as $event) {
                                    /**
                                     *  Si le nombre maximal d'évènement à afficher n'est pas encore atteint alors on affiche l'évènement
                                     *  Sinon le masque
                                     */

                                    /**
                                     *  Cas où on masque l'évènement
                                     */
                                    if ($i > $printMaxItems) {
                                        /**
                                         *  Si l'évènement est une demande de mise à jour
                                         */
                                        if ($event['Event_type'] == "update_request") {
                                            /**
                                             *  Si le cookie showUpdateRequest n'est pas défini ou est égal à 'no' alors n'affiche pas les évènement de type 'update_request'
                                             */
                                            if (empty($_COOKIE['showUpdateRequests']) OR $_COOKIE['showUpdateRequests'] == "no") {
                                                continue;
                                            }

                                            echo '<div class="header-container update-request hide">';
                                        }                                 
                                        /**
                                         *  Si l'évènement est un 'event'
                                         */
                                        if ($event['Event_type'] == "event") {
                                            echo '<div class="header-container event hide">';
                                        }
                                    /**
                                     *  Cas où on affiche l'évènement
                                     */
                                    } else {
                                        /**
                                         *  Si l'évènement est une demande de mise à jour
                                         */
                                        if ($event['Event_type'] == "update_request") {
                                            /**
                                             *  Si le cookie showUpdateRequest n'est pas défini ou est égal à 'no' alors n'affiche pas les évènement de type 'update_request'
                                             */
                                            if (empty($_COOKIE['showUpdateRequests']) OR $_COOKIE['showUpdateRequests'] == "no") {
                                                continue;
                                            }

                                            echo '<div class="header-container update-request">';
                                        }                                 
                                        /**
                                         *  Si l'évènement est un 'event'
                                         */
                                        if ($event['Event_type'] == "event") {
                                            echo '<div class="header-container event">';
                                        }
                                    } ?>
	                                    <div class="header-blue">
                                            <table>
                                                <tr>
                                                    <td>
                                                        <span><?php echo 'Le <b>'.DateTime::createFromFormat('Y-m-d', $event['Date'])->format('d-m-Y').'</b> à <b>'.$event['Time']; ?></b></span>
                                                    </td>
                                                    <?php
                                                    if ($event['Event_type'] == "update_request") {
                                                        echo '<td colspan="5">';
                                                            echo '<span>';
                                                                /**
                                                                 *  Affichage d'une icone en fonction du status
                                                                 */
                                                                if ($event['Status'] == 'done') {
                                                                    echo '<img src="ressources/icons/greencircle.png" class="icon-small" />';
                                                                }
                                                                if ($event['Status'] == 'error') {
                                                                    echo '<img src="ressources/icons/redcircle.png" class="icon-small" />';
                                                                }
                                                                if ($event['Status'] == 'running') {
                                                                    echo '<img src="ressources/images/loading.gif" class="icon" />';
                                                                }
                                                                /**
                                                                 *  Affichage du type de demande
                                                                 */
                                                                if ($event['Type'] == 'general-status-update') {
                                                                    echo 'Transfert des informations générales';
                                                                }
                                                                if ($event['Type'] == 'available-packages-status-update') {
                                                                    echo 'Transfert de la liste des paquets disponibles';
                                                                }
                                                                if ($event['Type'] == 'installed-packages-status-update') {
                                                                    echo 'Transfert de la liste des paquets installés';
                                                                }
                                                                if ($event['Type'] == 'full-history-update') {
                                                                    echo 'Transfert de l\'historique des évènements';
                                                                }
                                                                if ($event['Type'] == 'packages-update') {
                                                                    echo 'Mise à jour des paquets';
                                                                }
                                                                /**
                                                                 *  Affichage du status
                                                                 */
                                                                if ($event['Status'] == 'done') {
                                                                    echo ' terminé';
                                                                }
                                                                if ($event['Status'] == 'error') {
                                                                    echo ' en erreur';
                                                                }
                                                                if ($event['Status'] == 'running') {
                                                                    echo ' en cours';
                                                                }
                                                                if ($event['Status'] == 'requested') {
                                                                    echo ' demandé';
                                                                }
                                                            echo '</span>';
                                                        echo '</td>';
                                                    }

                                                    if ($event['Event_type'] == "event") {
                                                        /**
                                                         *  Récupération des paquets installés par cet évènement
                                                         */
                                                        $packagesInstalled = $myhost->getEventPackagesList($event['Id'], 'installed');
                                                        $packagesInstalled_count = count($packagesInstalled);
                                                        /**
                                                         *  Récupération des paquets mis à jour par cet évènement
                                                         */
                                                        $packagesUpdated = $myhost->getEventPackagesList($event['Id'], 'upgraded');
                                                        $packagesUpdated_count = count($packagesUpdated);
                                                        /**
                                                         *  Récupération des paquets rétrogradés (downgrade) par cet évènement
                                                         */
                                                        $packagesDowngraded = $myhost->getEventPackagesList($event['Id'], 'downgraded');
                                                        $packagesDowngraded_count = count($packagesDowngraded);
                                                        /**
                                                         *  Récupération des paquets supprimés par cet évènement
                                                         */
                                                        $packagesRemoved = $myhost->getEventPackagesList($event['Id'], 'removed');
                                                        $packagesRemoved_count = count($packagesRemoved);
                                                        /**
                                                         *  Récupération des dépendances installées par cet évènement
                                                         */
                                                        /*$dependenciesInstalled = $myhost->getEventPackagesList($event['Id'], '');
                                                        $dependenciesInstalled_count = count($dependenciesInstalled);*/
                                                        $dependenciesInstalled_count = 0;

                                                        echo '<td>';
                                                            if ($packagesInstalled_count == 0) {
                                                                echo '<img src="../ressources/icons/products/package.png" class="icon" /><i title="Paquets installés">'.$packagesInstalled_count.'</i>';
                                                            } else {
                                                                echo '<img src="../ressources/icons/products/package.png" class="icon yellowimg" /><i class="yellowtext pointer showEventDetailsBtn" host-id="'.$id.'" event-id="'.$event['Id'].'" package-state="installed">'.$packagesInstalled_count.'</i>';
                                                            }
                                                        echo '</td>';
                                                        echo '<td>';
                                                            if ($packagesUpdated_count == 0) {
                                                                echo '<img src="../ressources/icons/update.png" class="icon" /><i title="Paquets mis à jour">'.$packagesUpdated_count.'</i>';
                                                            } else {
                                                                echo '<img src="../ressources/icons/update.png" class="icon yellowimg" /><i class="yellowtext pointer showEventDetailsBtn" host-id="'.$id.'" event-id="'.$event['Id'].'" package-state="upgraded">'.$packagesUpdated_count.'</i>';
                                                            }
                                                        echo '</td>';
                                                        echo '<td>';
                                                            if ($packagesDowngraded_count == 0) {
                                                                echo '<img src="../ressources/icons/products/package.png" class="icon" /><i title="Paquets rétrogradés">'.$packagesDowngraded_count.'</i>';
                                                            } else {
                                                                echo '<img src="../ressources/icons/products/package.png" class="icon yellowimg" /><i class="yellowtext pointer showEventDetailsBtn" host-id="'.$id.'" event-id="'.$event['Id'].'" package-state="downgraded">'.$packagesDowngraded_count.'</i>';
                                                            }
                                                        echo '</td>';
                                                        echo '<td>';
                                                            if ($dependenciesInstalled_count == 0) {
                                                                echo '<img src="../ressources/icons/products/package.png" class="icon" /><i title="Dépendances installées">'.$dependenciesInstalled_count.'</i>';
                                                            } else {
                                                                echo '<img src="../ressources/icons/products/package.png" class="icon yellowimg" /><i class="yellowtext pointer">'.$dependenciesInstalled_count.'</i>';
                                                            }
                                                        echo '</td>';
                                                        echo '<td>';
                                                            if ($packagesRemoved_count == 0) {
                                                                echo '<img src="../ressources/icons/bin.png" class="icon" /><i title="Paquets supprimés">'.$packagesRemoved_count.'</i>';
                                                            } else {
                                                                echo '<img src="../ressources/icons/bin.png" class="icon yellowimg" /><i class="yellowtext pointer showEventDetailsBtn" host-id="'.$id.'" event-id="'.$event['Id'].'" package-state="removed">'.$packagesRemoved_count.'</i>';
                                                            }
                                                        echo '</td>';
                                                    } ?>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                <?php
                                    ++$i;
                                }

                                if ($i > $printMaxItems) {
                                    /**
                                     * 	Affichage du bouton Afficher tout
                                     */
                                    echo '<p id="print-all-events-btn" class="pointer center"><b>Afficher tout</b> <img src="ressources/icons/chevron-circle-down.png" class="icon" /></p>';
                                }
                            } ?>
                    </div>
                </div>
            </div>
<?php 
/**
 *  On ferme la connexion à la BDD dédiée de l'hôte
 */
$myhost->closeHostDb(); ?>

<script>
$(document).ready(function(){
    /**
     *  Graphique chartjs type line
     */
    // Données
    var lineChartData = {
        labels: [<?=$lineChartDates?>],
        datasets: [
            {
                label: 'Paquets installés',
                data: [<?=$lineChartInstalledPackagesCount?>],
                borderColor: '#489f4d',
                fill: false
            },
            {
                label: 'Paquets mis à jour',
                data: [<?=$lineChartUpgradedPackagesCount?>],
                borderColor: '#3e95cd',
                fill: false
            },
            {
                label: 'Paquets désinstallés',
                data: [<?=$lineChartRemovedPackagesCount?>],
                borderColor: '#d9534f',
                fill: false
            }
        ],
    };
    // Options
    var lineChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        borderWidth: 1.5,
        scales: {
            x: {
                display: false // ne pas afficher les dates sur l'axe x
            },
            y: {
                beginAtZero: true
            }      
        },
        plugins: {
            legend: {
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Evolution des paquets (7 jours)',
                }
            },
        },
    }
    // Affichage du chart
    var ctx = document.getElementById('packages-status-chart').getContext("2d");
    window.myLine = new Chart(ctx, {
        type: "line",
        data: lineChartData,
        options: lineChartOptions
    });
});
</script>