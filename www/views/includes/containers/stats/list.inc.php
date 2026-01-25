<?php
use \Controllers\Utils\Generate\Html\Label;
use \Controllers\Layout\Table\Render as TableRender; ?>

<section class="section-main">
    <h3>STATISTICS & METRICS</h3>

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
            <h6 class="margin-top-0">SNAPSHOT</h6>
            <p><span class="label-black"><?= $repoController->getDateFormatted() ?></span></p>
        </div>

        <div>
            <h6 class="margin-top-0">ENVIRONMENT</h6>
            <p><?= Label::envtag($repoController->getEnv()) ?></p>
        </div>

        <div>
            <h6 class="margin-top-0">SIZE</h6>
            <p><?= $repoSize ?></p>
        </div>

        <div>
            <h6 class="margin-top-0">PACKAGES</h6>
            <p><?= $packagesCount ?></p>
        </div>
    </div>

    <div id="repo-access-chart-div" class="div-generic-blue">
        <div class="flex justify-space-between">
            <div>
                <h6 class="margin-top-0">ACCESSES</h6>
                <p class="note">Number of accesses to the repository snapshot over time.</p>
            </div>

            <div>
                <h6 class="margin-0">SELECT PERIOD</h6>
                <select id="stats-days-select" class="select-medium">
                    <option value="7" selected>1 week</option>
                    <option value="30">1 month</option>
                    <option value="90">3 months</option>
                    <option value="180">6 months</option>
                    <option value="365">1 year</option>
                </select>
            </div>
        </div>

        <div class="echart-container">
            <div id="repo-access-chart-loading" class="echart-loading">
                <img src="/assets/icons/loading.svg" class="icon-np" />
            </div>

            <div id="repo-access-chart" class="echart min-height-400"></div>
        </div>
    </div>

    <?php
    // Print access logs
    TableRender::render('stats/access'); ?>

    <div class="grid grid-rfr-1-2 justify-space-between column-gap-15 row-gap-15 margin-top-15">
        <div class="div-generic-blue">
            <h6 class="margin-top-0">SIZE</h6>
            <p class="note">The size of the repository snapshot over time.</p>

            <div class="echart-container">
                <div id="repo-size-chart-loading" class="echart-loading">
                    <img src="/assets/icons/loading.svg" class="icon-np" />
                </div>

                <div id="repo-size-chart" class="echart min-height-400"></div>
            </div>
        </div>

        <div class="div-generic-blue">
            <h6 class="margin-top-0">PACKAGES COUNT</h6>
            <p class="note">The number of packages in the repository snapshot over time.</p>

            <div class="echart-container">
                <div id="repo-packages-count-chart-loading" class="echart-loading">
                    <img src="/assets/icons/loading.svg" class="icon-np" />
                </div>

                <div id="repo-packages-count-chart" class="echart min-height-400"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-rfr-1-2 justify-space-between column-gap-15 row-gap-15 margin-top-15">
        <?php
        // Print access logs
        TableRender::render('stats/ip-access'); ?>
    </div>

    <script>
        $(document).ready(function() {
            new EChart('line', 'repo-access-chart');
            new EChart('line', 'repo-size-chart');
            new EChart('line', 'repo-packages-count-chart');
        });
    </script>
</section>
