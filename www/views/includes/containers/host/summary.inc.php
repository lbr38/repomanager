<section class="section-main reloadable-container" container="host/summary">
    <?php
    if (IS_ADMIN) : ?>
        <div class="relative">
            <div class="host-action-btns-container">
                <span class="btn-large-green"><img src="/assets/icons/rocket.svg" class="icon" />Actions</span>
                <span class="host-action-btn btn-large-green" hostid="<?= $id ?>" action="general-status-update" title="Send general informations (OS and state informations).">Request to send general info.</span>
                <span class="host-action-btn btn-large-green" hostid="<?= $id ?>" action="packages-status-update" title="Send packages informations (available, installed, updated...).">Request to send packages info.</span>
                <span class="host-action-btn btn-large-red" hostid="<?= $id ?>" action="update" title="Update all available packages using linupdate.">Update packages</span>
                <span class="host-action-btn btn-large-red" hostid="<?= $id ?>" action="reset" title="Reset known data.">Reset</span>
                <span class="host-action-btn btn-large-red" hostid="<?= $id ?>" action="delete" title="Delete this host">Delete</span>
            </div>
        </div>
        <?php
    endif ?>

    <h3><?= strtoupper($hostname) ?></h3>

    <div class="grid grid-2 justify-space-between align-item-center div-generic-blue reloadable-container" container="host/summary">
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
