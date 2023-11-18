<section class="section-main">
    <h3>STATISTICS & METRICS</h3>

    <?php
    if ($myrepo->getPackageType() == 'rpm') {
        echo '<p>Statistics of <span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>⟶' . \Controllers\Common::envtag($myrepo->getEnv()) . '</p>';
    }
    if ($myrepo->getPackageType() == 'deb') {
        echo '<p>Statistics of <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>⟶' . \Controllers\Common::envtag($myrepo->getEnv()) . '</p>';
    } ?>

    <br>

    <div class="div-generic-blue grid grid-2">
        <div>
            <div class="circle-div-container">
                <div class="circle-div-container-count-green">
                    <span>
                        <?= $repoSize ?>
                    </span>
                </div>
                <div>
                    <span>Repo size</span>
                </div>
            </div>
        </div>

        <div>
            <div class="circle-div-container">
                <div class="circle-div-container-count-green">
                    <span>
                        <?= $packagesCount ?>
                    </span>
                </div>
                <div>
                    <span>Total packages</span>
                </div>
            </div>
        </div>
    </div>

    <div id="repo-access-chart-div" class="div-generic-blue">
        <?php
        if (!empty($repoAccessChartDates) and !empty($repoAccessChartData)) : ?>
            <span class="btn-small-green repo-access-chart-filter-button" filter="1week">1 week</span>
            <span class="btn-small-green repo-access-chart-filter-button" filter="1month">1 month</span>
            <span class="btn-small-green repo-access-chart-filter-button" filter="3months">3 months</span>
            <span class="btn-small-green repo-access-chart-filter-button" filter="6months">6 months</span>
            <span class="btn-small-green repo-access-chart-filter-button" filter="1year">1 year</span>
            <br><br>
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
                                borderColor: '#5473e8',
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
     *  Tableau des derniers logs d'accès
     */ ?>
    <div class="div-generic-blue">
        <p class="center lowopacity-cst">Last access requests</p>
        <table class="stats-access-table">
            <?php
            if (!empty($lastAccess)) { ?>
                <thead>
                    <tr>
                        <td class="td-10"></td>
                        <td class="td-100">Date</td>
                        <td class="td-100">Source</td>
                        <td>Target file</td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($lastAccess as $access) :
                        /**
                         *  Récupération de la cible (le paquet ou le fichier téléchargé) à partir de la requête
                         *  Ici le preg_match permet de récupérer le nom du paquet ou du fichier ciblé dans l'URL complète
                         *  Il récupère une occurence composée de lettres, de chiffres et de caractères spéciaux et qui commence par un slash '/' et se termine par un espace [[:space:]]
                         *
                         *  Par exemple :
                         *  GET /repo/debian-security/buster/updates/main_test/pool/main/b/bind9/bind9-host_9.11.5.P4%2bdfsg-5.1%2bdeb10u6_amd64.deb HTTP/1.1
                         *                                                                      |                                                   |
                         *                                                                      |_                                                  |_
                         *                                                                        |                                                   |
                         *                                                                preg_match récupère l'occurence située entre un slash et un espace
                         *  Il récupère uniquement une occurence comportant des lettres, des chiffres et certains caractères spéciaux comme - _ . et %
                         */
                        preg_match('#/[a-zA-Z0-9\%_\.-]+[[:space:]]#i', $access['Request'], $accessTarget);
                        $accessTarget[0] = str_replace('/', '', $accessTarget[0]); ?>
                        <tr>
                            <td class="td-10">
                                <?php
                                if ($access['Request_result'] == "200" or $access['Request_result'] == "304") {
                                    echo '<img src="/assets/icons/greencircle.png" class="icon-small" title="' . $access['Request_result'] . '" />';
                                } else {
                                    echo '<img src="/assets/icons/redcircle.png" class="icon-small" title="' . $access['Request_result'] . '" />';
                                } ?>
                            </td>
                            <td class="td-100"><?= DateTime::createFromFormat('Y-m-d', $access['Date'])->format('d-m-Y') . ' ' . $access['Time'] ?></td>
                            <td class="td-100"><?= $access['Source'] . ' (' . $access['IP'] . ')' ?></td>
                            <td><span title="<?= str_replace('"', '', $access['Request']) ?>"><?= $accessTarget[0] ?></span></td>
                        </tr>
                        <?php
                    endforeach; ?>
                </tbody>
                <?php
            } else {
                echo "<tr><td>No access request was found.</td></tr>";
            } ?>
        </table>
    </div>
    
    <div class="div-flex">
        <?php
        /**
         *  Print snapshot size and packages count charts
         */
        if (!empty($sizeDateLabels) and !empty($sizeData)) : ?>
            <div class="flex-div-50 div-generic-blue">
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
                                borderColor: '#5473e8',
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
                                borderColor: '#5473e8',
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
</section>