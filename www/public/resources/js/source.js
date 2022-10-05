$(document).ready(function () {
    /**
     *  Affichage des bons champs dans le formulaire de création de nouveau repo, en fonction du type de paquets qui est sélectionné
     */
    newSourceFormPrintRepoTypeFields();
});

/**
 *  Fonctions
 */

/**
 *  Afficher / masquer les champs de saisie en fonction du type de repo source sélectionné
 */
function newSourceFormPrintRepoTypeFields()
{
    var repoType = $('#addSourceForm').find('input:radio[name=addSourceRepoType]:checked').val();

    /**
     *  En fonction du type de repo sélectionné, affiche uniquement les champs en lien avec ce type de repo et masque les autres.
     */
    $('#addSourceForm').find('[field-type][field-type!='+repoType+']').hide();
    $('#addSourceForm').find('[field-type][field-type='+repoType+']').show();
}

/**
 *  Rechargement de la div des sources
 */
function reloadSourcesDiv()
{
    $("#sourcesDiv").load(" #sourcesDiv > *");
    setTimeout(function () {
        newSourceFormPrintRepoTypeFields();
    },100);
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
 *  Event : affiche/masque des inputs en fonction du type de repo sélectionné
 */
$(document).on('change','input:radio[name="addSourceRepoType"]',function () {
    newSourceFormPrintRepoTypeFields();
});

/**
 *  Event : afficher des inputs supplémentaires pour importer une clé GPG (CentOS)
 */
$(document).on('change','#newRepoGpgSelect',function () {
    if ($("#newRepoGpgSelect_yes").is(":selected")) {
        $(".sourceGpgDiv").show();
    } else {
        $(".sourceGpgDiv").hide();
    }
}).trigger('change');

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
    /**
     *  Rpm uniquement
     *  On récupère le type de clé GPG (fichier, ASCII, URL)
     */
    if (repoType == 'rpm') {
        if ($("#newRepoGpgSelect_yes").is(":selected")) {
            var gpgKeyURL = $('input[name=gpgKeyURL]').val();
            var gpgKeyText = $('#rpmGpgKeyText').val();
        }
    }
    if (repoType == 'deb') {
        /**
         *  Deb
         *  La clé GPG est renseignée au format ASCII
         */
        var gpgKeyText = $('#debGpgKeyText').val();
    }

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
$(document).on('click','.source-repo-edit-key-btn',function () {
    var sourceId = $(this).attr('source-id');

    $('.source-repo-key-tr[source-id='+sourceId+']').slideToggle();
});

/**
 *  Event: Edit source repo gpg key
 */
$(document).on('keypress','.source-repo-key-input',function () {
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if (keycode == '13') {
        var sourceId = $(this).attr('source-id');
        var gpgkey = $(this).val();

        editSourceGpgKey(sourceId, gpgkey);
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
        url: "controllers/sources/ajax.php",
        data: {
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
        url: "controllers/sources/ajax.php",
        data: {
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
        url: "controllers/sources/ajax.php",
        data: {
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
        url: "controllers/sources/ajax.php",
        data: {
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
        url: "controllers/sources/ajax.php",
        data: {
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
        url: "controllers/sources/ajax.php",
        data: {
            action: "editGpgKey",
            sourceId: sourceId,
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

/**
 * Ajax: Import a new gpg key
 * @param {string} gpgkey
 */
function importGpgKey(gpgkey)
{
    $.ajax({
        type: "POST",
        url: "controllers/sources/ajax.php",
        data: {
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
