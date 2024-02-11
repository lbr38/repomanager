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
                    <span class="hosts-chart-title">Agent release version</span>
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
</section>
