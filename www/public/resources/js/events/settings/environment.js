/**
 *  Event: add a new environment by pressing enter
 */
$(document).on('keypress','input[name="add-env-name"]', function (e) {
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if (keycode == '13') {
        e.stopPropagation();
        addEnv();
    }
});

/**
 *  Event: add a new environment by clicking the add button
 */
$(document).on('click','#add-env-btn',function () {
    addEnv();
});

/**
 *  Event: edit environments by pressing enter
 */
$(document).on('keypress','input[name="env-name"]', function (e) {
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if (keycode == '13') {
        e.stopPropagation();
        editEnv();
    }
});

/**
 *  Event: edit environments by clicking the edit button
 */
$(document).on('click','#edit-env-btn', function () {
    editEnv()
});

/**
 *  Event: delete an environment
 */
$(document).on('click','.delete-env-btn',function () {
    var id = $(this).attr('env-id');
    var name = $(this).attr('env-name');

    confirmBox(
        {
            'title': 'Delete environment',
            'message': 'Are you sure you want to delete environment <b>' + name + '</b>?',
            'buttons': [
            {
                'text': 'Delete',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'environment',
                        // Action:
                        'delete-env',
                        // Data:
                        {
                            id: id,
                        },
                        // Print success alert:
                        true,
                        // Print error alert:
                        true,
                        // Reload container:
                        [],
                        // Execute functions on success:
                        [
                            "reloadContentById('envs-div')"
                        ]
                    );
                }
            }]
        }
    );
});
