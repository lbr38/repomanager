<?php
use \Controllers\Task\Task;
use \Controllers\App\DebugMode;
use \Controllers\User\Permission\Task as TaskPermission; ?>

<div class="flex align-item-center column-gap-15 justify-space-between">
    <div class="task-log-meta flex align-item-center flex-wrap column-gap-10 row-gap-5">
        <h3 class="margin-right-10">TASK #<?= $taskId ?></h3>

        <?php
        // If the task is a relaunch of a previous task, print a link to it
        if (!empty($taskInfo['Relaunched_from_task_id'])) : ?>
            <span class="dot mediumopacity-cst">●</span>
            <a class="task-item-relaunch" href="/task/<?= $taskInfo['Relaunched_from_task_id'] ?>" title="This task is a relaunch of task #<?= $taskInfo['Relaunched_from_task_id'] ?>, click to view it"><img src="/assets/icons/update.svg" class="icon-small mediumopacity-cst" />Relaunch of #<?= $taskInfo['Relaunched_from_task_id'] ?></a>
            <?php
        endif;

        // If the task has been relaunched, print a link to its relaunch
        if (!empty($relaunchTaskId)) : ?>
            <span class="dot mediumopacity-cst">●</span>
            <a class="task-item-relaunch" href="/task/<?= $relaunchTaskId ?>" title="This task has been relaunched by task #<?= $relaunchTaskId ?>, click to view it"><img src="/assets/icons/update.svg" class="icon-small mediumopacity-cst" />Relaunched by #<?= $relaunchTaskId ?></a>
            <?php
        endif;

        // User who launched the task
        if (!empty($taskInfo['Launched_by'])) : ?>
            <span class="dot mediumopacity-cst">●</span>
            <span class="task-item-launched-by" title="Launched by <?= htmlspecialchars($taskInfo['Launched_by'], ENT_QUOTES, 'UTF-8') ?>"><img src="/assets/icons/user.svg" class="icon-small mediumopacity-cst" /><?= htmlspecialchars($taskInfo['Launched_by'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php
        endif; ?>
    </div>

    <div class="flex align-item-center column-gap-15">
        <?php
        // If the task has a parent task, print a link to it
        if (!empty($taskInfo['Parent_task_id'])) : ?>
            <a href="/task/<?= $taskInfo['Parent_task_id'] ?>" title="View the task that generated this task"><p class="label-white pointer">View parent task</p></a>
            <?php
        endif;
        // Show the relaunch button only if the task is not a parent task and has not already been relaunched
        if (($taskInfo['Status'] == 'error' or $taskInfo['Status'] == 'stopped') and !$isParent and empty($relaunchTaskId)) :
            if (TaskPermission::allowedAction('relaunch')) : ?>
                <p id="relaunch-task-log-btn" class="label-yellow relaunch-task-btn pointer" redirect="true" task-id="<?= $taskId ?>" title="Relaunch the task">Relaunch</p>
                <?php
            endif;
        endif;

        // Print the stop button if the task is running or waiting to be run, and the user has permission to stop it
        if ($taskInfo['Status'] == 'running' or $taskInfo['Status'] == 'queued') :
            if (TaskPermission::allowedAction('stop')) : ?>
                <p id="stop-task-log-btn" class="label-red stop-task-btn pointer" task-id="<?= $taskId ?>" title="Stop the task">Stop</p>
                <?php
            endif;
        endif ?>
    </div>
</div>

<div id="task-refresh-container" task-id="<?= $taskId ?>" task-status="<?= $taskInfo['Status'] ?>" <?php echo ($legacyLog === true) ? 'legacy="true"' : '' ?>>
    <div class="flex flex-wrap align-item-center column-gap-15 row-gap-15 margin-bottom-15 kpi-container">
        <?php
        $statusText = ucfirst($taskInfo['Status']);
        $statusLabel = 'Status';

        if ($taskInfo['Status'] == 'done') {
            $statusText = 'Success'; // Override status text for better readability
            $icon = 'check';
            $accent = 'green';
        } elseif ($taskInfo['Status'] == 'error') {
            $statusText = 'Failed'; // Override status text for better readability
            $icon = 'warning-red';
            $accent = 'red';
        } elseif ($taskInfo['Status'] == 'stopped') {
            $icon = 'warning-red';
            $accent = 'red';
        } elseif ($taskInfo['Status'] == 'scheduled') {
            $icon = 'time';
            $accent = 'yellow';
        } elseif ($taskInfo['Status'] == 'running') {
            $icon = 'loading';
            $accent = 'cyan';
        }

        /**
         *  If the task has sub-tasks, its own status is meaningless as it only dispatched them,
         *  so the status displayed is a summary of the sub-tasks statuses
         */
        if (!empty($subTasksSummary)) {
            $statusText = 'Success ' . $subTasksSummary['success'] . '/' . $subTasksSummary['total'];

            if ($subTasksSummary['status'] == 'running') {
                $icon = 'loading';
                $accent = 'cyan';
            } elseif ($subTasksSummary['status'] == 'error') {
                $icon = 'warning-red';
                $accent = 'red';
            } else {
                $icon = 'check';
                $accent = 'green';
            }
        } ?>

        <div class="kpi-card kpi-accent-<?= $accent ?>">
            <img src="/assets/icons/<?= $icon ?>.svg" class="icon-np icon-medium" title="<?= $statusText ?>" />

            <div>
                <p class="kpi-value"><?= !empty($rawParams['action']) ? Task::generateLiteralAction($taskInfo)['title'] : 'Unknown action' ?></p>
                <p class="mediumopacity-cst">Action</p>
            </div>

            <div class="flex column-gap-10 margin-left-auto">
                <?php
                if (empty($subTasksSummary) and $taskInfo['Status'] == 'running' and !Task::processRunning($taskId)) {
                    echo '<img src="/assets/icons/warning-red.svg" class="icon-np" task-id="' . $taskId . '" title="No running process found for this task, the process might have crashed, you should stop this task" />';
                }

                if ((DEVEL or DebugMode::enabled()) and file_exists(MAIN_LOGS_DIR . '/repomanager-task-' . $taskId . '-log.process')) {
                    echo '<img src="/assets/icons/file.svg" class="icon view-task-process-log margin-left-auto" task-id="' . $taskId . '" title="Debug log" />';
                } ?>
            </div>
        </div>

        <?php
        // A task that has never been executed yet (e.g. a scheduled task) has no date
        $taskDate = !empty($taskInfo['Date']) ? DateTime::createFromFormat('Y-m-d', $taskInfo['Date']) : false;

        if ($taskDate !== false) : ?>
            <div class="kpi-card">
                <img src="/assets/icons/calendar.svg" class="icon-np icon-medium" />
                <div>
                    <p class="kpi-value"><?= $taskDate->format('d-m-Y') ?> <?= $taskInfo['Time'] ?></p>
                    <p class="mediumopacity-cst"><?= $taskInfo['Status'] == 'scheduled' ? 'Last execution' : 'Date' ?></p>
                </div>
            </div>
            <?php
        endif;

        if (!empty($taskInfo['Duration'])) : ?>
            <div class="kpi-card">
                <img src="/assets/icons/time.svg" class="icon-np icon-medium" />
                <div>
                    <p class="kpi-value"><?= $taskInfo['Duration'] ?></p>
                    <p class="mediumopacity-cst">Total duration</p>
                </div>
            </div>
            <?php
        endif ?>
    </div>

    <?= $output ?>
</div>

<div id="scroll-btns-container">
    <div id="scroll-btns">
        <?php
        // Print the auto scroll button if the task is running
        if ($taskInfo['Status'] == 'running') : ?>
            <div class="pointer margin-bottom-15">
                <?php
                $autoscroll = 'true';

                if (!empty($_COOKIE['autoscroll'])) {
                    $autoscroll = $_COOKIE['autoscroll'];
                }

                if ($autoscroll == 'true') : ?>
                    <div id="autoscroll-btn" class="round-btn-yellow" title="Disable auto refresh and scroll">
                        <span class="round-btn-icon" style="--icon: url('/assets/icons/pause.svg')"></span>
                    </div>
                    <?php
                else : ?>
                    <div id="autoscroll-btn" class="round-btn-green" title="Enable auto refresh and scroll">
                        <span class="round-btn-icon" style="--icon: url('/assets/icons/play.svg')"></span>
                    </div>
                    <?php
                endif ?>
            </div>
            <?php
        endif ?>
    </div>
</div>