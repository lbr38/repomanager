<?php
use \Controllers\Utils\Generate\Html\Label; ?>

<section class="section-main reloadable-container" container="repos/kpi">
    <h3 class="margin-bottom-40">REPOSITORIES</h3>

    <div class="flex flex-wrap column-gap-20 row-gap-15 margin-bottom-10 kpi-container">
        <div class="kpi-card">
            <img src="/assets/icons/package.svg" class="icon-np icon-medium" />
            <div>
                <p class="kpi-value"><?= $totalRepos ?></p>
                <p class="mediumopacity-cst"><?= $totalRepos <= 1 ? 'Repository' : 'Repositories' ?></p>
            </div>
        </div>

        <div class="kpi-card">
            <img src="/assets/icons/disk.svg" class="icon-np icon-medium" />
            <div class="flex flex-direction-column flex-wrap width-100">
                <div class="flex flex-wrap-mobile align-item-center column-gap-30 justify-space-between">
                    <p class="kpi-value"><?= $diskUsedSpacePercent ?>%</p>

                    <div class="<?= $diskUsedSpaceClass ?>" title="<?= $diskUsedSpacePercent ?>% used storage">
                        <span style="width: <?= $diskUsedSpacePercent ?>%"></span>
                    </div>
                </div>

                <div class="flex flex-wrap align-item-center column-gap-10 row-gap-5 justify-space-between">
                    <p class="mediumopacity-cst">Used storage</p>
                    <p class="mediumopacity-cst margin-top-5"><?= $diskUsedSpaceHuman ?> used / <?= $diskFreeSpaceHuman ?> free</p>
                </div>
            </div>
        </div>

        <?php
        // KPI card: last scheduled task status
        if (!empty($lastScheduledTask) and !empty($lastScheduledTask['Date']) and !empty($lastScheduledTask['Time']) and (time() - strtotime($lastScheduledTask['Date'] . ' ' . $lastScheduledTask['Time']) <= 1296000)) :
            if ($lastScheduledTask['Status'] == 'error' or $lastScheduledTask['Status'] == 'stopped') {
                $lastTaskIcon = 'warning-red';
                $lastTaskValue = 'Failed';
            } else {
                $lastTaskIcon = 'check';
                $lastTaskValue = 'Success';
            }

            // If the task dispatched sub-tasks, its real status is a summary of their statuses
            if (!empty($lastScheduledTaskSummary)) {
                $lastTaskValue = 'Success';

                if ($lastScheduledTaskSummary['status'] == 'running') {
                    $lastTaskIcon = 'loading';
                } elseif ($lastScheduledTaskSummary['status'] == 'error') {
                    $lastTaskIcon = 'warning-red';
                } else {
                    $lastTaskIcon = 'check';
                }
            } ?>

            <a href="/task/<?= $lastScheduledTask['Id'] ?>" class="kpi-card">
                <img src="/assets/icons/<?= $lastTaskIcon ?>.svg" class="icon-np icon-medium" />
                <div>
                    <div class="flex align-item-center column-gap-10">
                        <p class="kpi-value"><?= $lastTaskValue ?></p>
                        <?php
                        // Add a label with the number of successful sub-tasks if the last scheduled task has sub-tasks
                        if (!empty($lastScheduledTaskSummary)) {
                            echo Label::white($lastScheduledTaskSummary['success'] . '/' . $lastScheduledTaskSummary['total']);
                        } ?>
                    </div>
                    <p class="mediumopacity-cst">Last scheduled task</p>
                </div>
            </a>
            <?php
        endif;

        // KPI card: next scheduled task
        if (!empty($nextScheduledTasks)) : ?>
            <a href="/tasks" class="kpi-card">
                <img src="/assets/icons/time.svg" class="icon-np icon-medium" />
                <div>
                    <p class="kpi-value"><?php
                    if ($nextScheduledTasks[0]['left']['days'] > 0) {
                        echo $nextScheduledTasks[0]['left']['days'] . ' day' . ($nextScheduledTasks[0]['left']['days'] > 1 ? 's' : '');
                    } else {
                        echo $nextScheduledTasks[0]['left']['time'];
                    } ?></p>
                    <p class="mediumopacity-cst">Next scheduled task</p>
                </div>
            </a>
            <?php
        endif ?>
    </div>
</section>
