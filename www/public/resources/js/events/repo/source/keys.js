/**
 *  Keys of the arch, alpine and openbsd source repositories (items are handled by events/repo/source/arch.js):
 *  GPG keys of the arch source repositories, RSA keys of the alpine ones and signify keys of the openbsd ones
 */

/**
 *  Event: import a public key in an alpine or openbsd source repository, from its URL or its content (plain text)
 */
$(document).on('click','button.source-repo-import-key-btn',function () {
    const id = $(this).attr('source-id');
    const block = $('.source-repo-import-key[source-id="' + id + '"]');

    ajaxRequest(
        // Controller:
        'repo/source/keys',
        // Action:
        'add-key',
        // Data:
        {
            id: id,
            url: block.find('input[name="key-url"]').val(),
            content: block.find('textarea[name="key-content"]').val(),
            name: block.find('input[name="key-name"]').val()
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
 *  Event: remove a public key from an alpine or openbsd source repository, after confirmation (as the GPG keys)
 */
$(document).on('click','.source-repo-remove-key-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    const id = $(this).attr('source-id');
    const name = $(this).attr('key-name');

    myconfirmbox.print(
        {
            'title': 'Remove key',
            'message': 'The signatures made with the ' + name + ' key can no longer be verified (the sync fails if the signature check is enabled)',
            'buttons': [
            {
                'text': 'Remove',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'repo/source/keys',
                        // Action:
                        'remove-key',
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
                }
            }]
        }
    );
});

/**
 *  Event: import a GPG key and link it to an arch source repository, from its URL, its fingerprint or plain text
 */
$(document).on('click','button.source-repo-import-gpgkey-btn',function () {
    const id = $(this).attr('source-id');
    const block = $('.source-repo-import-gpgkey[source-id="' + id + '"]');

    ajaxRequest(
        // Controller:
        'repo/source/keys',
        // Action:
        'add-gpgkey',
        // Data:
        {
            id: id,
            gpgKeyUrl: block.find('input[name="gpgkey-url"]').val(),
            gpgKeyFingerprint: block.find('input[name="gpgkey-fingerprint"]').val(),
            gpgKeyPlainText: block.find('textarea[name="gpgkey-plaintext"]').val()
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
 *  Event: remove a GPG key from an arch source repository
 */
$(document).on('click','.source-repo-remove-gpgkey-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    const id = $(this).attr('source-id');
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
                        'repo/source/keys',
                        // Action:
                        'remove-gpgkey',
                        // Data:
                        {
                            id: id,
                            gpgkeyId: gpgkeyId
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
 *  Event: import the key when pressing 'Enter' in a field of the import fields of an arch, alpine or openbsd source repository
 *  The default action is prevented so that the parent form (source repository form) is not submitted
 */
$(document).on('keydown', '.source-repo-import-gpgkey input, .source-repo-import-key input', function (e) {
    if (e.key !== 'Enter' || e.originalEvent.isComposing) {
        return;
    }

    e.preventDefault();
    e.stopPropagation();

    // Ignore key auto-repeat to avoid sending the same key multiple times
    if (e.originalEvent.repeat) {
        return;
    }

    $(this).closest('.source-repo-import-gpgkey, .source-repo-import-key').find('button').trigger('click');
});
