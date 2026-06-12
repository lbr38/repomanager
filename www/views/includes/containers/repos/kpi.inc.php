<h3 class="margin-bottom-40">REPOSITORIES</h3>

<section class="section-main reloadable-container" container="repos/kpi">
    <div class="flex flex-wrap column-gap-15 row-gap-15 margin-bottom-10 kpi-container">
        <div class="kpi-card">
            <img src="/assets/icons/package.svg" class="icon-np icon-medium" />
            <div>
                <p class="kpi-value"><?= $totalRepos ?></p>
                <p class="mediumopacity-cst"><?= $totalRepos <= 1 ? 'Repository' : 'Repositories' ?></p>
            </div>
        </div>

        <div class="kpi-card">
            <img src="/assets/icons/disk.svg" class="icon-np icon-medium" />
            <div class="width-100">
                <p class="kpi-value"><?= $diskUsedSpacePercent ?>%</p>
                <div class="flex align-item-center justify-space-between">
                    <p class="mediumopacity-cst">Used storage</p>
                    <p class="mediumopacity-cst margin-top-5"><?= $diskUsedSpaceHuman ?> / <?= $diskFreeSpaceHuman ?> free</p>
                </div>
                <div class="storage-meter margin-top-10" title="<?= $diskUsedSpacePercent ?>% used storage">
                    <span style="width: <?= $diskUsedSpacePercent ?>%"></span>
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
            } ?>
            <a href="/task/<?= $lastScheduledTask['Id'] ?>" class="kpi-card">
                <img src="/assets/icons/<?= $lastTaskIcon ?>.svg" class="icon-np icon-medium" />
                <div>
                    <p class="kpi-value"><?= $lastTaskValue ?></p>
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
