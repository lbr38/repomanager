/**
 *  Events listeners
 */

/**
 *  Event: Create new profile
 */
$(document).on('submit','#newProfileForm',function () {
    event.preventDefault();

    var name = $("#newProfileInput").val();

    ajaxRequest(
        // Controller:
        'profile',
        // Action:
        'new',
        // Data:
        {
            name: name
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        ['profiles/list']
    );

    return false;
});

/**
 *  Event: Delete profile
 */
$(document).on('click','.profile-delete-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var id = $(this).attr('profile-id');
    var name = $(this).attr('profile-name');

    confirmBox(
        {
            'title': 'Delete profile',
            'message': 'Are you sure you want to delete profile <b>' + name + '</b>?',
            'buttons': [
            {
                'text': 'Delete',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'profile',
                        // Action:
                        'delete',
                        // Data:
                        {
                            id: id
                        },
                        // Print success alert:
                        true,
                        // Print error alert:
                        true,
                        // Reload container:
                        ['profiles/list']
                    );
                }
            }]
        }
    );
});

/**
 *  Event: Duplicate profile
 */
$(document).on('click','.profile-duplicate-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var id = $(this).attr('profile-id');

    ajaxRequest(
        // Controller:
        'profile',
        // Action:
        'duplicate',
        // Data:
        {
            id: id
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        ['profiles/list']
    );
});

/**
 *  Event: Print profile configuration
 */
$(document).on('click','.profile-config-btn',function () {
    var id = $(this).attr('profile-id');

    slide('.profile-config-div[profile-id=' + id + ']');
});

/**
 *  Event: Save profile configuration
 */
$(document).on('submit','.profile-config-form',function () {
    event.preventDefault();
    /**
     *  Retrieve profile configuration
     */
    var id = $(this).attr('profile-id');
    var name = $(this).find('input[name=profile-name]').val();
    var reposList = $(this).find('select[name=profile-repos]').val();
    var exclude = $(this).find('select[name=profile-exclude]').val();
    var excludeMajor = $(this).find('select[name=profile-exclude-major]').val();
    var serviceRestart = $(this).find('select[name=profile-service-restart]').val();
    var notes = $(this).find('textarea[name=profile-notes]').val();

    ajaxRequest(
        // Controller:
        'profile',
        // Action:
        'configure',
        // Data:
        {
            id: id,
            name: name,
            reposList: reposList,
            exclude: exclude,
            excludeMajor: excludeMajor,
            serviceRestart: serviceRestart,
            notes: notes
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        ['profiles/list']
    );

    return false;
});
