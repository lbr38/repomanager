/**
 *  Start task log autorefresh
 */
function autorefresh()
{
    // Ignore refresh if 'legacy' attribute is set
    if ($('#log-refresh-container').attr('legacy') == 'true') {
        return;
    }

    // Autorefresh with new steps and content every 2sec
    setInterval(function () {
        // Retrieve task Id
        var taskId = $('#log-refresh-container').attr('task-id');

        // Ignore refresh if task is not running (wait for 2sec and try again)
        if ($('#log-refresh-container').attr('task-status') != 'running') {
            return;
        }

        // Ignore refresh if an autorefresh is still running and is not finished yet
        if (localStorage.getItem('autorefreshLock') == 'true') {
            return;
        }

        // Lock interval to prevent multiple refresh at the same time
        localStorage.setItem('autorefreshLock', 'true');

        // Get all task step
        ajaxRequest(
            // Controller:
            'task',
            // Action:
            'get-steps',
            // Data:
            {
                taskId: taskId
            },
            // Print success alert:
            false,
            // Print error alert:
            'console'
        ).then(function () {
            // Refresh each step in the DOM
            refreshStepsInDOM(JSON.parse(jsonValue.message)).then(function () {
                // Restart scroll event listener after each step refresh
                scrollEventListener();
            });

            /**
             *  Get current task status, then refresh the task status in the DOM
             */
            ajaxRequest(
                // Controller:
                'task',
                // Action:
                'get-task-status',
                // Data:
                {
                    taskId: taskId
                },
                // Print success alert:
                false,
                // Print error alert:
                'console'
            ).then(function () {
                var status = jsonValue.message;

                // Refresh task status in the DOM, if the task is not running anymore, this will prevent the task from refreshing again
                $('#log-refresh-container').attr('task-status', status);

                // Remove the autoscroll button if the task is done, error or stopped
                if (status == 'done' || status == 'error' || status == 'stopped') {
                    $('#autoscroll-btn').remove();
                }
            });
        }).finally(function () {
            // Remove autorefresh lock
            localStorage.removeItem('autorefreshLock');
        });
    }, 2000);
}

/**
 *  Enable autoscroll
 */
function enableAutoScroll()
{
    // Autoscroll can be enabled only if the task is running
    if ($('#log-refresh-container').attr('task-status') == 'running') {
        // Set autoscroll cookie to true to enable autoscroll
        setCookie('autoscroll', 'true');

        $('#autoscroll-btn').find('img').attr('src', '/assets/icons/pause.svg');
        $('#autoscroll-btn').addClass('round-btn-yellow').removeClass('round-btn-green');
        $('#autoscroll-btn').attr('title', 'Disable auto refresh and scroll');

        console.log('Autoscroll enabled');
    }

    // Restart scroll event listener
    scrollEventListener();
}

/**
 *  Disable autoscroll
 */
function disableAutoScroll()
{
    // Get current autoscroll status
    if (getCookie('autoscroll') == '' || getCookie('autoscroll') == 'true') {
        // Set autoscroll cookie to false to disable autoscroll
        setCookie('autoscroll', 'false');

        $('#autoscroll-btn').find('img').attr('src', '/assets/icons/play.svg');
        $('#autoscroll-btn').addClass('round-btn-green').removeClass('round-btn-yellow');
        $('#autoscroll-btn').attr('title', 'Enable auto refresh and scroll');

        // Restart scroll event listener
        scrollEventListener();

        console.log('Autoscroll disabled');
    }
}

/**
 * Refresh each step, based on the status received from the server (in JSON format)
 * @param {*} steps
 * @returns
 */
function refreshStepsInDOM(steps)
{
    return new Promise((resolve, reject) => {
        try {
            var autoscroll = false;

            // Retrieve task Id
            var taskId = $('#log-refresh-container').attr('task-id');

            // Parse steps JSON
            steps = JSON.parse(JSON.stringify(steps));

            // For each step
            $.each(steps.steps, function (stepIdentifier, step) {
                // If the step exists in the DOM
                if ($('.task-step[task-id="' + taskId + '"][step="' + stepIdentifier + '"]').length > 0) {
                    // Get current step title status in the DOM
                    var status = $('.task-step[task-id="' + taskId + '"][step="' + stepIdentifier + '"]').attr('status');

                    // If the status is different, replace its content with the new one
                    if (status != step.status) {
                        $('.task-step[task-id="' + taskId + '"][step="' + stepIdentifier + '"]').replaceWith(step.html);
                    }

                // If the step does not exist in the DOM, append it to the container
                } else {
                    $('.steps-container[task-id="' + taskId + '"]').append(step.html);
                }

                // If autoscroll if enabled, scroll to the bottom of the step content
                if (getCookie('autoscroll') == '' || getCookie('autoscroll') == 'true') {
                    autoscroll = true;
                }

                // Get step content (all its substeps)
                ajaxRequest(
                    // Controller:
                    'task',
                    // Action:
                    'get-step-content',
                    // Data:
                    {
                        taskId: taskId,
                        stepIdentifier: stepIdentifier,
                        autoscroll: autoscroll
                    },
                    // Print success alert:
                    false,
                    // Print error alert:
                    'console'
                ).then(function () {
                    // If there is no step content, append step content to the container, just after the step title, then make it visible
                    if ($('.steps-container[task-id="' + taskId + '"]').find('.task-step-content[task-id="' + taskId + '"][step="' + stepIdentifier + '"]').length == 0) {
                        $('.steps-container[task-id="' + taskId + '"]').find('.task-step[task-id="' + taskId + '"][step="' + stepIdentifier + '"]').after(jsonValue.message);
                        $('.task-step-content[task-id="' + taskId + '"][step="' + stepIdentifier + '"]').css('display', 'grid');
                    }

                    // If autoscroll if enabled, scroll to the bottom of the step content
                    if (autoscroll) {
                        // Get current step content visibility
                        visibility = $('.task-step-content[task-id="' + taskId + '"][step="' + stepIdentifier + '"]').is(':visible');

                        // Replace step content with the new one
                        $('.task-step-content[task-id="' + taskId + '"][step="' + stepIdentifier + '"]').replaceWith(jsonValue.message);

                        // If step content was visible, make it visible again
                        if (visibility) {
                            $('.task-step-content[task-id="' + taskId + '"][step="' + stepIdentifier + '"]').css('display', 'grid');
                        }

                        // If step content exists, scroll to the bottom of the step content
                        if ($('.task-step-content[task-id="' + taskId + '"][step="' + stepIdentifier + '"]').length > 0) {
                            $('.task-step-content[task-id="' + taskId + '"][step="' + stepIdentifier + '"]').scrollTop($('.task-step-content[task-id="' + taskId + '"][step="' + stepIdentifier + '"]')[0].scrollHeight);
                        }
                    }

                    // Resolve promise
                    resolve('Steps refreshed');
                });
            });
        } catch (error) {
            // Reject promise
            reject('Failed to refresh steps');
        }
    });
}

/**
 *  Start log scroll event listener
 *  This is used to listen for user scroll up or down in the logs
 */
function scrollEventListener()
{
    $('.task-step-content').scroll(function () {
        var taskId = $(this).attr('task-id');
        var step = $(this).attr('step');

        /**
         *  If scroll is locked, do nothing
         */
        if (localStorage.getItem('scrollLock') == 'true') {
            return;
        }

        /**
         *  Get latest scroll position from local storage
         *  Get latest scroll direction from local storage
         */
        var lastScrollPosition = localStorage.getItem('lastScrollPosition');
        var lastScrollDirection = localStorage.getItem('lastScrollDirection');

        /**
         *  Get current scroll position
         */
        var currentScrollPosition = $(this).scrollTop();

        /**
         *  If latest scroll position is null (meaning the page has been refreshed), set it to 0
         */
        if (lastScrollPosition == "" || lastScrollPosition == "undefined") {
            lastScrollPosition = 0;
        }

        /**
         *  Detect if the current scroll is a scroll up or down
         */
        if (currentScrollPosition > lastScrollPosition) {
            var scroll = 'down';
        }
        if (currentScrollPosition < lastScrollPosition) {
            var scroll = 'up';
        }

        /**
         *  Save current scroll position in local storage
         *  Save current scroll direction in local storage
         */
        localStorage.setItem('lastScrollPosition', currentScrollPosition);
        localStorage.setItem('lastScrollDirection', scroll);

        /**
         *  If the user is scrolling up and the last scroll was also up, disable autoscroll
         *  Because it means that the user is really trying to scroll up
         */
        if (scroll == 'up' && lastScrollDirection == 'up') {
            disableAutoScroll();
        }

        /**
         *  Load more logs on scroll up
         */
        if (scroll == 'up' && $(this).scrollTop() < 20) {
            // Lock any other scroll from appending data
            localStorage.setItem('scrollLock', 'true');

            // Get first substep key
            var substepFirstKey = $(this).find('.task-sub-step-content:first').attr('key');

            // Get more logs from server
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
                    direction: 'up',
                    key: substepFirstKey
                },
                // Print success alert:
                false,
                // Print error alert:
                'console'
            ).then(function () {
                // If server returns logs, prepend them to the container, then remove the last 30 logs
                // Set scroll bar position to 1.5 from the top
                if (jsonValue.message != '') {
                    $('.task-step-content[task-id="' + taskId + '"][step="' + step + '"] .task-sub-step-container').prepend(jsonValue.message);
                    $('.task-step-content[task-id="' + taskId + '"][step="' + step + '"] .task-sub-step-content:first').nextAll().slice(30).remove();
                    $('.task-step-content[task-id="' + taskId + '"][step="' + step + '"]').scrollTop(1.5);
                }

                // Remove scroll lock
                localStorage.removeItem('scrollLock');
            });
        }

        /**
         *  Load more logs on scroll down
         */
        if (scroll == 'down' && $(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight - 20) {
            // Lock any other scroll from appending data
            localStorage.setItem('scrollLock', 'true');

            // Get last substep key
            var substepLastKey = $(this).find('.task-sub-step-content:last').attr('key');

            // Get more logs from server
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
                    direction: 'down',
                    key: substepLastKey
                },
                // Print success alert:
                false,
                // Print error alert:
                'console'
            ).then(function () {
                // If server returns logs, append them to the container, then remove the first 30 logs
                // Set scroll bar position to 1.5 from the bottom
                if (jsonValue.message != '') {
                    $('.task-step-content[task-id="' + taskId + '"][step="' + step + '"] .task-sub-step-container').append(jsonValue.message);
                    $('.task-step-content[task-id="' + taskId + '"][step="' + step + '"] .task-sub-step-content').slice(0, -30).remove();
                    $('.task-step-content[task-id="' + taskId + '"][step="' + step + '"]').scrollTop($('.task-step-content[task-id="' + taskId + '"][step="' + step + '"]')[0].scrollHeight - $('.task-step-content').innerHeight() - 1.5);
                }

                // Remove scroll lock
                localStorage.removeItem('scrollLock');
            });
        }
    });
}
