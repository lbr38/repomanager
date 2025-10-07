/**
 *  Events listeners
 */

/**
 *  Event: Create new group
 */
$(document).on('submit','#newGroupForm',function (e) {
    e.preventDefault();

    ajaxRequest(
        // Controller:
        'group',
        // Action:
        'new',
        // Data:
        {
            name: $("#newGroupInput").val(),
            type: 'repo'
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        // Reload repos list
        mycontainer.reload('repos/list');
        // Reload group panel
        mypanel.reload('repos/groups/list');
        // Reload create repo div
        mypanel.reload('repos/new');
    });

    return false;
});

/**
 *  Event: select groups for deletion
 */
$(document).on('click','.delete-group-checkbox',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var id = [];

    // If the checkbox is checked, make it visible, else remove any custom visibility so it returns to default
    if ($(this).is(':checked')) {
        $(this).css('opacity', '1');
    } else {
        $(this).css('opacity', '');
    }

    // Get all checked checkboxes
    const checked = $('.slide-panel-reloadable-div[slide-panel="repos/groups/list"]').find('input[type="checkbox"].delete-group-checkbox:checked');

    if (checked.length == 0) {
        myconfirmbox.close();
        return;
    }

    // For each checked checkbox, get the group id
    checked.each(function () {
        id.push($(this).attr('group-id'));
    });

    myconfirmbox.print(
        {
            'title': 'Delete group',
            'message': checked.length + ' group' + (checked.length > 1 ? 's' : '') + ' selected',
            'id': 'repo-group-select-confirm-box',
            'buttons': [
                {
                    'text': 'Delete',
                    'color': 'red',
                    'callback': function () {
                        ajaxRequest(
                            // Controller:
                            'group',
                            // Action:
                            'delete',
                            // Data:
                            {
                                id: id,
                                type: 'repo'
                            },
                            // Print success alert:
                            true,
                            // Print error alert:
                            true
                        ).then(function () {
                            // Reload repos list
                            mycontainer.reload('repos/list');
                            // Reload group panel
                            mypanel.reload('repos/groups/list');
                            // Reload create repo panel
                            mypanel.reload('repos/new');
                        });
                    }
            }
            ]
        }
    );
});

/**
 *  Event: Edit group
 */
$(document).on('submit','.group-form',function (e) {
    e.preventDefault();

    /**
     *  Retrieve group name (from <form>) and repos list (from <select>)
     */
    var id = $(this).attr('group-id');
    var name = $(this).find('.group-name-input[group-id="' + id + '"]').val();
    var reposId = $(this).find('select.group-repos-list[group-id="' + id + '"]').val();

    ajaxRequest(
        // Controller:
        'group',
        // Action:
        'edit',
        // Data:
        {
            id: id,
            name: name,
            data: reposId,
            type: 'repo'
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        ['repos/list']
    ).then(function () {
        // Reload group panel
        mypanel.reload('repos/groups/list');
        // Reload create repo div
        mypanel.reload('repos/new');
    });

    return false;
});

/**
 *  Event: Print group configuration div
 */
$(document).on('click','.group-config-btn',function () {
    var id = $(this).attr('group-id');

    slide('.group-config-div[group-id="' + id + '"]');
});
