<section class="section-main reloadable-container" container="hosts/overview">
    <?php
    if ($totalHosts >= 1) : ?>
        <h3>OVERVIEW</h3>

        <div class="hosts-charts-container">
            <div class="hosts-chart-sub-container div-generic-blue">
                <h6 class="margin-top-0">HOSTS (<?= $totalHosts ?>)</h6>
                <div id="hosts-count-chart-loading" class="loading-veil">
                    <p class="lowopacity-cst">Loading</p>
                </div>
                <canvas id="hosts-count-chart" class="host-pie-chart"></canvas>
            </div>

            <?php
            if (!empty(HOSTS_KERNEL_LIST)) : ?>
                <div class="hosts-chart-sub-container div-generic-blue">
                    <h6 class="margin-top-0">KERNELS</h6>
                
                    <div class="grid grid-2 row-gap-5 column-gap-40 margin-15">
                        <?php
                        foreach (HOSTS_KERNEL_LIST as $kernel) :
                            if (empty($kernel['Kernel'])) {
                                $kernelName = 'Unknown';
                            } else {
                                $kernelName = $kernel['Kernel'];
                            } ?>

                            <div class="hosts-charts-list-label flex justify-space-between align-item-center" chart-type="kernel" kernel="<?= $kernelName ?>">
                                <div class="flex column-gap-5 align-item-center">
                                    <!-- square figure -->
                                    <span style="background-color: <?= $mycolor->randomColor() ?>"></span>
                                    <p class="font-size-14"><?= $kernelName ?></p>
                                </div>
                                <p class="font-size-14"><?= $kernel['Kernel_count'] ?></p>
                            </div>
                            <?php
                        endforeach ?>
                    </div>
                </div>
                <?php
            endif;

            if (!empty(HOSTS_PROFILES_LIST)) : ?>
                <div class="hosts-chart-sub-container div-generic-blue">
                    <h6 class="margin-top-0">PROFILES</h6>
                    <div class="grid grid-2 row-gap-5 column-gap-40 margin-15">
                        <?php
                        foreach (HOSTS_PROFILES_LIST as $profile) {
                            if (empty($profile['Profile'])) {
                                $profileName = 'Unknown';
                            } else {
                                $profileName = $profile['Profile'];
                            } ?>
                            
                            <div class="hosts-charts-list-label flex justify-space-between align-item-center" chart-type="profile" profile="<?= $profileName ?>">
                                <div class="flex column-gap-5 align-item-center">
                                    <!-- square figure -->
                                    <span style="background-color: <?= $mycolor->randomColor() ?>"></span>
                                    <p class="font-size-14"><?= $profileName ?></p>
                                </div>
                                <p class="font-size-14"><?= $profile['Profile_count'] ?></p>
                            </div>
                            <?php
                        } ?>
                    </div>
                </div>
                <?php
            endif;

            if (!empty(HOSTS_OS_LIST)) : ?>
                <div class="hosts-chart-sub-container div-generic-blue">
                    <h6 class="margin-top-0">OPERATING SYSTEMS</h6>
                    <div id="hosts-os-chart-loading" class="loading-veil">
                        <p class="lowopacity-cst">Loading</p>
                    </div>
                    <canvas id="hosts-os-chart" class="host-bar-chart"></canvas>
                </div>
                <?php
            endif;

            if (!empty(HOSTS_ARCHS_LIST)) : ?>
                <div class="hosts-chart-sub-container div-generic-blue">
                    <h6 class="margin-top-0">ARCHITECTURES</h6>
                    <div id="hosts-arch-chart-loading" class="loading-veil">
                        <p class="lowopacity-cst">Loading</p>
                    </div>
                    <canvas id="hosts-arch-chart" class="host-pie-chart"></canvas>
                </div>
                <?php
            endif;

            if (!empty(HOSTS_ENVS_LIST)) : ?>
                <div class="hosts-chart-sub-container div-generic-blue">
                    <h6 class="margin-top-0">ENVIRONMENTS</h6>
                    <div id="hosts-env-chart-loading" class="loading-veil">
                        <p class="lowopacity-cst">Loading</p>
                    </div>
                    <canvas id="hosts-env-chart" class="host-pie-chart"></canvas>
                </div>
                <?php
            endif;

            if (!empty(HOSTS_AGENT_STATUS_LIST)) : ?>
                <div class="hosts-chart-sub-container div-generic-blue">
                    <h6 class="margin-top-0">AGENT STATUS</h6>
                    <div id="hosts-agent-status-chart-loading" class="loading-veil">
                        <p class="lowopacity-cst">Loading</p>
                    </div>
                    <canvas id="hosts-agent-status-chart" class="host-pie-chart"></canvas>
                </div>
                <?php
            endif;

            if (!empty(HOSTS_AGENT_VERSION_LIST)) : ?>
                <div class="hosts-chart-sub-container div-generic-blue">
                    <h6 class="margin-top-0">AGENT VERSION</h6>
                    <div id="hosts-agent-version-chart-loading" class="loading-veil">
                        <p class="lowopacity-cst">Loading</p>
                    </div>
                    <canvas id="hosts-agent-version-chart" class="host-pie-chart"></canvas>
                </div>
                <?php
            endif ?>

            <div class="hosts-chart-sub-container div-generic-blue">
                <h6 class="margin-top-0">HOSTS REQUIRING REBOOT</h6>
                
                <div id="hosts-requiring-reboot-chart" class="flex justify-center align-item-center">
                    <div>
                        <p><?= $rebootRequiredCount ?></p>
                    </div>

                    <?php
                    if ($rebootRequiredCount > 0) : ?>
                        <div id="hosts-requiring-reboot-chart-list">
                            <?php
                            foreach ($rebootRequiredList as $rebootRequiredHost) : ?>
                                <div class="flex align-item-center column-gap-10 div-generic-blue margin-bottom-0">
                                    <div>
                                        <?= \Controllers\Common::printOsIcon($rebootRequiredHost['Os']) ?>
                                    </div>

                                    <div class="flex flex-direction-column row-gap-4">
                                        <span class="copy">
                                            <a href="/host/<?= $rebootRequiredHost['Id'] ?>" target="_blank" rel="noopener noreferrer">
                                                <?= $rebootRequiredHost['Hostname'] ?>
                                            </a>
                                        </span>
                                        <span class="copy font-size-12 lowopacity-cst" title="<?= $rebootRequiredHost['Hostname'] ?> IP address"><?= $rebootRequiredHost['Ip'] ?></span>
                                    </div>
                                </div>
                                <?php
                            endforeach ?>
                        </div>
                        <?php
                    endif ?>
                </div>
            </div>
        </div>

        <?php
        /**
         *  Hosts chart
         */
        $labels = "'Up to date', 'Need update'";
        $datas = "'" . HOSTS_TOTAL_UPTODATE . "', '" . HOSTS_TOTAL_NOT_UPTODATE . "'";
        $backgrounds = "'#24d794','#F32F63'";
        $title = '';
        $chartId = 'hosts-count-chart';

        include(ROOT . '/views/includes/charts/hosts-pie-chart.inc.php');

        /**
         *  OS chart
         */
        if (!empty(HOSTS_OS_LIST)) {
            $osNameList = '';
            $osCountList = '';
            $osBackgroundColor = '';

            foreach (HOSTS_OS_LIST as $os) {
                if (empty($os['Os'])) {
                    $osNameList .= "'Unknown',";
                } else {
                    $osNameList .= "'" . ucfirst($os['Os']) . " " . $os['Os_version'] . "',";
                }
                $osCountList .= "'" . $os['Os_count'] . "',";
                $osBackgroundColor .= "'" . $mycolor->randomColor() . "',";
            }

            $labels = rtrim($osNameList, ',');
            $datas = rtrim($osCountList, ',');
            $backgrounds = rtrim($osBackgroundColor, ',');
            $title = '';
            $chartId = 'hosts-os-chart';

            include(ROOT . '/views/includes/charts/hosts-bar-chart.inc.php');
        }

        /**
         *  Arch chart
         */
        if (!empty(HOSTS_ARCHS_LIST)) {
            $archNameList = '';
            $archCountList = '';
            $archBackgroundColor = '';

            foreach (HOSTS_ARCHS_LIST as $arch) {
                if (empty($arch['Arch'])) {
                    $archNameList .= "'Unknown',";
                } else {
                    $archNameList .= "'" . $arch['Arch'] . "',";
                }
                $archCountList .= "'" . $arch['Arch_count'] . "',";
                $archBackgroundColor .= "'" . $mycolor->randomColor() . "',";
            }

            $labels = rtrim($archNameList, ',');
            $datas = rtrim($archCountList, ',');
            $backgrounds = rtrim($archBackgroundColor, ',');
            $title = '';
            $chartId = 'hosts-arch-chart';

            include(ROOT . '/views/includes/charts/hosts-pie-chart.inc.php');
        }

        /**
         *  Envs chart
         */
        if (!empty(HOSTS_ENVS_LIST)) {
            $envNameList = '';
            $envCountList = '';
            $envBackgroundColor = '';

            foreach (HOSTS_ENVS_LIST as $env) {
                if (empty($env['Env'])) {
                    $envName = 'Unknown';
                } else {
                    $envName = $env['Env'];
                }

                $envNameList .= "'" . $envName . "',";
                $envCountList .= "'" . $env['Env_count'] . "',";
                $envBackgroundColor .= '"' . \Controllers\Environment::getEnvColor($envName) . '",';
            }

            $labels = rtrim($envNameList, ',');
            $datas = rtrim($envCountList, ',');
            $backgrounds = rtrim($envBackgroundColor, ',');
            $title = '';
            $chartId = 'hosts-env-chart';

            include(ROOT . '/views/includes/charts/hosts-pie-chart.inc.php');
        }

        /**
         *  Agent status chart
         */
        if (!empty(HOSTS_AGENT_STATUS_LIST)) {
            $agentStatusNameList = '';
            $agentStatusCountList = '';
            $agentBackgroundColor = '';

            if (!empty(HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_online_count'])) {
                $agentStatusNameList .= "'Online',";
                $agentStatusCountList .= "'" . HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_online_count'] . "',";
                $agentBackgroundColor .= "'#24d794',";
            }

            if (!empty(HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_seems_stopped_count'])) {
                $agentStatusNameList .= "'Seems stopped',";
                $agentStatusCountList .= "'" . HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_seems_stopped_count'] . "',";
                $agentBackgroundColor .= "'#e0b05f',";
            }

            if (!empty(HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_stopped_count'])) {
                $agentStatusNameList .= "'Stopped',";
                $agentStatusCountList .= "'" . HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_stopped_count'] . "',";
                $agentBackgroundColor .= "'rgb(255, 99, 132)',";
            }

            if (!empty(HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_disabled_count'])) {
                $agentStatusNameList .= "'Disabled',";
                $agentStatusCountList .= "'" . HOSTS_AGENT_STATUS_LIST['Linupdate_agent_status_disabled_count'] . "',";
                $agentBackgroundColor .= "'rgb(255, 99, 132)',";
            }

            $labels = rtrim($agentStatusNameList, ',');
            $datas = rtrim($agentStatusCountList, ',');
            $backgrounds = rtrim($agentBackgroundColor, ',');
            $title = '';
            $chartId = 'hosts-agent-status-chart';

            include(ROOT . '/views/includes/charts/hosts-pie-chart.inc.php');
        }

        /**
         *  Agent version chart
         */
        if (!empty(HOSTS_AGENT_VERSION_LIST)) {
            $agentNameList = '';
            $agentCountList = '';
            $agentBackgroundColor = '';

            foreach (HOSTS_AGENT_VERSION_LIST as $agent) {
                if (empty($agent['Linupdate_version'])) {
                    $agentNameList .= "'Unknown',";
                } else {
                    $agentNameList .= "'" . $agent['Linupdate_version'] . "',";
                }
                $agentCountList .= "'" . $agent['Linupdate_version_count'] . "',";
                $agentBackgroundColor .= "'" . $mycolor->randomColor() . "',";
            }

            $labels = rtrim($agentNameList, ',');
            $datas = rtrim($agentCountList, ',');
            $backgrounds = rtrim($agentBackgroundColor, ',');
            $title = '';
            $chartId = 'hosts-agent-version-chart';

            include(ROOT . '/views/includes/charts/hosts-pie-chart.inc.php');
        }
    endif; ?>
</section>
