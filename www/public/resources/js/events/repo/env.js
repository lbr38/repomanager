/**
 *  Event: select one or multiple snapshot environment(s) to delete
 */
$(document).on('click','.select-env-checkbox',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var actions = [];

    // If the checkbox is checked, make it visible, else remove any custom visibility so it returns to default
    if ($(this).is(':checked')) {
        $(this).css('visibility', 'visible');
    } else {
        $(this).css('visibility', '');
    }

    // Get all checked checkboxes
    const checked = $('.reposList').find('input[type="checkbox"].select-env-checkbox:checked');

    if (checked.length == 0) {
        myconfirmbox.close();
        return;
    }

    // For each checked checkbox, prepare the task action to be done
    checked.each(function () {
        actions.push({
            'action': 'removeEnv',
            'repo-id': $(this).attr('repo-id'),
            'snap-id': $(this).attr('snap-id'),
            'env-id': $(this).attr('env-id'),
            'env': $(this).attr('env'),
            'schedule': {
                'scheduled': false
            }
        });
    });

    myconfirmbox.print(
        {
            'title': 'Remove environment',
            'message': checked.length + ' environment' + (checked.length > 1 ? 's' : '') + ' selected',
            'id': 'repo-env-select-confirm-box',
            'buttons': [
                {
                    'text': 'Remove',
                    'color': 'red',
                    'callback': function () {
                        for (var i = 0; i < actions.length; i++) {
                            ajaxRequest(
                                // Controller:
                                'task',
                                // Action:
                                'validateForm',
                                // Data:
                                {
                                    taskParams: JSON.stringify([actions[i]]),
                                },
                                // Print success alert:
                                true,
                                // Print error alert:
                                true
                            );
                        }
                    }
            }
            ]
        }
    );
});
