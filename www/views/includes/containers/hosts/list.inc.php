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
            
                            <div class="hosts-group-container div-generic-blue veil-on-reload">
                                <?php
                                /**
                                 *  Print the group name except if it's the Default group
                                 */
                                if ($group['Name'] == 'Default') {
                                    $groupName = 'Ungrouped';
                                } else {
                                    $groupName = $group['Name'];
                                }

                                /**
                                 *  Count number of hosts in the group
                                 */
                                $hostsCount = count($hostsList);

                                /**
                                 *  Generate count message
                                 */
                                if ($hostsCount < 2) {
                                    $countMessage = $hostsCount . ' host';
                                } else {
                                    $countMessage = $hostsCount . ' hosts';
                                } ?>

                                <div class="flex justify-space-between">
                                    <div>
                                        <p class="font-size-16"><?= $groupName ?></p>
                                        <p class="lowopacity-cst"><?= $countMessage ?></p>
                                    </div>
                                </div>

                                <?php
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
                                            $hostDb->openHostDb($id);

                                            /**
                                             *  Retrieve the total number of available packages
                                             */
                                            $packagesAvailableTotal = count($hostDb->getPackagesAvailable());

                                            /**
                                             *  Retrieve the total number of installed packages
                                             */
                                            $packagesInstalledTotal = count($hostDb->getPackagesInstalled());

                                            /**
                                             *  Retrieve the last pending request (if there is one)
                                             */
                                            $lastPendingRequest = $myhost->getLastPendingRequest($id);

                                            /**
                                             *  Close the dedicated database of the host
                                             */
                                            $hostDb->closeHostDb();

                                            /**
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
                                                            <a href="/host/<?= $id ?>" class="wordbreakall" target="_blank" rel="noopener noreferrer"><?= $hostname ?></a>
                                                        </span>

                                                        <span class="copy font-size-12 lowopacity-cst" title="<?= $hostname ?> IP address">
                                                            <?= $ip ?>
                                                        </span>

                                                        <div class="host-update-status">
                                                            <?php
                                                            /**
                                                             *  Last request status
                                                             *  Ignore it if the request was a 'disconnect' request
                                                             */
                                                            if (!empty($lastPendingRequest) and $lastPendingRequest['Request'] != 'disconnect') :
                                                                if ($lastPendingRequest['Request'] == 'request-general-infos') {
                                                                    $requestTitle = 'Requested the host to send its general informations';
                                                                    $shortRequestTitle = 'General informations';
                                                                }
                                                                if ($lastPendingRequest['Request'] == 'request-packages-infos') {
                                                                    $requestTitle = 'Requested the host to send its packages informations';
                                                                    $shortRequestTitle = 'Packages informations';
                                                                }
                                                                if ($lastPendingRequest['Request'] == 'update-all-packages') {
                                                                    $requestTitle = 'Requested the host to update all of its packages';
                                                                    $shortRequestTitle = 'Update all packages';
                                                                }

                                                                if ($lastPendingRequest['Status'] == 'new') {
                                                                    $icon = '<span class="yellowtext">⧖</span>';
                                                                    $requestStatus = 'pending';
                                                                    $textColor = 'yellowtext';
                                                                }
                                                                if ($lastPendingRequest['Status'] == 'sent') {
                                                                    $icon = '<span class="yellowtext">⧖</span>';
                                                                    $requestStatus = 'sent';
                                                                    $textColor = 'yellowtext';
                                                                }
                                                                if ($lastPendingRequest['Status'] == 'received') {
                                                                    $icon = '<span class="yellowtext">⧖</span>';
                                                                    $requestStatus = 'received';
                                                                    $textColor = 'yellowtext';
                                                                }
                                                                if ($lastPendingRequest['Status'] == 'canceled') {
                                                                    $icon = '<span>✕</span>';
                                                                    $requestStatus = 'canceled';
                                                                    $textColor = 'lowopacity-cst';
                                                                }
                                                                if ($lastPendingRequest['Status'] == 'failed') {
                                                                    $icon = '<span class="redtext">✕</span>';
                                                                    $requestStatus = 'failed';
                                                                    $textColor = 'redtext';
                                                                }
                                                                if ($lastPendingRequest['Status'] == 'completed') {
                                                                    $icon = '<span class="greentext">✔</span>';
                                                                    $requestStatus = 'completed';
                                                                    $textColor = 'lowopacity-cst';
                                                                }

                                                                if (!empty($lastPendingRequest['Info_json'])) {
                                                                    /**
                                                                     *  If the request was a packages update, retrieve more informations from the summary (number of packages updated)
                                                                     */
                                                                    if ($lastPendingRequest['Request'] == 'update-all-packages' and !empty($lastPendingRequest['Info_json'])) {
                                                                        $summary = json_decode($lastPendingRequest['Info_json'], true);

                                                                        // If there was no packages to update
                                                                        if ($summary['update']['status'] == 'nothing-to-do') {
                                                                            $requestInfo = 'no packages to update';
                                                                        }

                                                                        // If there was packages to update, retrieve the number of packages updated
                                                                        if ($summary['update']['status'] == 'done' or $summary['update']['status'] == 'failed') {
                                                                            $successCount = $summary['update']['success']['count'];
                                                                            $failedCount = $summary['update']['failed']['count'];

                                                                            $requestInfo = $successCount . ' package(s) updated, ' . $failedCount . ' failed';
                                                                        }
                                                                    } else {
                                                                        $requestInfo = $lastPendingRequest['Info'];
                                                                    }
                                                                }

                                                                /**
                                                                 *  Only print the request title if it was send less than 1h ago
                                                                 */
                                                                if (strtotime($lastPendingRequest['Date'] . ' ' . $lastPendingRequest['Time']) >= strtotime(date('Y-m-d H:i:s') . ' - 1 hour')) {
                                                                    if (!empty($requestInfo)) {
                                                                        echo '<p class="' . $textColor . '" title="' . $requestTitle . '">' . $icon . ' ' . $shortRequestTitle . ' - ' . $requestInfo . '</p>';
                                                                    } else {
                                                                        echo '<p class="' . $textColor . '" title="' . $requestTitle . '">' . $icon . ' ' . $shortRequestTitle . ' - ' . $requestStatus . '</p>';
                                                                    }
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
    endif; ?>
</section>
