<section class="section-main reloadable-container" container="hosts/overview">
    <?php
    use \Controllers\Utils\Generate\Html\Color;

    if ($totalHosts >= 1) : ?>
        <h3>OVERVIEW</h3>

        <div class="hosts-charts-container">
            <div class="echart-container div-generic-blue">
                <h6 class="margin-top-0">HOSTS (<?= $totalHosts ?>)</h6>

                <div id="hosts-count-chart-loading" class="echart-loading">
                    <img src="/assets/icons/loading.svg" class="icon-np" />
                </div>

                <div id="hosts-count-chart" class="echart"></div>
            </div>

            <div class="hosts-chart-sub-container div-generic-blue">
                <h6 class="margin-top-0">KERNELS</h6>
            
                <div class="grid grid-2 row-gap-5 column-gap-40 margin-15">
                    <?php
                    foreach ($kernels as $kernel) :
                        if (empty($kernel['Kernel'])) {
                            $kernelName = 'Unknown';
                        } else {
                            $kernelName = $kernel['Kernel'];
                        } ?>

                        <div class="hosts-charts-list-label flex justify-space-between align-item-center" chart-type="kernel" kernel="<?= $kernelName ?>">
                            <div class="flex column-gap-5 align-item-center">
                                <!-- square figure -->
                                <span style="background-color: <?= Color::random() ?>"></span>
                                <p class="font-size-14"><?= $kernelName ?></p>
                            </div>
                            <p class="font-size-14"><?= $kernel['Kernel_count'] ?></p>
                        </div>
                        <?php
                    endforeach ?>
                </div>
            </div>

            <div class="hosts-chart-sub-container div-generic-blue">
                <h6 class="margin-top-0">PROFILES</h6>
                <div class="grid grid-2 row-gap-5 column-gap-40 margin-15">
                    <?php
                    foreach ($profiles as $profile) {
                        if (empty($profile['Profile'])) {
                            $profileName = 'Unknown';
                        } else {
                            $profileName = $profile['Profile'];
                        } ?>
                        
                        <div class="hosts-charts-list-label flex justify-space-between align-item-center" chart-type="profile" profile="<?= $profileName ?>">
                            <div class="flex column-gap-5 align-item-center">
                                <!-- square figure -->
                                <span style="background-color: <?= Color::random() ?>"></span>
                                <p class="font-size-14"><?= $profileName ?></p>
                            </div>
                            <p class="font-size-14"><?= $profile['Profile_count'] ?></p>
                        </div>
                        <?php
                    } ?>
                </div>
            </div>

            <div class="hosts-chart-sub-container div-generic-blue">
                <h6 class="margin-top-0">OPERATING SYSTEMS</h6>

                <div class="echart-container">
                    <div id="hosts-os-chart-loading" class="echart-loading">
                        <img src="/assets/icons/loading.svg" class="icon-np" />
                    </div>

                    <div id="hosts-os-chart" class="echart"></div>
                </div>
            </div>

            <div class="hosts-chart-sub-container div-generic-blue">
                <h6 class="margin-top-0">ARCHITECTURES</h6>

                <div class="echart-container">
                    <div id="hosts-arch-chart-loading" class="echart-loading">
                        <img src="/assets/icons/loading.svg" class="icon-np" />
                    </div>

                    <div id="hosts-arch-chart" class="echart"></div>
                </div>
            </div>

            <div class="hosts-chart-sub-container div-generic-blue">
                <h6 class="margin-top-0">ENVIRONMENTS</h6>

                <div class="echart-container">
                    <div id="hosts-env-chart-loading" class="echart-loading">
                        <img src="/assets/icons/loading.svg" class="icon-np" />
                    </div>

                    <div id="hosts-env-chart" class="echart"></div>
                </div>
            </div>

            <div class="hosts-chart-sub-container div-generic-blue">
                <h6 class="margin-top-0">AGENT STATUS</h6>

                <div class="echart-container">
                    <div id="hosts-agent-status-chart-loading" class="echart-loading">
                        <img src="/assets/icons/loading.svg" class="icon-np" />
                    </div>

                    <div id="hosts-agent-status-chart" class="echart"></div>
                </div>
            </div>

            <div class="hosts-chart-sub-container div-generic-blue">
                <h6 class="margin-top-0">AGENT VERSION</h6>

                <div class="echart-container">
                    <div id="hosts-agent-version-chart-loading" class="echart-loading">
                        <img src="/assets/icons/loading.svg" class="icon-np" />
                    </div>

                    <div id="hosts-agent-version-chart" class="echart"></div>
                </div>
            </div>

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
                                        <?= \Controllers\Utils\Generate\Html\Icon::os($rebootRequiredHost['Os']) ?>
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

        <script>
            $(document).ready(function() {
                new EChart('nightingale', 'hosts-count-chart');
                new EChart('bar', 'hosts-os-chart');
                new EChart('nightingale', 'hosts-arch-chart');
                new EChart('nightingale', 'hosts-env-chart');
                new EChart('nightingale', 'hosts-agent-status-chart');
                new EChart('nightingale', 'hosts-agent-version-chart');
            });
        </script>
        <?php
    endif; ?>
</section>
