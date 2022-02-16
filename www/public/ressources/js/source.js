/**
 *  Rechargement de la div des sources
 */
function reloadSourcesDiv(){
    $("#sourcesDiv").load(" #sourcesDiv > *");
}

/**
 *  Events listeners
 */
/**
 *  Event : affichage du div permettant de gérer les sources
 */
 $(document).on('click','#ReposSourcesToggleButton',function(){
    $("#sourcesDiv").slideToggle();
});

/**
 *  Event : masquage du div permettant de gérer les sources
 */
$(document).on('click','#reposSourcesDivCloseButton',function(){
    $("#sourcesDiv").slideToggle();
});

/**
 *  Event : afficher des inputs supplémentaires pour importer une clé GPG (CentOS)
 */
$(document).on('change','#newRepoGpgSelect',function(){
    if ($("#newRepoGpgSelect_yes").is(":selected")) {
        $(".sourceGpgDiv").show();
    } else {
        $(".sourceGpgDiv").hide();
    }
}).trigger('change');

/**
 *  Event : ajouter une source
 */
$(document).on('submit','#sourceAddForm',function(){
    event.preventDefault();

    var urlType = '';
    var existingGpgKey = '';
    var gpgKeyURL = '';
    var gpgKeyText = '';

    // Récupération du nom de la source à ajouter
    var name = $('input[name=addSourceName]').val();

    /**
     *  Redhat / CentOS uniquement :
     *  On récupère le type d'url
     */
    if ($('#addSourceUrlType').length == 1) {
        var urlType = $('#addSourceUrlType').val();
    }
    
    // Récupération de l'url
    var url = $('input[name=addSourceUrl]').val();

    /**
     *  Clé GPG
     */
    /**
     *  Redhat - CentOS 
     *  On récupère le type de clé GPG (fichier, ASCII, URL)
     */
    // Si #newRepoGpgSelect existe alors on est forcément sur centos
    if ($('#newRepoGpgSelect').length == 1) {
        if ($("#newRepoGpgSelect_yes").is(":selected")) {
            var existingGpgKey = $('select[name=existingGpgKey]').val();
            var gpgKeyURL = $('input[name=gpgKeyURL]').val();
            var gpgKeyText = $('#gpgKeyText').val();
        }
    } else {
        /**
         *  Debian
         *  La clé GPG est renseignée au format ASCII
         */
        var gpgKeyText = $('#gpgKeyText').val();
    }

    addSource(name, urlType, url, existingGpgKey, gpgKeyURL, gpgKeyText);

    return false;
});


/**
 *  Event : Renommage d'une source
 */
$(document).on('keypress','.sourceFormInput',function(){
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if(keycode == '13'){
        /**
         *  Récupération du nom actuel et du nouveau nom
         */
        var name = $(this).attr('sourcename');
        var newname = $(this).val();

        renameSource(name, newname);
    }
    event.stopPropagation();
});

/**
 *  Event : Modification d'une url source (Debian uniquement)
 */
$(document).on('keypress','.sourceFormUrlInput',function(){
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if(keycode == '13'){
        /**
         *  Récupération du nom de la source dont on souhaite modifier l'url
         */
        var name = $(this).attr('sourcename');

        /**
         *  Récupère l'url du repo source si existe (Debian uniquement)
         */
        var url = $('input[sourcename='+name+'].sourceFormUrlInput').val();

        editSourceUrl(name, url);
    }
    event.stopPropagation();
});

/**
 *  Event : modification de la configuration d'un repo source (Redhat uniquement)
 */
$(document).on('submit','.sourceConfForm',function(){
    event.preventDefault();

    var name = $(this).attr('sourcename');
    var options_array = [];

    /**
     *  D'abord on compte le nombre d'input de class 'sourceConfForm-optionName' dans ce formulaire
     */
    var countTotal = $(this).find('input[name=option-name]').length

    /**
     *  Chaque paramètre de configuration et leur valeur associée possèdent un id
     *  On récupère le nom du paramètre et sa valeur associée ayant le même id et on push le tout dans un tableau
     */
    if (countTotal > 0) {
        for (let i = 0; i < countTotal; i++) {

            var option_name = $(this).find('input[name=option-name][option-id='+i+']').val();
            /**
             *  Si la valeur 'option_value' est un bouton checkbox alors on regarde si il est 'checked' ou non.
             *  Si oui alors on récupére la valeur correspondante dans l'input
             *  Si non alors on laisse une valeur vide
             */
            if ($(this).find('input[name=option-value][option-id='+i+']').attr('type') == 'checkbox') {
                /**
                 *  C'est une checkbox, on vérifie si elle est 'checked'
                 */
                if ($(this).find('input[name=option-value][option-id='+i+'][type=checkbox]').is(':checked')) {
                    /**
                     *  Si elle est 'checked' alors on récupère la valeur indiquée dans son input
                     */
                    var option_value = $(this).find('input[name=option-value][option-id='+i+'][type=checkbox]').val();

                } else {
                    /**
                     *  Si elle n'est pas 'checked' alors on set une valeur vide
                     */
                    var option_value = '';
                }
            
            } else {
                /**
                 *  Ce n'est pas une checkbox, on récupère directement la valeur dans l'input
                 */
                 var option_value = $(this).find('input[name=option-value][option-id='+i+']').val()
            }

            /**
             *  On pousse un nouvel array contenant l'id, le nom du paramètre et sa valeur dans l'array options_array
             */
            options_array.push(
                {
                    name: option_name,
                    value: option_value
                }
            );
        }
    }
    /**
     *  Récupération des éventuels commentaires laissés dans le textarea
     */
    var comments = $(this).find('textarea[name=comments]').val();

    configureSource(name, options_array, comments);

    return false;
});

/**
 *  Event : Suppression d'une source
 */
$(document).on('click','.sourceDeleteToggleBtn',function(){
    var name = $(this).attr('sourcename');
    deleteConfirm('Êtes vous sûr de vouloir supprimer le repo source <b>'+name+'</b> ?', function(){deleteSource(name)});
});

/**
 * Event : Afficher la configuration d'une source
 * @param {*} name 
 */
$(document).on('click','.sourceConfigurationBtn',function(){
    var name = $(this).attr('sourcename');
    $('#sourceConfigurationDiv-'+name).slideToggle(150);
});

/**
 *  Event : suppression d'une clé GPG
 */
$(document).on('click','.gpgKeyDeleteBtn',function(){
    var gpgkey = $(this).attr('gpgkey');
    deleteConfirm('Êtes-vous sûr de vouloir supprimer la clé GPG <b>'+gpgkey+'</b> ?', function(){deleteGpgKey(gpgkey)});
});
 

/**
 * Ajax : Ajouter une nouvelle source
 * @param {string} name 
 */
 function addSource(name, urlType, url, existingGpgKey, gpgKeyURL, gpgKeyText) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "addSource",
            name: name,
            urlType: urlType,
            url: url,
            existingGpgKey: existingGpgKey,
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
 * @param {string} name 
 */
 function deleteSource(name) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "deleteSource",
            name: name
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
 function renameSource(name, newname) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "renameSource",
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
 * Ajax : Modifier l'url d'un repo source (Debian uniquement)
 * @param {string} name
 * @param {string} url
 */
 function editSourceUrl(name, url) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "editSourceUrl",
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
 * Ajax : Modifier la configuration d'un repo source (Redhat/CentOS seulement)
 * @param {string} name
 * @param {array} options_array
 * @param {string} comments
 */
function configureSource(name, options_array, comments) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "configureSource",
            name: name,
            options_array: options_array,
            comments: comments
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
 function deleteGpgKey(gpgkey) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "deleteGpgKey",
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


