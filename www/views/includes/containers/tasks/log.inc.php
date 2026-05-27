<?php
use \Controllers\Task\Task;
use \Controllers\App\DebugMode ?>

<section class="section-main reloadable-container" container="tasks/log">
    <h3>TASK #<?= $taskId ?></h3>

    <div id="task-refresh-container" task-id="<?= $taskId ?>" task-status="<?= $taskInfo['Status'] ?>" <?php echo ($legacyLog === true) ? 'legacy="true"' : '' ?>>
        <div class="flex flex-wrap align-item-center column-gap-15 row-gap-15 margin-bottom-15 kpi-container">
            <div class="kpi-card kpi-status-<?= $taskInfo['Status'] ?>">
                <?php
                if ($taskInfo['Status'] == 'done') {
                    $status = 'Success'; // Override status text for better readability
                    $icon = 'check';
                } elseif ($taskInfo['Status'] == 'error') {
                    $status = 'Failed'; // Override status text for better readability
                    $icon = 'warning-red';
                } elseif ($taskInfo['Status'] == 'stopped') {
                    $icon = 'warning-red';
                } elseif ($taskInfo['Status'] == 'scheduled') {
                    $icon = 'time';
                } elseif ($taskInfo['Status'] == 'running') {
                    $icon = 'loading';
                } ?>

                <img src="/assets/icons/<?= $icon ?>.svg" class="icon-np icon-medium" />
                <div>
                    <div class="flex align-item-center column-gap-10">
                        <p class="kpi-value"><?= ucfirst($status ?? $taskInfo['Status']) ?></p>
                        <?php
                        if ((DEVEL or DebugMode::enabled()) and file_exists(MAIN_LOGS_DIR . '/repomanager-task-' . $taskId . '-log.process')) {
                            echo '<img src="/assets/icons/file.svg" class="icon view-task-process-log" task-id="' . $taskId . '" title="Debug log" />';
                        } ?>
                    </div>
                    <p class="mediumopacity-cst">Status</p>
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
                <img src="/assets/icons/package.svg" class="icon-np icon-medium" />
                <div>
                    <p class="kpi-value"><?= DateTime::createFromFormat('Y-m-d', $taskInfo['Date'])->format('d-m-Y') ?> <?= $taskInfo['Time'] ?></p>
                    <p class="mediumopacity-cst">Date</p>
                </div>
            </div>        
        </div>

        <?= $output ?>
    </div>

    <div id="scroll-btns-container">
        <div id="scroll-btns">
            <?php
            // Print the auto scroll button only if the task is running
            if (true) :
            // if ($taskInfo['Status'] == 'running') : ?>
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
</section>