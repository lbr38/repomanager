<?php
use \Controllers\Layout\Table\Render as TableRender; ?>

<section class="section-main">
    <h3>REPOSITORY STATISTICS</h3>

    <?php
    if ($repoController->getPackageType() == 'rpm') {
        $repo = $repoController->getName() . ' ❯ ' . $repoController->getReleasever();
    }
    if ($repoController->getPackageType() == 'deb') {
        $repo = $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
    } ?>

    <div class="grid grid-rfr-1-5 row-gap-20 div-generic-blue margin-bottom-15">
        <div>
            <h6 class="margin-top-0">REPOSITORY</h6>
            <div class="flex align-item-center column-gap-5">
                <p><span class="label-white"><?= $repo ?></span></p>
                <span class="label-pkg-<?= $repoController->getPackageType() ?>"><?= strtoupper($repoController->getPackageType()) ?></span>
            </div>
        </div>

        <div>
            <h6 class="margin-top-0">SNAPSHOTS</h6>
            <p><?= count($snapshots) ?></p>
        </div>

        <div>
            <h6 class="margin-top-0">TOTAL ACCESSES TODAY</h6>
            <p><?= $accessCount ?></p>
        </div>
    </div>

    <div class="div-generic-blue">
        <div>
            <h6 class="margin-top-0">REPOSITORY SNAPSHOTS</h6>
            <p class="note">Snapshots timeline & size.</p>
        </div>

        <div class="echart-container">
            <div id="repo-snapshots-chart-loading" class="echart-loading">
                <img src="/assets/icons/loading.svg" class="icon-np" />
            </div>

            <div id="repo-snapshots-chart" class="echart" type="points" generate></div>
        </div>
    </div>

    <!-- <div class="div-generic-blue">
        <div>
            <h6 class="margin-top-0">SNAPSHOTS</h6>
            <p class="note">Snapshots over time.</p>
        </div>

        <div class="echart-container">
            <div id="repo-snapshots-size-chart-loading" class="echart-loading">
                <img src="/assets/icons/loading.svg" class="icon-np" />
            </div>

            <div id="repo-snapshots-size-chart" class="echart min-height-400" type="points" generate></div>
        </div>
    </div> -->

    <div class="div-generic-blue">
        <div class="flex flex-wrap column-gap-15 row-gap-15 justify-space-between">
            <div>
                <h6 class="margin-top-0">ACCESSES</h6>
                <p class="note">Accesses to the repository over time, by environment.</p>
            </div>

            <div class="flex flex-wrap align-item-center column-gap-20 row-gap-15">
                <div>
                    <h6 class="margin-0">SELECT PERIOD</h6>
                    <select class="select-medium echart-period" chart-id="repo-accesses-chart">
                        <option value="1" selected>1 day</option>
                        <option value="3">3 days</option>
                        <option value="7">1 week</option>
                        <option value="30">1 month</option>
                        <option value="90">3 months</option>
                        <option value="180">6 months</option>
                        <option value="365">1 year</option>
                    </select>
                </div>

                <div>
                    <h6 class="margin-0">FILTER BY ENVIRONMENT</h6>
                    <select id="accesses-env" type="date" class="input-medium" multiple>
                        <?php
                        foreach (ENVS as $env) {
                            echo '<option value="' . $env['Name'] . '">' . $env['Name'] . '</option>';
                        } ?>
                    </select>
                </div>
                <!-- TODO: ranges -->
                <!-- <input type="text" class="input-large echart-range" chart-id="repo-accesses-chart" value="" /> -->
            </div>
        </div>

        <div class="echart-container">
            <div id="repo-accesses-chart-loading" class="echart-loading">
                <img src="/assets/icons/loading.svg" class="icon-np" />
            </div>

            <div id="repo-accesses-chart" class="echart min-height-400" type="line" generate></div>
        </div>
    </div>

    <div class="div-generic-blue">
        <div class="flex flex-wrap justify-space-between row-gap-15 margin-bottom-15">
            <div>
                <h6 class="margin-top-0">ACCESS REQUESTS</h6>
                <p class="note">Access requests to the repository. Sorted by most recent first.</p>
            </div>

            <div class="flex flex-wrap align-item-center column-gap-20 row-gap-15">
                <div>
                    <h6 class="margin-0">SELECT PERIOD</h6>
                    <div id="access-requests-range" class="pointer input-daterangepicker input-large">
                        <p>Today</p>
                    </div>
                </div>

                <div>
                    <h6 class="margin-0">FILTER BY ENVIRONMENT</h6>
                    <select id="access-requests-env" type="date" class="input-medium" multiple>
                        <?php
                        foreach (ENVS as $env) {
                            echo '<option value="' . $env['Name'] . '">' . $env['Name'] . '</option>';
                        } ?>
                    </select>
                </div>
            </div>
        </div>

        <?php
        // Print access by IP logs
        TableRender::render('stats/access'); ?>
    </div>

    <div class="div-generic-blue">
        <div class="flex flex-wrap justify-space-between row-gap-15 margin-bottom-15">
            <div>
                <h6 class="margin-top-0">TOP IP ADDRESSES</h6>
                <p class="note">Accesses to the repository grouped by IP address.</p>
            </div>

            <div class="flex flex-wrap align-item-center column-gap-20 row-gap-15">
                <div>
                    <h6 class="margin-0">SELECT PERIOD</h6>

                    <div id="ip-access-range" class="pointer input-daterangepicker input-large">
                        <p>Today</p>
                    </div>
                </div>

                <div>
                    <h6 class="margin-0">FILTER BY ENVIRONMENT</h6>
                    <select id="ip-access-env" type="date" class="input-medium" multiple>
                        <?php
                        foreach (ENVS as $env) {
                            echo '<option value="' . $env['Name'] . '">' . $env['Name'] . '</option>';
                        } ?>
                    </select>
                </div>
            </div>
        </div>

        <?php
        // Print access by IP logs
        TableRender::render('stats/ip-access'); ?>
    </div>
</section>

<script>
    $(document).ready(function(){
        myselect2.convert('select#accesses-env', 'Select environment...', true);

        mydaterangepicker.convert('div#access-requests-range');
        myselect2.convert('select#access-requests-env', 'Select environment...', true);

        mydaterangepicker.convert('div#ip-access-range');
        myselect2.convert('select#ip-access-env', 'Select environment...', true);

        // TODO: ranges
        // mydaterangepicker.convert('input.echart-range[chart-id="repo-accesses-chart"]');
    });
</script>