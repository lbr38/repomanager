$(document).ready(function () {
    // Clear scroll position in local storage (lastScrollPosition)
    localStorage.removeItem('lastScrollPosition');
    localStorage.removeItem('scrollLock');
    localStorage.removeItem('autorefreshLock');

    // Start log scroll event listener
    scrollEventListener();

    // Start log autorefresh, with or without autoscroll
    autorefresh();
});

/**
 *  Event: show/hide step content
 */
$(document).on('click','.show-step-content-btn',function () {
    var taskId = $(this).attr('task-id');
    var step = $(this).attr('step');

    if ($('.task-step-content[task-id="' + taskId + '"][step="' + step + '"]').is(':visible')) {
        $('.task-step-content[task-id="' + taskId + '"][step="' + step + '"]').hide();
    } else {
        $('.task-step-content[task-id="' + taskId + '"][step="' + step + '"]').css('display', 'grid');
    }
});

/**
 *  Event: show logfile
 */
$(document).on('click','.show-task-btn',function () {
    var taskId = $(this).attr('task-id');

    // Change URL without reloading the page
    history.pushState(null, null, '/run/' + taskId);

    // Reload task container to print the new task log
    mycontainer.reload('tasks/log').then(function () {
        // Restart log scroll event listener
        scrollEventListener();
    });
});

/**
 *  Event: enable / disable automatic scroll on log
 */
$(document).on('click','#autoscroll-btn',function () {
    var autoscroll = mycookie.get('autoscroll');

    // Enable autoscroll
    if (autoscroll == 'false') {
        enableAutoScroll();
    }

    // Disable autoscroll
    if (autoscroll == '' || autoscroll == 'true') {
        disableAutoScroll();
    }
});

/**
 *  Event: print all log (legacy logs)
 */
$(document).on('click','#display-log-btn',function () {
    var display = $(this).attr('display');

    if (display == 'true') {
        $('.getPackagesDiv').css('display', 'block');
        $('.signRepoDiv').css('display', 'block');
        $('.createRepoDiv').css('display', 'block');
        $('#display-log-btn').attr('display', 'false');
        $('#display-log-btn').attr('title', 'Hide details');
        $('#display-log-btn').find('img').attr('src', '/assets/icons/view.svg');
        $('.task-step-content').show();
    }

    if (display == 'false') {
        $('.getPackagesDiv').css('display', 'none');
        $('.signRepoDiv').css('display', 'none');
        $('.createRepoDiv').css('display', 'none');
        $('#display-log-btn').attr('display', 'true');
        $('#display-log-btn').attr('title', 'Show details');
        $('#display-log-btn').find('img').attr('src', '/assets/icons/view-off.svg');
        $('.task-step-content').hide();
    }
});

/**
 *  Event: go to the top in step log
 */
$(document).on('click','.step-top-btn',function () {
    // Retrieve task Id
    var taskId = $(this).attr('task-id');

    // Retrieve current step
    var step = $(this).attr('step');

    // Lock any other scroll from appending data
    localStorage.setItem('scrollLock', 'true');

    // Get logs from server
    // Server will return a html with the new logs
    ajaxRequest(
        // Controller:
        'task',
        // Action:
        'get-log-lines',
        // Data:
        {
            taskId: taskId,
            step: step,
            direction: 'top',
            key: ''
        },
        // Print success alert:
        false,
        // Print error alert:
        'console'
    ).then(function () {
        // If server returns logs, clear all and append them to the container, and set scroll bar position to 0
        if (jsonValue.message != '') {
            $('.task-step-content[task-id="' + taskId + '"][step="' + step + '"] .task-sub-step-container').html(jsonValue.message);
            $('.task-step-content[task-id="' + taskId + '"][step="' + step + '"]').scrollTop(0);
        }

        // Remove scroll lock
        localStorage.removeItem('scrollLock');
    });
});

/**
 *  Event: go to the bottom in step log
 */
$(document).on('click','.step-bottom-btn',function () {
    // Retrieve task Id
    var taskId = $(this).attr('task-id');

    // Retrieve current step
    var step = $(this).attr('step');

    // Lock any other scroll from appending data
    localStorage.setItem('scrollLock', 'true');

    // Get logs from server
    // Server will return a html with the new logs
    ajaxRequest(
        // Controller:
        'task',
        // Action:
        'get-log-lines',
        // Data:
        {
            taskId: taskId,
            step: step,
            direction: 'bottom',
            key: ''
        },
        // Print success alert:
        false,
        // Print error alert:
        'console'
    ).then(function () {
        // If server returns logs, clear all and append them to the container, and set scroll bar position to 0 from the bottom
        if (jsonValue.message != '') {
            $('.task-step-content[task-id="' + taskId + '"][step="' + step + '"] .task-sub-step-container').html(jsonValue.message);
            $('.task-step-content[task-id="' + taskId + '"][step="' + step + '"]').scrollTop($('.task-step-content[task-id="' + taskId + '"][step="' + step + '"]')[0].scrollHeight);
        }

        // Remove scroll lock
        localStorage.removeItem('scrollLock');

        // Enable autoscroll
        enableAutoScroll()
    });
});

/**
 *  Event: go up in step log
 */
$(document).on('click','.step-up-btn',function () {
    // Retrieve task Id
    var taskId = $(this).attr('task-id');

    // Retrieve current step
    var step = $(this).attr('step');

    // Move scroll bar up
    $('.task-step-content[task-id="' + taskId + '"][step="' + step + '"]').scrollTop($('.task-step-content[task-id="' + taskId + '"][step="' + step + '"]').scrollTop() - 100);
});

/**
 *  Event: go down in step log
 */
$(document).on('click','.step-down-btn',function () {
    // Retrieve task Id
    var taskId = $(this).attr('task-id');

    // Retrieve current step
    var step = $(this).attr('step');

    // Move scroll bar down
    $('.task-step-content[task-id="' + taskId + '"][step="' + step + '"]').scrollTop($('.task-step-content[task-id="' + taskId + '"][step="' + step + '"]').scrollTop() + 100);
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
 *  Event: cancel unlaunched task
 */
$(document).on('click','.cancel-task-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var taskId = $(this).attr('task-id');

    myconfirmbox.print(
        {
            'title': 'Cancel task',
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