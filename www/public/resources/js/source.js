/**
 *  Events listeners
 */

/**
 *  Event: Add source repo
 */
$(document).on('submit','#addSourceForm',function () {
    event.preventDefault();

    /**
     *  Retrieve source repo type
     */
    var repoType = $('input[name=addSourceRepoType]:checked').val();

    /**
     *  Retrieve source repo name
     */
    var name = $('input[name=addSourceName]').val();

    /**
     *  Retrieve source repo url
     */
    var url = $('input[name=addSourceUrl]').val();

    /**
     *  Retrieve source repo gpg key url or text
     */
    var gpgKeyURL = $('input[name=gpgKeyURL]').val();
    var gpgKeyText = $('#gpgKeyText').val();

    newSource(repoType, name, url, gpgKeyURL, gpgKeyText);

    return false;
});

/**
 *  Event: Edit source repo
 */
$(document).on('submit','.source-form',function () {
    event.preventDefault();

    var id = $(this).attr('source-id');
    var name = $(this).find('.source-input-name').val();
    var url = $(this).find('.source-input-url').val();
    var gpgkey = $(this).find('.source-gpgkey-input').val();
    var sslCertificatePath = $(this).find('.source-ssl-crt-input').val();
    var sslPrivateKeyPath = $(this).find('.source-ssl-key-input').val();
    var sslCaCertificatePath = $(this).find('.source-ssl-cacrt-input').val();

    editSource(id, name, url, gpgkey, sslCertificatePath, sslPrivateKeyPath, sslCaCertificatePath);

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

    confirmBox('Are you sure you want to delete <b>' + name + '</b> source repo?', function () {
        deleteSource(sourceId)
    });
});

/**
 *  Event: delete a GPG key
 */
$(document).on('click','.gpgKeyDeleteBtn',function () {
    var gpgKeyId = $(this).attr('gpgkey-id');
    var gpgkeyName = $(this).attr('gpgkey-name');

    confirmBox('Are you sure you want to delete <b>' + gpgkeyName + '</b> GPG key?', function () {
        deleteGpgKey(gpgKeyId)
    });
});

/**
 *  Event: Import a new GPG key
 */
$(document).on('submit','#source-repo-add-key-form',function () {
    event.preventDefault();

    var gpgkey = $(this).find('#source-repo-add-key-textarea').val();

    importGpgKey(gpgkey);

    return false;
});

/**
 * Ajax: Add source repo
 * @param {string} name
 */
function newSource(repoType, name, url, gpgKeyURL, gpgKeyText)
{
    ajaxRequest(
        // Controller:
        'source',
        // Action:
        'new',
        // Data:
        {
            repoType: repoType,
            name: name,
            url: url,
            gpgKeyURL: gpgKeyURL,
            gpgKeyText: gpgKeyText
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload containers:
        [],
        // Execute functions on success:
        [
            "reloadPanel('repos/sources')",
            "reloadNewRepoDiv()"
        ]
    );
}

/**
 * Ajax: Edit source repo
 * @param {*} id
 * @param {*} name
 * @param {*} url
 * @param {*} gpgkey
 * @param {*} sslCertificatePath
 * @param {*} sslPrivateKeyPath
 * @param {*} sslCaCertificatePath
 */
function editSource(id, name, url, gpgkey, sslCertificatePath, sslPrivateKeyPath, sslCaCertificatePath)
{
    ajaxRequest(
        // Controller:
        'source',
        // Action:
        'edit',
        // Data:
        {
            id: id,
            name: name,
            url: url,
            gpgkey: gpgkey,
            sslCertificatePath: sslCertificatePath,
            sslPrivateKeyPath: sslPrivateKeyPath,
            sslCaCertificatePath: sslCaCertificatePath
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload containers:
        [],
        // Execute functions on success:
        [
            "reloadPanel('repos/sources')",
            "reloadNewRepoDiv()"
        ]
    );
}

/**
 * Ajax: Delete source repo
 * @param {string} sourceId
 */
function deleteSource(sourceId)
{
    ajaxRequest(
        // Controller:
        'source',
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
            "reloadPanel('repos/sources')",
            "reloadNewRepoDiv()"
        ]
    );
}

/**
 * Ajax: Delete a gpg key
 * @param {string} gpgkey
 */
function deleteGpgKey(gpgKeyId)
{
    ajaxRequest(
        // Controller:
        'source',
        // Action:
        'deleteGpgKey',
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
            "reloadPanel('repos/sources')"
        ]
    );
}

/**
 * Ajax: Import a new gpg key
 * @param {string} gpgkey
 */
function importGpgKey(gpgkey)
{
    ajaxRequest(
        // Controller:
        'source',
        // Action:
        'importGpgKey',
        // Data:
        {
            gpgkey: gpgkey
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload containers:
        [],
        // Execute functions on success:
        [
            "reloadPanel('repos/sources')"
        ]
    );
}
