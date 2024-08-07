<section class="section-main reloadable-container" container="hosts/overview">

    <h3>OVERVIEW</h3>

    <br>

    <?php
    if ($totalHosts == 0) : ?>
        <p>No host registered yet.<br><br></p>

        <p>
            You can register hosts that use <a href="https://github.com/lbr38/linupdate"><b>linupdate</b></a> with <b>reposerver</b> module enabled. This page will then display dashboards and informations about your hosts and their packages status (installed, available, updated...).
        </p>
        <?php
    endif ?>

    <div class="hosts-charts-container">
        <?php
        if ($totalHosts >= 1) : ?>
            <div class="hosts-chart-sub-container div-generic-blue">
                <span class="hosts-chart-title">Hosts (<?= $totalHosts ?>)</span>
                <canvas id="hosts-count-chart" class="host-pie-chart"></canvas>
            </div>

            <?php
            if (!empty(HOSTS_KERNEL_LIST)) : ?>
                <div class="hosts-chart-sub-container div-generic-blue">
                    <span class="hosts-chart-title">Kernels</span>
                
                    <div class="hosts-charts-list-column-container">
                        <?php
                        foreach (HOSTS_KERNEL_LIST as $kernel) :
                            if (empty($kernel['Kernel'])) {
                                $kernelName = 'Unknow';
                            } else {
                                $kernelName = $kernel['Kernel'];
                            } ?>

                            <div class="hosts-charts-list-container">
                                <div class="hosts-charts-list-label flex justify-space-between" chart-type="kernel" kernel="<?= $kernelName ?>">
                                    <div class="flex column-gap-5 align-item-center">
                                        <!-- square figure -->
                                        <span style="background-color: <?= $mycolor->randomColor() ?>"></span>
                                        <span><?= $kernelName ?></span>
                                    </div>
                                    <span><?= $kernel['Kernel_count'] ?></span>
                                </div>
                            </div>
                            <?php
                        endforeach ?>
                    </div>
                </div>
                <?php
            endif;

            if (!empty(HOSTS_PROFILES_LIST)) : ?>
                <div class="hosts-chart-sub-container div-generic-blue">
                    <span class="hosts-chart-title">Profiles</span>
                    <div class="hosts-charts-list-column-container">
                        <?php
                        foreach (HOSTS_PROFILES_LIST as $profile) {
                            if (empty($profile['Profile'])) {
                                $profileName = 'Unknow';
                            } else {
                                $profileName = $profile['Profile'];
                            } ?>
                            
                            <div class="hosts-charts-list-container">
                                <div class="hosts-charts-list-label flex justify-space-between" chart-type="profile" profile="<?= $profileName ?>">
                                    <div class="flex column-gap-5 align-item-center">
                                        <!-- square figure -->
                                        <span style="background-color: <?= $mycolor->randomColor() ?>"></span>
                                        <span><?= $profileName ?></span>
                                    </div>
                                    <span><?= $profile['Profile_count'] ?></span>
                                </div>
                            </div>
                            <?php
                        } ?>
                    </div>
                </div>
                <?php
            endif;

            if (!empty(HOSTS_OS_LIST)) : ?>
                <div class="hosts-chart-sub-container div-generic-blue">
                    <span class="hosts-chart-title">Operating systems</span>
                    <canvas id="hosts-os-chart" class="host-bar-chart"></canvas>
                </div>
                <?php
            endif;

            if (!empty(HOSTS_ARCHS_LIST)) : ?>
                <div class="hosts-chart-sub-container div-generic-blue">
                    <span class="hosts-chart-title">Architectures</span>
                    <canvas id="hosts-arch-chart" class="host-pie-chart"></canvas>
                </div>
                <?php
            endif;

            if (!empty(HOSTS_ENVS_LIST)) : ?>
                <div class="hosts-chart-sub-container div-generic-blue">
                    <span class="hosts-chart-title">Environments</span>
                    <canvas id="hosts-env-chart" class="host-pie-chart"></canvas>
                </div>
                <?php
            endif;

            if (!empty(HOSTS_AGENT_STATUS_LIST)) : ?>
                <div class="hosts-chart-sub-container div-generic-blue">
                    <span class="hosts-chart-title">Agent status</span>
                    <canvas id="hosts-agent-status-chart" class="host-pie-chart"></canvas>
                </div>
                <?php
            endif;

            if (!empty(HOSTS_AGENT_VERSION_LIST)) : ?>
                <div class="hosts-chart-sub-container div-generic-blue">
                    <span class="hosts-chart-title">Agent version</span>
                    <canvas id="hosts-agent-version-chart" class="host-pie-chart"></canvas>
                </div>
                <?php
            endif ?>

            <div class="hosts-chart-sub-container div-generic-blue">
                <span class="hosts-chart-title">Hosts requiring reboot</span>
                
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
            <?php
        endif ?>
    </div>

    <?php
    /**
     *  Hosts chart
     */
    $labels = "'Up to date', 'Need update'";
    $datas = "'" . HOSTS_TOTAL_UPTODATE . "', '" . HOSTS_TOTAL_NOT_UPTODATE . "'";
    $backgrounds = "'rgb(75, 192, 192)','rgb(255, 99, 132)'";
    $title = '';
    $chartId = 'hosts-count-chart';

    include(ROOT . '/views/includes/charts/hosts-pie-chart.inc.php');

    /**
     *  Profiles chart
     */
    if (!empty(HOSTS_PROFILES_LIST)) {
        $profileNameList = '';
        $profileCountList = '';
        $profileBackgroundColor = '';

        foreach (HOSTS_PROFILES_LIST as $profile) {
            if (empty($profile['Profile'])) {
                $profileNameList .= "'Unknow',";
            } else {
                $profileNameList .= "'" . $profile['Profile'] . "',";
            }
            $profileCountList .= "'" . $profile['Profile_count'] . "',";
            $profileBackgroundColor .= "'" . $mycolor->randomColor() . "',";
        }

        $labels = rtrim($profileNameList, ',');
        $datas = rtrim($profileCountList, ',');
        $backgrounds = rtrim($profileBackgroundColor, ',');
        $title = '';
        $chartId = 'hosts-profile-chart';

        include(ROOT . '/views/includes/charts/hosts-bar-chart.inc.php');
    }

    /**
     *  OS chart
     */
    if (!empty(HOSTS_OS_LIST)) {
        $osNameList = '';
        $osCountList = '';
        $osBackgroundColor = '';

        foreach (HOSTS_OS_LIST as $os) {
            if (empty($os['Os'])) {
                $osNameList .= "'Unknow',";
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
                $archNameList .= "'Unknow',";
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
                $envNameList .= "'Unknow',";
            } else {
                $envNameList .= "'" . $env['Env'] . "',";
            }
            $envCountList .= "'" . $env['Env_count'] . "',";

            if ($env['Env'] == LAST_ENV) {
                $envBackgroundColor .= "'rgb(255, 99, 132)',";
            } else {
                $envBackgroundColor .= "'" . $mycolor->randomColor() . "',";
            }
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
                $agentNameList .= "'Unknow',";
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
    } ?>
</section>
