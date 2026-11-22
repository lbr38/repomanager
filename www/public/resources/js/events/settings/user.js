/**
 *  Return a box printing a generated password, only one generated password is displayed at a time
 */
function userGeneratedPasswordBox(message, username, password)
{
    $('.user-generated-passwd').remove();

    return $('<div class="note-info user-generated-passwd">')
        .append('<img src="/assets/icons/info.svg" />')
        .append(
            $('<div>')
                .append($('<p>').text(message + ' ').append($('<b>').text(username)).append(':'))
                .append($('<p>').append($('<span class="greentext copy">').text(password)))
        );
}

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
        mylayout.reloadContentById('current-users');

        // Print generated password for the new user, below the creation form
        $('#users-settings-container').find('#user-settings-generated-passwd').html(userGeneratedPasswordBox('Temporary password generated for', username, jsonValue.message.password));
    });

    return false;
});

/**
 *  Event: reset user password
 */
$(document).on('click','.reset-password-btn',function () {
    var username = $(this).attr('username');
    var id = $(this).attr('user-id');
    var userLine = $(this).closest('.table-container');

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
                        // Print new generated password, below the user line
                        userLine.after(userGeneratedPasswordBox('New password generated for', username, jsonValue.message.password));
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
                        mylayout.reloadContentById('current-users');
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
    var selectedActions = $(this).find('#user-permissions-repos-actions').val() || [];

    // Repositories and mirrors actions share the same selector, mirrors actions are prefixed with 'mirror-'
    var reposActions = selectedActions.filter(function (action) {
        return !action.startsWith('mirror-');
    });
    var mirrorsActions = selectedActions.filter(function (action) {
        return action.startsWith('mirror-');
    }).map(function (action) {
        return action.substring('mirror-'.length);
    });
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

    // If no mirrors actions are selected, set to empty array
    if (empty(mirrorsActions)) {
        mirrorsActions = [''];
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
            mirrorsActions: mirrorsActions,
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
