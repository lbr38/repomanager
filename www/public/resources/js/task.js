
loadNewRepoFormJS();

function loadNewRepoFormJS()
{
    /**
     *  Convert select to select2
     */
    selectToSelect2('.task-param[param-name="releasever"]', 'e.g: 8', true);
    selectToSelect2('.task-param[param-name="dist"]', 'e.g: bullseye', true);
    selectToSelect2('.task-param[param-name="section"]', 'e.g: main', true);
    selectToSelect2('.task-param[param-name="arch"]', 'Select architecture', true);
    selectToSelect2('select.task-param[param-name="schedule-day"]', 'Select day(s)...', true);
    selectToSelect2('select.task-param[param-name="schedule-reminder"]', 'Select reminder...', true);
    selectToSelect2('select.task-param[param-name="schedule-recipient"]', 'Select or add recipients...', true);

    /**
     *  Show / hide the necessary fields
     */
    newRepoFormPrintFields();
}

/**
 *  Reload the 'new repo' task div
 */
function reloadNewRepoDiv()
{
    $(".slide-panel-reloadable-div[slide-panel='repos/new']").load(" .slide-panel-reloadable-div[slide-panel='repos/new'] > *",function () {
        loadNewRepoFormJS();
    });
}

/**
 *  Count the number of checked checkboxes
 */
function countChecked()
{
    var countTotal = $('.reposList').find('input[name=checkbox-repo\\[\\]]:checked').length;

    return countTotal;
};

/**
 *  Show / hide the fields according to the selected package type (rpm or deb)
 */
function newRepoFormPrintFields()
{
    /**
     *  Search for the type of repo and the type of packages selected in the task form whose
     *  action is 'create' (form for creating a new repo)
     */

    /**
     *  Retrieve the value of the 'repoType' radio button
     */
    var repoType = $('.task-form-params[action="create"]').find('input:radio[name="repo-type"]:checked').val();

    /**
     *  Retrieve the value of the 'package-type' radio button
     */
    var packageType = $('.task-form-params[action="create"]').find('input:radio[name="package-type"]:checked').val();

    /**
     *  Hide all fields
     */
    $('.task-form-params').find('[field-type]').hide();

    /**
     *  Depending on the type of repo and packages selected, display only the fields related to this type of repo and packages.
     */
    $('.task-form-params').find('[field-type~=' + repoType + '][field-type~=' + packageType + ']').show();
}

/**
 *  Event: show / hide task inputs depending on the selected repo type or package type
 */
$(document).on('change','input:radio[name="repo-type"], input:radio[name="package-type"]',function () {
    newRepoFormPrintFields();
});

/**
 *  Event: show / hide task inputs depending on the selected schedule type
 */
$(document).on('change','input:radio[name="task-schedule-type"]',function () {
    /**
     *  Retrieve task action
     */
    var action = $(this).attr('action');

    /**
     *  Case it is a unique task
     */
    if ($('input:radio[name="task-schedule-type"][action="' + action + '"][value="unique"]').is(":checked")) {
        $('.task-schedule-form-params[action="' + action + '"]').find('.task-schedule-unique-input').css('display', 'table-row');
        $('.task-schedule-form-params[action="' + action + '"]').find('.task-schedule-time-input').css('display', 'table-row');
        $('.task-schedule-form-params[action="' + action + '"]').find('.task-schedule-recurring-frequency-input').hide();
        $('.task-schedule-form-params[action="' + action + '"]').find('.task-schedule-recurring-day-input').hide();
    }

    /**
     *  Case it is a recurring task
     */
    if ($('input:radio[name="task-schedule-type"][action="' + action + '"][value="recurring"]').is(":checked")) {
        $('.task-schedule-form-params[action="' + action + '"]').find('.task-schedule-recurring-frequency-input').css('display', 'table-row');
        $('.task-schedule-form-params[action="' + action + '"]').find('.task-schedule-unique-input').hide();
    }
}).trigger('change');

/**
 *  Event: show / hide task inputs depending on the schedule frequency
 */
$(document).on('change','select.task-param[param-name="schedule-frequency"]',function () {
    var frequency = $(this).val();

    if (frequency == 'hourly') {
        $('.task-schedule-recurring-day-input').hide();
        $('.task-schedule-time-input').hide();
        $('.task-schedule-recurring-monthly-input').hide();
    }

    if (frequency == 'daily') {
        $('.task-schedule-recurring-day-input').hide();
        $('.task-schedule-time-input').css('display', 'table-row');
        $('.task-schedule-recurring-monthly-input').hide();
    }

    if (frequency == 'weekly') {
        $('.task-schedule-recurring-day-input').css('display', 'table-row');
        $('.task-schedule-time-input').css('display', 'table-row');
        $('.task-schedule-recurring-monthly-input').hide();
    }

    if (frequency == 'monthly') {
        $('.task-schedule-recurring-monthly-input').css('display', 'table-row');
        $('.task-schedule-time-input').css('display', 'table-row');
        $('.task-schedule-recurring-day-input').hide();
        $('task-schedule-recurring-day-input').hide();
    }
}).trigger('change');

/**
 *  Event: click on the delete environment button
 */
$(document).on('click','.delete-env-btn',function () {
    var envName = $(this).attr('env');

    /**
     *  Retrieve the repo id, snap id and env id
     */
    taskParams = [{
        'action': 'removeEnv',
        'repo-id': $(this).attr('repo-id'),
        'snap-id': $(this).attr('snap-id'),
        'env-id': $(this).attr('env-id'),
        'env': envName,
        'schedule': {
            'scheduled': false
        }
    }];

    var taskParamsJson = JSON.stringify(taskParams);

    confirmBox('Remove <b>' + envName + '</b> environment?', function () {
        ajaxRequest(
            // Controller:
            'task',
            // Action:
            'validateForm',
            // Data:
            {
                taskParams: taskParamsJson,
            },
            // Print success alert:
            true,
            // Print error alert:
            true,
            // Reload container:
            [],
        )
    });
});

/**
 *  Event: when a checkbox is checked/unchecked
 */
$(document).on('click',"input[name=checkbox-repo\\[\\]]",function () {
    /**
     *  Count the number of checked checkboxes
     */
    var count_checked = countChecked();

    /**
     *  If all checkboxes are unchecked then we hide all action buttons, otherwise we show them
     *  Also remove the style applied by jquery on the checkboxes when they are checked
     */
    if (count_checked == 0) {
        $('#repo-actions-btn-container').hide();
        $('.reposList').find('input[name=checkbox-repo\\[\\]]').removeAttr('style');
        return;
    } else {
        $('#repo-actions-btn-container').show();
    }

    /**
     *  If there is at least 1 checkbox checked then we display all the other checkboxes
     *  All checked checkboxes are set to opacity = 1
     */
    $('.reposList').find('input[name=checkbox-repo\\[\\]]').css("visibility", "visible");
    $('.reposList').find('input[name=checkbox-repo\\[\\]]:checked').css("opacity", "1");

    /**
     *  If a 'local' repo is checked then we hide the 'update' button
     */
    if ($('.reposList').find('input[name=checkbox-repo\\[\\]][repo-type=local]:checked').length > 0) {
        $('.repo-action-btn[action=update]').hide();
    } else {
        $('.repo-action-btn[action=update]').show();
    }
});

/**
 *  Event: Click on an action button
 */
$(document).on('click',".repo-action-btn",function () {
    var repos_array = [];

    /**
     *  Hide all tasks buttons
     */
    $('#repo-actions-btn-container').hide();

    /**
     *  Retrive the selected action
     */
    var action = $(this).attr('action');

    /**
     *  Loop through all checked repos and retrieve their id
     */
    $('.reposList').find('input[name=checkbox-repo\\[\\]]:checked').each(function () {
        var obj = {};

        /**
         *  Retrieving the repo id and status
         */
        obj['repo-id'] = $(this).attr('repo-id');
        obj['snap-id'] = $(this).attr('snap-id');
        obj['env-id'] = $(this).attr('env-id');
        obj['repo-status'] = $(this).attr('repo-status');

        repos_array.push(obj);
    });

    /**
     *  Execute the selected action
     */
    var repos_array = JSON.stringify(repos_array);

    /**
     *  Get the form for the selected action and open the panel
     */
    ajaxRequest(
        // Controller:
        'task',
        // Action:
        'getForm',
        // Data:
        {
            taskAction: action,
            repos_array: repos_array
        },
        // Print success alert:
        false,
        // Print error alert:
        true,
        // Reload container:
        [],
        // Execute functions on success:
        [
            "$('.slide-panel-container[slide-panel=\"repos/task\"]').find('.slide-panel-reloadable-div').html(jsonValue.message)",
            "openPanel('repos/task')"
        ]
    );
});

/**
 *  Event: Schedule a task
 */
$(document).on('click',".task-schedule-btn", function () {
    /**
     *  Find parent task-form
     */
    var form = $(this).parents('.task-form');

    /**
     *  Show schedule params from this form
     */


    /**
     *  Change button text and color if schedule is checked
     */
    if ($(this).is(':checked')) {
        form.find('.task-schedule-params').show();
        form.find('.task-confirm-btn').css('background-color', '#15bf7f');
        form.find('.task-confirm-btn').html('Schedule');
    } else {
        form.find('.task-schedule-params').hide();
        form.find('.task-confirm-btn').css('background-color', '#ff0044');
        form.find('.task-confirm-btn').html('Execute now');
    }
});

/**
 *  Event: submit task form
 */
$(document).on('submit','.task-form',function () {
    event.preventDefault();

     /**
     *  Main array that will contain the schedule parameters
     */
    var scheduleObj = {};

    /**
     *  Retrieve the schedule parameters
     */
    $(this).find('.task-schedule-form-params').each(function () {
        /**
         *  Retrieve the schedule parameters entered by the user and push them into the object
         *  There is no associative array in js so we push an object.
         */
        var params = $(this).find('.task-param');

        params.each(function () {
            /**
             *  Retrieve the parameter name (input name) and its value (input value)
             */
            var param_name = $(this).attr('param-name');

            /**
             *  If the input is a checkbox and it is checked then its value will be 'true'
             *  If it is not checked then its value will be 'false'
             */
            if ($(this).attr('type') == 'checkbox') {
                if ($(this).is(":checked")) {
                    var param_value = 'true';
                } else {
                    var param_value = 'false';
                }

            /**
             *  If the input is a radio button then we only retrieve its value if it is checked, otherwise we move on to the next parameter
             */
            } else if ($(this).attr('type') == 'radio') {
                if ($(this).is(":checked")) {
                    var param_value = $(this).val();
                } else {
                    return; // return is the equivalent of 'continue' for jquery loops .each()
                }
            } else {
                /**
                 *  If the input is not a checkbox then we retrieve its value
                 */
                var param_value = $(this).val();
            }

            scheduleObj[param_name] = param_value;
        });
    });

    /**
     *  Main array that will contain all the parameters of each repo to be processed (1 or more repos depending on the user's selection)
     */
    var taskParams = [];

    /**
     *  Retrive the parameters entered in the form
     */
    $(this).find('.task-form-params').each(function () {
        /**
         *  Object that will contain the parameters entered in the form for this repo
         */
        var obj = {};

        /**
         *  Retrieve the task action
         */
        obj['action'] = $(this).attr('action');

        /**
         *  If action is not 'create' then we retrieve the snap id and env id
         */
        if (obj['action'] != 'create') {
            obj['repo-id'] = $(this).attr('repo-id');
            obj['snap-id'] = $(this).attr('snap-id');
            obj['env-id']  = $(this).attr('env-id');
        }

        /**
         *  If action is 'create', retrieve the package type
         *  It will be used to get the correct parameters for the selected package type (rpm or deb)
         */
        if (obj['action'] == 'create') {
            packageType = $(this).find('.task-param[param-name="package-type"]:checked').val();
        }

        /**
         *  Retrieve the parameters entered by the user and push them into the object
         *  There is no associative array in js so we push an object.
         */
        $(this).find('.task-param').each(function () {
            /**
             *  If the input has an attribute 'package-type' ('create' form only)
             *  then only retrieve input value if its package type is the same as the selected package type
             *  Else continue to the next parameter
             */
            if ($(this).attr('package-type')) {
                if ($(this).attr('package-type') != packageType && $(this).attr('package-type') != 'all') {
                    return; // return is the equivalent of 'continue' for jquery loops .each()
                }
            }

            /**
             *  Retrieve the parameter name (input name) and its value (input value)
             */
            var param_name = $(this).attr('param-name');

            /**
             *  If the input is a checkbox and it is checked then its value will be 'true'
             *  If it is not checked then its value will be 'false'
             */
            if ($(this).attr('type') == 'checkbox') {
                if ($(this).is(":checked")) {
                    var param_value = 'true';
                } else {
                    var param_value = 'false';
                }

            /**
             *  If the input is a radio button then we only retrieve its value if it is checked, otherwise we move on to the next parameter
             */
            } else if ($(this).attr('type') == 'radio') {
                if ($(this).is(":checked")) {
                    var param_value = $(this).val();
                } else {
                    return; // return is the equivalent of 'continue' for jquery loops .each()
                }
            } else {
                /**
                 *  If the input is not a checkbox then we retrieve its value
                 */
                var param_value = $(this).val();
            }

            obj[param_name] = param_value;
        });

        /**
         *  Add the schedule parameters to the task itself
         */
        obj['schedule'] = scheduleObj;

        /**
         *  Push each repo parameter into the main array
         */
        taskParams.push(obj);
    });

    /**
     *  Convert the main array to JSON format and send it to php for verification of the parameters
     */
    var taskParamsJson = JSON.stringify(taskParams);

    // for debug only
    // console.log(taskParamsJson);

    ajaxRequest(
        // Controller:
        'task',
        // Action:
        'validateForm',
        // Data:
        {
            taskParams: taskParamsJson,
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        [],
        // Execute functions on success:
        [ 'closePanel()' ]
    );

    return false;
});
