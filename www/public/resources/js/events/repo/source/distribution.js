/**
 *  Event: Show/hide source repo distribution params
 */
$(document).on('click','.source-repo-distribution-edit-param-btn',function () {
    var id = $(this).attr('source-id');
    var distributionId = $(this).attr('distribution-id');

    mypanel.get('repos/sources/edit-distribution', {
        id: id,
        distributionId: distributionId
    });
});

/**
 *  Event: add source repository distribution
 */
$(document).on('click','button.source-repo-add-distribution-btn',function () {
    var id = $(this).attr('source-id');
    var name = $('input.source-repo-add-distribution-input[source-id="' + id + '"]').val();

    ajaxRequest(
        // Controller:
        'repo/source/distribution',
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
 *  Event: edit source repository distribution
 */
$(document).on('submit','form.source-repo-edit-distribution',function () {
    event.preventDefault();

    var id = $(this).attr('source-id');
    var distributionId = $(this).attr('distribution-id');
    var params = {};

    /**
     *  Retrieve the parameters entered by the user and push them into the object
     */
    $('form.source-repo-edit-distribution[source-id="' + id + '"][distribution-id="' + distributionId + '"]').find('.distribution-param').each(function () {
        var name = $(this).attr('param-name');
        var value = $(this).val();

        params[name] = value;
    });

    ajaxRequest(
        // Controller:
        'repo/source/distribution',
        // Action:
        'edit',
        // Data:
        {
            id: id,
            distributionId: distributionId,
            params: params
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mypanel.reload('repos/sources/list');
        mypanel.reload('repos/sources/edit-distribution', {id: id, distributionId: distributionId});
    });

    return false;
});

/**
 *  Event: remove source repository distribution
 */
$(document).on('click','.source-repo-remove-distribution-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var id = $(this).attr('source-id');
    var distributionId = $(this).attr('distribution-id');

    confirmBox(
        {
            'title': 'Remove distribution',
            'buttons': [
            {
                'text': 'Remove',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'repo/source/distribution',
                        // Action:
                        'remove',
                        // Data:
                        {
                            id: id,
                            distributionId: distributionId,
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
 *  Event: add source repository distribution section
 */
$(document).on('click','.source-repo-edit-distribution-add-section-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var id = $(this).attr('source-id');
    var distributionId = $(this).attr('distribution-id');
    var section = $('.source-repo-edit-distribution-add-section-input[source-id="' + id + '"][distribution-id="' + distributionId + '"]').val();

    ajaxRequest(
        // Controller:
        'repo/source/distribution',
        // Action:
        'add-section',
        // Data:
        {
            id: id,
            distributionId: distributionId,
            section: section,
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
 *  Event: remove source repository distribution section
 */
$(document).on('click','.source-repo-edit-distribution-remove-section-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var id = $(this).attr('source-id');
    var distributionId = $(this).attr('distribution-id');
    var sectionId = $(this).attr('section-id');

    confirmBox(
        {
            'title': 'Remove component',
            'buttons': [
            {
                'text': 'Remove',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'repo/source/distribution',
                        // Action:
                        'remove-section',
                        // Data:
                        {
                            id: id,
                            distributionId: distributionId,
                            sectionId: sectionId,
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

/**
 *  Event: add gpg key to distribution
 */
$(document).on('submit','.source-repo-edit-distribution-add-gpgkey-form',function () {
    event.preventDefault();

    var id = $(this).attr('source-id');
    var distributionId = $(this).attr('distribution-id');
    var gpgKeyUrl = $(this).find('input[type="text"][name="gpgkey-url"]').val();
    var gpgKeyFingerprint = $(this).find('input[type="text"][name="gpgkey-fingerprint"]').val();
    var gpgKeyPlainText = $(this).find('textarea[name="gpgkey-plaintext"]').val();

    ajaxRequest(
        // Controller:
        'repo/source/distribution',
        // Action:
        'add-gpgkey',
        // Data:
        {
            id: id,
            distributionId: distributionId,
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
        mypanel.reload('repos/sources/edit-distribution', {id: id, distributionId: distributionId});
    });

    return false;
});

/**
 *  Event: remove gpg key from distribution
 */
$(document).on('click','.source-repo-edit-distribution-remove-gpgkey-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var id = $(this).attr('source-id');
    var distributionId = $(this).attr('distribution-id');
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
                        'repo/source/distribution',
                        // Action:
                        'remove-gpgkey',
                        // Data:
                        {
                            id: id,
                            distributionId: distributionId,
                            gpgkeyId: gpgkeyId,
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
