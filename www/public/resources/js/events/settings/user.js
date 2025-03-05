
/**
 *  Event: create a new user
 */
$(document).on('submit','#new-user-form',function () {
    event.preventDefault();

    var username = $(this).find('input[name=username]').val();
    var role = $(this).find('select[name=role]').val();

    ajaxRequest(
        // Controller:
        'settings/user',
        // Action:
        'create',
        // Data:
        {
            username: username,
            role: role
        },
        // Print success alert:
        false,
        // Print error alert:
        true
    ).then(function () {
        // Reload current users div
        reloadContentById('currentUsers');

        // Print generated password for the new user
        $('#users-settings-container').find('#user-settings-generated-passwd').html('<p>Temporary password generated for <b>' + username + '</b>:<br><span class="greentext copy">' + jsonValue.message.password + '</span></p>');
    });

    return false;
});

/**
 *  Event: reset user password
 */
$(document).on('click','.reset-password-btn',function () {
    var username = $(this).attr('username');
    var id = $(this).attr('user-id');

    confirmBox(
        {
            'title': 'Reset password',
            'message': 'Reset password of user ' + username + '?',
            'buttons': [
            {
                'text': 'Reset',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'settings/user',
                        // Action:
                        'reset-password',
                        // Data:
                        {
                            id: id
                        },
                        // Print success alert:
                        false,
                        // Print error alert:
                        true
                    ).then(function () {
                        // Print new generated password
                        $('#users-settings-container').find('#user-settings-generated-passwd').html('<p>New password generated for <b>' + username + '</b>:<br><span class="greentext copy">' + jsonValue.message.password + '</span></p>');
                    });
                }
            }]
        }
    );
});

/**
 *  Event: delete user
 */
$(document).on('click','.delete-user-btn',function () {
    var username = $(this).attr('username');
    var id = $(this).attr('user-id');

    confirmBox(
        {
            'title': 'Delete user',
            'message': 'Delete user ' + username + '?',
            'buttons': [
            {
                'text': 'Delete',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'settings/user',
                        // Action:
                        'delete',
                        // Data:
                        {
                            id: id
                        },
                        // Print success alert:
                        true,
                        // Print error alert:
                        true
                    ).then(function () {
                        // Reload current users div
                        reloadContentById('currentUsers');
                    });
                }
            }]
        }
    );
});
