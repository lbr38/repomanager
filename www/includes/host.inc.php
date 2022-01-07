<?php

if (!defined('ROOT')) {
    define('ROOT', dirname(__FILE__, 2));
}
require_once(ROOT.'/models/Autoloader.php');
Autoloader::loadAll();
require_once(ROOT."/functions/common-functions.php");

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
        $id           = $hostProperties['Id'];
        $hostname     = $hostProperties['Hostname'];
        $ip           = $hostProperties['Ip'];
        $os           = $hostProperties['Os'];
        $os_version   = $hostProperties['Os_version'];
        $profile      = $hostProperties['Profile'];
        $env          = $hostProperties['Env'];
        $onlineStatus = $hostProperties['Online_status'];

        /**
         *  Si on a pu récupérer les informations de l'hôte alors on ouvre sa base de données (lecture seule)
         */
        $myhost->openHostDb($id, 'rw');

    } else {
        $idError++;
    }

} else {
    $idError++;
}

if ($idError != 0) {
    echo '<span class="yellowtext">Erreur : l\'id renseigné est invalide</span>';
    die();
}

/**
 *  Récupération d'informations dans la BDD dédiée de l'hôte
 */
/**
 *  Récupération de la liste des paquets installés sur l'hôte et le total
 */
$packagesInventored = $myhost->getPackagesInventory();
$packagesInstalledCount = $myhost->getPackagesInstalledCount();
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
array_multisort(array_column($allEventsList, 'Date'), SORT_DESC, array_column($allEventsList, 'Time'), SORT_DESC, $allEventsList); ?>

<?php echo '<h3>'.strtoupper($hostname).'</h3>'; ?>

<article>
    <section class="main">
            <div class="div-flex">
                <div class="hosts-div-100">
                    
                    <!-- <span class="host-update-request-btn btn-medium-blue"><img src="../ressources/icons/update.png" class="icon" /><b>Mettre à jour</b></span> -->

                    <table class="host-properties-table">
                        <tr>
                            <th>IP</th>
                            <td><?php echo $ip; ?></td>
                        </tr>
                        <tr>
                            <th>STATUS</th>
                            <td>
                                <?php
                                if ($onlineStatus == "online")
                                    echo '<span><img src="ressources/icons/greencircle.png" class="icon-small" title="En ligne" />Online</span>';
                                if ($onlineStatus == "unknown")
                                    echo '<img src="ressources/icons/redcircle.png" class="icon-small" title="Inconnu" />';
                                if ($onlineStatus == "unreachable")
                                    echo '<img src="ressources/icons/redcircle.png" class="icon-small" title="Injoignable" />';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>OS</th>
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
                            <th>PROFIL</th>
                            <?php
                            if (!empty($profile)) {
                                echo "<td>$profile</td>";
                            } else {
                                echo '<td>Inconnu</td>';
                            } ?>
                        </tr>
                        <tr>
                            <th>ENVIRONNEMENT</th>
                            <?php
                            if (!empty($env)) {
                                echo "<td>".envtag($env)."</td>";
                            } else {
                                echo '<td>Inconnu</td>';
                            } ?>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="div-flex">
                <div class="hosts-div-50">
                    <h4>ETATS DES PAQUETS</h4>

                    <!-- <span class="host-update-request-btn btn-medium-blue"><img src="../ressources/icons/update.png" class="icon" /><b>Mettre à jour</b></span> -->

                    <table class="hosts-table">
                        <thead>
                            <tr>
                                <td></td>
                                <td>À mettre à jour</td>
                                <td>Total installés</td>
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

                            <input type="text" id="packagesIntalledSearchInput" onkeyup="searchPackage()" autocomplete="off" placeholder="Rechercher...">
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
                                            if ($package['State'] == "inventored" OR $package['State'] == "installed" OR $package['State'] == "upgraded") {
                                                echo $package['Name'];
                                            }
                                            if ($package['State'] == "removed") {
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

                <div class="hosts-div-50">
                    <h4>HISTORIQUE</h4>
                    
                    <!--<span class="host-update-request-btn btn-medium-blue"><img src="../ressources/icons/update.png" class="icon" /><b>Mettre à jour</b></span>-->

                    <div id="eventsContainer">
                        
                        <div id="eventsDiv">
                            <?php
                            if (empty($allEventsList)) {
                                echo '<p>Aucun historique</p>';
                
                            } else {
                                /**
                                 * 	Nombre maximal d'évènements qu'on souhaite afficher par défaut, le reste est masqué et affichable par un bouton "Afficher tout"
                                 * 	Lorsque $i a atteint le nombre maximal $printMaxItems, on commence à masquer les opérations
                                 */
                                $i = 0;
                                $printMaxItems = 5;
                                
                                foreach ($allEventsList as $event) { 
                                    if ($i > $printMaxItems) {
                                        echo '<div class="header-container hidden-event hide">';
                                    } else {
                                        echo '<div class="header-container">';
                                    } ?>
	                                    <div class="header-blue">
                                            <span><?php echo 'Le <b>'.DateTime::createFromFormat('Y-m-d', $event['Date'])->format('d-m-Y').'</b> à <b>'.$event['Time']; ?></b></span>
                                            <?php 
                                            if ($event['Event_type'] == "update_request") {
                                                echo '<span>Demande de mise à jour</span>';
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

                                                if ($packagesInstalled_count == 0) {
                                                    echo '<img src="../ressources/icons/products/package.png" class="icon" /><i title="Paquets installés">'.$packagesInstalled_count.'</i>';
                                                } else {
                                                    echo '<img src="../ressources/icons/products/package.png" class="icon yellowimg" /><i class="yellowtext pointer showEventDetailsBtn" host-id="'.$id.'" event-id="'.$event['Id'].'" package-state="installed">'.$packagesInstalled_count.'</i>';
                                                }
                                                if ($packagesUpdated_count == 0) {
                                                    echo '<img src="../ressources/icons/update.png" class="icon" /><i title="Paquets mis à jour">'.$packagesUpdated_count.'</i>';
                                                } else {
                                                    echo '<img src="../ressources/icons/update.png" class="icon yellowimg" /><i class="yellowtext pointer showEventDetailsBtn" host-id="'.$id.'" event-id="'.$event['Id'].'" package-state="upgraded">'.$packagesUpdated_count.'</i>';
                                                }
                                                if ($packagesDowngraded_count == 0) {
                                                    echo '<img src="../ressources/icons/products/package.png" class="icon" /><i title="Paquets rétrogradés">'.$packagesDowngraded_count.'</i>';
                                                } else {
                                                    echo '<img src="../ressources/icons/products/package.png" class="icon yellowimg" /><i class="yellowtext pointer showEventDetailsBtn" host-id="'.$id.'" event-id="'.$event['Id'].'" package-state="downgraded">'.$packagesDowngraded_count.'</i>';
                                                }
                                                if ($dependenciesInstalled_count == 0) {
                                                    echo '<img src="../ressources/icons/products/package.png" class="icon" /><i title="Dépendances installées">'.$dependenciesInstalled_count.'</i>';
                                                } else {
                                                    echo '<img src="../ressources/icons/products/package.png" class="icon yellowimg" /><i class="yellowtext pointer">'.$dependenciesInstalled_count.'</i>';
                                                }
                                                if ($packagesRemoved_count == 0) {
                                                    echo '<img src="../ressources/icons/bin.png" class="icon" /><i title="Paquets supprimés">'.$packagesRemoved_count.'</i>';
                                                } else {
                                                    echo '<img src="../ressources/icons/bin.png" class="icon yellowimg" /><i class="yellowtext pointer showEventDetailsBtn" host-id="'.$id.'" event-id="'.$event['Id'].'" package-state="removed">'.$packagesRemoved_count.'</i>';
                                                }
                                            }
                                            ?>
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
    </section>
</article>

<?php 
/**
 *  On ferme la connexion à la BDD dédiée de l'hôte
 */
$myhost->closeHostDb(); ?>