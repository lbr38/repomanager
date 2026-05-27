<?php
use \Controllers\Layout\Table\Render as TableRender; ?>

<section class="section-main reloadable-container" container="tasks/tasks">
    <h3>TASKS</h3>

    <div class="grid grid-rfr-1-3 column-gap-20 row-gap-20 margin-bottom-20">
        <div class="kpi-card">
            <img src="/assets/icons/server.svg" class="icon-np icon-medium" />
            <div>
                <p class="kpi-value"><?= $totalCount ?></p>
                <p class="mediumopacity-cst">Total tasks</p>
            </div>
        </div>

        <div class="kpi-card">
            <img src="/assets/icons/server.svg" class="icon-np icon-medium" />
            <div>
                <p class="kpi-value"><?= $runningCount ?></p>
                <p class="mediumopacity-cst">Running tasks</p>
            </div>
        </div>

        <div class="kpi-card">
            <img src="/assets/icons/time.svg" class="icon-np icon-medium" />
            <div>
                <p class="kpi-value"><?= $scheduledCount ?></p>
                <p class="mediumopacity-cst">Scheduled tasks</p>
            </div>
        </div>
    </div>

    <div class="div-generic-blue">
        <?php
        // Print running tasks table
        TableRender::render('tasks/list-running');

        // Print queued tasks table
        TableRender::render('tasks/list-queued');

        // Print scheduled tasks table
        TableRender::render('tasks/list-scheduled');

        // Print done tasks table
        TableRender::render('tasks/list-done'); ?>
    </div>
</section>
