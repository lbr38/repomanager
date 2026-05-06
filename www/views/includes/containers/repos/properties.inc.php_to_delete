<section class="section-right reloadable-container" container="repos/properties">
    <h3>PROPERTIES</h3>

    <div class="grid grid-2 column-gap-15 align-item-center justify-space-between div-generic-blue padding-right-40">
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

        <!-- <div class="echart-container width-100">
            <div id="repo-storage-chart-loading" class="echart-loading">
                <img src="/assets/icons/loading.svg" class="icon-np" />
            </div>

            <p class="donut-legend-title lowopacity-cst">Storage</p>
            <span class="donut-legend-content"><?= $diskUsedSpacePercent . '%' ?></span>

            <div id="repo-storage-chart" class="echart min-height-120"></div>
        </div> -->
    </div>

    <?php
    if (!empty($lastScheduledTask) or !empty($nextScheduledTasks)) : ?>
        <div class="div-generic-blue flex-direction-column flex row-gap-20">
            <?php
            if (!empty($lastScheduledTask) and !empty($lastScheduledTask['Date']) and !empty($lastScheduledTask['Time'])) : ?>
                <div>
                    <?php
                    if ($lastScheduledTask['Status'] == 'error' or $lastScheduledTask['Status'] == 'stopped') {
                        $taskStatus = 'Error';
                        $borderColor = 'red';
                    } else {
                        $taskStatus = 'OK';
                        $borderColor = 'green';
                    } ?>

                    <h6 class="margin-top-0">LAST SCHEDULED TASK</h6>
                    <br>
                    <div class="flex column-gap-15 align-item-center">
                        <div class="circle-div-container-count-<?= $borderColor ?>">
                            <span>
                                <?= $taskStatus ?>
                            </span>
                        </div>
                        <div>
                            <span>
                                <a href="/run/<?= $lastScheduledTask['Id'] ?>"><?= DateTime::createFromFormat('Y-m-d', $lastScheduledTask['Date'])->format('d-m-Y') . ' ' . $lastScheduledTask['Time'] ?></a>
                            </span>
                        </div>
                    </div>
                </div>
                <?php
            endif ?>

            <?php
            if (!empty($nextScheduledTasks)) : ?>
                <div>
                    <h6 class="margin-top-0">NEXT SCHEDULED TASKS</h6>
                    <br>

                    <div class="flex flex-direction-column row-gap-15">
                        <?php
                        foreach ($nextScheduledTasks as $scheduledTask) : ?>
                            <div class="flex column-gap-15 align-item-center">
                                <div class="circle-div-container-count-yellow">
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
                                                echo DateTime::createFromFormat('Y-m-d', $scheduledTask['date'])->format('d-m-Y') . ' ';
                                            }

                                            if (!empty($scheduledTask['time'])) {
                                                echo $scheduledTask['time'];
                                            } ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                            <?php
                        endforeach ?>
                    </div>
                </div>
                <?php
            endif ?>
        </div>
        <?php
    endif ?>

    <script>
        // $(document).ready(function() {
        //     new EChart('doughnut', 'repo-storage-chart');
        // });
    </script>
</section>