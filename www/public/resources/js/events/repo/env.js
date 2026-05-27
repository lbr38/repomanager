/**
 *  Event: click on a snap-env-container to toggle the environment checkbox
 */
$(document).on('click', '.snap-env-container', function (e) {
    // Prevent triggering the parent snap-container click
    e.stopPropagation();

    // Toggle the hidden checkbox
    var checkbox = $(this).find('.select-env-checkbox');
    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
});

/**
 *  Event: toggle selected state and confirmbox when env checkbox changes
 */
$(document).on('change', '.select-env-checkbox', function (e) {
    // Prevent parent to be triggered
    e.stopPropagation(e);

    var container = $(this).closest('.snap-env-container');

    // Toggle visual state
    if ($(this).is(':checked')) {
        container.addClass('env-selected');
    } else {
        container.removeClass('env-selected');
    }

    var actions = [];

    // Get all checked checkboxes
    const checked = $('#repositories-list').find('input[type="checkbox"].select-env-checkbox:checked');

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
