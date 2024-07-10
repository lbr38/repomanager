function editEnv()
{
    var envs = [];

    $('.env-input').each(function () {
        var env = $(this).val();

        envs.push(env);
    });

    ajaxRequest(
        // Controller:
        'environment',
        // Action:
        'editEnv',
        // Data:
        {
            envs: envs,
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        [],
        // Execute functions on success:
        [
            "reloadContentById('envDiv')"
        ]
    );
}

/**
 *  Event: delete an environment
 */
$(document).on('click','.delete-env-btn',function () {
    var name = $(this).attr('env-name');

    confirmBox('Are you sure you want to delete environment <b>' + name + '</b>?', function () {
        ajaxRequest(
            // Controller:
            'environment',
            // Action:
            'deleteEnv',
            // Data:
            {
                name: name,
            },
            // Print success alert:
            true,
            // Print error alert:
            true,
            // Reload container:
            [],
            // Execute functions on success:
            [
                "reloadContentById('envDiv')"
            ]
        );
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
