<section class="section-main reloadable-container" container="host/summary">
    <div id="title-button-div">
        <h3><?= strtoupper($hostname) ?></h3>

        <?php
        if (IS_ADMIN) : ?>
            <div class="flex justify-space-between">
                <div id="host-reset-btn" class="slide-btn-yellow" host-id="<?= $id ?>" title="Reset host informations">
                    <img src="/assets/icons/update.svg">
                    <span>Reset</span>
                </div>

                <div id="host-delete-btn" class="slide-btn-red" host-id="<?= $id ?>" title="Delete host">
                    <img src="/assets/icons/delete.svg">
                    <span>Delete</span>
                </div>
            </div>
            <?php
        endif ?>
    </div>

    <div class="grid grid-2 justify-space-between align-item-center div-generic-blue padding-left-30 padding-right-30">
        <div class="grid grid-3 column-gap-30">
            <div>
                <h6 class="margin-top-0">IP</h6>
                <p class="mediumopacity-cst copy"><?= $ip ?></p>

                <h6>OS</h6>
                <div class="flex align-item-center column-gap-5">
                    <?php
                    if (!empty($os)) {
                        echo \Controllers\Common::printOsIcon($os);
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

                <h6>PROFILE</h6>
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
                <p class="copy">
                    <?php
                    if (!empty($env)) {
                        echo Controllers\Common::envtag($env);
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>

                <h6>TYPE</h6>
                <p class="mediumopacity-cst copy">
                    <?php
                    if (!empty($type)) {
                        echo $type;
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>

                <h6>ARCHITECTURE</h6>
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
                <h6 class="margin-top-0">KERNEL</h6>
                <p class="mediumopacity-cst copy">
                    <?php
                    if (!empty($kernel)) {
                        echo $kernel;
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>

                <h6>AGENT STATUS</h6>
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

                <h6>AGENT VERSION</h6>
                <p class="mediumopacity-cst copy">
                    <?php
                    if (!empty($agentVersion)) {
                        echo $agentVersion;
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>
            </div>
        </div>

        <div>
            <div class="host-line-chart-container">
                <canvas id="packages-status-chart"></canvas>
            </div>
        </div>

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
                        borderColor: '#F32F63',
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
                        beginAtZero: true,
                        ticks: {
                            color: '#8A99AA',
                            font: {
                                size: 11,
                                family: 'Roboto'
                            },
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            padding: 15,
                            font: {
                                size: 13,
                                family: 'Roboto',
                            },
                            color: '#8A99AA'
                        },
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Packages evolution',
                            font: {
                                size: 14,
                                family: 'Roboto',
                            },
                            color: '#8A99AA',
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
    </div>
</section>
