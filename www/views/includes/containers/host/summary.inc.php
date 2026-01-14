<?php
use \Controllers\User\Permission\Host as HostPermission; ?>

<section class="section-main reloadable-container" container="host/summary">
    <div id="title-button-div">
        <h3><?= strtoupper($hostname) ?></h3>

        <div class="flex justify-space-between">
            <?php
            if (HostPermission::allowedAction('reset')) : ?>
                <div id="host-reset-btn" class="slide-btn-yellow" host-id="<?= $id ?>" title="Reset host informations">
                    <img src="/assets/icons/update.svg">
                    <span>Reset</span>
                </div>
                <?php
            endif;

            if (HostPermission::allowedAction('delete')) : ?>
                <div id="host-delete-btn" class="slide-btn-red" host-id="<?= $id ?>" title="Delete host">
                    <img src="/assets/icons/delete.svg">
                    <span>Delete</span>
                </div>
                <?php
            endif ?>
        </div>
    </div>

    <div class="grid grid-rfr-1-2 row-gap-30 justify-space-between align-item-center div-generic-blue padding-left-30 padding-right-30">
        <div class="grid grid-rfr-1-3 column-gap-30 row-gap-15">
            <div>
                <h6 class="margin-top-0">IP</h6>
                <p class="mediumopacity-cst copy"><?= $ip ?></p>
            </div>

            <div>
                <h6 class="margin-top-0">CPU</h6>
                <p class="mediumopacity-cst copy">
                    <?php
                    if (!empty($cpu)) {
                        echo $cpu;
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>
            </div>

            <div>
                <h6 class="margin-top-0">UPTIME</h6>
                <p class="mediumopacity-cst copy">
                    <?php
                    if (!empty($uptime)) {
                        echo $uptime;
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>
            </div>

            <div>
                <h6 class="margin-top-0">OS</h6>
                <div class="flex align-item-center column-gap-5">
                    <?php
                    if (!empty($os)) {
                        echo \Controllers\Utils\Generate\Html\Icon::os($os);
                    } ?>

                    <p class="mediumopacity-cst">
                        <?php
                        if (!empty($os)) {
                            echo $os;
                            if (!empty($osVersion)) {
                                echo ' ' . $osVersion;
                            }
                        } else {
                            echo 'Unknown';
                        } ?>
                    </p>
                </div>
            </div>

            <div>
                <h6 class="margin-top-0">RAM</h6>
                <p class="mediumopacity-cst copy">
                    <?php
                    if (!empty($ram)) {
                        echo $ram;
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>
            </div>

            <div>
                <h6 class="margin-top-0">REBOOT REQUIRED</h6>
                <p class="mediumopacity-cst copy">
                    <?php
                    if ($rebootRequired == 'true') {
                        echo 'Yes';
                    } else {
                        echo 'No';
                    } ?>
                </p>
            </div>

            <div>
                <h6 class="margin-top-0">PROFILE</h6>
                <p class="mediumopacity-cst copy">
                    <?php
                    if (!empty($profile)) {
                        echo $profile;
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>
            </div>

            <div>
                <h6 class="margin-top-0">ARCHITECTURE</h6>
                <p class="mediumopacity-cst copy">
                    <?php
                    if (!empty($arch)) {
                        echo $arch;
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>
            </div>

            <div>
                <h6 class="margin-top-0">AGENT STATUS</h6>
                <div class="flex align-item-center column-gap-5">
                    <?php
                    if ($agentStatus == 'running') {
                        $status = 'running';
                        $statusTitle = 'Running';
                        $icon = 'check.svg';
                    }
                    if ($agentStatus == "disabled") {
                        $status = 'disabled';
                        $statusTitle = 'Disabled';
                        $icon = 'warning-red.svg';
                    }
                    if ($agentStatus == "stopped") {
                        $status = 'stopped';
                        $statusTitle = 'Stopped';
                        $icon = 'warning-red.svg';
                    }
                    if ($agentStatus == "seems-stopped") {
                        $status = 'seems-stopped';
                        $statusTitle = 'Seems stopped';
                        $icon = 'warning-red.svg';
                    }
                    if ($agentStatus == "unknown") {
                        $status = 'unknown';
                        $statusTitle = 'Unknown';
                        $icon = 'warning-red.svg';
                    }

                    echo '<img src="/assets/icons/' . $icon . '" class="icon" title="Agent state on this host: ' . $status . ' (' . $agentLastSendStatusMsg . ')." />';
                    echo '<p class="mediumopacity-cst">' . $statusTitle . '</p>'; ?>
                </div>
            </div>

            <div>
                <h6 class="margin-top-0">ENVIRONMENT</h6>
                <p class="copy">
                    <?php
                    if (!empty($env)) {
                        echo \Controllers\Utils\Generate\Html\Label::envtag($env);
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>
            </div>

            <div>
                <h6 class="margin-top-0">KERNEL</h6>
                <p class="mediumopacity-cst copy">
                    <?php
                    if (!empty($kernel)) {
                        echo $kernel;
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>
            </div>

            <div>
                <h6 class="margin-top-0">AGENT VERSION</h6>
                <p class="mediumopacity-cst copy">
                    <?php
                    if (!empty($agentVersion)) {
                        echo $agentVersion;
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>
            </div>

            <div>
                <h6 class="margin-top-0">TYPE</h6>
                <p class="mediumopacity-cst copy">
                    <?php
                    if (!empty($type)) {
                        echo $type;
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>
            </div>
        </div>

        <div class="echart-container">
            <div id="host-packages-status-chart-loading" class="echart-loading">
                <img src="/assets/icons/loading.svg" class="icon-np" />
            </div>

            <div id="host-packages-status-chart" class="echart"></div>
        </div>

        <script>
            $(document).ready(function(){
                new EChart('line', 'host-packages-status-chart');
            });
        </script>
    </div>
</section>
