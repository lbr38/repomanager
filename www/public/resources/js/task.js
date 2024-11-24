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
 * Print environment tag with color, in task form
 * Environment colors must be set in localStorage
 * @param {*} env
 * @param {*} selector
 */
function printEnv(env, selector)
{
    // Default colors
    var background = '#ffffff';
    var color = '#000000';

    // Check if the environment color is set in localStorage
    if (localStorage.getItem('env/' + env) !== null) {
        definition = JSON.parse(localStorage.getItem('env/' + env));
        color = definition.color;
        background = definition.background;
    }

    // Generate html
    var html = '⟵<span class="env" style="background-color: ' + background + '; color: ' + color + ';">' + env + '</span>';

    // Print environment
    $(selector).html(html);
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
        $('.task-schedule-form-params[action="' + action + '"]').find('.task-schedule-unique-input').show();
        $('.task-schedule-form-params[action="' + action + '"]').find('.task-schedule-time-input').show();
        $('.task-schedule-form-params[action="' + action + '"]').find('.task-schedule-recurring-frequency-input').hide();
        $('.task-schedule-form-params[action="' + action + '"]').find('.task-schedule-recurring-day-input').hide();
        $('.task-schedule-form-params[action="' + action + '"]').find('.task-schedule-recurring-monthly-input').hide();
    }

    /**
     *  Case it is a recurring task
     */
    if ($('input:radio[name="task-schedule-type"][action="' + action + '"][value="recurring"]').is(":checked")) {
        $('.task-schedule-form-params[action="' + action + '"]').find('.task-schedule-recurring-frequency-input').show();
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
        $('.task-schedule-time-input').show();
        $('.task-schedule-recurring-monthly-input').hide();
    }

    if (frequency == 'weekly') {
        $('.task-schedule-recurring-day-input').show();
        $('.task-schedule-time-input').show();
        $('.task-schedule-recurring-monthly-input').hide();
    }

    if (frequency == 'monthly') {
        $('.task-schedule-recurring-monthly-input').show();
        $('.task-schedule-time-input').show();
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

    confirmBox(
        {
            'title': 'Remove environment',
            'message': 'Remove <b>' + envName + '</b> environment?',
            'buttons': [
            {
                'text': 'Remove',
                'color': 'red',
                'callback': function () {
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
                }
            }]
        }
    );
});

/**
 *  Event: when a checkbox is checked/unchecked
 */
$(document).on('click',"input[name=checkbox-repo]",function () {
    /**
     *  Retrieve checkbox's group id
     */
    var groupId = $(this).attr('group-id');

    /**
     *  Count the number of checked checkboxes
     */
    var count_checked = $('.reposList').find('input[name=checkbox-repo]:checked').length;

    /**
     *  Define if 'Update' action is available in the action buttons
     */
    var updateAction = true;

    /**
     *  If all checkboxes are unchecked then we hide all action buttons
     */
    if (count_checked == 0) {
        closeConfirmBox();
        $('.reposList').find('input[name=checkbox-repo]').removeAttr('style');
        $('.repos-list-group[group-id=' + groupId + ']').find('.repos-list-group-select-all-btns').hide();
        return;
    }

    /**
     *  If a 'local' repo is checked then we hide the 'update' button
     */
    if ($('.reposList').find('input[name=checkbox-repo][repo-type=local]:checked').length > 0) {
        // TODO: avoid displaying the Update button when a local repo is selected
        // updateAction = false;
        // closeConfirmBox();
    }

    /**
     *  Define confirm box buttons
     */
    var buttons = [];

    // If 'update' action is available then we add the 'update' button. This happens when no 'local' repo is checked
    if (updateAction) {
        buttons.push({
            'text': 'Update',
            'color': 'blue-alt',
            'callback': function () {
                executeAction('update')
            }
        });
    }

    // Add all other buttons
    buttons.push(
        {
            'text': 'Duplicate',
            'color': 'blue-alt',
            'callback': function () {
                executeAction('duplicate');
            }
        },
        {
            'text': 'Point an environment',
            'color': 'blue-alt',
            'callback': function () {
                executeAction('env');
            }
        },
        {
            'text': 'Rebuild',
            'color': 'blue-alt',
            'callback': function () {
                executeAction('rebuild');
            }
        },
        {
            'text': 'Delete',
            'color': 'red',
            'callback': function () {
                executeAction('delete');
            }
        }
    );

    confirmBox(
        {
            'title': 'Execute',
            'message': 'Select an action to execute on the selected repositories.',
            'id': 'repo-actions-confirm-box',
            'buttons': buttons
        }
    );

    /**
     *  Show 'select all latest snapshots' buttons
     */
    $('.repos-list-group[group-id=' + groupId + ']').find('.repos-list-group-select-all-btns').css('display', 'flex');

    /**
     *  If there is at least 1 checkbox checked then we display all the other checkboxes
     *  All checked checkboxes are set to opacity = 1
     */
    $('.reposList').find('input[name=checkbox-repo]').css("visibility", "visible");
    $('.reposList').find('input[name=checkbox-repo]:checked').css("opacity", "1");
});

function executeAction(action)
{
    var repos = [];

    /**
     *  Loop through all checked repos and retrieve their id
     */
    $('.reposList').find('input[name=checkbox-repo]:checked').each(function () {
        var obj = {};

        /**
         *  Retrieving the repo id and status
         */
        obj['repo-id'] = $(this).attr('repo-id');
        obj['snap-id'] = $(this).attr('snap-id');
        obj['env-id'] = $(this).attr('env-id');
        obj['repo-status'] = $(this).attr('repo-status');

        repos.push(obj);
    });

    /**
     *  Execute the selected action
     */
    var repos = JSON.stringify(repos);

    /**
     *  Get the panel and form for the selected action
     */
    getPanel('repos/task', {
        action: action,
        repos: repos
    });
}

/**
 *  Event: Click on 'select all latest snapshots' button
 */
$(document).on('click',".repos-list-group-select-all-btns",function () {
    /**
     *  Retrieve group Id
     */
    var groupId = $(this).attr('group-id');

    /**
     *  Retrieve all repos in the group
     */
    var reposCheckboxes = $('.repos-list-group[group-id=' + groupId + ']').find('input[name=checkbox-repo]');

    /**
     *  Retrieve select status
     */
    var selectStatus = $(this).attr('status');

    /**
     *  If current status is not 'selected', then select all the latest snaps
     */
    if (selectStatus != 'selected') {
        /**
         *  Loop through all checkboxes and check the latest snap
         *  The latest snap is the first snap in the list (the first checkbox of each repo)
         */
        latestRepoId = '';
        reposCheckboxes.each(function () {
            // Click the checkbox if it matches the latest snap
            if ($(this).attr('repo-id') != latestRepoId) {
                // Click the checkbox if not already checked
                if (!$(this).is(':checked')) {
                    $(this).click();
                }
            // Otherwise, uncheck the checkbox to make sure only the latest snaps are checked
            } else {
                if ($(this).is(':checked')) {
                    $(this).click();
                }
            }

            latestRepoId = $(this).attr('repo-id');
        });

        // Set status
        $(this).attr('status', 'selected');

        // Make sure the 'Select latest snapshots' button is visible and its checkbox is checked
        $('.repos-list-group-select-all-btns[group-id="' + groupId + '"]').css('display', 'flex');
        $('.repos-list-group-select-all-btns[group-id="' + groupId + '"]').css('opacity', '1');
        $('.repos-list-group-select-all-btns[group-id="' + groupId + '"]').css('filter', 'initial');
        $('.repos-list-group-select-all-btns[group-id="' + groupId + '"]').find('input[type="checkbox"]').prop('checked', true);

    /**
     *  Otherwise, uncheck all checkboxes
     */
    } else {
        reposCheckboxes.each(function () {
            if ($(this).is(':checked')) {
                $(this).click();
            }
        });

        // Set status
        $(this).attr('status', '');

        // Make sure the 'Select latest snapshots' button is hidden and its checkbox is unchecked
        $('.repos-list-group-select-all-btns[group-id="' + groupId + '"]').hide();
        $('.repos-list-group-select-all-btns[group-id="' + groupId + '"]').css('opacity', '');
        $('.repos-list-group-select-all-btns[group-id="' + groupId + '"]').css('filter', '');
        $('.repos-list-group-select-all-btns[group-id="' + groupId + '"]').find('input[type="checkbox"]').prop('checked', false);
    }
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
     *  Change button text and color if schedule is checked
     */
    if ($(this).is(':checked')) {
        form.find('.task-schedule-params').show();
        form.find('.task-confirm-btn').css('background-color', '#15bf7f');
        form.find('.task-confirm-btn').html('Schedule');
    } else {
        form.find('.task-schedule-params').hide();
        form.find('.task-confirm-btn').css('background-color', '#F32F63');
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
        [
            'closePanel()',
            // Uncheck all checkboxes and remove all styles JQuery could have applied
            "$('.reposList').find('input[name=checkbox-repo]').prop('checked', false);",
            "$('.reposList').find('input[name=checkbox-repo]').removeAttr('style');",
            // Reload right panel
            "reloadContainer('repos/properties')"
        ]
    );

    return false;
});

/**
 *  Event: on source repository selection
 */
$(document).on('change','select[param-name="source"]',function () {
    /**
     *  Get package type and source
     */
    var packageType = $(this).attr('package-type');
    var source = $(this).val();

    /**
     *  Quit if no source selected
     */
    if (source == '') {
        return;
    }

    /**
     *  Get predefined values
     */

    // Case of a deb source
    if (packageType == 'deb') {
        // Get predefined distributions for the selected source
        ajaxRequest(
            // Controller:
            'repo/source/distribution',
            // Action:
            'get-predefined-distributions',
            // Data:
            {
                source: source
            },
            // Print success alert:
            false,
            // Print error alert:
            true,
            // Reload container:
            [],
            // Execute functions on success:
            [
                // Update select2 with the new values
                "updateSelect2('.task-param[param-name=\"dist\"]', jsonValue.message, 'Select distribution', true)"
            ]
        );
    }

    // Case of a rpm source
    if (packageType == 'rpm') {
        // Get predefined release versions for the selected source
        ajaxRequest(
            // Controller:
            'repo/source/releasever',
            // Action:
            'get-predefined-releasevers',
            // Data:
            {
                source: source
            },
            // Print success alert:
            false,
            // Print error alert:
            true,
            // Reload container:
            [],
            // Execute functions on success:
            [
                // Update select2 with the new values
                "updateSelect2('.task-param[param-name=\"releasever\"]', jsonValue.message, 'Select release version', true)"
            ]
        );
    }
}).trigger('change');

/**
 *  Event: on repository distribution selection
 */
$(document).on('change','select[param-name="dist"]',function () {
    /**
     *  Get source and distribution
     */
    var source = $('select[param-name="source"][package-type="deb"]').val();
    var distribution = $(this).val();

    /**
     *  Quit if no source selected
     */
    if (source == '') {
        return;
    }

    /**
     *  Quit if no distribution selected
     */
    if (distribution == '') {
        return;
    }

    /**
     *  Get predefined values
     */

    // Get predefined components for the selected distribution
    ajaxRequest(
        // Controller:
        'repo/source/distribution',
        // Action:
        'get-predefined-components',
        // Data:
        {
            source: source,
            distribution: distribution
        },
        // Print success alert:
        false,
        // Print error alert:
        true,
        // Reload container:
        [],
        // Execute functions on success:
        [
            // Update select2 with the new values
            "updateSelect2('.task-param[param-name=\"section\"]', jsonValue.message, 'Select component', true)"
        ]
    );
}).trigger('change');
