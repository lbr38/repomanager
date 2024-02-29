<section class="section-main reloadable-container" container="hosts/list">
    <?php
    if ($totalHosts >= 1) : ?>
        <div id="hostsDiv">
            <div>
                <div id="title-button-div">
                    <h3>HOSTS</h3>

                    <?php
                    if (IS_ADMIN) : ?>
                        <div id="title-button-container">
                            <div class="slide-btn slide-panel-btn" slide-panel="hosts/groups" title="Manage hosts groups">
                                <img src="/assets/icons/folder.svg" />
                                <span>Manage groups</span>
                            </div>

                            <div class="slide-btn slide-panel-btn" slide-panel="hosts/settings" title="Edit display settings">
                                <img src="/assets/icons/cog.svg" />
                                <span>Settings</span>
                            </div>
                        </div>
                        <?php
                    endif ?>
                </div>

                <?php
                if (!empty($hostGroupsList)) :
                    /**
                     *  If there is at least 1 active host then we display the search fields
                     */
                    if ($totalHosts != 0) : ?>
                        <div class="searchInput-container">
                            <div class="searchInput-subcontainer">
                                <div>
                                    <div class="flex align-item-center justify-center">
                                        <img src="/assets/icons/info.svg" class="icon-lowopacity" title="Search a host by its name.&#13;&#13;You can specify a filter before your search entry:&#13;os:<os name> <search>&#13;os_version:<os version> <search>&#13;os_family:<os family> <search>&#13;type:<virtualization type> <search>&#13;kernel:<kernel> <search>&#13;arch:<architecture> <search>&#13;agent_version:<version> <search>&#13;reboot_required:<true/false> <search>" />
                                        <span>Search host:</span>
                                    </div>
                                    <input type="text" id="searchHostInput" onkeyup="searchHost()" class="input-large" autocomplete="off" placeholder="Hostname, IP" />
                                </div>
                                <div>
                                    <div class="flex align-item-center justify-center">
                                        <img src="/assets/icons/info.svg" class="icon-lowopacity" title="Search a package on all hosts, by its name" />
                                        <span>Search package:</span>
                                    </div>
                                    <input type="text" id="getHostsWithPackageInput" onkeyup="getHostsWithPackage()" class="input-large" autocomplete="off" placeholder="Package name" />
                                </div>
                            </div>
                        </div>
                        <?php
                    endif ?>
                    
                    <div class="groups-container">
                        <?php
                        foreach ($hostGroupsList as $group) :
                            /**
                             *  Retrieve the list of hosts in the group
                             */
                            $hostsList = $myhost->listByGroup($group['Name']);

                            /**
                             *  If it's the default group 'Default' and it has no host then we ignore its display
                             */
                            if ($group['Name'] == "Default" and empty($hostsList)) {
                                continue;
                            } ?>
                            <input type='hidden' name='groupname' value='<?=$group['Name']?>'>
            
                            <div class="div-generic-blue hosts-group-container veil-on-reload">
                                <?php
                                /**
                                 *  Print the group name except if it's the Default group
                                 */
                                if ($group['Name'] != "Default") :
                                    echo '<h3>';
                                    echo $group['Name'];

                                    /**
                                     *  Print the number of hosts in the group
                                     */
                                    if (!empty($hostsList)) {
                                        echo ' (' . count($hostsList) . ')';
                                    }
                                    echo '</h3>';
                                endif;

                                /**
                                 *  Print the hosts of the group
                                 */
                                if (!empty($hostsList)) : ?>
                                    <div class="hosts-table">
                                        <div class="hosts-table-title">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span title="Hosting type">Type</span>
                                            <span title="Host agent version">Agent v.</span>
                                            <span title="Total installed packages.">Inst.</span>
                                            <span title="Total available updates.">Avail.</span>
                                            <?php
                                            if (IS_ADMIN) : ?>
                                                <span class="text-right margin-right-15">
                                                    <input class="js-select-all-button verylowopacity pointer" type="checkbox" group="<?=$group['Name']?>" title="Select all" >
                                                </span>
                                                <?php
                                            endif ?>
                                        </div>
                                    
                                        <?php
                                        /**
                                         *  Process the hosts list
                                         *  Here we will display the details of each host and we take the opportunity to retrieve some additional information from the database
                                         */
                                        foreach ($hostsList as $host) :
                                            $id = $host['Id'];

                                            if (!empty($host['Hostname'])) {
                                                $hostname = $host['Hostname'];
                                            } else {
                                                $hostname = 'unknow';
                                            }
                                            if (!empty($host['Ip'])) {
                                                $ip = $host['Ip'];
                                            } else {
                                                $ip = 'unknow';
                                            }
                                            if (!empty($host['Os'])) {
                                                $os = $host['Os'];
                                            } else {
                                                $os = 'unknow';
                                            }
                                            if (!empty($host['Os_version'])) {
                                                $os_version = $host['Os_version'];
                                            } else {
                                                $os_version = 'unknow';
                                            }
                                            if (!empty($host['Os_family'])) {
                                                $os_family = $host['Os_family'];
                                            } else {
                                                $os_family = 'unknow';
                                            }
                                            if (!empty($host['Type'])) {
                                                $type = $host['Type'];
                                            } else {
                                                $type = 'unknow';
                                            }
                                            if (!empty($host['Kernel'])) {
                                                $kernel = $host['Kernel'];
                                            } else {
                                                $kernel = 'unknow';
                                            }
                                            if (!empty($host['Arch'])) {
                                                $arch = $host['Arch'];
                                            } else {
                                                $arch = 'unknow';
                                            }
                                            if (!empty($host['Profile'])) {
                                                $profile = $host['Profile'];
                                            } else {
                                                $profile = 'unknow';
                                            }
                                            if (!empty($host['Env'])) {
                                                $env = $host['Env'];
                                            } else {
                                                $env = 'unknow';
                                            }
                                            if (!empty($host['Linupdate_version'])) {
                                                $agentVersion = $host['Linupdate_version'];
                                            } else {
                                                $agentVersion = 'unknow';
                                            }
                                            if (!empty($host['Reboot_required'])) {
                                                $rebootRequired = $host['Reboot_required'];
                                            } else {
                                                $rebootRequired = 'unknow';
                                            }

                                            /**
                                             *  Define the agent status
                                             *  This status can change to 'stopped' if the agent has not given news after 1h
                                             */
                                            $agentStatus = $host['Online_status'];

                                            /**
                                             *  Check if the last time the agent reported its status is less than 1h (and 10min of "margin")
                                             */
                                            if ($host['Online_status_date'] != DATE_YMD or $host['Online_status_time'] <= date('H:i:s', strtotime(date('H:i:s') . ' - 70 minutes'))) {
                                                $agentStatus = 'seems-stopped';
                                            }

                                            /**
                                             *  Last known status message
                                             */
                                            $agentLastSendStatusMsg = 'state on ' . DateTime::createFromFormat('Y-m-d', $host['Online_status_date'])->format('d-m-Y') . ' ' . $host['Online_status_time'];

                                            /**
                                             *  Open the dedicated database of the host from its ID to be able to retrieve additional information
                                             */
                                            $myhost->openHostDb($id);

                                            /**
                                             *  Retrieve the total number of available packages
                                             */
                                            $packagesAvailableTotal = count($myhost->getPackagesAvailable());

                                            /**
                                             *  Retrieve the total number of installed packages
                                             */
                                            $packagesInstalledTotal = count($myhost->getPackagesInstalled());

                                            /**
                                             *  If the total number of available packages retrieved previously is > $packagesCountConsideredOutdated (threshold defined by the user) then we increment $totalNotUptodate (counts the number of hosts that are not up to date in the chartjs)
                                             *  Else it's $totalUptodate that we increment.
                                             */
                                            if ($packagesAvailableTotal >= $packagesCountConsideredOutdated) {
                                                $totalNotUptodate++;
                                            } else {
                                                $totalUptodate++;
                                            }

                                            /**
                                             *  Retrieve the status of the last update request (if there is one)
                                             */
                                            $lastRequestedUpdate = $myhost->getLastRequestedUpdateStatus();

                                            /**
                                             *  Close the dedicated database of the host
                                             */
                                            $myhost->closeHostDb();

                                            /**
                                             *  Affichage des informations de l'hôte
                                             *  Print the host informations
                                             *  Here the <div> will contain all the host information, this in order to be able to search on it (input 'search a host')
                                             */ ?>
                                            <div class="host-line" hostid="<?= $id ?>" hostname="<?= $hostname ?>" os="<?= $os ?>" os_version="<?= $os_version ?>" os_family="<?= $os_family ?>" type="<?= $type ?>" kernel="<?= $kernel ?>" arch="<?= $arch ?>" agent_version="<?= $agentVersion ?>" reboot_required="<?= $rebootRequired ?>">
                                                <div>
                                                    <?php
                                                    /**
                                                     *  Linupdate agent state
                                                     */
                                                    if ($agentStatus == 'running') {
                                                        echo '<img src="/assets/icons/greencircle.png" class="icon-small" title="Linupdate agent state on the host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." />';
                                                    }
                                                    if ($agentStatus == "disabled") {
                                                        echo '<img src="/assets/icons/yellowcircle.png" class="icon-small" title="Linupdate agent state on the host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." />';
                                                    }
                                                    if ($agentStatus == "stopped") {
                                                        echo '<img src="/assets/icons/redcircle.png" class="icon-small" title="Linupdate agent state on the host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." />';
                                                    }
                                                    if ($agentStatus == "seems-stopped") {
                                                        echo '<img src="/assets/icons/redcircle.png" class="icon-small" title="Linupdate agent state on the host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." />';
                                                    }
                                                    if ($agentStatus == "unknow") {
                                                        echo '<img src="/assets/icons/graycircle.png" class="icon-small" title="Linupdate agent state on the host: unknow." />';
                                                    } ?>
                                                </div>

                                                <div>
                                                    <?php
                                                    if (preg_match('/centos/i', $os)) {
                                                        echo '<img src="/assets/icons/products/centos.png" class="icon" title="' . $os . '" />';
                                                    } elseif (preg_match('/redhat/i', $os)) {
                                                        echo '<img src="/assets/icons/products/redhat.png" class="icon" title="' . $os . '" />';
                                                    } elseif (preg_match('/debian/i', $os)) {
                                                        echo '<img src="/assets/icons/products/debian.png" class="icon" title="' . $os . '" />';
                                                    } elseif (preg_match('/ubuntu|mint/i', $os)) {
                                                        echo '<img src="/assets/icons/products/ubuntu.png" class="icon" title="' . $os . '" />';
                                                    } else {
                                                        echo '<img src="/assets/icons/products/tux.png" class="icon" title="' . $os . '" />';
                                                    } ?>
                                                </div>

                                                <div>
                                                    <?php
                                                    /**
                                                     *  Print hostname and IP, with more infos about the host in the tooltip box
                                                     */
                                                    $tooltip  = 'Hostname: '. $hostname . '&#10;';
                                                    $tooltip .= 'IP: '. $ip . '&#10;';
                                                    $tooltip .= 'OS Family: '. ucfirst($os_family) . '&#10;';
                                                    $tooltip .= 'OS: '. ucfirst($os) . ' ' . $os_version . '&#10;';
                                                    $tooltip .= 'Kernel: '. $kernel . '&#10;';
                                                    $tooltip .= 'Arch: '. $arch . '&#10;';
                                                    $tooltip .= 'Profile: '. $profile . '&#10;';
                                                    $tooltip .= 'Env: '. $env . '&#10;'; ?>

                                                    <div class="flex flex-direction-column row-gap-4">
                                                        <span class="copy" title="<?= $tooltip ?>">
                                                            <a href="/host/<?= $id ?>" target="_blank" rel="noopener noreferrer"><?= $hostname ?></a>
                                                        </span>

                                                        <span class="copy font-size-12 lowopacity-cst" title="<?= $hostname ?> IP address">
                                                            <?= $ip ?>
                                                        </span>

                                                        <div class="host-update-status">
                                                            <?php
                                                            /**
                                                             *  Last request status
                                                             */
                                                            if (!empty($lastRequestedUpdate)) :
                                                                if ($lastRequestedUpdate['Type'] == 'packages-update') {
                                                                    $updateType = 'Packages update';
                                                                }
                                                                if ($lastRequestedUpdate['Type'] == 'general-status-update') {
                                                                    $updateType = 'Retrieving general informations';
                                                                }
                                                                if ($lastRequestedUpdate['Type'] == 'packages-status-update') {
                                                                    $updateType = 'Retrieving packages state';
                                                                }
                                                                if ($lastRequestedUpdate['Status'] == 'requested') {
                                                                    $updateStatus = '(request send)';
                                                                }
                                                                if ($lastRequestedUpdate['Status'] == 'running') {
                                                                    $updateStatus = 'running<img src="/assets/images/loading.gif" class="icon" />';
                                                                }
                                                                if ($lastRequestedUpdate['Status'] == 'error') {
                                                                    $updateStatus = 'has failed';
                                                                }
                                                                /**
                                                                 *  If the update request has been made several days ago or has been made +10min ago then we display the message in yellow, the remote host may not have received or processed the request
                                                                 */
                                                                if ($lastRequestedUpdate['Status'] == 'requested' or $lastRequestedUpdate['Status'] == 'running') {
                                                                    if ($lastRequestedUpdate['Date'] != DATE_YMD or $lastRequestedUpdate['Time'] <= date('H:i:s', strtotime(date('H:i:s') . ' - 10 minutes'))) {
                                                                        echo '<span class="yellowtext" title="The request does not seem to have been taken into account by the host (requested on ' . DateTime::createFromFormat('Y-m-d', $lastRequestedUpdate['Date'])->format('d-m-Y') . ' ' . $lastRequestedUpdate['Time'] . ')">' . $updateType . ' ' . $updateStatus . '</span>';
                                                                    } else {
                                                                        echo '<span class="lowopacity-cst" title="On ' . DateTime::createFromFormat('Y-m-d', $lastRequestedUpdate['Date'])->format('d-m-Y') . ' ' . $lastRequestedUpdate['Time'] . '">' . $updateType . ' ' . $updateStatus . '</span>';
                                                                    }
                                                                }
                                                                if ($lastRequestedUpdate['Status'] == 'error') {
                                                                    echo '<span class="redtext" title="On ' . DateTime::createFromFormat('Y-m-d', $lastRequestedUpdate['Date'])->format('d-m-Y') . ' ' . $lastRequestedUpdate['Time'] . '">' . $updateType . ' ' . $updateStatus . '</span>';
                                                                }
                                                            endif ?>
                                                        </div>
                                                    
                                                        <div class="host-additionnal-info">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="hostType-td">
                                                    <span class="label-black font-size-11" title="Type <?= $type ?>"><?= $type ?></span>
                                                </div>

                                                <div>
                                                    <span class="label-black font-size-11" title="Host agent version"><?= $agentVersion ?></span>
                                                </div>

                                                <div class="packagesCount-td" title="<?= $packagesInstalledTotal . ' installed package(s) on this host.'?>">
                                                    <span class="label-white font-size-11"><?= $packagesInstalledTotal ?></span>
                                                </div>

                                                <div class="packagesCount-td" title="<?= $packagesAvailableTotal . ' available update(s) on this host.'?>">
                                                    <?php
                                                    if ($packagesAvailableTotal >= $packagesCountConsideredCritical) {
                                                        echo '<span class="label-white font-size-11 bkg-red">' . $packagesAvailableTotal . '</span>';
                                                    } elseif ($packagesAvailableTotal >= $packagesCountConsideredOutdated) {
                                                        echo '<span class="label-white font-size-11 bkg-yellow">' . $packagesAvailableTotal . '</span>';
                                                    } else {
                                                        echo '<span class="label-white font-size-11">' . $packagesAvailableTotal . '</span>';
                                                    } ?>
                                                </div>

                                                <?php
                                                if (IS_ADMIN) : ?>
                                                    <div class="text-right margin-right-15" title="Select <?= $hostname ?>">
                                                        <input type="checkbox" class="js-host-checkbox verylowopacity pointer" name="checkbox-host[]" group="<?= $group['Name'] ?>" value="<?= $id ?>">
                                                    </div>
                                                    <?php
                                                endif ?>
                                            </div>
                                            <?php
                                        endforeach; ?>
                                    </div>
                                    <?php
                                else :
                                    echo '<div class="hosts-table-empty"><p class="lowopacity-cst">(empty)</p></div>';
                                endif ?>
                            </div>
                            <?php
                        endforeach ?>
                    </div>
                    <?php
                endif ?>
            </div>
        </div>
        <?php
    endif;

    /**
     *  Export hosts up to date and not up to date counters to be used in the chartjs
     */
    define('HOSTS_TOTAL_UPTODATE', $totalUptodate);
    define('HOSTS_TOTAL_NOT_UPTODATE', $totalNotUptodate); ?>
</section>
