/**
 *  Event: generate new apikey
 */
$(document).on('click','#user-generate-apikey-btn',function () {
    generateApikey();

    event.stopPropagation();
});

/**
 *  Event: edit user personnal informations
 */
$(document).on('submit','#user-edit-info',function () {
    event.preventDefault();

    var username = $('#user-edit-info').find('input[type=hidden][name=username]').val();
    var firstName = $('#user-edit-info').find('input[type=text][name=first-name]').val();
    var lastName = $('#user-edit-info').find('input[type=text][name=last-name]').val();
    var email = $('#user-edit-info').find('input[type=email][name=email]').val();

    edit(username, firstName, lastName, email);

    return false;
});

/**
 *  Event: edit password
 */
$(document).on('submit','#user-change-password',function () {
    event.preventDefault();

    var username = $('#user-change-password').find('input[type=hidden][name=username]').val();
    var actualPassword = $('#user-change-password').find('input[type=password][name=actual-password]').val();
    var newPassword = $('#user-change-password').find('input[type=password][name=new-password]').val();
    var newPasswordConfirm = $('#user-change-password').find('input[type=password][name=new-password-confirm]').val();

    changePassword(username, actualPassword, newPassword, newPasswordConfirm);

    return false;
});


/**
 *  Ajax: generate new apikey
 */
function generateApikey()
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "login",
            action: "generateApikey"
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message.message, 'success');

            /**
             *  Print API key
             */
            $('.slide-panel-container[slide-panel="general/userspace"]').find('#user_apikey').html(jsonValue.message.apikey);
            $('.slide-panel-container[slide-panel="general/userspace"]').find('#user-apikey-copy-btn').css('display', 'inline-block');
        },
        error: function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: edit personnal informations
 * @param {*} username
 * @param {*} firstName
 * @param {*} lastName
 * @param {*} email
 */
function edit(username, firstName, lastName, email)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "login",
            action: "edit",
            username: username,
            firstName: firstName,
            lastName: lastName,
            email: email
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
 * Ajax: edit password
 * @param {*} username
 * @param {*} actualPassword
 * @param {*} newPassword
 * @param {*} newPasswordConfirm
 */
function changePassword(username, actualPassword, newPassword, newPasswordConfirm)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "login",
            action: "changePassword",
            username: username,
            actualPassword: actualPassword,
            newPassword: newPassword,
            newPasswordConfirm: newPasswordConfirm
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