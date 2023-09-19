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

    <?php
    /**
     *  Retrieve last access logs from database
     */
    if ($myrepo->getPackageType() == 'rpm') {
        $lastAccess = $mystats->getLastAccess($myrepo->getName(), '', '', $myrepo->getEnv());
    }
    if ($myrepo->getPackageType() == 'deb') {
        $lastAccess = $mystats->getLastAccess($myrepo->getName(), $myrepo->getDist(), $myrepo->getSection(), $myrepo->getEnv());
    }

    /**
     *  Sort by date and time
     */
    if (!empty($lastAccess)) {
        array_multisort(array_column($lastAccess, 'Date'), SORT_DESC, array_column($lastAccess, 'Time'), SORT_DESC, $lastAccess);
    }

    /**
     *  Count repo size and packages count
     */
    if ($myrepo->getPackageType() == 'rpm') {
        $repoSize = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getName());
        $packagesCount = count(\Controllers\Common::findRecursive(REPOS_DIR . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getName(), 'rpm'));
    }
    if ($myrepo->getPackageType() == 'deb') {
        $repoSize = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getSection());
        $packagesCount = count(\Controllers\Common::findRecursive(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getSection(), 'deb'));
    }

    /**
     *  Convert repo size in the most suitable byte format
     */
    $repoSize = \Controllers\Common::sizeFormat($repoSize); ?>

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
        /**
         *  Si aucun filtre n'a été sélectionné par l'utilisateur alors on le set à 1 semaine par défaut
         */
        if (empty($repo_access_chart_filter)) {
            $repo_access_chart_filter = "1week";
        }

        /**
         *  Initialisation de la date de départ du graphique, en fonction du filtre choisi
         */
        if ($repo_access_chart_filter == "1week") {
            $dateCounter = date('Y-m-d', strtotime('-1 week', strtotime(DATE_YMD))); // le début du compteur commence à la date actuelle -1 semaine
        }
        if ($repo_access_chart_filter == "1month") {
            $dateCounter = date('Y-m-d', strtotime('-1 month', strtotime(DATE_YMD))); // le début du compteur commence à la date actuelle -1 mois
        }
        if ($repo_access_chart_filter == "3months") {
            $dateCounter = date('Y-m-d', strtotime('-3 months', strtotime(DATE_YMD))); // le début du compteur commence à la date actuelle -3 mois
        }
        if ($repo_access_chart_filter == "6months") {
            $dateCounter = date('Y-m-d', strtotime('-6 months', strtotime(DATE_YMD))); // le début du compteur commence à la date actuelle -6 mois
        }
        if ($repo_access_chart_filter == "1year") {
            $dateCounter = date('Y-m-d', strtotime('-1 year', strtotime(DATE_YMD))); // le début du compteur commence à la date actuelle -1 an
        }

        $repoAccessChartLabels = '';
        $repoAccessChartData = '';

        /**
         *  On traite toutes les dates jusqu'à atteindre la date du jour (qu'on traite aussi)
         */
        while ($dateCounter != date('Y-m-d', strtotime('+1 day', strtotime(DATE_YMD)))) {
            if ($myrepo->getPackageType() == 'rpm') {
                $dateAccessCount = $mystats->getDailyAccessCount($myrepo->getName(), '', '', $myrepo->getEnv(), $dateCounter);
            }
            if ($myrepo->getPackageType() == 'deb') {
                $dateAccessCount = $mystats->getDailyAccessCount($myrepo->getName(), $myrepo->getDist(), $myrepo->getSection(), $myrepo->getEnv(), $dateCounter);
            }
            if (!empty($dateAccessCount)) {
                $repoAccessChartData .= $dateAccessCount . ', ';
            } else {
                $repoAccessChartData .= '0, ';
            }
            /**
             *  Ajout de la date en cours aux labels
             */
            $repoAccessChartLabels .= "'$dateCounter', ";

            /**
             *  On incrémente de 1 jour pour pouvori traiter la date suivante
             */
            $dateCounter = date('Y-m-d', strtotime('+1 day', strtotime($dateCounter)));
        }

        /**
         *  Suppression de la dernière virgule
         */
        $repoAccessChartLabels = rtrim($repoAccessChartLabels, ', ');
        $repoAccessChartData  = rtrim($repoAccessChartData, ', ');

        if (!empty($repoAccessChartLabels) and !empty($repoAccessChartData)) : ?>
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
                    <span id="repo-access-chart-labels" labels="<?= $repoAccessChartLabels ?>"></span>
                    <span id="repo-access-chart-data" data="<?= $repoAccessChartData ?>"></span>
                </canvas>

                <script>
                    var ctx = document.getElementById('repo-access-chart').getContext('2d');
                    var myRepoAccessChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: [<?= $repoAccessChartLabels ?>],
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
         *  Get stats for the last 60 days
         */
        $stats = $mystats->getAll($myrepo->getEnvId());
        $envSizeStats = $mystats->getEnvSize($myrepo->getEnvId(), 60);
        $pkgCountStats = $mystats->getPkgCount($myrepo->getEnvId(), 60);

        /**
         *  Snapshot size (by its env Id)
         */
        if (!empty($envSizeStats)) {
            $sizeDateLabels = '';
            $sizeData = '';

            foreach ($envSizeStats as $stat) {
                $date = DateTime::createFromFormat('Y-m-d', $stat['Date'])->format('d-m-Y');
                // Convert bytes to MB
                $size = round(round($stat['Size'] / 1024) / 1024);

                /**
                 *  Build data for chart
                 */
                $sizeDateLabels .= '"' . $date . '", ';
                $sizeData .= '"' . $size . '", ';
            }

            /**
             *  Remove last comma
             */
            $sizeDateLabels = rtrim($sizeDateLabels, ', ');
            $sizeData   = rtrim($sizeData, ', ');
        }

        /**
         *  Snapshot package count (by its env Id)
         */
        if (!empty($pkgCountStats)) {
            $countDateLabels = '';
            $countData = '';

            foreach ($pkgCountStats as $stat) {
                $date = DateTime::createFromFormat('Y-m-d', $stat['Date'])->format('d-m-Y');
                $count = $stat['Packages_count'];

                /**
                 *  Build data for chart
                 */
                $countDateLabels .= '"' . $date . '", ';
                $countData .= '"' . $count . '", ';
            }

            /**
             *  Remove last comma
             */
            $countDateLabels = rtrim($countDateLabels, ', ');
            $countData  = rtrim($countData, ', ');
        }

        /**
         *  Print charts
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