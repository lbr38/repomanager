/**
 *  Event: Open user permission panel
 */
$(document).on('click','.user-permissions-edit-btn',function () {
    var id = $(this).attr('user-id');

    mypanel.get('settings/user/permissions', {'Id': id});
});

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
        mylayout.reloadContentById('currentUsers');

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

    myconfirmbox.print(
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

    myconfirmbox.print(
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
                        mylayout.reloadContentById('currentUsers');
                    });
                }
            }]
        }
    );
});

/**
 *  Event: edit user permissions
 */
$(document).on('submit','#user-permissions-form',function () {
    event.preventDefault();

    const id = $(this).attr('user-id');
    var reposView = $(this).find('#user-permissions-repos-view').val();
    var reposActions = $(this).find('#user-permissions-repos-actions').val();
    var tasksActions = $(this).find('#user-permissions-tasks-actions').val();
    var hostsActions = $(this).find('#user-permissions-hosts-actions').val();

    // If no repos view are selected, set to empty array
    if (empty(reposView)) {
        reposView = [''];
    }

    // If no repos actions are selected, set to empty array
    if (empty(reposActions)) {
        reposActions = [''];
    }

    // If no tasks actions are selected, set to empty array
    if (empty(tasksActions)) {
        tasksActions = [''];
    }

    // If no hosts actions are selected, set to empty array
    if (empty(hostsActions)) {
        hostsActions = [''];
    }

    ajaxRequest(
        // Controller:
        'settings/user',
        // Action:
        'edit-permissions',
        // Data:
        {
            id: id,
            reposView: reposView,
            reposActions: reposActions,
            tasksActions: tasksActions,
            hostsActions: hostsActions
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    );

    return false;
});
