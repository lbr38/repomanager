<div class="flex align-item-center column-gap-15 justify-space-between">
    <h3><?= !empty($taskId) ? 'TASK #' . $taskId : 'TASK' ?></h3>
</div>

<div class="empty-state margin-top-0">
    <div>
        <p class="empty-state-title">Task not executed yet!</p>
        <p class="note">This task has not been executed yet. Please check back later for the task log.</p>
    </div>
</div>

<a href="/tasks"><button type="button" class="btn-fit-tr">Go back to tasks list</button></a>
