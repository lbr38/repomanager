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
    var sslCertificatePath = $(this).find('.source-sslcrt-input').val();
    var sslPrivateKeyPath = $(this).find('.source-sslkey-input').val();

    editSource(id, name, url, gpgkey, sslCertificatePath, sslPrivateKeyPath);

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
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "source",
            action: "new",
            repoType: repoType,
            name: name,
            url: url,
            gpgKeyURL: gpgKeyURL,
            gpgKeyText: gpgKeyText
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadPanel('repos/sources');
            reloadNewRepoDiv();
        },
        error: function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Edit source repo
 * @param {*} id
 * @param {*} name
 * @param {*} url
 * @param {*} gpgkey
 * @param {*} sslCertificatePath
 * @param {*} sslPrivateKeyPath
 */
function editSource(id, name, url, gpgkey, sslCertificatePath, sslPrivateKeyPath)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "source",
            action: "edit",
            id: id,
            name: name,
            url: url,
            gpgkey: gpgkey,
            sslCertificatePath: sslCertificatePath,
            sslPrivateKeyPath: sslPrivateKeyPath
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadPanel('repos/sources');
            reloadNewRepoDiv();
        },
        error: function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Delete source repo
 * @param {string} sourceId
 */
function deleteSource(sourceId)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "source",
            action: "delete",
            sourceId: sourceId
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadPanel('repos/sources');
            reloadNewRepoDiv();
        },
        error: function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Delete a gpg key
 * @param {string} gpgkey
 */
function deleteGpgKey(gpgKeyId)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "source",
            action: "deleteGpgKey",
            gpgKeyId: gpgKeyId
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadPanel('repos/sources');
        },
        error: function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Import a new gpg key
 * @param {string} gpgkey
 */
function importGpgKey(gpgkey)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "source",
            action: "importGpgKey",
            gpgkey: gpgkey
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadPanel('repos/sources');
        },
        error: function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}
