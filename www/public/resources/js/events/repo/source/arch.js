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
