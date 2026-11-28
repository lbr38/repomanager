/**
 *  Event: add a repository or an architecture to an arch source repository
 */
$(document).on('click','button.source-repo-arch-add-btn',function () {
    const id = $(this).attr('source-id');
    const field = $(this).attr('field');
    const name = $('input.source-repo-arch-add-input[source-id="' + id + '"][field="' + field + '"]').val();

    ajaxRequest(
        // Controller:
        'repo/source/arch',
        // Action:
        'add',
        // Data:
        {
            id: id,
            field: field,
            name: name
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mypanel.reload('repos/sources/list');
    });
});

/**
 *  Event: open the edit panel of a repository or an architecture
 */
$(document).on('click','.source-repo-arch-edit-btn',function () {
    mypanel.get('repos/sources/edit-arch-item', {
        id: $(this).attr('source-id'),
        field: $(this).attr('field'),
        itemId: $(this).attr('item-id')
    });
});

/**
 *  Event: edit a repository or an architecture
 */
$(document).on('submit','form.source-repo-edit-arch-item',function (e) {
    e.preventDefault();

    const id = $(this).attr('source-id');
    const field = $(this).attr('field');
    const itemId = $(this).attr('item-id');

    ajaxRequest(
        // Controller:
        'repo/source/arch',
        // Action:
        'edit',
        // Data:
        {
            id: id,
            field: field,
            itemId: itemId,
            name: $(this).find('.arch-item-param[param-name="name"]').val(),
            description: $(this).find('.arch-item-param[param-name="description"]').val(),
            // Only alpine branches and openbsd versions have an end of life date
            eol: $(this).find('.arch-item-param[param-name="eol"]').val() ?? ''
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mypanel.reload('repos/sources/list');
        mypanel.reload('repos/sources/edit-arch-item', {id: id, field: field, itemId: itemId});
    });

    return false;
});

/**
 *  Event: remove a repository or an architecture from an arch source repository
 */
$(document).on('click','.source-repo-arch-remove-btn',function (e) {
    e.stopPropagation();

    ajaxRequest(
        // Controller:
        'repo/source/arch',
        // Action:
        'remove',
        // Data:
        {
            id: $(this).attr('source-id'),
            field: $(this).attr('field'),
            itemId: $(this).attr('item-id')
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mypanel.reload('repos/sources/list');
    });
});
