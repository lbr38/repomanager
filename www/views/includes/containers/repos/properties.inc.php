<section class="section-right reloadable-container" container="repos/properties">
    <div>
        <h3>PROPERTIES</h3>

        <div class="div-generic-blue flex align-item-center justify-space-between padding-right-40">
            <div>
                <div class="flex column-gap-15 align-item-center">
                    <div class="circle-div-container-count-green">
                        <span>
                            <?= $totalRepos ?>
                        </span>
                    </div>
                    <div>
                        <span>
                            <?php
                            if ($totalRepos <= 1) {
                                echo 'Repository';
                            } else {
                                echo 'Repositories';
                            } ?>
                        </span>
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
        if (!empty($lastScheduledTask) or !empty($nextScheduledTasks)) : ?>
            <div class="div-generic-blue flex-direction-column flex row-gap-15">
                <?php
                if (!empty($lastScheduledTask) and !empty($lastScheduledTask['Date']) and !empty($lastScheduledTask['Time'])) :
                    if ($lastScheduledTask['Status'] == 'done') {
                        $taskStatus = 'OK';
                        $borderColor = 'green';
                    } else {
                        $taskStatus = 'Error';
                        $borderColor = 'red';
                    } ?>

                    <h5>Last scheduled task</h5>

                    <div class="circle-div-container">
                        <div class="circle-div-container-count-<?= $borderColor ?>">
                            <span>
                                <?= $taskStatus ?>
                            </span>
                        </div>
                        <div>
                            <span>
                                <a href="/run"><?= DateTime::createFromFormat('Y-m-d', $lastScheduledTask['Date'])->format('d-m-Y') . ' ' . $lastScheduledTask['Time'] ?></a>
                            </span>
                        </div>
                    </div>
                    <?php
                endif;

                if (!empty($nextScheduledTasks)) : ?>
                    <h5>Next scheduled tasks</h5>

                    <?php
                    foreach ($nextScheduledTasks as $scheduledTask) : ?>
                        <div class="flex column-gap-15 align-item-center">
                            <div class="circle-div-container-count">
                                <span>
                                    <?php
                                    /**
                                     *  If days left = 0 (current day) then print hours left instead
                                     */
                                    if ($scheduledTask['left']['days'] > 0) {
                                        echo $scheduledTask['left']['days'] . 'd';
                                    } else {
                                        echo $scheduledTask['left']['time'];
                                    } ?>
                                </span>
                            </div>

                            <div>
                                <p>
                                    <a href="/run"> 
                                        <?php
                                        if (!empty($scheduledTask['date'])) {
                                            echo $scheduledTask['date'] . ' ';
                                        }

                                        if (!empty($scheduledTask['time'])) {
                                            echo $scheduledTask['time'];
                                        } ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        <?php
                    endforeach;
                endif ?>
            </div>
            <?php
        endif ?>
    </div>
</section>