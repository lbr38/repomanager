var display = getCookie('display-log');
showHideLog(display);

function showHideLog(display)
{
    if (display == 'true') {
        $('.getPackagesDiv').css('display', 'block');
        $('.signRepoDiv').css('display', 'block');
        $('.createRepoDiv').css('display', 'block');
        $('#display-log-btn').attr('display', 'false');

        document.cookie = "display-log=true; Secure";
    }

    if (display == 'false') {
        $('.getPackagesDiv').css('display', 'none');
        $('.signRepoDiv').css('display', 'none');
        $('.createRepoDiv').css('display', 'none');
        $('#display-log-btn').attr('display', 'true');

        document.cookie = "display-log=false; Secure";
    }
}

/**
 *  Event: print full log
 */
$(document).on('click','#display-log-btn',function () {
    var display = $(this).attr('display');
    showHideLog(display);
});

/**
 *  Event: show logfile content
 */
$(document).on('click','.task-logfile-btn',function () {
    var logfile = $(this).attr('logfile');

    setCookie('task-log', logfile, 1);

    reloadContainer('tasks/log');
});

/**
 *  Event: relaunch task
 */
$(document).on('click','.relaunch-task-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var taskId = $(this).attr('task-id');

    ajaxRequest(
        // Controller:
        'task',
        // Action:
        'relaunchTask',
        // Data:
        {
            taskId: taskId,
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    );
});

/**
 *  Event: show or hide scheduled task informations
 */
$(document).on('click','.show-scheduled-task-info-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var taskId = $(this).attr('task-id');

    /**
     *  Show or hide task informations
     */
    $('.scheduled-task-info[task-id="' + taskId + '"]').toggle();
});

/**
 *  Event: disable scheduled task execution
 */
$(document).on('click','.disable-scheduled-task-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var taskId = $(this).attr('task-id');

    ajaxRequest(
        // Controller:
        'task',
        // Action:
        'disableTask',
        // Data:
        {
            taskId: taskId,
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload containers:
        ['tasks/list']
    );
});

/**
 *  Event: enable scheduled task execution
 */
$(document).on('click','.enable-scheduled-task-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var taskId = $(this).attr('task-id');

    ajaxRequest(
        // Controller:
        'task',
        // Action:
        'enableTask',
        // Data:
        {
            taskId: taskId,
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload containers:
        ['tasks/list']
    );
});

/**
 *  Event: cancel scheduled task
 */
$(document).on('click','.cancel-scheduled-task-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var taskId = $(this).attr('task-id');

    confirmBox(
        {
            'title': 'Cancel and delete scheduled task',
            'message': 'Are you sure you want to cancel and delete this task?',
            'buttons': [
            {
                'text': 'Delete',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'task',
                        // Action:
                        'deleteTask',
                        // Data:
                        {
                            taskId: taskId,
                        },
                        // Print success alert:
                        true,
                        // Print error alert:
                        true,
                        // Reload containers:
                        ['tasks/list']
                    )
                }
            }]
        }
    );
});
