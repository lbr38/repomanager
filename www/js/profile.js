/**
 *  Chargement des menus Select2
 */
loadProfilesSelect2();

/**
 *  Fonctions utiles
 */
/**
 *  Chargement de tous les Select2 de la pages des profils
 */
function loadProfilesSelect2() {
    $('.reposSelectList').select2({
        closeOnSelect: false,
        placeholder: 'Ajouter un repo ✎'
    });
    $('.excludeMajorSelectList').select2({
        closeOnSelect: false,
        placeholder: 'Sélectionner un paquet ✎',
        tags: true
    });
    $('.excludeSelectList').select2({
        closeOnSelect: false,
        placeholder: 'Sélectionner un paquet ✎',
        tags: true
    });
    $('.needRestartSelectList').select2({
        closeOnSelect: false,
        placeholder: 'Sélectionner un service ✎',
        tags: true
    });
}

/**
 *  Rechargement de la div des profils
 *  Recharge les menus select2 en même temps
 */
 function reloadProfileDiv(){
    $("#profilesDiv").load(" #profilesDiv > *",function(){
        /**
         *  Rechargement de tous les menus Select2
         */
        loadProfilesSelect2();
    });
}

/**
 *  Events listeners
 */

/**
 *  Event : Création d'un nouveau profil
 */
$(document).on('submit','#newProfileForm',function(){
    event.preventDefault();
    /**
     *  Récupération du nom de profil à créer dans l'input prévu à cet effet
     */
    var name = $("#newProfileInput").val();
    newProfile(name);

    return false;
});

/**
 *  Event : Suppression d'un profil
 */
$(document).on('click','.deleteProfileBtn',function(){
     var name = $(this).attr('profilename');
    deleteConfirm('Êtes vous sûr de vouloir supprimer le profil <b>'+name+'</b> ?', function(){deleteProfile(name)});
});

/**
 *  Event : Renommage d'un profil
 */
$(document).on('submit','.profileForm',function(){
    event.preventDefault();
    /**
     *  Récupération du nom actuel (dans <form>) et du nouveau nom (dans <input> contenant l'attribut profilename="name")
     */
    var name = $(this).attr('profilename');
    var newname = $('input[profilename='+name+'].profileFormInput').val();
    renameProfile(name, newname);

    return false;
});

/**
 *  Event : duplication d'un profil
 */
$(document).on('click','.duplicateProfileBtn',function(){
    var name = $(this).attr('profilename');
   
    duplicateProfile(name);
});

/**
 *  Event : Afficher la configuration d'un profil
 */
$(document).on('click','.profileConfigurationBtn',function(){
    var name = $(this).attr('profilename');
    $("#profileConfigurationDiv-"+name).slideToggle(150);
});

/**
 *  Event : modifier la configuration d'un profil (repos, exclusions...)
 */
 $(document).on('submit','.profileConfigurationForm',function(){
    event.preventDefault();
    /**
     *  Récupération du nom du groupe (dans <form>)
     *  de la liste des repos (dans le <select>)
     *  de la liste des exclusions
     *  des paramètres de mise à jour
     *  etc...
     */
    var name = $(this).attr('profilename');

    var reposList = $('select[profilename='+name+'].reposSelectList').val();
    var packagesMajorExcluded = $('select[profilename='+name+'].excludeMajorSelectList').val();
    var packagesExcluded = $('select[profilename='+name+'].excludeSelectList').val();
    var serviceNeedRestart = $('select[profilename='+name+'].needRestartSelectList').val();

    if ($('#profileConf_keepCron[profilename='+name+']').is(':checked')) {
        var keepCron = 'yes';
    } else {
        var keepCron = 'no';
    }
    if ($('#profileConf_allowOverwrite[profilename='+name+']').is(':checked')) {
        var allowOverwrite = 'yes';
    } else {
        var allowOverwrite = 'no';
    }
    if ($('#profileConf_allowReposFilesOverwrite[profilename='+name+']').is(':checked')) {
        var allowReposFilesOverwrite = 'yes';
    } else {
        var allowReposFilesOverwrite = 'no';
    }

    configureProfile(name, reposList, packagesMajorExcluded, packagesExcluded, serviceNeedRestart, keepCron, allowOverwrite, allowReposFilesOverwrite);

    return false;
});


/**
 * Ajax: Créer un nouveau profil
 * @param {string} name 
 */
 function newProfile(name) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "newProfile",
            name: name
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement des profils
             */
            printAlert(jsonValue.message, 'success');
            reloadProfileDiv();
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax : Supprimer un profil
 * @param {string} name 
 */
 function deleteProfile(name) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "deleteProfile",
            name: name
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement des profils
             */
            printAlert(jsonValue.message, 'success');
            reloadProfileDiv();
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });   
}

/**
 * Ajax: Renommer un profil
 * @param {string} name 
 */
 function renameProfile(name, newname) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "renameProfile",
            name: name,
            newname : newname
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement des profils
             */
            printAlert(jsonValue.message, 'success');
            reloadProfileDiv();
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });   
}

/**
 * Ajax: Renommer un profil
 * @param {string} name 
 */
 function renameProfile(name, newname) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "renameProfile",
            name: name,
            newname : newname
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement des profils
             */
            printAlert(jsonValue.message, 'success');
            reloadProfileDiv();
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });   
}

/**
 * Ajax: Dupliquer un profil
 * @param {string} name 
 */
 function duplicateProfile(name) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "duplicateProfile",
            name: name
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement des profils
             */
            printAlert(jsonValue.message, 'success');
            reloadProfileDiv();
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });   
}

/**
 * Ajax: Modifier la configuration d'un profil
 */
 function configureProfile(name, reposList, packagesMajorExcluded, packagesExcluded, serviceNeedRestart, keepCron, allowOverwrite, allowReposFilesOverwrite) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "configureProfile",
            name: name,
            reposList: reposList,
            packagesMajorExcluded: packagesMajorExcluded,
            packagesExcluded: packagesExcluded,
            serviceNeedRestart: serviceNeedRestart,
            keepCron: keepCron,
            allowOverwrite: allowOverwrite,
            allowReposFilesOverwrite: allowReposFilesOverwrite
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement des groupes et de la liste des repos
             */
            printAlert(jsonValue.message, 'success');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });   
}