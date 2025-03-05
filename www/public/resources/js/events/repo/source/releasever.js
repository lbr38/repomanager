/**
 *  Event: Show/hide source repo release version params
 */
$(document).on('click','.source-repo-releasever-edit-param-btn',function () {
    var id = $(this).attr('source-id');
    var releaseverId = $(this).attr('releasever-id');

    mypanel.get('repos/sources/edit-releasever', {
        id: id,
        releaseverId: releaseverId
    });
});

/**
 *  Event: add source repository release version
 */
$(document).on('click','button.source-repo-add-releasever-btn',function () {
    var id = $(this).attr('source-id');
    var name = $('input.source-repo-add-releasever-input[source-id="' + id + '"]').val();

    ajaxRequest(
        // Controller:
        'repo/source/releasever',
        // Action:
        'add',
        // Data:
        {
            id: id,
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
 *  Event: edit source repository release version
 */
$(document).on('submit','form.source-repo-edit-releasever',function () {
    event.preventDefault();

    var id = $(this).attr('source-id');
    var releaseverId = $(this).attr('releasever-id');
    var params = {};

    /**
     *  Retrieve the parameters entered by the user and push them into the object
     */
    $('form.source-repo-edit-releasever[source-id="' + id + '"][releasever-id="' + releaseverId + '"]').find('.releasever-param').each(function () {
        var name = $(this).attr('param-name');
        var value = $(this).val();

        params[name] = value;
    });

    ajaxRequest(
        // Controller:
        'repo/source/releasever',
        // Action:
        'edit',
        // Data:
        {
            id: id,
            releaseverId: releaseverId,
            params: params
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mypanel.reload('repos/sources/list');
        mypanel.reload('repos/sources/edit-releasever', {id: id, releaseverId: releaseverId});
    });

    return false;
});

/**
 *  Event: remove source repository release version
 */
$(document).on('click','.source-repo-remove-releasever-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var id = $(this).attr('source-id');
    var releaseverId = $(this).attr('releasever-id');

    confirmBox(
        {
            'title': 'Remove release version',
            'buttons': [
            {
                'text': 'Remove',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'repo/source/releasever',
                        // Action:
                        'remove',
                        // Data:
                        {
                            id: id,
                            releaseverId: releaseverId,
                        },
                        // Print success alert:
                        true,
                        // Print error alert:
                        true
                    ).then(function () {
                        mypanel.reload('repos/sources/list');
                    });
                }
            }]
        }
    );
});

/**
 *  Event: add gpg key to release version
 */
$(document).on('submit','.source-repo-edit-releasever-add-gpgkey-form',function () {
    event.preventDefault();

    var id = $(this).attr('source-id');
    var releaseverId = $(this).attr('releasever-id');
    var gpgKeyUrl = $(this).find('input[type="text"][name="gpgkey-url"]').val();
    var gpgKeyFingerprint = $(this).find('input[type="text"][name="gpgkey-fingerprint"]').val();
    var gpgKeyPlainText = $(this).find('textarea[name="gpgkey-plaintext"]').val();

    ajaxRequest(
        // Controller:
        'repo/source/releasever',
        // Action:
        'add-gpgkey',
        // Data:
        {
            id: id,
            releaseverId: releaseverId,
            gpgKeyUrl: gpgKeyUrl,
            gpgKeyFingerprint: gpgKeyFingerprint,
            gpgKeyPlainText: gpgKeyPlainText
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mypanel.reload('repos/sources/list');
        mypanel.reload('repos/sources/edit-releasever', {id: id, releaseverId: releaseverId});
    });

    return false;
});

/**
 *  Event: remove gpg key from release version
 */
$(document).on('click','.source-repo-edit-releasever-remove-gpgkey-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var id = $(this).attr('source-id');
    var releaseverId = $(this).attr('releasever-id');
    var gpgkeyId = $(this).attr('gpgkey-id');

    confirmBox(
        {
            'title': 'Remove GPG key',
            'buttons': [
            {
                'text': 'Remove',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'repo/source/releasever',
                        // Action:
                        'remove-gpgkey',
                        // Data:
                        {
                            id: id,
                            releaseverId: releaseverId,
                            gpgkeyId: gpgkeyId,
                        },
                        // Print success alert:
                        true,
                        // Print error alert:
                        true
                    ).then(function () {
                        mypanel.reload('repos/sources/list');
                        mypanel.reload('repos/sources/edit-releasever', {id: id, releaseverId: releaseverId});
                    });
                }
            }]
        }
    );
});
