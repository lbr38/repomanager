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
        true,
        // Reload containers:
        [],
        // Execute functions on success:
        [
            "reloadPanel('repos/sources/list')",
            "reloadPanel('repos/new')"
        ]
    );

    return false;
});

/**
 *  Event: import source repositories from list
 */
$(document).on('submit','#import-source-repos',function () {
    event.preventDefault();

    list = $(this).find('select[name="source-repos-list"]').val();

    printAlert('Importing source repositories...', null, null);

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
        true,
        // Reload containers:
        [],
        // Execute functions on success:
        [
            "reloadPanel('repos/sources/list')",
            "reloadPanel('repos/new')"
        ]
    );

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
        var value = $(this).val();

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
        true,
        // Reload containers:
        [],
        // Execute functions on success:
        [
            "reloadPanel('repos/sources/list')",
            "reloadPanel('repos/new')"
        ]
    );

    return false;
});

/**
 *  Event: Show/hide source repo params
 */
$(document).on('click','.source-repo-edit-param-btn',function () {
    var sourceId = $(this).attr('source-id');

    slide('.source-repo-param-div[source-id="' + sourceId + '"]');
});

/**
 *  Event: Delete a source repo
 */
$(document).on('click','.source-repo-delete-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var sourceId = $(this).attr('source-id');
    var name = $(this).attr('source-name');

    confirmBox(
        {
            'title': 'Delete source repository',
            'message': 'Are you sure you want to delete <b>' + name + '</b> source repository?',
            'buttons': [
            {
                'text': 'Delete',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'repo/source/source',
                        // Action:
                        'delete',
                        // Data:
                        {
                            sourceId: sourceId
                        },
                        // Print success alert:
                        true,
                        // Print error alert:
                        true,
                        // Reload containers:
                        [],
                        // Execute functions on success:
                        [
                            "reloadPanel('repos/sources/list')",
                            "reloadPanel('repos/new')"
                        ]
                    );
                }
            }]
        }
    );
});

/**
 *  Event: delete a GPG key
 */
$(document).on('click','.gpgKeyDeleteBtn',function () {
    var gpgKeyId = $(this).attr('gpgkey-id');
    var gpgkeyName = $(this).attr('gpgkey-name');

    confirmBox(
        {
            'title': 'Delete GPG key',
            'message': 'Are you sure you want to delete <b>' + gpgkeyName + '</b> GPG key?',
            'buttons': [
            {
                'text': 'Delete',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'repo/source/source',
                        // Action:
                        'delete-gpgkey',
                        // Data:
                        {
                            gpgKeyId: gpgKeyId
                        },
                        // Print success alert:
                        true,
                        // Print error alert:
                        true,
                        // Reload containers:
                        [],
                        // Execute functions on success:
                        [
                            "reloadPanel('repos/sources/list')"
                        ]
                    );
                }
            }]
        }
    );
});
