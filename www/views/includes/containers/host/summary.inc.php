<section class="section-main reloadable-container" container="host/summary">
    <div id="title-button-div">
        <h3><?= strtoupper($hostname) ?></h3>

        <?php
        if (IS_ADMIN) : ?>
            <div class="flex justify-space-between">
                <div class="slide-btn-yellow host-action-btn" host-id="<?= $id ?>" action="reset" title="Reset host informations">
                    <img src="/assets/icons/update.svg">
                    <span>Reset</span>
                </div>

                <div class="slide-btn-red host-action-btn" host-id="<?= $id ?>" action="delete" title="Delete host">
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
                <p class="copy"><?= $ip ?></p>

                <h6>OS</h6>
                <p class="flex align-item-center column-gap-5">
                    <?php
                    if (!empty($os)) {
                        echo \Controllers\Common::printOsIcon($os);
                        echo $os;
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>

                <h6>OS VERSION</h6>
                <p class="copy">
                    <?php
                    if (!empty($osVersion)) {
                        echo $osVersion;
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>
            </div>

            <div>
                <h6 class="margin-top-0">PROFILE</h6>
                <p class="copy">
                    <?php
                    if (!empty($profile)) {
                        echo $profile;
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>

                <h6>ENVIRONMENT</h6>
                <p class="copy">
                    <?php
                    if (!empty($env)) {
                        echo Controllers\Common::envtag($env);
                    } else {
                        echo 'Unknown';
                    } ?>
                </p>
            </div>

            <div>
                <h6 class="margin-top-0">AGENT STATUS</h6>
                <p class="flex align-item-center column-gap-5">
                    <?php
                    if ($agentStatus == 'running') {
                        echo '<img src="/assets/icons/check.svg" class="icon" title="Linupdate agent state on this host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." /> Running';
                    }
                    if ($agentStatus == "disabled") {
                        echo '<img src="/assets/icons/warning.svg" class="icon" title="Linupdate agent state on this host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." /> Disabled';
                    }
                    if ($agentStatus == "stopped") {
                        echo '<img src="/assets/icons/warning-red.svg" class="icon" title="Linupdate agent state on this host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." /> Stopped';
                    }
                    if ($agentStatus == "seems-stopped") {
                        echo '<img src="/assets/icons/warning-red.svg" class="icon" title="Linupdate agent state on this host: ' . $agentStatus . ' (' . $agentLastSendStatusMsg . ')." /> Seems stopped';
                    }
                    if ($agentStatus == "unknow") {
                        echo '<img src="/assets/icons/graycircle.png" class="icon" title="Linupdate agent state on this host: ' . $agentStatus . '." /> Unknown';
                    } ?>
                </p>

                <h6>AGENT VERSION</h6>
                <p class="copy">
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
    </div>
</section>
