/**
 *  Event: show the parameters of the selected action
 */
$(document).on('change', '#scheduled-task-action', function () {
    myscheduledtask.printActionParams();
});

/**
 *  Event: refresh the number of targeted repositories when a target filter changes
 */
$(document).on('change', '.scheduled-task-target-param', function () {
    myscheduledtask.refreshCount();
});

/**
 *  Event: create the scheduled task
 */
$(document).on('click', '.scheduled-task-confirm-btn', function () {
    myscheduledtask.schedule();
});
