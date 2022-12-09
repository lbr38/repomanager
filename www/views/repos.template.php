<?php
if (IS_ADMIN) :
    include_once('../includes/display.inc.php');
    include_once('../templates/forms/op-form-new.inc.php');
    include_once('../includes/operation.inc.php');
    include_once('../includes/manage-groups.inc.php');
    include_once('../includes/manage-sources.inc.php');
endif ?>

<section class="mainSectionRight">
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
            <div class="donut-chart-container">
                <p class="donut-legend-title lowopacity"><?= REPOS_DIR ?></p>
                <span class="donut-legend-content"><?= $diskUsedSpace . '%' ?></span>
                <?php
                    $donutChartName = 'donut-chart';
                    include(ROOT . '/includes/index-donut.inc.php');
                ?>
            </div>
        </div>

        <?php
        if (!empty($lastPlan or !empty($nextPlan))) : ?>
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
                                <a href="/plans">Last plan (<?=DateTime::createFromFormat('Y-m-d', $lastPlan['Date'])->format('d-m-Y') . ' at ' . $lastPlan['Time']?>)</a>
                            </span>
                        </div>
                    </div>
                    <?php
                endif;

                if (!empty($nextPlan)) :
                    /**
                     *  Calcul du nombre de jours restants avant la prochaine planification
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
                                 *  Si le nombre de jours restants = 0 (jour même) alors on affiche le nombre d'heures restantes
                                 */
                                if ($days_left->days == 0) {
                                    /**
                                     *  Si le nombre d'heures restantes = 0 alors on affiche les minutes restantes
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
                                <a href="/plans">Next plan (<?=DateTime::createFromFormat('Y-m-d', $nextPlan['Date'])->format('d-m-Y') . ' at ' . $nextPlan['Time']?>)</a>
                            </span>
                        </div>
                    </div>
                    <?php
                endif; ?>
            </div>
            <?php
        endif;

        /**
         *  Get current CPU load
         */
        $currentLoad = sys_getloadavg();
        $currentLoad = substr($currentLoad[0], 0, 4);
        $borderColor = 'green';

        if ($currentLoad >= 1) {
            $borderColor = 'yellow';
        }
        if ($currentLoad >= 2) {
            $borderColor = 'red';
        }

        /**
         *  Print CPU load only if >= 1
         */
        if ($currentLoad >= 1) : ?>
            <div class="div-generic-blue circle-div-container-container">
                <div class="circle-div-container">
                    <div class="circle-div-container-count-<?=$borderColor?>">
                        <span>
                            <?= $currentLoad ?>
                        </span>
                    </div>
                    <div>
                        <span>CPU load</span>
                    </div>
                </div>
            </div>
            <?php
        endif ?>
    </div>
</section>

<section class="mainSectionLeft">
    <div class="reposList">
        <?php include_once('../includes/repos-list-container.inc.php'); ?>
    </div>
</section>
