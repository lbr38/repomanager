<section class="section-main">
    <?php

    $idError = 0;

    if (!empty($_GET['id'])) {
        /**
         *  Getting all informations about this host
         */
        $hostProperties = $myhost->getAll($_GET['id']);

        if (!empty($hostProperties)) {
            $id               = $hostProperties['Id'];
            $hostname         = $hostProperties['Hostname'];
            $ip               = $hostProperties['Ip'];
            $os               = $hostProperties['Os'];
            $os_version       = $hostProperties['Os_version'];
            $kernel           = $hostProperties['Kernel'];
            $arch             = $hostProperties['Arch'];
            $profile          = $hostProperties['Profile'];
            $env              = $hostProperties['Env'];
            $status           = $hostProperties['Status'];
            $agentStatus      = $hostProperties['Online_status'];
            $agentVersion     = $hostProperties['Linupdate_version'];
            $rebootRequired   = $hostProperties['Reboot_required'];

            /**
             *  Checking that the last time the agent has sent his status was before 1h10m
             */
            if ($hostProperties['Online_status_date'] != DATE_YMD or $hostProperties['Online_status_time'] <= date('H:i:s', strtotime(date('H:i:s') . ' - 70 minutes'))) {
                $agentStatus = 'seems-stopped';
            }

            /**
             *  Last known agent state message
             */
            $agentLastSendStatusMsg = 'state on ' . DateTime::createFromFormat('Y-m-d', $hostProperties['Online_status_date'])->format('d-m-Y') . ' at ' . $hostProperties['Online_status_time'];

            /**
             *  If the host has 'deleted' state then don't print it
             */
            if ($status == 'deleted') {
                $idError++;
            } else {
                /**
                 *  Open host database
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
        echo '<span class="yellowtext">Specified host Id is invalid.</span>';
        die();
    }

    /**
     *  Getting hosts general threshold settings
     */
    $hosts_settings = $myhost->getSettings();

    /**
     *  Threshold of the maximum number of available update above which the host is considered as 'not up to date' (but not critical)
     */
    $pkgs_count_considered_outdated = $hosts_settings['pkgs_count_considered_outdated'];

    /**
     *  Threshold of the maximum number of available update above which the host is considered as 'not up to date' (critical)
     */
    $pkgs_count_considered_critical = $hosts_settings['pkgs_count_considered_critical'];

    /**
     *  Getting informations from host's database
     */

    /**
     *  Getting installed packages and its total
     */
    $packagesInventored = $myhost->getPackagesInventory();
    $packagesInstalledCount = count($myhost->getPackagesInstalled());

    /**
     *  Getting available packages and its total
     */
    $packagesAvailable = $myhost->getPackagesAvailable();
    $packagesAvailableTotal = count($packagesAvailable);

    /**
     *  Getting all packages events history
     */
    $eventsList = $myhost->getEventsHistory();

    /**
     *  Getting updates requests that Repomanager has sent to this host
     */
    $updatesRequestsList = $myhost->getUpdatesRequests();

    /**
     *  Merging events history and updates requests into the same array and order it by date and time
     */
    $allEventsList = array_merge($eventsList, $updatesRequestsList);
    array_multisort(array_column($allEventsList, 'Date'), SORT_DESC, array_column($allEventsList, 'Time'), SORT_DESC, $allEventsList);

    /**
     *  Generating values for the 'line' chart
     */

    /**
     *  First create a list of dates on a 15days period
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
     *  Then generate a new array from the date period. Every date is initialized with a 0 value.
     */
    foreach ($period as $key => $value) {
        $dates[$value->format('Y-m-d')] = 0;
    }

    /**
     *  Getting last 15days installed packages
     */
    $lastInstalledPackagesArray = $myhost->getLastPackagesStatusCount('installed', '15');

    /**
     *  Getting last 15days updated packages
     */
    $lastUpgradedPackagesArray = $myhost->getLastPackagesStatusCount('upgraded', '15');

    /**
     *  Getting last 15days deleted packages
     */
    $lastRemovedPackagesArray = $myhost->getLastPackagesStatusCount('removed', '15');

    /**
     *  Merging all arrays with dates array
     */
    $lastInstalledPackagesArray = array_merge($dates, $lastInstalledPackagesArray);
    $lastUpgradedPackagesArray  = array_merge($dates, $lastUpgradedPackagesArray);
    $lastRemovedPackagesArray   = array_merge($dates, $lastRemovedPackagesArray);

    /**
     *  Formating values to ChartJS format
     *  Formating dates array to ChartJS format
     */
    $lineChartInstalledPackagesCount = "'" . implode("','", $lastInstalledPackagesArray) . "'";
    $lineChartUpgradedPackagesCount  = "'" . implode("','", $lastUpgradedPackagesArray) . "'";
    $lineChartRemovedPackagesCount   = "'" . implode("','", $lastRemovedPackagesArray) . "'";
    $lineChartDates = "'" . implode("','", array_keys($dates)) . "'";

    echo '<h3>' . strtoupper($hostname) . '</h3>';

    if (IS_ADMIN) : ?>
        <div class="relative">
            <div class="hostActionBtn-container">
                <span class="btn-large-green"><img src="/assets/icons/rocket.svg" class="icon" />Actions</span>
                <span class="hostActionBtn btn-large-green" hostid="<?= $id ?>" action="general-status-update" title="Send general informations (OS and state informations).">Request to send general info.</span>
                <span class="hostActionBtn btn-large-green" hostid="<?= $id ?>" action="packages-status-update" title="Send packages informations (available, installed, updated...).">Request to send packages info.</span>
                <span class="hostActionBtn btn-large-red"  hostid="<?= $id ?>" action="update" title="Update all available packages using linupdate.">Update packages</span>
                <span class="hostActionBtn btn-large-red"  hostid="<?= $id ?>" action="reset" title="Reset known data.">Reset</span>
                <span class="hostActionBtn btn-large-red"  hostid="<?= $id ?>" action="delete" title="Delete this host">Delete</span>
            </div>
        </div>
        <?php
    endif ?>

    <div class="host-overview-container div-generic-blue">
        <div>
            <table class="table-generic host-table">
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
                            echo '<img src="/assets/icons/products/centos.png" class="icon" />';
                        } elseif ($os == "Debian" or $os == "debian") {
                            echo '<img src="/assets/icons/products/debian.png" class="icon" />';
                        } elseif ($os == "Ubuntu" or $os == "ubuntu" or $os == "linuxmint") {
                            echo '<img src="/assets/icons/products/ubuntu.png" class="icon" />';
                        } else {
                            echo '<img src="/assets/icons/products/tux.png" class="icon" />';
                        }
                        echo ucfirst($os) . ' ' . $os_version . ' - ' . $kernel . ' ' . $arch . '';
                        echo '</td>';
                    } else {
                        echo '<td>Unknow</td>';
                    } ?>
                </tr>
                <tr>
                    <td>PROFILE</td>
                    <td>
                    <?php
                    if (!empty($profile)) {
                        echo '<span class="label-white">' . $profile . '</span>';
                    } else {
                        echo 'Unknow';
                    } ?>
                    </td>
                </tr>
                <tr>
                    <td>ENVIRONMENT</td>
                    <?php
                    if (!empty($env)) {
                        echo "<td>" . Controllers\Common::envtag($env) . "</td>";
                    } else {
                        echo '<td>Unknow</td>';
                    } ?>
                </tr>
                <tr>
                    <td>AGENT STATUS</td>
                    <td>
                        <span>
                        <?php
                        if ($agentStatus == 'running') {
                            echo '<img src="/assets/icons/greencircle.png" class="icon-small" title="Linupdate agent state on this host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." /> Running';
                        }
                        if ($agentStatus == "disabled") {
                            echo '<img src="/assets/icons/yellowcircle.png" class="icon-small" title="Linupdate agent state on this host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." /> Disabled';
                        }
                        if ($agentStatus == "stopped") {
                            echo '<img src="/assets/icons/redcircle.png" class="icon-small" title="Linupdate agent state on this host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." /> Stopped';
                        }
                        if ($agentStatus == "seems-stopped") {
                            echo '<img src="/assets/icons/redcircle.png" class="icon-small" title="Linupdate agent state on this host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." /> Seems stopped';
                        }
                        if ($agentStatus == "unknow") {
                            echo '<img src="/assets/icons/graycircle.png" class="icon-small" title="Linupdate agent state on this host: ' . $agentStatus . '." /> Unknow';
                        } ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>AGENT VERSION</td>
                    <td>
                        <span class="label-black">
                            <?php
                            if (!empty($agentVersion)) {
                                echo $agentVersion;
                            } else {
                                echo 'Unknow';
                            } ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
        <div>
            <div class="host-line-chart-container">
                <canvas id="packages-status-chart"></canvas>
            </div>
        </div>
    </div>

    <div class="host-pkg-history-container">
        <div class="div-generic-blue">

            <h4>PACKAGES</h4>
                
            <div class="grid grid-2">
                <div id="packagesAvailableButton" class="flex align-item-center column-gap-5 pointer">
                    <?php
                    if ($packagesAvailableTotal >= $pkgs_count_considered_critical) {
                        $labelColor = 'red';
                    } elseif ($packagesAvailableTotal >= $pkgs_count_considered_outdated) {
                        $labelColor = 'yellow';
                    } else {
                        $labelColor = 'white';
                    } ?>

                    <span class="label-<?= $labelColor ?>"><?= $packagesAvailableTotal ?></span>
                    <span><b>To update</b></span>
                </div>

                <div id="packagesInstalledButton" class="flex align-item-center column-gap-5 pointer">
                    <span class="label-white"><?= $packagesInstalledCount ?></span>
                    <span><b>Total installed</b></span>
                </div>
            </div>

            <div id="packagesContainerLoader">
                <br><br>
                <span>Loading <img src="/assets/images/loading.gif" class="icon" /></span>
            </div>
        
            <div id="packagesContainer">
                <div id="packagesAvailableDiv" class="hide">
                    <h4>Package to update</h4>
                    <table class="packages-table">
                        <thead>
                            <tr>
                                <td>Name</td>
                                <td>Version</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($packagesAvailable)) {
                                foreach ($packagesAvailable as $package) : ?>
                                    <tr>
                                        <td>
                                            <?= \Controllers\Common::printProductIcon($package['Name']); ?>
                                            <?= $package['Name']; ?>
                                        </td>
                                        <td>
                                            <?= $package['Version'] ?>
                                        </td>
                                    </tr>
                                    <?php
                                endforeach;
                            } ?>
                        </tbody>
                    </table>
                </div>

                <div id="packagesInstalledDiv" class="hide">
                    <h4>Package inventory (<?= count($packagesInventored) ?>)</h4>
                    <input type="text" id="packagesIntalledSearchInput" onkeyup="filterPackage()" autocomplete="off" placeholder="Search...">
                    <table id="packagesIntalledTable" class="packages-table">
                        <thead>
                            <tr>
                                <td>Name</td>
                                <td>Version</td>
                                <td></td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($packagesInventored)) :
                                foreach ($packagesInventored as $package) : ?>
                                    <tr class="pkg-row">
                                        <td>
                                            <?php
                                            if (preg_match('/python/i', $package['Name'])) {
                                                echo '<img src="/assets/icons/products/python.png" class="icon" />';
                                            } elseif (preg_match('/^code$/i', $package['Name'])) {
                                                echo '<img src="/assets/icons/products/vsdownload.svg" class="icon" />';
                                            } elseif (preg_match('/^firefox/i', $package['Name'])) {
                                                echo '<img src="/assets/icons/products/firefox.png" class="icon" />';
                                            } elseif (preg_match('/^chrome-/i', $package['Name'])) {
                                                echo '<img src="/assets/icons/products/chrome.png" class="icon" />';
                                            } elseif (preg_match('/^chromium-/i', $package['Name'])) {
                                                echo '<img src="/assets/icons/products/chromium.png" class="icon" />';
                                            } elseif (preg_match('/^brave-/i', $package['Name'])) {
                                                echo '<img src="/assets/icons/products/brave.png" class="icon" />';
                                            } elseif (preg_match('/^filezilla/i', $package['Name'])) {
                                                echo '<img src="/assets/icons/products/filezilla.png" class="icon" />';
                                            } elseif (preg_match('/^java/i', $package['Name'])) {
                                                echo '<img src="/assets/icons/products/java.png" class="icon" />';
                                            } elseif (preg_match('/^teams$/i', $package['Name'])) {
                                                echo '<img src="/assets/icons/products/teams.png" class="icon" />';
                                            } elseif (preg_match('/^teamviewer$/i', $package['Name'])) {
                                                echo '<img src="/assets/icons/products/teamviewer.png" class="icon" />';
                                            } elseif (preg_match('/^thunderbird/i', $package['Name'])) {
                                                echo '<img src="/assets/icons/products/thunderbird.png" class="icon" />';
                                            } elseif (preg_match('/^vlc/i', $package['Name'])) {
                                                echo '<img src="/assets/icons/products/vlc.png" class="icon" />';
                                            } else {
                                                echo '<img src="/assets/icons/package.svg" class="icon" />';
                                            }
                                            if ($package['State'] == "removed" or $package['State'] == "purged") {
                                                echo '<span class="redtext">' . $package['Name'] . ' (uninstalled)</span>';
                                            } else {
                                                echo $package['Name'];
                                            } ?>
                                        </td>
                                        <td><?= $package['Version'] ?></td>
                                        <td class="td-10">
                                            <span class="getPackageTimeline pointer label-white" hostid="<?= $id ?>" packagename="<?= $package['Name'] ?>">History</span>
                                        </td>
                                    </tr>
                                    <?php
                                endforeach;
                            endif ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="div-generic-blue">
            <h4>HISTORY</h4>
            <p>Packages events history (installation, update, uninstallation...)</p>
            <br>
            <div id="eventsContainer">
                    <?php
                    if (empty($allEventsList)) {
                        echo '<p>No history</p>';
                    } else { ?>
                        <div class="flex align-item-center column-gap-4">
                            <span>Show sent requests</span>
                            <label class="onoff-switch-label">
                                <input id="showUpdateRequests" type="checkbox" name="" class="onoff-switch-input" <?php echo (!empty($_COOKIE['showUpdateRequests']) and $_COOKIE['showUpdateRequests'] == "yes") ? 'checked' : ''; ?>>
                                <span class="onoff-switch-slider"></span>
                            </label>
                        </div>

                        <table class="table-generic-blue">
                            <?php
                            /**
                             *  Default maximum number of printed events (10). Others events can be printed with 'Show all' button.
                             *  When $i has reached maximal number $printMaxItems, then mask all next events.
                             */
                            $i = 0;
                            $printMaxItems = 10;
                            foreach ($allEventsList as $event) :
                                /**
                                 *  Case event must be masked
                                 */
                                if ($i > $printMaxItems) {
                                    /**
                                     *  If the event is 'update request' type
                                     */
                                    if ($event['Event_type'] == "update_request") {
                                        /**
                                         *  If 'showUpdateRequest' cookie is not set or is equal to 'no' then don't print 'update_request' events
                                         */
                                        if (empty($_COOKIE['showUpdateRequests']) or $_COOKIE['showUpdateRequests'] == "no") {
                                            continue;
                                        }
                                        echo '<tr class="update-request hide">';
                                    }
                                    /**
                                     *  If the event is 'event' type
                                     */
                                    if ($event['Event_type'] == "event") {
                                        echo '<tr class="event hide">';
                                    }
                                /**
                                 *  Case event is printed
                                 */
                                } else {
                                    /**
                                     *  If the event is 'update request' type
                                     */
                                    if ($event['Event_type'] == "update_request") {
                                        /**
                                         *  If 'showUpdateRequest' cookie is not set or is equal to 'no' then don't print 'update_request' events
                                         */
                                        if (empty($_COOKIE['showUpdateRequests']) or $_COOKIE['showUpdateRequests'] == "no") {
                                            continue;
                                        }
                                        echo '<tr class="update-request">';
                                    }
                                    /**
                                     *  If the event is 'event' type
                                     */
                                    if ($event['Event_type'] == "event") {
                                        echo '<tr class="event">';
                                    }
                                } ?>
                                    <td class="td-fit">
                                        <span><?= '<b>' . DateTime::createFromFormat('Y-m-d', $event['Date'])->format('d-m-Y') . '</b> at <b>' . $event['Time']; ?></b></span>
                                    </td>
                                
                                    <?php
                                    if ($event['Event_type'] == "update_request") :
                                        echo '<td class="td-10">';
                                        /**
                                         *  Event status icon
                                         */
                                        if ($event['Status'] == 'requested') {
                                            echo '<img src="/assets/icons/graycircle.png" class="icon-small" />';
                                        }
                                        if ($event['Status'] == 'done') {
                                            echo '<img src="/assets/icons/greencircle.png" class="icon-small" />';
                                        }
                                        if ($event['Status'] == 'error') {
                                            echo '<img src="/assets/icons/redcircle.png" class="icon-small" />';
                                        }
                                        if ($event['Status'] == 'running') {
                                            echo '<img src="/assets/images/loading.gif" class="icon" />';
                                        }

                                        /**
                                         *  Request type
                                         */
                                        if ($event['Type'] == 'general-status-update') {
                                            echo 'Retrieving general informations';
                                        }
                                        if ($event['Type'] == 'packages-status-update') {
                                            echo 'Retrieving packages state';
                                        }
                                        if ($event['Type'] == 'packages-update') {
                                            echo 'Packages update';
                                        }

                                        /**
                                         *  Status
                                         */
                                        if ($event['Status'] == 'done') {
                                            echo ' completed';
                                        }
                                        if ($event['Status'] == 'error') {
                                            echo ' has failed';
                                        }
                                        if ($event['Status'] == 'running') {
                                            echo ' running';
                                        }
                                        if ($event['Status'] == 'requested') {
                                            echo ' (request send)';
                                        }
                                        echo '</td>';
                                    endif;

                                    if ($event['Event_type'] == "event") :
                                        /**
                                         *  Getting installed packages from this event
                                         */
                                        $packagesInstalled = $myhost->getEventPackagesList($event['Id'], 'installed');
                                        $packagesInstalledCount = count($packagesInstalled);

                                        /**
                                         *  Getting isntalled dependencies packages from this event
                                         */
                                        $dependenciesInstalled = $myhost->getEventPackagesList($event['Id'], 'dep-installed');
                                        $dependenciesInstalledCount = count($dependenciesInstalled);

                                        /**
                                         *  Getting updated packages from this event
                                         */
                                        $packagesUpdated = $myhost->getEventPackagesList($event['Id'], 'upgraded');
                                        $packagesUpdatedCount = count($packagesUpdated);

                                        /**
                                         *  Getting downgraded packages from this event
                                         */
                                        $packagesDowngraded = $myhost->getEventPackagesList($event['Id'], 'downgraded');
                                        $packagesDowngradedCount = count($packagesDowngraded);

                                        /**
                                         *  Getting removed packages from this event
                                         */
                                        $packagesRemoved = $myhost->getEventPackagesList($event['Id'], 'removed');
                                        $packagesRemovedCount = count($packagesRemoved);

                                        if ($packagesInstalledCount > 0) : ?>
                                            <td class="td-10">
                                                <div class="pointer showEventDetailsBtn" host-id="<?= $id ?>" event-id="<?= $event['Id'] ?>" package-state="installed">
                                                <span class="label-green">Installed</span>
                                                <span class="label-green"><?= $packagesInstalledCount ?></span>
                                            </td>
                                            <?php
                                        endif;

                                        if ($dependenciesInstalledCount > 0) : ?>
                                            <td class="td-10">
                                                <div class="pointer showEventDetailsBtn" host-id="<?= $id ?>" event-id="<?= $event['Id'] ?>" package-state="dep-installed">
                                                <span class="label-green">Dep. installed</span>
                                                <span class="label-green"><?= $dependenciesInstalledCount ?></span>
                                            </td>
                                            <?php
                                        endif;

                                        if ($packagesUpdatedCount > 0) : ?>
                                            <td class="td-10">
                                                <div class="pointer showEventDetailsBtn" host-id="<?= $id ?>" event-id="<?= $event['Id'] ?>" package-state="upgraded">
                                                <span class="label-yellow">Updated</span>
                                                <span class="label-yellow"><?= $packagesUpdatedCount ?></span>
                                            </td>
                                            <?php
                                        endif;

                                        if ($packagesDowngradedCount > 0) : ?>
                                            <td class="td-10">
                                                <div class="pointer showEventDetailsBtn" host-id="<?= $id ?>" event-id="<?= $event['Id'] ?>" package-state="downgraded">
                                                <span class="label-red">Downgraded</span>
                                                <span class="label-red"><?= $packagesDowngradedCount ?></span>
                                            </td>
                                            <?php
                                        endif;

                                        if ($packagesRemovedCount > 0) : ?>
                                            <td class="td-10">
                                                <div class="pointer showEventDetailsBtn" host-id="<?= $id ?>" event-id="<?= $event['Id'] ?>" package-state="removed">
                                                <span class="label-red">Uninstalled</span>
                                                <span class="label-red"><?= $packagesRemovedCount ?></span>
                                            </td>
                                            <?php
                                        endif;
                                    endif ?>
                                    <td colspan="100%"></td>
                                </tr>
                                <?php
                                ++$i;
                            endforeach ?>
                        </table>
                        
                        <?php
                        if ($i > $printMaxItems) {
                            /**
                             *  'Show all' button
                             */
                            echo '<p id="print-all-events-btn" class="pointer center"><b>Show all</b> <img src="/assets/icons/down.svg" class="icon" /></p>';
                        }
                    } ?>
            </div>
        </div>
    </div>
    <?php
    /**
     *  Closing host database
     */
    $myhost->closeHostDb(); ?>

    <script>
    $(document).ready(function(){
        /**
         *  Line chart
         */
        // Data
        var lineChartData = {
            labels: [<?=$lineChartDates?>],
            datasets: [
                {
                    label: 'Installed',
                    data: [<?=$lineChartInstalledPackagesCount?>],
                    borderColor: '#14be7e',
                    fill: false
                },
                {
                    label: 'Updated',
                    data: [<?=$lineChartUpgradedPackagesCount?>],
                    borderColor: '#cc9951',
                    fill: false
                },
                {
                    label: 'Uninstalled',
                    data: [<?=$lineChartRemovedPackagesCount?>],
                    borderColor: '#ff0044',
                    fill: false
                }
            ],
        };
        // Options
        var lineChartOptions = {
            tension: 0.2,
            responsive: true,
            maintainAspectRatio: false,
            borderWidth: 1.5,
            scales: {
                x: {
                    display: false // do not print dates on X axis
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
                        text: 'Packages evolution',
                    }
                },
            },
        }
        // Print chart
        var ctx = document.getElementById('packages-status-chart').getContext("2d");
        window.myLine = new Chart(ctx, {
            type: "line",
            data: lineChartData,
            options: lineChartOptions
        });
    });
    </script>
</section>
