/**
 *  Event: generate new apikey
 */
$(document).on('click','#user-generate-apikey-btn',function () {
    confirmBox(
        {
            'title': 'Generate API key',
            'message': 'Are you sure you want to generate a new API key? Once you generate a new API key, the old one will be invalid.',
            'buttons': [
            {
                'text': 'Generate',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        'login',
                        'generateApikey',
                        {},
                        false,
                        true,
                        [],
                        [
                            "$('.slide-panel-container[slide-panel=\"general/userspace\"]').find('input#user-apikey').val(jsonValue.message);",
                            "$('.slide-panel-container[slide-panel=\"general/userspace\"]').find('input#user-apikey').addClass('copy-input-onclick');"
                        ]
                    );
                }
            }]
        }
    );

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

    ajaxRequest(
        // Controller:
        'login',
        // Action:
        'edit',
        // Data:
        {
            username: username,
            firstName: firstName,
            lastName: lastName,
            email: email
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload containers:
        []
    );

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

    ajaxRequest(
        // Controller:
        'login',
        // Action:
        'changePassword',
        // Data:
        {
            username: username,
            actualPassword: actualPassword,
            newPassword: newPassword,
            newPasswordConfirm: newPasswordConfirm
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload containers:
        []
    );

    return false;
});
