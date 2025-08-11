/**
 *  Event: stop task
 */
$(document).on('click','.stop-task-btn',function () {
    var taskId = $(this).attr('task-id');

    myalert.print('Stopping task...');

    ajaxRequest(
        // Controller:
        'task',
        // Action:
        'stopTask',
        // Data:
        {
            taskId: taskId
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    );
});