/**
 *  Event: show source repo distribution params
 */
$(document).on('click','.source-repo-distribution-edit-btn',function () {
    mypanel.get('repos/sources/edit-distribution', {
        id: $(this).attr('source-id'),
        distributionId: $(this).attr('distribution-id')
    });
});

/**
 *  Event: add source repository distribution
 */
$(document).on('click','button.source-repo-add-distribution-btn',function () {
    const id = $(this).attr('source-id');
    const name = $('input.source-repo-add-distribution-input[source-id="' + id + '"]').val();

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
$(document).on('submit','form.source-repo-edit-distribution',function (e) {
    e.preventDefault();

    const id = $(this).attr('source-id');
    const distributionId = $(this).attr('distribution-id');
    var params = {};

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

    const id = $(this).attr('source-id');
    const distributionId = $(this).attr('distribution-id');

    myconfirmbox.print(
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
 *  Event: add gpg key to distribution
 */
$(document).on('submit','form.source-repo-distribution-add-gpgkey',function (e) {
    e.preventDefault();

    const id = $(this).attr('source-id');
    const distributionId = $(this).attr('distribution-id');
    const gpgKeyUrl = $(this).find('input[type="text"][name="gpgkey-url"]').val();
    const gpgKeyFingerprint = $(this).find('input[type="text"][name="gpgkey-fingerprint"]').val();
    const gpgKeyPlainText = $(this).find('textarea[name="gpgkey-plaintext"]').val();

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
$(document).on('click','.source-repo-distribution-remove-gpgkey-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    const id = $(this).attr('source-id');
    const distributionId = $(this).attr('distribution-id');
    const gpgkeyId = $(this).attr('gpgkey-id');

    myconfirmbox.print(
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
