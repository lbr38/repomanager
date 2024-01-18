<section class="section-right reloadable-container" container="repos/properties">
    <div>
        <h3>PROPERTIES</h3>

        <div class="div-generic-blue circle-div-container-container">
            <div>
                <div class="circle-div-container">
                    <div class="circle-div-container-count-green">
                        <span>
                            <?= $totalRepos ?>
                        </span>
                    </div>
                    <div>
                        <span>Repos</span>
                    </div>
                </div>
            </div>
            <div>
                <div class="donut-chart-container">
                    <p class="donut-legend-title lowopacity-cst">Repo storage</p>
                    <span class="donut-legend-content"><?= $diskUsedSpace . '%' ?></span>
                    <?php
                        $donutChartName = 'donut-chart';
                        include(ROOT . '/views/includes/charts/index-donut.inc.php');
                    ?>
                </div>
            </div>
        </div>

        <?php
        if (!empty($lastPlan) or !empty($nextPlan)) : ?>
            <div class="div-generic-blue">
                <?php
                if (!empty($lastPlan)) :
                    if ($lastPlan['Status'] == 'done') {
                        $planStatus = 'OK';
                        $borderColor = 'green';
                    } else {
                        $planStatus = 'Error';
                        $borderColor = 'red';
                    } ?>

                    <div class="circle-div-container">
                        <div class="circle-div-container-count-<?= $borderColor ?>">
                            <span>
                                <?= $planStatus ?>
                            </span>
                        </div>
                        <div>
                            <span>
                                <a href="/plans">Last scheduled task (<?=DateTime::createFromFormat('Y-m-d', $lastPlan['Date'])->format('d-m-Y') . ' ' . $lastPlan['Time']?>)</a>
                            </span>
                        </div>
                    </div>
                    <?php
                endif;

                if (!empty($nextPlan)) :
                    /**
                     *  Calculating of many days left before next plan
                     */
                    $date_now = new DateTime(DATE_YMD);
                    $date_plan = new DateTime($nextPlan['Date']);
                    $time_now = new DateTime(date('H:i'));
                    $time_plan = new DateTime($nextPlan['Time']);
                    $days_left = $date_plan->diff($date_now);
                    $time_left = $time_plan->diff($time_now); ?>

                    <div class="circle-div-container">
                        <div class="circle-div-container-count">
                            <span>
                                <?php
                                /**
                                 *  If days left = 0 (current day) then print hours left instead
                                 */
                                if ($days_left->days == 0) {
                                    /**
                                     *  If hours left = 0 then print minutes left instead
                                     */
                                    if ($time_left->format('%h') == 0) {
                                        echo $time_left->format('%im');
                                    } else {
                                        echo $time_left->format('%hh%im');
                                    }
                                } else {
                                    echo $days_left->days . 'd';
                                } ?>
                            </span>
                        </div>
                        <div>
                            <span>
                                <a href="/plans">Next scheduled task (<?=DateTime::createFromFormat('Y-m-d', $nextPlan['Date'])->format('d-m-Y') . ' ' . $nextPlan['Time']?>)</a>
                            </span>
                        </div>
                    </div>
                    <?php
                endif; ?>
            </div>
            <?php
        endif; ?>
    </div>
</section>