/**
 *  Event: create a new environment
 */
$(document).on('submit','#newEnvironmentForm',function () {
    event.preventDefault();

    var name = $('#new-env-input').val();

    newEnv(name);

    return false;
});

/**
 *  Event: delete an environment
 */
$(document).on('click','.delete-env-btn',function () {
    var name = $(this).attr('env-name');

    deleteConfirm('Are you sure you want to delete environment <b>' + name + '</b>?', function () {
        deleteEnv(name)
    });
});

/**
 *  Event: rename / reorder environment(s)
 */
$(document).on('submit','#environmentForm',function () {
    event.preventDefault();

    var env_array = [];

    $('.actual-env-input').each(function () {
        var name = $(this).val();

        env_array.push(name);
    });

    renameEnv(env_array);

    return false;
});

/**
 * Ajax: Create a new environment
 * @param {string} name
 */
function newEnv(name)
{
    $.ajax({
        type: "POST",
        url: "controllers/environments/ajax.php",
        data: {
            action: "newEnv",
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
 * Ajax: Delete an environment
 * @param {string} name
 */
function deleteEnv(name)
{
    $.ajax({
        type: "POST",
        url: "controllers/environments/ajax.php",
        data: {
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
function renameEnv(envs)
{
    $.ajax({
        type: "POST",
        url: "controllers/environments/ajax.php",
        data: {
            action: "renameEnv",
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