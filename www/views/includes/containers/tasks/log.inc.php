<?php
use \Controllers\Task\Task; 
use DateTime; ?>

<section class="section-main reloadable-container" container="tasks/log">
    <h3>TASK #<?= $taskId ?></h3>

    <div class="flex flex-wrap align-item-center column-gap-10 row-gap-15 margin-bottom-10 kpi-container">
        <div class="kpi-card">
            <img src="/assets/icons/package.svg" class="icon-np icon-medium" />
            <div>
                <p class="kpi-value"><?= DateTime::createFromFormat('Y-m-d', $taskInfo['Date'])->format('d-m-Y') ?> <?= $taskInfo['Time'] ?></p>
                <p class="mediumopacity-cst">Date</p>
            </div>
        </div>

        <div class="kpi-card">
            <img src="/assets/icons/package.svg" class="icon-np icon-medium" />
            <div>
                <p class="kpi-value"><?= Task::generateLiteralAction($rawParams['action']) ?></p>
                <p class="mediumopacity-cst">Action</p>
            </div>
        </div>

        <div class="kpi-card">
            <?php
            if ($taskInfo['Status'] == 'done') {
                $icon = 'check';
            } elseif ($taskInfo['Status'] == 'error' or $taskInfo['Status'] == 'stopped') {
                $icon = 'warning-red';
            } elseif ($taskInfo['Status'] == 'scheduled') {
                $icon = 'time';
            } elseif ($taskInfo['Status'] == 'running') {
                $icon = 'loading';
            } ?>

            <img src="/assets/icons/<?= $icon ?>.svg" class="icon-np icon-medium" />
            <div>
                <p class="kpi-value"><?= ucfirst($taskInfo['Status']) ?></p>
                <p class="mediumopacity-cst">Status</p>
            </div>
        </div>
    </div>










    <div class="flex justify-space-between">
        <div id="log-refresh-container" task-id="<?= $taskId ?>" task-status="<?= $taskInfo['Status'] ?>" <?php echo ($legacyLog === true) ? 'legacy="true"' : '' ?>>
            <?= $output ?>
        </div>

        <div id="scroll-btns-container">
            <div id="scroll-btns">
                <?php
                // Print the auto scroll button only if the task is running
                if ($taskInfo['Status'] == 'running') : ?>
                    <div class="pointer margin-bottom-15">
                        <?php
                        $autoscroll = 'true';

                        if (!empty($_COOKIE['autoscroll'])) {
                            $autoscroll = $_COOKIE['autoscroll'];
                        }

                        if ($autoscroll == 'true') : ?>
                            <div id="autoscroll-btn" class="round-btn-yellow" title="Disable auto refresh and scroll">
                                <img src="/assets/icons/pause.svg" />
                            </div>
                            <?php
                        else : ?>
                            <div id="autoscroll-btn" class="round-btn-green" title="Enable auto refresh and scroll">
                                <img src="/assets/icons/play.svg" />
                            </div>
                            <?php
                        endif ?>
                    </div>
                    <?php
                endif ?>
            </div>
        </div>
    </div>
</section>