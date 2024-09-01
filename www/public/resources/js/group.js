selectToSelect2('select.group-repos-list', 'Add repository');

/**
 *  Events listeners
 */

/**
 *  Event: Create new group
 */
$(document).on('submit','#newGroupForm',function () {
    event.preventDefault();
    /**
     *  Retrieve group name from input
     */
    var name = $("#newGroupInput").val();

    newGroup(name);

    return false;
});

/**
 *  Event: Delete group
 */
$(document).on('click','.delete-group-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var id = $(this).attr('group-id');
    var name = $(this).attr('group-name');

    confirmBox('Are you sure you want to delete group ' + name + '?', function () {
        deleteGroup(id)});
});

/**
 *  Event: Edit group
 */
$(document).on('submit','.group-form',function () {
    event.preventDefault();

    /**
     *  Retrieve group name (from <form>) and repos list (from <select>)
     */
    var id = $(this).attr('group-id');
    var name = $(this).find('.group-name-input[group-id="' + id + '"]').val();
    var reposId = $(this).find('select.group-repos-list[group-id="' + id + '"]').val();

    editGroup(id, name, reposId);

    return false;
});

/**
 *  Event: Print group configuration div
 */
$(document).on('click','.group-config-btn',function () {
    var id = $(this).attr('group-id');

    slide('.group-config-div[group-id="' + id + '"]');
});

/**
 * Ajax: Create a new group
 * @param {string} name
 */
function newGroup(name)
{
    ajaxRequest(
        // Controller:
        'group',
        // Action:
        'new',
        // Data:
        {
            name: name,
            type: 'repo'
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        ['repos/list'],
        // Execute functions on success:
        [
            // Reload group panel
            "reloadPanel('repos/groups', function () { selectToSelect2('select.group-repos-list', 'Add repository'); })",
            // Reload create repo div
            "reloadNewRepoDiv()"
        ]
    );
}

/**
 * Ajax: Delete a group
 * @param {string} id
 */
function deleteGroup(id)
{
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
        true,
        // Reload container:
        ['repos/list'],
        // Execute functions on success:
        [
            // Reload group panel
            "reloadPanel('repos/groups', function () { selectToSelect2('select.group-repos-list', 'Add repository'); })",
            // Reload create repo div
            "reloadNewRepoDiv()"
        ]
    );
}

/**
 * Ajax: Edit a group
 * @param {string} id
 * @param {string} name
 * @param {string} reposId
 */
function editGroup(id, name, reposId)
{
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
        ['repos/list'],
        // Execute functions on success:
        [
            // Reload group panel
            "reloadPanel('repos/groups', function () { selectToSelect2('select.group-repos-list', 'Add repository'); })",
        ]
    );
}
