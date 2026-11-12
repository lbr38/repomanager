<?php
use \Controllers\User\Permission\Host as HostPermission; ?>

<section class="section-main reloadable-container" container="host/summary">
    <div class="margin-top-50 margin-bottom-40">
        <div class="flex flex-wrap justify-space-between column-gap-20 row-gap-15">
            <div class="flex align-item-center column-gap-10">
                <h3 class="margin-0"><?= strtoupper($hostname) ?></h3>

                <div class="flex column-gap-8">
                    <?php
                    if ($compliance['compliant']) {
                        echo '<img src="/assets/icons/shield.svg" class="icon-np icon-medium" title="Host is compliant" />';
                    } else {
                        echo '<img src="/assets/icons/shield-warning.svg" class="icon-np icon-medium" title="Host is not compliant: ' . strtolower($compliance['reason']) . '" />';
                    } ?>
                </div>
            </div>

            <div class="flex column-gap-10 justify-space-between">
                <?php
                if (HostPermission::allowedAction('reset')) : ?>
                    <p id="host-reset-btn" class="label-yellow pointer" host-id="<?= $id ?>" title="Reset host informations">Reset</p>
                    <?php
                endif;

                if (HostPermission::allowedAction('delete')) : ?>
                    <p id="host-delete-btn" class="label-red pointer" host-id="<?= $id ?>" title="Delete host">Delete</p>
                    <?php
                endif ?>
            </div>
        </div>
    </div>

    <div class="div-generic-blue grid grid-rfr-1-6 column-gap-50 row-gap-20">
        <div>
            <h6 class="margin-top-0">HOSTNAME</h6>
            <p class="mediumopacity-cst copy"><?= $hostname ?></p>
        </div>

        <div>
            <h6 class="margin-top-0">IP</h6>
            <p class="mediumopacity-cst copy"><?= $ip ?></p>
        </div>

        <?php
        if (!empty($publicIp)) : ?>
            <div>
                <h6 class="margin-top-0">PUBLIC IP</h6>
                <p class="mediumopacity-cst copy"><?= htmlspecialchars($publicIp) ?></p>
            </div>
            <?php
        endif ?>

        <div>
            <h6 class="margin-top-0">NET. INTERFACES</h6>
            <div class="flex flex-wrap align-item-center column-gap-5 row-gap-5 margin-top-5">
                <?php
                // Skip loopback interface
                unset($network['lo']);

                if (!empty($network)) {
                    // Number of interfaces printed, the others are hidden behind a button
                    $maxInterfaces = 3;
                    $count = 0;

                    foreach ($network as $interface => $properties) {
                        $count++;
                        $interface = htmlspecialchars($interface);

                        // Properties to print in the tooltip (values are already escaped when stored)
                        // All the addresses of the interface if sent by the host, otherwise its first IPv4 and IPv6
                        if (!empty($properties['addresses']) and is_array($properties['addresses'])) {
                            $tooltip = [];

                            foreach ($properties['addresses'] as $address) {
                                $tooltip[] = [str_contains($address, ':') ? 'IPv6' : 'IPv4', $address];
                            }
                        } else {
                            $tooltip = [['IPv4', $properties['ipv4'] ?? ''], ['IPv6', $properties['ipv6'] ?? '']];
                        }

                        $tooltip[] = ['MAC', $properties['mac'] ?? ''];
                        $tooltip = array_filter($tooltip, fn ($property) => $property[1] !== ''); ?>

                        <span class="label-white<?= $count > $maxInterfaces ? ' host-network-interface-hidden hide' : '' ?>" data-tooltip-template>
                            <?= $interface ?>
                            <template>
                                <p class="tooltip-title"><?= $interface ?></p>
                                <?php
                                if (!empty($tooltip)) : ?>
                                    <div class="tooltip-properties">
                                        <?php
                                        foreach ($tooltip as [$label, $value]) : ?>
                                            <span class="tooltip-property-label"><?= $label ?></span>
                                            <span class="tooltip-property-value copy"><?= $value ?></span>
                                            <?php
                                        endforeach ?>
                                    </div>
                                    <?php
                                else : ?>
                                    <p class="lowopacity-cst">No network information available</p>
                                    <?php
                                endif ?>
                            </template>
                        </span>
                        <?php
                    }

                    if ($count > $maxInterfaces) : ?>
                        <span class="label-white lowopacity pointer host-network-interfaces-more-btn" more="+<?= $count - $maxInterfaces ?>">+<?= $count - $maxInterfaces ?></span>
                        <?php
                    endif;
                } else {
                    echo '<p class="mediumopacity-cst">Unknown</p>';
                } ?>
            </div>
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
            <h6 class="margin-top-0">ENVIRONMENT</h6>

            <div>
                <?php
                if (!empty($env)) {
                    echo \Controllers\Utils\Generate\Html\Label::envtag($env);
                } else {
                    echo '<p class="mediumopacity-cst">Unknown</p>';
                } ?>
            </div>
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
    </div>
</section>
