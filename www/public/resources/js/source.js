/**
 *  Rechargement de la div des sources
 */
function reloadSourcesDiv()
{
    $("#sourcesDiv").load(" #sourcesDiv > *",function () {
        $('.param-slide').css({
            right: 0
        })
    });
}

/**
 *  Events listeners
 */
/**
 *  Event : affichage du div permettant de gérer les sources
 */
$(document).on('click','#source-repo-toggle-btn',function () {
    openSlide("#sourcesDiv");
});

/**
 *  Event : masquage du div permettant de gérer les sources
 */
$(document).on('click','#source-repo-close-btn',function () {
    closeSlide("#sourcesDiv");
});

/**
 *  Event : ajouter une source
 */
$(document).on('submit','#addSourceForm',function () {
    event.preventDefault();

    var repoType = '';
    var gpgKeyURL = '';
    var gpgKeyText = '';

    /**
     *  Récupération du type de repo source
     */
    var repoType = $('input[name=addSourceRepoType]:checked').val();

    /**
     *  Récupération du nom de la source à ajouter
     */
    var name = $('input[name=addSourceName]').val();

    /**
     *  Récupération de l'url
     */
    var url = $('input[name=addSourceUrl]').val();

    /**
     *  Clé GPG
     */
    var gpgKeyURL = $('input[name=gpgKeyURL]').val();
    var gpgKeyText = $('#gpgKeyText').val();

    addSource(repoType, name, url, gpgKeyURL, gpgKeyText);

    return false;
});

/**
 *  Event : Renommage d'une source
 */
$(document).on('keypress','.source-input-name',function () {
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if (keycode == '13') {
        /**
         *  Récupération du nom actuel et du nouveau nom
         */
        var type = $(this).attr('source-type');
        var name = $(this).attr('source-name');
        var newname = $(this).val();

        renameSource(type, name, newname);
    }
    event.stopPropagation();
});

/**
 *  Event : Modification d'une url source
 */
$(document).on('keypress','.source-input-url',function () {
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if (keycode == '13') {
        var type = $(this).attr('source-type');
        var name = $(this).attr('source-name');
        var url = $(this).val();

        editSourceUrl(type, name, url);
    }
    event.stopPropagation();
});

/**
 *  Event: Show/hide source repo gpg key
 */
$(document).on('click','.source-repo-edit-param-btn',function () {
    var sourceId = $(this).attr('source-id');

    $('.source-repo-param-div[source-id='+sourceId+']').slideToggle();
});

/**
 *  Event: Edit source repo gpg key
 */
$(document).on('keypress','.source-repo-gpgkey-input',function () {
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if (keycode == '13') {
        var sourceId = $(this).attr('source-id');
        var gpgkey = $(this).val();

        editSourceGpgKey(sourceId, gpgkey);
    }
    event.stopPropagation();
});

/**
 *  Event: Edit source repo SSL certificate file
 */
$(document).on('keypress','.source-repo-crt-input',function () {
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if (keycode == '13') {
        var sourceId = $(this).attr('source-id');
        var sslCertificatePath = $(this).val();

        editSourceSslCertificatePath(sourceId, sslCertificatePath);
    }
    event.stopPropagation();
});

/**
 *  Event: Edit source repo SSL private key file
 */
$(document).on('keypress','.source-repo-key-input',function () {
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if (keycode == '13') {
        var sourceId = $(this).attr('source-id');
        var sslPrivateKeyPath = $(this).val();

        editSourceSslPrivateKeyPath(sourceId, sslPrivateKeyPath);
    }
    event.stopPropagation();
});

/**
 *  Event : Suppression d'une source
 */
$(document).on('click','.source-repo-delete-btn',function () {
    var sourceId = $(this).attr('source-id');
    var name = $(this).attr('source-name');

    deleteConfirm('Are you sure you want to delete <b>' + name + '</b> source repo?', function () {
        deleteSource(sourceId)
    });
});

/**
 *  Event : suppression d'une clé GPG
 */
$(document).on('click','.gpgKeyDeleteBtn',function () {
    var gpgKeyId = $(this).attr('gpgkey-id');
    var gpgkeyName = $(this).attr('gpgkey-name');

    deleteConfirm('Are you sure you want to delete <b>' + gpgkeyName + '</b> GPG key?', function () {
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
 * Ajax : Ajouter une nouvelle source
 * @param {string} name
 */
function addSource(repoType, name, url, gpgKeyURL, gpgKeyText)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "source",
            action: "addSource",
            repoType: repoType,
            name: name,
            url: url,
            gpgKeyURL: gpgKeyURL,
            gpgKeyText: gpgKeyText
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
           /**
            *  Affichage d'une alerte success et rechargement des sources
            */
            printAlert(jsonValue.message, 'success');
            reloadSourcesDiv();
            reloadNewRepoDiv();
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax : Supprimer une source
 * @param {string} sourceId
 */
function deleteSource(sourceId)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "source",
            action: "deleteSource",
            sourceId: sourceId
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
           /**
            *  Affichage d'une alerte success et rechargement des sources
            */
            printAlert(jsonValue.message, 'success');
            reloadSourcesDiv();
            reloadNewRepoDiv();
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax : Renommer une source
 * @param {string} name
 */
function renameSource(type, name, newname)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "source",
            action: "renameSource",
            type: type,
            name: name,
            newname: newname
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
           /**
            *  Affichage d'une alerte success
            */
            printAlert(jsonValue.message, 'success');
            reloadSourcesDiv();
            reloadNewRepoDiv();
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax : Modifier l'url d'un repo source (repo source de type deb uniquement)
 * @param {string} type
 * @param {string} name
 * @param {string} url
 */
function editSourceUrl(type, name, url)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "source",
            action: "editSourceUrl",
            type: type,
            name: name,
            url: url
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
           /**
            *  Affichage d'une alerte success
            */
            printAlert(jsonValue.message, 'success');
            reloadNewRepoDiv();
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax : Supprimer une clé GPG
 * @param {string} gpgkey
 */
function deleteGpgKey(gpgKeyId)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "source",
            action: "deleteGpgKey",
            gpgKeyId: gpgKeyId
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
           /**
            *  Affichage d'une alerte success
            */
            printAlert(jsonValue.message, 'success');
            reloadSourcesDiv();
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Edit source repo gpg key url
 * @param {string} sourceId
 * @param {string} gpgkey
 */
function editSourceGpgKey(sourceId, gpgkey)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "source",
            action: "editGpgKey",
            sourceId: sourceId,
            gpgkey: gpgkey
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Edit source repo SSL certificate path
 * @param {string} sourceId
 * @param {string} sslCertificatePath
 */
function editSourceSslCertificatePath(sourceId, sslCertificatePath)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "source",
            action: "editSslCertificatePath",
            sourceId: sourceId,
            sslCertificatePath: sslCertificatePath
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Edit source repo SSL certificate path
 * @param {string} sourceId
 * @param {string} sslPrivateKeyPath
 */
function editSourceSslPrivateKeyPath(sourceId, sslPrivateKeyPath)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "source",
            action: "editSslPrivateKeyPath",
            sourceId: sourceId,
            sslPrivateKeyPath: sslPrivateKeyPath
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
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
        url: "ajax/controller.php",
        data: {
            controller: "source",
            action: "importGpgKey",
            gpgkey: gpgkey
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
           /**
            *  Affichage d'une alerte success
            */
            printAlert(jsonValue.message, 'success');
            reloadSourcesDiv();
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}
