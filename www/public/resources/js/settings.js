/**
 *  Convert some select to select2
 */
idToSelect2('#emailRecipientSelect', 'Select recipients...', true);
idToSelect2('#debArchitectureSelect', 'Select architectures...');
idToSelect2('#rpmArchitectureSelect', 'Select architectures...');
idToSelect2('#debTranslationSelect', 'Select translations...');

/**
 *  Event: update Repomanager
 */
$(document).on('click','#update-repomanager-btn',function () {
    var release = $(this).attr('release');

    confirmBox('Update Repomanager to release version ' + release + '?', function () {
        updateRepomanager();
    }, 'Update');
});

/**
 *  Event: send a test email
 */
$(document).on('click','#send-test-email-btn',function () {
    sendTestEmail();
});

/**
 *  Event: apply settings
 */
$(document).on('submit','#settingsForm',function () {
    event.preventDefault();

    /**
     *  Gettings all params in the form
     */
    var settings_params = $(this).find('.settings-param');
    var settings_params_obj = {};

    settings_params.each(function () {
        /**
         *  Getting param name in the 'param-name' attribute of each input
         */
        var param_name = $(this).attr('param-name');

        /**
         *  If input is a checkbox and it is checked then its value is 'true'
         *  Else its value is 'false'
         */
        if ($(this).attr('type') == 'checkbox') {
            if ($(this).is(":checked")) {
                var param_value = 'true';
            } else {
                var param_value = 'false';
            }

        /**
         *  If input is a radio then get its value only if it is checked, else process the next param
         */
        } else if ($(this).attr('type') == 'radio') {
            if ($(this).is(":checked")) {
                var param_value = $(this).val();
            } else {
                return; // In jquery '.each()' loops, return is like 'continue'
            }
        } else {
            /**
             *  If input is not a checkbox nor a radio then get its value
             */
            var param_value = $(this).val();
        }

        /**
         *  Add param name and value to the global object array
         */
        settings_params_obj[param_name] = param_value;
    });

    /**
     *  Convert object array to JSON before sending
     */
    var settings_params_json = JSON.stringify(settings_params_obj);

    applySettings(settings_params_json);

    return false;
});

/**
 *  Event: create a new user
 */
$(document).on('submit','#newUserForm',function () {
    event.preventDefault();

    var username = $(this).find('input[name=username]').val();
    var role = $(this).find('select[name=role]').val();

    newUser(username, role);

    return false;
});

/**
 *  Event: reset user password
 */
$(document).on('click','.reset-password-btn',function () {
    var username = $(this).attr('username');
    var id = $(this).attr('user-id');

    confirmBox('Reset password of user ' + username + '?', function () {
        resetPassword(id, username);
    }, 'Reset');
});

/**
 *  Event: delete user
 */
$(document).on('click','.delete-user-btn',function () {
    var username = $(this).attr('username');
    var id = $(this).attr('user-id');

    confirmBox('Delete user ' + username + '?', function () {
        deleteUser(id, username);
    }, 'Delete');
});

/**
 *  Ajax: update Repomanager
 */
function updateRepomanager()
{
    printAlert('Update running <img src="assets/images/loading.gif" class="icon" />', null, 'none');
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "general",
            action: "updateRepomanager"
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            window.location.replace("/settings");
        }
    });
}

/**
 *  Ajax: Apply settings params
 *  @param {*} settings_params_json
 */
function applySettings(settings_params_json)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "settings",
            action: "applySettings",
            settings_params: settings_params_json,
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Reload div and select2
             */
            $("#settingsDiv").load(" #settingsDiv > *",function () {
                idToSelect2('#emailRecipientSelect', 'Select recipients...', true);
                idToSelect2('#debArchitectureSelect', 'Select architectures...');
                idToSelect2('#rpmArchitectureSelect', 'Select architectures...');
                idToSelect2('#debTranslationSelect', 'Select translations...');
            });
            printAlert(jsonValue.message, 'success');
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 *  Ajax: create a new user
 */
function newUser(username, role)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "settings",
            action: "createUser",
            username: username,
            role: role
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Print returned message
             */
            printAlert(jsonValue.message.message, 'success');

            /**
             *  Reload current users div
             */
            reloadContentById('currentUsers');

            /**
             *  Print generated password for the new user
             */
            $('#usersDiv').find('#generatedPassword').html('<p class="greentext">Temporary password generated for <b>' + username + '</b>: ' + jsonValue.message.password + '</p>');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 *  Ajax: reset user password
 */
function resetPassword(id, username)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "settings",
            action: "resetPassword",
            id: id
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Print returned message
             */
            printAlert(jsonValue.message.message, 'success');

            /**
             *  Print new generated password
             */
            $('#usersDiv').find('#generatedPassword').html('<p class="greentext">New password generated for <b>' + username + '</b>: ' + jsonValue.message.password + '</p>');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 *  Ajax: delete user
 */
function deleteUser(id, username)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "settings",
            action: "deleteUser",
            id: id
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');

            /**
             *  Reload current users div
             */
            reloadContentById('currentUsers');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: send a test email
 */
function sendTestEmail()
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "settings",
            action: "sendTestEmail"
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}