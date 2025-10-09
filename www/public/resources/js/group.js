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
