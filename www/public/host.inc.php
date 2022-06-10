<?php

if (!defined('ROOT')) {
    define('ROOT', dirname(__FILE__, 2));
}
require_once(ROOT . '/controllers/Autoloader.php');
\Controllers\Autoloader::load();

$idError = 0;

if (!empty($_GET['id'])) {
    $myhost = new \Controllers\Host();

    /**
     *  Récupération de toutes les informations de base concernant cet hôte
     */
    $hostProperties = $myhost->getAll($_GET['id']);

    if (!empty($hostProperties)) {
        $id               = $hostProperties['Id'];
        $hostname         = $hostProperties['Hostname'];
        $ip               = $hostProperties['Ip'];
        $os               = $hostProperties['Os'];
        $os_version       = $hostProperties['Os_version'];
        $profile          = $hostProperties['Profile'];
        $env              = $hostProperties['Env'];
        $status           = $hostProperties['Status'];
        $agentStatus      = $hostProperties['Online_status'];

        /**
         *  On vérifie que la dernière fois que l'agent a remonté son status est inférieur à 1h (et 10min de "marge")
         */
        if ($hostProperties['Online_status_date'] != DATE_YMD or $hostProperties['Online_status_time'] <= date('H:i:s', strtotime(date('H:i:s') . ' - 70 minutes'))) {
            $agentStatus = 'seems-stopped';
        }
        /**
         *  Message du dernier état connu
         */
        $agentLastSendStatusMsg = 'état au ' . DateTime::createFromFormat('Y-m-d', $hostProperties['Online_status_date'])->format('d-m-Y') . ' à ' . $hostProperties['Online_status_time'];

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
 *  Récupération des seuils généraux des hôtes
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
$packagesAvailableTotal = count($packagesAvailable);
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
 *  Récupération du nombre de paquets installés ces 15 derniers jours, triés par date
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
$lineChartInstalledPackagesCount = "'" . implode("','", $lastInstalledPackagesArray) . "'";
$lineChartUpgradedPackagesCount  = "'" . implode("','", $lastUpgradedPackagesArray) . "'";
$lineChartRemovedPackagesCount   = "'" . implode("','", $lastRemovedPackagesArray) . "'";
$lineChartDates = "'" . implode("','", array_keys($dates)) . "'";

echo '<h3>' . strtoupper($hostname) . '</h3>';

if (Models\Common::isadmin()) { ?>
    <div class="hostActionBtn-container">
        <span class="btn-large-blue"><img src="../ressources/icons/rocket.png" class="icon-lowopacity" />Actions</span>
        <span class="hostActionBtn btn-large-blue" hostid="<?= $id ?>" action="general-status-update" title="Rafraichir les informations générales">Demander l'envoi des informations générales</span>
        <span class="hostActionBtn btn-large-blue" hostid="<?= $id ?>" action="packages-status-update" title="Rafraichir les paquets disponibles">Demander l'envoi des informations concernant les paquets</span>
        <span class="hostActionBtn btn-large-red"  hostid="<?= $id ?>" action="update" title="Mettre à jour tous les paquets de l'hôte">Demander la mise à jour des paquets</span>
        <span class="hostActionBtn btn-large-red"  hostid="<?= $id ?>" action="reset" title="Réinitialiser cet hôte">Réinitialiser cet hôte</span>
        <span class="hostActionBtn btn-large-red"  hostid="<?= $id ?>" action="delete" title="Supprimer cet hôte">Supprimer cet hôte</span>
    </div>
<?php } ?>
            <div class="div-flex">
                <div class="flex-div-100 div-generic-gray">
                    <table class="table-generic table-small host-table opacity-80">
                        <tr>
                            <td>IP</td>
                            <td><?= $ip ?></td>
                        </tr>
                        <tr>
                            <td>OS</td>
                            <?php
                            if (!empty($os) and !empty($os_version)) {
                                echo '<td>';
                                if ($os == "Centos" or $os == "centos" or $os == "CentOS") {
                                    echo '<img src="ressources/icons/centos.png" class="icon" />';
                                } elseif ($os == "Debian" or $os == "debian") {
                                    echo '<img src="ressources/icons/debian.png" class="icon" />';
                                } elseif ($os == "Ubuntu" or $os == "ubuntu" or $os == "linuxmint") {
                                    echo '<img src="ressources/icons/ubuntu.png" class="icon" />';
                                } else {
                                    echo '<img src="ressources/icons/tux.png" class="icon" />';
                                }
                                echo ucfirst($os) . ' ' . $os_version;
                                echo '</td>';
                            } else {
                                echo '<td>Inconnu</td>';
                            } ?>
                        </tr>
                        <tr>
                            <td>PROFIL</td>
                            <td>
                            <?php
                            if (!empty($profile)) {
                                echo '<span class="label-white">' . $profile . '</span>';
                            } else {
                                echo 'Inconnu';
                            } ?>
                            </td>
                        </tr>
                        <tr>
                            <td>ENVIRONNEMENT</td>
                            <?php
                            if (!empty($env)) {
                                echo "<td>" . Models\Common::envtag($env) . "</td>";
                            } else {
                                echo '<td>Inconnu</td>';
                            } ?>
                        </tr>
                        <tr>
                            <td>AGENT STATUS</td>
                            <td>
                                <span>
                                <?php
                                if ($agentStatus == 'running') {
                                    echo '<img src="ressources/icons/greencircle.png" class="icon-small" title="État de l\'agent linupdate sur l\'hôte : actif (' . $agentLastSendStatusMsg . ')." /> Démarré';
                                }
                                if ($agentStatus == "disabled") {
                                    echo '<img src="ressources/icons/yellowcircle.png" class="icon-small" title="État du module d\'agent reposerver sur l\'hôte : désactivé (' . $agentLastSendStatusMsg . ')." /> Désactivé';
                                }
                                if ($agentStatus == "stopped") {
                                    echo '<img src="ressources/icons/redcircle.png" class="icon-small" title="État de l\'agent linupdate sur l\'hôte : stoppé (' . $agentLastSendStatusMsg . ')." /> Stoppé';
                                }
                                if ($agentStatus == "seems-stopped") {
                                    echo '<img src="ressources/icons/redcircle.png" class="icon-small" title="État de l\'agent linupdate sur l\'hôte : semble stoppé (' . $agentLastSendStatusMsg . ')." /> Semble stoppé';
                                }
                                if ($agentStatus == "unknow") {
                                    echo '<img src="ressources/icons/graycircle.png" class="icon-small" title="État de l\'agent linupdate sur l\'hôte : inconnu." /> Inconnu';
                                } ?>
                                </span>
                            </td>
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
                                if ($packagesAvailableTotal >= $pkgs_count_considered_critical) {
                                    echo '<span class="label-red">' . $packagesAvailableTotal . '</span>';
                                } elseif ($packagesAvailableTotal >= $pkgs_count_considered_outdated) {
                                    echo '<span class="label-yellow">' . $packagesAvailableTotal . '</span>';
                                } else {
                                    echo '<span class="label-white">' . $packagesAvailableTotal . '</span>';
                                }
                                /**
                                 *  Affichage d'un bouton 'Détails' si il y a au moins 1 paquet disponible
                                 */
                                if ($packagesAvailableTotal > 0) {
                                    echo ' <img src="ressources/icons/search.png" id="packagesAvailableButton" class="icon-lowopacity" />';
                                }
                                ?>
                                </td>
                                <td>
                                    <?php
                                    echo '<span class="label-white">' . $packagesInstalledCount . '</span>';
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
                                                echo '<td>' . $package['Version'] . '</td>';
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
                                            if ($package['State'] == "removed" or $package['State'] == "purged") {
                                                echo '<span class="redtext">' . $package['Name'] . ' (désinstallé)</span>';
                                            } else {
                                                echo $package['Name'];
                                            }
                                                echo '</td>';
                                                echo '<td>' . $package['Version'] . '</td>';
                                                echo '<td><span class="getPackageTimeline pointer" hostid="' . $id . '" packagename="' . $package['Name'] . '">Historique</span></td>';
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

                    <p>Historique des évènements (installation, mise à jour, désintallation de paquets...)</p>
                    <br>

                    <div id="eventsContainer">
                            <?php
                            if (empty($allEventsList)) {
                                echo '<p>Aucun historique</p>';
                            } else { ?>
                                <span>Afficher les demandes de transfert </span>
                                <label class="onoff-switch-label">
                                    <input id="showUpdateRequests" type="checkbox" name="" class="onoff-switch-input" <?php echo (!empty($_COOKIE['showUpdateRequests']) and $_COOKIE['showUpdateRequests'] == "yes") ? 'checked' : ''; ?>>
                                    <span class="onoff-switch-slider"></span>
                                </label>

                                <table class="table-generic-blue">

                                <?php

                                /**
                                 *  Nombre maximal d'évènements qu'on souhaite afficher par défaut, le reste est masqué et affichable par un bouton "Afficher tout"
                                 *  Lorsque $i a atteint le nombre maximal $printMaxItems, on commence à masquer les opérations
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
                                            if (empty($_COOKIE['showUpdateRequests']) or $_COOKIE['showUpdateRequests'] == "no") {
                                                continue;
                                            }

                                            echo '<tr class="update-request hide">';
                                        }
                                        /**
                                         *  Si l'évènement est un 'event'
                                         */
                                        if ($event['Event_type'] == "event") {
                                            echo '<tr class="event hide">';
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
                                            if (empty($_COOKIE['showUpdateRequests']) or $_COOKIE['showUpdateRequests'] == "no") {
                                                continue;
                                            }

                                            echo '<tr class="update-request">';
                                        }
                                        /**
                                         *  Si l'évènement est un 'event'
                                         */
                                        if ($event['Event_type'] == "event") {
                                            echo '<tr class="event">';
                                        }
                                    } ?>

                                        <td class="td-fit">
                                            <span><?php echo 'Le <b>' . DateTime::createFromFormat('Y-m-d', $event['Date'])->format('d-m-Y') . '</b> à <b>' . $event['Time']; ?></b></span>
                                        </td>
                                    
                                        <?php
                                        if ($event['Event_type'] == "update_request") {
                                            echo '<td class="td-10">';
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
                                                echo 'Envoi des informations générales';
                                            }
                                            if ($event['Type'] == 'packages-status-update') {
                                                echo 'Envoi de l\'état des paquets';
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
                                                echo ' demandé(e)';
                                            }
                                            echo '</td>';
                                        }

                                        if ($event['Event_type'] == "event") {
                                            /**
                                             *  Récupération des paquets installés par cet évènement
                                             */
                                            $packagesInstalled = $myhost->getEventPackagesList($event['Id'], 'installed');
                                            $packagesInstalledCount = count($packagesInstalled);
                                            /**
                                             *  Récupération des dépendances installées par cet évènement
                                             */
                                            $dependenciesInstalled = $myhost->getEventPackagesList($event['Id'], 'dep-installed');
                                            $dependenciesInstalledCount = count($dependenciesInstalled);
                                            /**
                                             *  Récupération des paquets mis à jour par cet évènement
                                             */
                                            $packagesUpdated = $myhost->getEventPackagesList($event['Id'], 'upgraded');
                                            $packagesUpdatedCount = count($packagesUpdated);
                                            /**
                                             *  Récupération des paquets rétrogradés (downgrade) par cet évènement
                                             */
                                            $packagesDowngraded = $myhost->getEventPackagesList($event['Id'], 'downgraded');
                                            $packagesDowngradedCount = count($packagesDowngraded);
                                            /**
                                             *  Récupération des paquets supprimés par cet évènement
                                             */
                                            $packagesRemoved = $myhost->getEventPackagesList($event['Id'], 'removed');
                                            $packagesRemovedCount = count($packagesRemoved); ?>

                                            <?php
                                            if ($packagesInstalledCount > 0) { ?>
                                                <td class="td-10">
                                                    <div class="pointer showEventDetailsBtn" host-id="<?= $id ?>" event-id="<?= $event['Id'] ?>" package-state="installed">
                                                    <span class="label-green">Installé(s)</span>
                                                    <span class="label-green"><?= $packagesInstalledCount ?></span>
                                                </td>
                                            <?php                                       }

                                            if ($dependenciesInstalledCount > 0) { ?>
                                                <td class="td-10">
                                                    <div class="pointer showEventDetailsBtn" host-id="<?= $id ?>" event-id="<?= $event['Id'] ?>" package-state="dep-installed">
                                                    <span class="label-green">Dép. Installé(s)</span>
                                                    <span class="label-green"><?= $dependenciesInstalledCount ?></span>
                                                </td>
                                            <?php                                       }

                                            if ($packagesUpdatedCount > 0) { ?>
                                                <td class="td-10">
                                                    <div class="pointer showEventDetailsBtn" host-id="<?= $id ?>" event-id="<?= $event['Id'] ?>" package-state="upgraded">
                                                    <span class="label-yellow">Mis à jour</span>
                                                    <span class="label-yellow"><?= $packagesUpdatedCount ?></span>
                                                </td>
                                            <?php                                       }

                                            if ($packagesDowngradedCount > 0) { ?>
                                                <td class="td-10">
                                                    <div class="pointer showEventDetailsBtn" host-id="<?= $id ?>" event-id="<?= $event['Id'] ?>" package-state="downgraded">
                                                    <span class="label-red">Rétrogradé(s)</span>
                                                    <span class="label-red"><?= $packagesDowngradedCount ?></span>
                                                </td>
                                            <?php                                       }

                                            if ($packagesRemovedCount > 0) { ?>
                                                <td class="td-10">
                                                    <div class="pointer showEventDetailsBtn" host-id="<?= $id ?>" event-id="<?= $event['Id'] ?>" package-state="removed">
                                                    <span class="label-red">Désintallé(s)</span>
                                                    <span class="label-red"><?= $packagesRemovedCount ?></span>
                                                </td>
                                            <?php                                       }
                                        } ?>
                                        <td colspan="100%"></td>
                                    </tr>
                                    <?php                               ++$i;
                                } ?>

                                </table>
                                
                                <?php                           if ($i > $printMaxItems) {
                                    /**
                                     *  Affichage du bouton Afficher tout
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
                borderColor: '#cc9951',
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
                    text: 'Evolution des paquets (15 jours)',
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