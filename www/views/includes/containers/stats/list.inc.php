<section class="section-main">
    <h3>STATISTICS & METRICS</h3>

    <?php
    if ($myrepo->getPackageType() == 'rpm') {
        $repo = $myrepo->getName();
    }
    if ($myrepo->getPackageType() == 'deb') {
        $repo = $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection();
    } ?>

    <div class="div-generic-blue grid grid-5 margin-bottom-15">
        <div>
            <h6 class="margin-top-0">REPOSITORY</h6>
            <p><span class="label-white"><?= $repo ?></span></p>
        </div>

        <div>
            <h6 class="margin-top-0">SNAPSHOT</h6>
            <p><span class="label-black"><?= $myrepo->getDateFormatted() ?></span></p>
        </div>

        <div>
            <h6 class="margin-top-0">ENVIRONMENT</h6>
            <p><?= \Controllers\Common::envtag($myrepo->getEnv()) ?></p>
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
                <h6 class="margin-top-0">ACCESS CHART</h6>
                <p class="note">This chart shows the number of accesses to the repository snapshot over time.</p>
            </div>

            <div>
                <?php
                if (!empty($repoAccessChartDates) and !empty($repoAccessChartData)) : ?>
                    <div class="flex column-gap-10">
                        <span class="btn-medium-blue repo-access-chart-filter-button" filter="1week">1 week</span>
                        <span class="btn-medium-blue repo-access-chart-filter-button" filter="1month">1 month</span>
                        <span class="btn-medium-blue repo-access-chart-filter-button" filter="3months">3 months</span>
                        <span class="btn-medium-blue repo-access-chart-filter-button" filter="6months">6 months</span>
                        <span class="btn-medium-blue repo-access-chart-filter-button" filter="1year">1 year</span>
                    </div>
                    <?php
                endif ?>
            </div>
        </div>
        
        <?php
        if (!empty($repoAccessChartDates) and !empty($repoAccessChartData)) : ?>
            <div id="repo-access-chart-canvas">
                <?php
                /**
                 *  On place deux span à l'intérieur du canvas, qui contiennent les valeurs 'labels' et 'data' du chart en cours
                 *  Utilisés par jqeury pour récupérer de nouvelles valeurs en fonction du filtre choisi par l'utilisateur (1week...)
                 */ ?>
                <canvas id="repo-access-chart">
                    <span id="repo-access-chart-labels" labels="<?= $repoAccessChartDates ?>"></span>
                    <span id="repo-access-chart-data" data="<?= $repoAccessChartData ?>"></span>
                </canvas>

                <script>
                    var ctx = document.getElementById('repo-access-chart').getContext('2d');
                    var myRepoAccessChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: [<?= $repoAccessChartDates ?>],
                            datasets: [{
                                data: [<?= $repoAccessChartData ?>],
                                label: "Total access",
                                borderColor: '#15bf7f',
                                fill: false
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,                            
                            tension: 0.2,
                            scales: {
                                x: {
                                    display: true,
                                },
                                y: {
                                    beginAtZero: true,
                                    display: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            },
                        }
                    });
                </script>
            </div>
            <?php
        endif ?>
    </div>

    <?php
    /**
     *  Print access logs
     */
    \Controllers\Layout\Table\Render::render('stats/access'); ?>

    <div class="flex justify-space-between margin-top-15">
        <?php
        /**
         *  Print snapshot size and packages count charts
         */
        if (!empty($sizeDateLabels) and !empty($sizeData)) : ?>
            <div class="flex-div-50 div-generic-blue">
                <h6 class="margin-top-0">REPOSITORY SIZE</h6>
                <p class="note">This chart shows the size of the repository snapshot over time.</p>

                <canvas id="repoSizeChart"></canvas>
                <script>
                    var ctx = document.getElementById('repoSizeChart').getContext('2d');
                    var myRepoSizeChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: [<?= $sizeDateLabels ?>],    
                            datasets: [{
                                data: [<?= $sizeData ?>],
                                label: 'Size in MB (last 60 days)',
                                borderColor: '#15bf7f',
                                fill: false
                            }]
                        },
                        options: {
                            tension: 0.2,
                            scales: {
                                x: {
                                    display: true,
                                },
                                y: {
                                    display: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            },
                        }
                    });
                </script>
            </div>
            <?php
        endif;

        if (!empty($countDateLabels) and !empty($countData)) : ?>
            <div class="flex-div-50 div-generic-blue">
                <h6 class="margin-top-0">REPOSITORY PACKAGES COUNT</h6>
                <p class="note">This chart shows the number of packages in the repository snapshot over time.</p>

                <canvas id="repoPackagesCountChart"></canvas>
                <script>
                    var ctx = document.getElementById('repoPackagesCountChart').getContext('2d');
                    var myRepoPackagesCountChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: [<?= $countDateLabels ?>],    
                            datasets: [{
                                data: [<?= $countData ?>],
                                label: 'Total packages (last 60 days)',
                                borderColor: '#15bf7f',
                                fill: false
                            }]
                        },
                        options: {
                            tension: 0.2,
                            scales: {
                                x: {
                                    display: true,
                                },
                                y: {
                                    display: true,
                                    ticks: {
                                        stepSize: 1,
                                    }
                                }
                            },
                        }
                    });
                </script>
            </div>
            <?php
        endif ?>
    </div>

    <div class="flex justify-space-between margin-top-15">
        <?php
        /**
         *  Print access logs
         */
        \Controllers\Layout\Table\Render::render('stats/ip-access'); ?>
    </div>
</section>
