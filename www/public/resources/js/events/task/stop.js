/**
 *  Event: stop task
 */
$(document).on('click','.stop-task-btn',function () {
    myalert.print('Stopping task...');

    ajaxRequest(
        // Controller:
        'task',
        // Action:
        'stop',
        // Data:
        {
            id: $(this).attr('task-id')
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    );
});