/**
 *  Events listeners
 */

/**
 *  Event: Add source repo
 */
$(document).on('submit','#add-source-repo-form',function () {
    event.preventDefault();

    var params = {};

    /**
     *  Retrieve source repo type
     */
    params['type'] = $('input[name=addSourceRepoType]:checked').val();

    /**
     *  Retrieve source repo name
     */
    params['name'] = $('input[name=addSourceName]').val();

    /**
     *  Retrieve source repo url
     */
    params['url'] = $('input[name=addSourceUrl]').val();

    ajaxRequest(
        // Controller:
        'repo/source/source',
        // Action:
        'new',
        // Data:
        {
            params: params
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mypanel.reload('repos/sources/list');
        mypanel.reload('repos/new');
    });

    return false;
});

/**
 *  Event: import source repositories from list
 */
$(document).on('submit','#import-source-repos',function () {
    event.preventDefault();

    list = $(this).find('select[name="source-repos-list"]').val();

    myalert.print('Importing source repositories...', null, null);

    ajaxRequest(
        // Controller:
        'repo/source/source',
        // Action:
        'import-source-repos',
        // Data:
        {
            list: list
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mypanel.reload('repos/sources/list');
        mypanel.reload('repos/new');
    });

    return false;
});

/**
 *  Event: Edit source repo
 */
$(document).on('click','.source-repo-form-submit-btn',function () {
    event.preventDefault();

    var id = $(this).attr('source-id');
    var params = {};

    /**
     *  Retrieve the parameters entered by the user and push them into the object
     */
    $('form.source-repo-form[source-id="' + id + '"]').find('.source-param').each(function () {
        var name = $(this).attr('param-name');

        // If input is a checkbox and it is checked then its value is 'true', else its value is 'false'
        if ($(this).attr('type') == 'checkbox') {
            if ($(this).is(":checked")) {
                var value = 'true';
            } else {
                var value = 'false';
            }
        } else {
            var value = $(this).val();
        }

        params[name] = value;
    });

    ajaxRequest(
        // Controller:
        'repo/source/source',
        // Action:
        'edit',
        // Data:
        {
            id: id,
            params: params
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mypanel.reload('repos/sources/list');
        mypanel.reload('repos/new');
    });

    return false;
});

/**
 *  Event: Show/hide source repo params
 */
$(document).on('click','.source-repo-edit-param-btn',function () {
    var sourceId = $(this).attr('source-id');

    slide('.source-repo-param-div[source-id="' + sourceId + '"]');
});
