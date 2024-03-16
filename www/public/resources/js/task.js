
loadNewRepoFormJS();

function loadNewRepoFormJS()
{
    /**
     *  Convert select to select2
     */
    selectToSelect2('.task-param[param-name="releasever"]', 'e.g: 8', true);
    selectToSelect2('.task-param[param-name="dist"]', 'e.g: bullseye', true);
    selectToSelect2('.task-param[param-name="section"]', 'e.g: main', true);
    selectToSelect2('.task-param[param-name="arch"]', true);

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
     *  Case it is a unique task
     */
    if ($("#task-schedule-type-unique").is(":checked")) {
        $(".task-schedule-unique-input, .task-schedule-time-input").css('display', 'table-row');
        $(".task-schedule-recurrent-input").hide();
        $(".task-schedule-recurrent-day-input").hide();
    }

    /**
     *  Case it is a recurrent task
     */
    if ($("#task-schedule-type-recurrent").is(":checked")) {
        $(".task-schedule-recurrent-input").css('display', 'table-row');
        $(".task-schedule-unique-input").hide();
    }
}).trigger('change');

/**
 *  Event: show / hide task inputs depending on the schedule frequency
 */
$(document).on('change','select.task-param[param-name="schedule-frequency"]',function () {
    var frequency = $(this).val();

    /**
     *  If frequency is "every hour"
     */
    if (frequency == 'every-hour') {
        $(".task-schedule-recurrent-day-input").hide();
        $(".task-schedule-time-input").hide();
    }

    /**
     *  If frequency is "every day"
     */
    if (frequency == 'every-day') {
        $(".task-schedule-recurrent-day-input").hide();
        $(".task-schedule-time-input").css('display', 'table-row');
    }

    /**
     *  If frequency is "every week"
     */
    if (frequency == 'every-week') {
        $(".task-schedule-recurrent-day-input").css('display', 'table-row');
        $(".task-schedule-time-input").css('display', 'table-row');
    }
}).trigger('change');

/**
 *  Event: click on the delete environment button
 */
$(document).on('click','.delete-env-btn',function () {
    var repoId = $(this).attr('repo-id');
    var snapId = $(this).attr('snap-id');
    var envId = $(this).attr('env-id');
    var envName = $(this).attr('env-name');

    confirmBox('Remove <b>' + envName + '</b> environment?', function () {
        removeEnv(repoId, snapId, envId)});
});

/**
 *  Ajax: Remove an environment
 *  @param {string} repoId
 *  @param {string} repoDescription
 */
function removeEnv(repoId, snapId, envId)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "task",
            action: "removeEnv",
            repoId: repoId,
            snapId: snapId,
            envId: envId
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
        },
        error: function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

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
        obj['repoId'] = $(this).attr('repo-id');
        obj['snapId'] = $(this).attr('snap-id');
        obj['envId'] = $(this).attr('env-id');
        obj['repoStatus'] = $(this).attr('repo-status');

        repos_array.push(obj);
    });

    /**
     *  Execute the selected action
     */
    var repos_array = JSON.stringify(repos_array);

    /**
     *  Get the form for the selected action and open the panel
     */
    getForm(action, repos_array);

    openPanel('repos/task');
});

/**
 *  Event: Schedule a task
 */
$(document).on('click',"#task-schedule-btn",function () {
    $('.task-schedule-container').slideToggle('fast');
});

/**
 *  Event: submit task form
 */
$(document).on('submit','.task-form',function () {
    event.preventDefault();

    /**
     *  Main array that will contain all the parameters of each repo to be processed (1 or more repos depending on the user's selection)
     */
    var taskParams = [];

    /**
     *  Retrive the parameters entered in the form
     */
    $(this).find('.task-form-params').each(function () {
        var obj = {};

        /**
         *  Object that will contain the parameters entered in the form for this repo
         */
        obj['action'] = $(this).attr('action');
        if (obj['action'] != 'new') {
            obj['snapId'] = $(this).attr('snap-id');
            obj['envId'] = $(this).attr('env-id');
        }

        /**
         *  If the action is 'new' then we retrieve the package type of the repo to create.
         *  Then depending on the type of package we will only retrieve certain parameters.
         */
        // if (obj['action'] == 'new') {
        //     var packageType = $(this).find('.task-param[param-name="package-type"]:checked').val();
        //     obj['packageType'] = packageType;
        // }

        /**
         *  Retrieve the parameters entered by the user and push them into the object
         *  There is no associative array in js so we push an object.
         *  In the case where the action is 'new', these are only the parameters having the attribute package-type=all OR package-type=packageType which are retrieved
         */
        // if (obj['action'] == 'new') {
        //     var taskParam = $(this).find('.task-param[package-type="all"],.task-param[package-type="' + packageType + '"]');
        // }
        // if (obj['action'] != 'new') {
        //     var taskParam = $(this).find('.task-param');
        // }
        var params = $(this).find('.task-param');

        params.each(function () {
            /**
             *  Retrieve the parameter name (input name) and its value (input value)
             */
            var param_name = $(this).attr('param-name');

            /**
             *  If the input is a checkbox and it is checked then its value will be 'yes'
             *  If it is not checked then its value will be 'no'
             */
            if ($(this).attr('type') == 'checkbox') {
                if ($(this).is(":checked")) {
                    var param_value = 'yes';
                } else {
                    var param_value = 'no';
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
         *  Push each repo parameter into the main array
         */
        taskParams.push(obj)
    });

    /**
     *  Convert the main array to JSON format and send it to php for verification of the parameters
     */
    var taskParamsJson = JSON.stringify(taskParams);

    // debug
    console.log(taskParams);

    // validateExecuteForm(taskParamsJson);

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
        // [ closePanel() ]
    );

    return false;
});

/**
 *  Ajax: Get a task form
 *  @param {string} action
 *  @param {array} repos_array
 */
function getForm(action, repos_array)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "task",
            action: "getForm",
            taskAction: action,
            repos_array: repos_array
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            $('.slide-panel-container[slide-panel="repos/task"]').find('.slide-panel-reloadable-div').html(jsonValue.message);
        },
        error: function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}
