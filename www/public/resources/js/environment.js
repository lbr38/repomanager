function editEnv()
{
    var env_array = [];

    $('.env-input').each(function () {
        var env = $(this).val();

        env_array.push(env);
    });

    editEnvAjax(env_array);
}


/**
 *  Event: delete an environment
 */
$(document).on('click','.delete-env-btn',function () {
    var name = $(this).attr('env-name');

    confirmBox('Are you sure you want to delete environment <b>' + name + '</b>?', function () {
        deleteEnv(name)
    });
});

/**
 *  Event: add / edit actual environments
 */
$(document).on('keypress','.env-input',function () {
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if (keycode == '13') {
        editEnv();
    }

    event.stopPropagation();
});
$(document).on('click','#edit-env-btn',function () {
    editEnv();
});

/**
 * Ajax: Delete an environment
 * @param {string} name
 */
function deleteEnv(name)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "environment",
            action: "deleteEnv",
            name: name
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            /**
             *  Reload env div
             */
            reloadContentById('envDiv');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Rename / reorder environment(s)
 * @param {string} envs
 */
function editEnvAjax(envs)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "environment",
            action: "editEnv",
            envs: envs
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            /**
             *  Reload env div
             */
            reloadContentById('envDiv');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}