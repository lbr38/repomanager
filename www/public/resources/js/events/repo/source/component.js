/**
 *  Event: show source repo component params
 */
$(document).on('click','.source-repo-component-edit-btn',function () {
    mypanel.get('repos/sources/edit-component', {
        id: $(this).attr('source-id'),
        distributionId: $(this).attr('distribution-id'),
        componentId: $(this).attr('component-id')
    });
});

/**
 *  Event: add source repository distribution component
 */
$(document).on('click','button.source-repo-add-component-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    const id = $(this).attr('source-id');
    const distributionId = $(this).attr('distribution-id');
    const component = $('.source-repo-edit-distribution-add-component-input[source-id="' + id + '"][distribution-id="' + distributionId + '"]').val();

    ajaxRequest(
        // Controller:
        'repo/source/component',
        // Action:
        'add',
        // Data:
        {
            id: id,
            distributionId: distributionId,
            component: component,
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mypanel.reload('repos/sources/list');
        mypanel.reload('repos/sources/edit-distribution', {id: id, distributionId: distributionId});
    });
});

/**
 *  Event: edit source repository component
 */
$(document).on('submit','form.source-repo-edit-component',function (e) {
    e.preventDefault();

    const id = $(this).attr('source-id');
    const distributionId = $(this).attr('distribution-id');
    const componentId = $(this).attr('component-id');
    var params = {};

    // Retrieve the parameters entered by the user and push them into the object
    $('form.source-repo-edit-component[source-id="' + id + '"][component-id="' + componentId + '"]').find('.component-param').each(function () {
        var name = $(this).attr('param-name');
        var value = $(this).val();

        params[name] = value;
    });

    ajaxRequest(
        // Controller:
        'repo/source/component',
        // Action:
        'edit',
        // Data:
        {
            id: id,
            distributionId: distributionId,
            componentId: componentId,
            params: params
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mypanel.reload('repos/sources/edit-distribution', {id: id, distributionId: distributionId});
        mypanel.reload('repos/sources/edit-component', {id: id, distributionId: distributionId, componentId: componentId});
    });

    return false;
});

/**
 *  Event: remove source repository distribution component
 */
$(document).on('click','.source-repo-remove-component-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    const id = $(this).attr('source-id');
    const distributionId = $(this).attr('distribution-id');
    const componentId = $(this).attr('component-id');

    myconfirmbox.print(
        {
            'title': 'Remove component',
            'buttons': [
            {
                'text': 'Remove',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'repo/source/component',
                        // Action:
                        'remove',
                        // Data:
                        {
                            id: id,
                            distributionId: distributionId,
                            componentId: componentId
                        },
                        // Print success alert:
                        true,
                        // Print error alert:
                        true
                    ).then(function () {
                        mypanel.reload('repos/sources/list');
                        mypanel.reload('repos/sources/edit-distribution', {id: id, distributionId: distributionId});
                    });
                }
            }]
        }
    );
});
