<?php
use \Controllers\User\Permission\Host as HostPermission; ?>

<section class="section-main reloadable-container" container="hosts/list">
    <div class="flex justify-space-between margin-top-50 margin-bottom-40">
        <h3 class="margin-0">HOSTS</h3>

        <div>
            <?php
            if ($totalHosts > 0) :
                // Default to compact view if no cookie is set
                $currentView = isset($_COOKIE['hosts/compact-view']) ? $_COOKIE['hosts/compact-view'] : "1";

                if ($currentView == "1") : // Compact view active ?>
                    <div id="compact-view-btn" class="slide-btn mediumopacity" title="Switch to full view">
                        <img src="/assets/icons/view.svg" />
                        <span>Full view</span>
                    </div>
                    <?php
                else : // Full view active ?>
                    <div id="compact-view-btn" class="slide-btn mediumopacity" title="Switch to compact view">
                        <img src="/assets/icons/view-off.svg" />
                        <span>Compact view</span>
                    </div>
                    <?php
                endif;
            endif;

            if (HostPermission::allowedAction('edit-profiles')) : ?>
                <div class="slide-btn get-panel-btn mediumopacity" panel="hosts/profiles" title="Manage hosts profiles">
                    <img src="/assets/icons/profile.svg" />
                    <span>Profiles</span>
                </div>
                <?php
            endif;

            if (HostPermission::allowedAction('edit-groups') and $totalHosts > 0) : ?>
                <div class="slide-btn get-panel-btn mediumopacity" panel="hosts/groups/list" title="Manage hosts groups">
                    <img src="/assets/icons/folder.svg" />
                    <span>Groups</span>
                </div>
                <?php
            endif;

            if (HostPermission::allowedAction('edit-settings') and $totalHosts > 0) : ?>
                <div class="slide-btn get-panel-btn mediumopacity" panel="hosts/settings" title="Edit display settings">
                    <img src="/assets/icons/cog.svg" />
                    <span>Settings</span>
                </div>
                <?php
            endif ?>      
        </div>
    </div>

    <?php
    if ($totalHosts == 0) : ?>
        <div class="empty-state">
            <p class="empty-state-title">No host registered yet.</p>
            <p class="note">Install <b>linupdate</b> on your hosts to register them to Repomanager. This page will display dashboards and informations about the hosts and their packages (installed, available, updated...).</p>
            <div class="empty-state-actions">
                <a href="https://github.com/lbr38/linupdate" target="_blank" rel="noopener noreferrer"><button type="button" class="btn-medium-blue">Linupdate GitHub</button></a>
                <a href="https://github.com/lbr38/linupdate/wiki/Module:-reposerver#quick-setup-example" target="_blank" rel="noopener noreferrer"><button type="button" class="btn-large-tr">Linupdate quick setup</button></a>
            </div>
        </div>
        <?php
    endif;

    if ($totalHosts >= 1) : ?>
        <div>
            <div class="grid grid-rfr-1-2 justify-space-between column-gap-20 row-gap-15 margin-bottom-10">
                <div>
                    <div class="flex align-item-center column-gap-5 margin-bottom-5">
                        <h6 class="margin-top-0 search-host-tooltip">SEARCH HOST</h6>
                        <img src="/assets/icons/info.svg" class="icon-small icon-np lowopacity search-host-tooltip" />
                    </div>
                    
                    <input type="text" id="search-host-input" onkeyup="HostSearch.search()" autocomplete="off" placeholder="Hostname, IP, type, ..." title="Search a host by hostname, IP" />
                </div>

                <div>
                    <div class="flex align-item-center column-gap-5 margin-bottom-5">
                        <h6 class="margin-top-0 search-package-tooltip">SEARCH PACKAGE</h6>
                        <img src="/assets/icons/info.svg" class="icon-small icon-np lowopacity search-package-tooltip" />
                    </div>

                    <input type="text" id="search-package-input" onkeyup="HostSearch.searchPackage()" autocomplete="off" placeholder="name=package name" />
                </div>
            </div>

            <div class="flex justify-end margin-bottom-10 margin-right-20">
                <div id="select-all-hosts" class="flex align-item-center column-gap-8 mediumopacity pointer">
                    <p>Select all</p>
                    <input type="checkbox" title="Select all hosts" />
                </div>
            </div>
        </div>

        <div id="hosts-search" class="align-item-center column-gap-5 hide">
            <img src="/assets/icons/loading.svg" class="icon-np" />
            <p class="note">Searching...</p>
        </div>

        <div id="hosts">
            <?php
            if (!empty($hostGroupsList)) : ?>
                <div class="groups-container">
                    <?php
                    foreach ($hostGroupsList as $group) :
                        // Retrieve the list of hosts in the group
                        $hostsList = $hostListingController->getByGroup($group['Name']);

                        // If it's the default group 'Default' and it has no host then we ignore its display
                        if ($group['Name'] == "Default" and empty($hostsList)) {
                            continue;
                        } ?>
                        <input type='hidden' name='groupname' value='<?=$group['Name']?>'>
        
                        <div class="hosts-group-container veil-on-reload">
                            <?php
                            // Print the group name except if it's the Default group
                            if ($group['Name'] == 'Default') {
                                $groupName = 'Ungrouped';
                            } else {
                                $groupName = $group['Name'];
                            }

                            // Count number of hosts in the group
                            $hostsCount = count($hostsList);

                            // Generate count message
                            if ($hostsCount < 2) {
                                $countMessage = $hostsCount . ' host';
                            } else {
                                $countMessage = $hostsCount . ' hosts';
                            } ?>

                            <div class="hosts-group-header">
                                <div class="hosts-group-header-left">
                                    <img src="/assets/icons/folder.svg" class="icon-np lowopacity-cst" />
                                    <span class="hosts-group-header-name"><?= $groupName ?></span>
                                    <span class="hosts-group-header-count"><?= $hostsCount ?></span>
                                </div>

                                <div>
                                    <input class="select-group-hosts-checkbox lowopacity pointer" type="checkbox" group="<?= $group['Name'] ?>" title="Select all" >
                                </div>
                            </div>

                            <?php
                            // Print the hosts of the group
                            if (!empty($hostsList)) : ?>
                                <div class="hosts-group-content">
                                    <div class="flex flex-direction-column row-gap-10">
                                
                                    <?php
                                    // Process the hosts list
                                    foreach ($hostsList as $host) :
                                        $id = $host['Id'];
                                        $hostname = 'unknown';
                                        $ip = 'unknown';
                                        $os = 'unknown';
                                        $osVersion = 'unknown';
                                        $osFamily = 'unknown';
                                        $type = 'unknown';
                                        $kernel = 'unknown';
                                        $arch = 'unknown';
                                        $profile = 'unknown';
                                        $env = 'unknown';
                                        $agentVersion = 'unknown';
                                        $rebootRequired = 'unknown';
                                        $agentStatus = 'unknown';
                                        $requestInfo = null;
                                        $responseDetails = null;

                                        if (!empty($host['Hostname'])) {
                                            $hostname = $host['Hostname'];
                                        }
                                        if (!empty($host['Ip'])) {
                                            $ip = $host['Ip'];
                                        }
                                        if (!empty($host['Os'])) {
                                            $os = $host['Os'];
                                        }
                                        if (!empty($host['Os_version'])) {
                                            $osVersion = $host['Os_version'];
                                        }
                                        if (!empty($host['Os_family'])) {
                                            $osFamily = $host['Os_family'];
                                        }
                                        if (!empty($host['Type'])) {
                                            $type = $host['Type'];
                                        }
                                        if (!empty($host['Kernel'])) {
                                            $kernel = $host['Kernel'];
                                        }
                                        if (!empty($host['Arch'])) {
                                            $arch = $host['Arch'];
                                        }
                                        if (!empty($host['Profile'])) {
                                            $profile = $host['Profile'];
                                        }
                                        if (!empty($host['Env'])) {
                                            $env = $host['Env'];
                                        }
                                        if (!empty($host['Linupdate_version'])) {
                                            $agentVersion = $host['Linupdate_version'];
                                        }
                                        if (!empty($host['Reboot_required'])) {
                                            $rebootRequired = $host['Reboot_required'];
                                        }
                                        if (!empty($host['Online_status'])) {
                                            $agentStatus = $host['Online_status'];
                                        }

                                        // Check if the last time the agent reported its status is less than 1h (and 10min of "margin")
                                        if ($host['Online_status_date'] != DATE_YMD or $host['Online_status_time'] <= date('H:i:s', strtotime(date('H:i:s') . ' - 70 minutes'))) {
                                            $agentStatus = 'seems-stopped';
                                        }

                                        // Last known status message
                                        $agentLastSendStatusMsg = 'state on ' . DateTime::createFromFormat('Y-m-d', $host['Online_status_date'])->format('d-m-Y') . ' ' . $host['Online_status_time'];

                                        // Open the dedicated database of the host from its ID to be able to retrieve additional information
                                        $hostPackageController = new \Controllers\Host\Package\Package($id);

                                        // Retrieve the total number of available packages
                                        $packagesAvailableTotal = count($hostPackageController->getAvailable());

                                        // Retrieve the total number of installed packages
                                        $packagesInstalledTotal = count($hostPackageController->getInstalled());

                                        // Retrieve the last pending request (if there is one)
                                        $lastPendingRequest = $hostRequestController->getLastPendingRequest($id);

                                        unset($hostPackageController);

                                        // Print the host informations
                                        // Here the <div> will contain all the host informations in order to be able to search on it (input 'search a host')
                                        $hostAccent = $agentStatus === 'running' ? 'host-accent-green' : 'host-accent-red'; ?>

                                        <div class="host-line <?= $hostAccent ?>" hostid="<?= $id ?>" hostname="<?= $hostname ?>" os="<?= $os ?>" os_version="<?= $osVersion ?>" os_family="<?= $osFamily ?>" type="<?= $type ?>" kernel="<?= $kernel ?>" arch="<?= $arch ?>" profile="<?= $profile ?>" env="<?= $env ?>" agent_version="<?= $agentVersion ?>" reboot_required="<?= $rebootRequired ?>" title="<?= $agentStatus !== 'running' ? 'Agent ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')' : 'Agent is running' ?>">
                                            <div class="width-100 overflowx-auto overflowy-hidden">
                                                <?php
                                                if ($compactView) : ?>
                                                    <div class="grid hosts-compact-view">
                                                        <div class="flex align-item-center column-gap-10">
                                                            <?= \Controllers\Utils\Generate\Html\Icon::os($os); ?>

                                                            <div>
                                                                <p title="Hostname" class="copy">
                                                                    <a href="/host/<?= $id ?>" class="wordbreakall" target="_blank" rel="noopener noreferrer">
                                                                        <b><?= $hostname ?></b>
                                                                    </a>
                                                                </p>
                                                                <p class="font-size-12 mediumopacity-cst copy" title="IP address"><?= $ip ?></p>
                                                            </div>
                                                        </div>

                                                        <div class="grid hosts-compact-view-subgrid align-item-center">
                                                            <div class="flex flex-direction-column row-gap-2">
                                                                <p class="font-size-13" title="OS"><?= ucfirst($os) . ' ' . $osVersion ?></p>
                                                                <p class="font-size-10 font-family-archivo mediumopacity-cst" title="Type"><b><?= strtoupper($type) ?></b></p>
                                                            </div>

                                                            <div>
                                                                <a href="/host/<?= $id ?>" target="_blank" rel="noopener noreferrer">
                                                                    <div class="flex align-item-center column-gap-8">
                                                                        <img src="/assets/icons/package.svg" class="icon-np" />
                                                                        <p class="label-white font-size-12" title="<?= $packagesInstalledTotal . ' package(s) installed on this host' ?>"><?= $packagesInstalledTotal ?></p>
                                                                        <?php
                                                                        if ($packagesAvailableTotal > 0) {
                                                                            $class = '';
                                                                            if ($packagesAvailableTotal >= $packagesCountConsideredCritical) {
                                                                                $class = 'label-red';
                                                                            } elseif ($packagesAvailableTotal >= $packagesCountConsideredOutdated) {
                                                                                $class = 'label-yellow';
                                                                            }

                                                                            echo '<p class="' . $class . ' font-size-11" title="' . $packagesAvailableTotal . ' package update(s) available on this host">' . $packagesAvailableTotal . '</p>';
                                                                        } ?>
                                                                    </div>
                                                                </a>
                                                            </div>

                                                            <div class="flex align-item-center justify-end">
                                                                <?php
                                                                if ($rebootRequired == 'true') {
                                                                    echo '<img src="/assets/icons/warning.svg" class="icon-np" title="Reboot required" />';
                                                                } ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php
                                                endif;

                                                if (!$compactView) : ?>
                                                    <div class="flex align-item-center column-gap-10 margin-bottom-15">
                                                        <?= \Controllers\Utils\Generate\Html\Icon::os($os); ?>
                                                        <div>
                                                            <p class="copy">
                                                                <a href="/host/<?= $id ?>" class="wordbreakall" target="_blank" rel="noopener noreferrer">
                                                                    <b><?= $hostname ?></b>
                                                                </a>
                                                            </p>
                                                            <p class="font-size-12 mediumopacity-cst copy"><?= $ip ?></p>
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-rfr-1-5 row-gap-20 column-gap-20">
                                                        <div>
                                                            <h6 class="margin-top-0">OS</h6>
                                                            <p class="mediumopacity-cst copy"><?= ucfirst($os) . ' ' . $osVersion ?></p>
                                                        </div>
                                                        
                                                        <div>
                                                            <h6 class="margin-top-0">TYPE</h6>
                                                            <p class="mediumopacity-cst copy"><?= $type ?></p>
                                                        </div>

                                                        <div>
                                                            <h6 class="margin-top-0">KERNEL</h6>
                                                            <p class="mediumopacity-cst copy"><?= $kernel ?></p>
                                                        </div>

                                                        <div>
                                                            <h6 class="margin-top-0">ARCH</h6>
                                                            <p class="mediumopacity-cst copy"><?= $arch ?></p>
                                                        </div>

                                                        <div>
                                                            <h6 class="margin-top-0">PROFILE</h6>
                                                            <p class="mediumopacity-cst copy"><?= $profile ?></p>
                                                        </div>

                                                        <div>
                                                            <h6 class="margin-top-0">ENVIRONMENT</h6>
                                                            <p class="copy">
                                                                <?= \Controllers\Utils\Generate\Html\Label::envtag($env) ?>
                                                            </p>
                                                        </div>

                                                        <div>
                                                            <h6 class="margin-top-0">AGENT VERSION</h6>
                                                            <p class="copy"><span class="label-white"><?= $agentVersion ?></span></p>
                                                        </div>

                                                        <div>
                                                            <h6 class="margin-top-0">REBOOT REQUIRED</h6>
                                                            <p class="flex align-item-center column-gap-5">
                                                                <?php
                                                                if ($rebootRequired == 'true') {
                                                                    echo '<img src="/assets/icons/warning.svg" class="icon-np" />';
                                                                    echo '<span>Yes</span>';
                                                                } else {
                                                                    echo '<span class="mediumopacity-cst">No</span>';
                                                                } ?>
                                                            </p>
                                                        </div>

                                                        <div>
                                                            <h6 class="margin-top-0"><?= $layoutPackagesTitle ?> INSTALLED</h6>
                                                            <p title="<?= $packagesInstalledTotal . ' package(s) installed on this host' ?>">
                                                                <span class="label-white"><?= $packagesInstalledTotal ?></span>
                                                            </p>
                                                        </div>

                                                        <div>
                                                            <h6 class="margin-top-0"><?= $layoutPackagesTitle ?> AVAILABLE</h6>
                                                            <p title="<?= $packagesAvailableTotal . ' package update(s) available on this host' ?>">
                                                                <?php
                                                                if ($packagesAvailableTotal >= $packagesCountConsideredCritical) {
                                                                    echo '<span class="label-red">' . $packagesAvailableTotal . '</span>';
                                                                } elseif ($packagesAvailableTotal >= $packagesCountConsideredOutdated) {
                                                                    echo '<span class="label-yellow">' . $packagesAvailableTotal . '</span>';
                                                                } else {
                                                                    echo '<span class="label-white">' . $packagesAvailableTotal . '</span>';
                                                                } ?>    
                                                            </p>
                                                        </div>
                                                    </div>
                                                
                                                    <div>
                                                        <?php
                                                        // Last request status
                                                        if (!empty($lastPendingRequest)) :
                                                            // Retrieve and decode JSON data
                                                            $requestJson = json_decode($lastPendingRequest['Request'], true);

                                                            // Request name
                                                            $request = $requestJson['request'];

                                                            // Request data
                                                            if (isset($requestJson['data'])) {
                                                                $requestData = $requestJson['data'];
                                                            }

                                                            // Only if not a 'disconnect' request
                                                            if ($request != 'disconnect') :
                                                                // Response data
                                                                if (!empty($lastPendingRequest['Response_json'])) {
                                                                    $responseJson = json_decode($lastPendingRequest['Response_json'], true);
                                                                }

                                                                // Request status
                                                                if ($lastPendingRequest['Status'] == 'new') {
                                                                    $requestStatus = 'Pending';
                                                                    $requestStatusIcon = 'pending.svg';
                                                                }
                                                                if ($lastPendingRequest['Status'] == 'sent') {
                                                                    $requestStatus = 'Sent';
                                                                    $requestStatusIcon = 'pending.svg';
                                                                }
                                                                if ($lastPendingRequest['Status'] == 'running') {
                                                                    $requestStatus = 'Running';
                                                                    $requestStatusIcon = 'loading.svg';
                                                                }
                                                                if ($lastPendingRequest['Status'] == 'canceled') {
                                                                    $requestStatus = 'Canceled';
                                                                    $requestStatusIcon = 'warning-red.svg';
                                                                }
                                                                if ($lastPendingRequest['Status'] == 'failed') {
                                                                    $requestStatus = 'Failed';
                                                                    $requestStatusIcon = 'error.svg';
                                                                }
                                                                if ($lastPendingRequest['Status'] == 'completed') {
                                                                    $requestStatus = 'Completed';
                                                                    $requestStatusIcon = 'check.svg';
                                                                }

                                                                // Request info
                                                                $requestInfo = $lastPendingRequest['Info'];

                                                                // Request title
                                                                if ($request == 'request-general-infos') {
                                                                    $requestTitle = 'Requested the host to send its general informations';
                                                                    $requestTitleShort = 'Request general information';
                                                                }
                                                                if ($request == 'request-packages-infos') {
                                                                    $requestTitle = 'Requested the host to send its packages informations';
                                                                    $requestTitleShort = 'Request package information';
                                                                }
                                                                if ($request == 'request-packages-update') {
                                                                    $requestTitle = 'Request to install a list of package(s)';
                                                                    $requestTitleShort = 'Request to update a list of package(s)';

                                                                    if (!empty($requestJson['packages'])) {
                                                                        $requestDetails = count($requestJson['packages']) . ' package(s) to install';
                                                                    }
                                                                }
                                                                if ($request == 'request-all-packages-update') {
                                                                    $requestTitle = 'Requested the host to update all of its packages';
                                                                    $requestTitleShort = 'Request to update all packages';

                                                                    if (!empty($responseJson)) {
                                                                        // If there was no packages to update
                                                                        if ($responseJson['update']['status'] == 'nothing-to-do') {
                                                                            $responseDetails = 'No packages to update';
                                                                        }

                                                                        // If there was packages to update, retrieve the number of packages updated
                                                                        if ($responseJson['update']['status'] == 'done' or $responseJson['update']['status'] == 'failed') {
                                                                            $successCount = $responseJson['update']['success']['count'];
                                                                            $failedCount  = $responseJson['update']['failed']['count'];

                                                                            // If the update was successful
                                                                            if ($responseJson['update']['status'] == 'done') {
                                                                                $requestStatus = 'Successful';
                                                                                $requestStatusIcon = 'check.svg';
                                                                            }

                                                                            // If the update failed
                                                                            if ($responseJson['update']['status'] == 'failed') {
                                                                                $requestStatus = 'Failed with errors';
                                                                                $requestStatusIcon = 'error.svg';
                                                                            }

                                                                            // If there was packages updated AND packages failed
                                                                            if ($successCount >= 1 and $failedCount >= 1) {
                                                                                $requestStatus = 'Partial success';
                                                                                $requestStatusIcon = 'warning.svg';
                                                                            }

                                                                            // If there was no packages updated AND packages failed
                                                                            if ($successCount == 0 and $failedCount >= 1) {
                                                                                $requestStatus = 'Failed';
                                                                                $requestStatusIcon = 'error.svg';
                                                                            }

                                                                            // Build a short info message
                                                                            $responseDetails = $successCount . ' package(s) updated, ' . $failedCount . ' failed';

                                                                            // Retrieve the list of packages updated
                                                                            // $successPackages = $responseJson['update']['success']['packages'];

                                                                            // Retrieve the list of packages failed
                                                                            // $failedPackages = $responseJson['update']['failed']['packages'];
                                                                        }
                                                                    }
                                                                }

                                                                // Only print the request title if it was executed less than 1h ago
                                                                if (strtotime($lastPendingRequest['Date'] . ' ' . $lastPendingRequest['Time']) >= strtotime(date('Y-m-d H:i:s') . ' - 1 hour')) : ?>
                                                                    <h6>LAST REQUEST</h6>
                                                                    <div class="flex align-item-center column-gap-5">
                                                                        <?php
                                                                        if (!empty($requestStatusIcon)) {
                                                                            if (str_ends_with($requestStatusIcon, '.svg')) {
                                                                                echo '<img src="/assets/icons/' . $requestStatusIcon . '" class="icon-np" title="' . $requestStatus . '" />';
                                                                            } else {
                                                                                echo '<span class="' . $requestStatusIcon . '" title="' . $requestStatus . '"></span> ';
                                                                            }
                                                                        } ?>
                                                                        <p class="mediumopacity-cst" title="<?= $requestTitle ?>">
                                                                            <?php
                                                                            echo $requestTitleShort;

                                                                            if (!empty($requestInfo)) {
                                                                                echo ' - ' . $requestInfo;
                                                                            }

                                                                            if (!empty($responseDetails)) {
                                                                                echo ' - ' . $responseDetails;
                                                                            } ?>
                                                                        </p>                                                                            
                                                                    </div>
                                                                    <?php
                                                                endif;
                                                            endif;
                                                        endif ?>
                                                    </div>
                                                    <?php
                                                endif ?>

                                                <div class="host-additionnal-info"></div>
                                            </div>

                                            <input type="checkbox" class="js-host-checkbox" name="checkbox-host[]" group="<?= $group['Name'] ?>" value="<?= $id ?>" title="Select <?= $hostname ?>">
                                        </div>
                                        <?php
                                    endforeach; ?>
                                    </div>
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
        <?php
    endif; ?>
</section>
