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

    <div class="grid grid-2 justify-space-between align-item-center div-generic-blue">
        <div class="grid grid-fr-1-2 align-item-center row-gap-20 margin-top-15 margin-bottom-15 margin-left-15">
            <span>IP</span>
            <span class="copy"><?= $ip ?></span>

            <span>OS</span>
            <div>
                <span class="copy">
                    <?php
                    if (!empty($os) and !empty($osVersion)) {
                        echo '<span>' . $os . ' ' . $osVersion . '</span>';
                    } else {
                        echo '<span>Unknow</span>';
                    } ?>
                </span>
                <span>
                    <?php
                    if (!empty($os)) {
                        echo \Controllers\Common::printOsIcon($os);
                    } ?>
                </span>
            </div>

            <span>PROFILE</span>
            <span class="copy">
                <?php
                if (!empty($profile)) {
                    echo '<span class="label-white">' . $profile . '</span>';
                } else {
                    echo 'Unknown';
                } ?>
            </span>

            <span>ENVIRONMENT</span>
            <span class="copy">
                <?php
                if (!empty($env)) {
                    echo Controllers\Common::envtag($env);
                } else {
                    echo 'Unknown';
                } ?>
            </span>

            <span>AGENT STATUS</span>
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
                    echo '<img src="/assets/icons/graycircle.png" class="icon-small" title="Linupdate agent state on this host: ' . $agentStatus . '." /> Unknown';
                } ?>
            </span>

            <span>AGENT VERSION</span>
            <span class="copy">
                <span class="label-black">
                    <?php
                    if (!empty($agentVersion)) {
                        echo $agentVersion;
                    } else {
                        echo 'Unknown';
                    } ?>
                </span>
            </span>
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
    </div>
</section>
