<div class="flex align-item-center column-gap-15 justify-space-between">
    <h3><?= !empty($taskId) ? 'TASK #' . $taskId : 'TASK' ?></h3>
</div>

<div class="flex flex-wrap align-item-center column-gap-15 row-gap-15 margin-bottom-15 kpi-container">
    <div class="kpi-card kpi-accent-red">
        <img src="/assets/icons/warning-red.svg" class="icon-np icon-medium" />
        <div>
            <p class="kpi-value">Error</p>
            <p class="mediumopacity-cst"><?= !empty($taskErrorMessage) ? $taskErrorMessage : 'This task does not exist or has been removed.' ?></p>
        </div>
    </div>
</div>

<p class="note">Go back to the <a href="/tasks" class="font-size-13"><b>tasks list</b></a></p>