/**
 *  Fonctions
 */

/**
 *  Fonction permettant de compter le nb de checkbox cochée
 */
function countChecked() {
    var countTotal = $('.reposList').find('input[name=checkbox-repo\\[\\]]:checked').length;
    return countTotal;
};

/**
 *  Events listeners
 */

/**
 *  Event : affichage du div permettant de créer un nouveau repo/section
 */
$(document).on('click','#newRepoToggleButton',function(){
    $("#newRepoDiv").slideToggle();
});

/**
 *  Event : masquage du div permettant de créer un nouveau repo/section
 */
$(document).on('click','#newRepoCloseButton',function(){
    $("#newRepoDiv").slideToggle();
});

/**
 *  Event : masquage du div permettant d'exécuter un opération
 */
$(document).on('click','#operationsDivCloseButton',function(){
    /**
     *  Suppression du contenu de la div
     */
    $("#op-forms-container").html('');

    $("#operationsDiv").slideToggle();
});

/**
 *  Event : afficher/masquer le contenu de tous les groupes de repos actifs
 */
$(document).on('click','#hideActiveReposGroups',function(){
    $('.repos-list-group-flex-div[status=active]').slideToggle();
});

/**
 *  Event : afficher/masquer le contenu de tous les groupes de repos archivés
 */
$(document).on('click','#hideArchivedReposGroups',function(){
    $('.repos-list-group-flex-div[status=archived]').slideToggle();
});

/**
 *  Event : afficher/masquer le contenu d'un groupe de repos
 */
$(document).on('click','.hideGroup',function(){
    var groupname = $(this).attr('group');
    $('.repos-list-group[group='+groupname+']').find('.repos-list-group-flex-div').slideToggle();
});

/**
 *  Event : affiche/masque des inputs en fonction du type de repo à créer ('miroir' ou 'local')
 */
$(document).on('change','input:radio[name="repoType"]',function(){
    if ($("#repoType_mirror").is(":checked")) {
        $(".type_mirror_input").show();
        $(".type_local_input").hide();
    } else {
        $(".type_mirror_input").hide();
        $(".type_local_input").show();
    }
});

/**
 *  Event : modification de la description d'un repo
 */
$(document).on('keypress','.repoDescriptionInput',function(){
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if(keycode == '13'){
        /**
         *  Récupération des valeurs suivantes :
         *   - L'Id du repo à modifier
         *   - Le status su repo
         *   - La description 
         */
        var id = $(this).attr('repo-id');
        var status = $(this).attr('repo-status');
        var description = $(this).val();

        setRepoDescription(id, status, description);            
    }
    event.stopPropagation();
});

/**
 *  Event : lorsqu'une checkbox est cochée/décochée
 */
$(document).on('click',"input[name=checkbox-repo\\[\\]]",function(){
    /**
     *  On compte le nombre de checkbox cochées
     */
    var count_checked = countChecked();

    /**
     *  Si toutes les checkbox ont été décochées alors on masque tous les boutons d'actions, sinon on les affiche
     *  On retire également le style appliqué par jquery sur les checkbox lorsqu'elles sont cochées
     */
    if (count_checked == 0) {
        $('#repo-actions-btn-container').hide();
        $('.reposList').find('input[name=checkbox-repo\\[\\]]').removeAttr('style');
        return;
    } else {
        $('#repo-actions-btn-container').show();
    }
 
    /**
     *  On récupère le type du repo (actif ou archivé)
     */
    var repo_status = $(this).attr('repo-status');

    /**
     *  A partir du moment où il y a au moins 1 checkbox cochée, on affiche toutes les autres
     *  Toutes les checkbox cochées sont passées en opacity = 1
     */
    $('.reposList').find("input[repo-status="+repo_status+"]").css("visibility", "visible");
    $('.reposList').find('input[name=checkbox-repo\\[\\]]:checked').css("opacity", "1");

    /**
     *  Par contre on masque les checkbox correspondant à l'autre status
     *  Ex: si on coche du 'active' alors on décoche les 'archived'
     */
    if (repo_status == 'active') {
        $('.reposList').find("input[repo-status=archived]").prop("checked", false);
    }
    if (repo_status == 'archived') {
        $('.reposList').find("input[repo-status=active]").prop("checked", false);
    }

    /**
     *  Masquage de boutons en fonction du status de repo coché
     */
     if (repo_status == 'archived') {
         $('.repo-action-btn[type=active-btn]').hide();
         $('.repo-action-btn[type=archived-btn]').show();
    }
    if (repo_status == 'active') {
        $('.repo-action-btn[type=archived-btn]').hide();
        $('.repo-action-btn[type=active-btn]').show();

        /**
         *  Si un repo 'non-updatable' est coché alors on masque le bouton 'mettre à jour'
         */
        if ($('.reposList').find('input[name=checkbox-repo\\[\\]][is-updatable=no]:checked').length > 0) {
            $('.repo-action-btn[action=update]').hide();
        } else {
            $('.repo-action-btn[action=update]').show();
        }
    }
});

/**
 *  Event : Lorsqu'on clique sur un bouton d'action
 */
$(document).on('click',".repo-action-btn",function(){
    var repos_array = [];

    /**
     *  Masquage des boutons d'opérations
     */
    $('#repo-actions-btn-container').hide();

    /**
     *  Récupération de l'action sélectionnée
     */
    var action = $(this).attr('action');

    /**
     *  On parcourt toutes les checkbox sélectionnés et on récupère les id de repo correspondant
     */
    $('.reposList').find('input[name=checkbox-repo\\[\\]]:checked').each(function(){
        var obj = {};

        /**
         *  Récupération de l'id et du status du ou des repos sélectionnés
         */
        obj['repoId'] = $(this).attr('repo-id');
        obj['repoStatus'] = $(this).attr('repo-status');

        repos_array.push(obj);
    });

    /**
     *  Exécution de l'opération sélectionnée
     */
    var repos_array = JSON.stringify(repos_array);

    /**
     *  Rechargement de operationsDiv, affichage et demande du formulaire correspondant à l'opération sélectionnée
     */
    $("#operationsDiv").load(" #operationsDiv > *",function(){
        getForm(action, repos_array);
        $('#operationsDiv').show();
    });

    /**
     *  Scroll vers le haut de la page
     */
    $('html, body').animate({ scrollTop: 0 }, 'fast');
});

/**
 *  Event : validation / exécution d'une opération
 */
$(document).on('submit','.operation-form-container',function(){
    event.preventDefault();

    /**
     *  Array principal qui contiendra tous les paramètres de chaque repo à traiter (1 ou plusieurs repos selon la sélection de l'utilisateur)
     */
    var operation_params = [];
    
    /**
     *  Récupération des paramètres saisis dans le formulaire
     */
    $(this).find('.operation-form').each(function(){
        var obj = {};

        /**
         *  Objet qui contiendra les paramètres saisis dans le formulaire pour ce repo
         */
        obj['action'] = $(this).attr('action');
        obj['repoId'] = $(this).attr('repo-id');
        obj['repoStatus'] = $(this).attr('repo-status');

        /**
         *  Puis on récupère chaque paramètres saisis par l'utilisateur et on les poussent à la suite
         *  Il n'existe pas de tableau associatif en js donc on pousse un objet
         */
        $(this).find('.operation_param').each(function(){
            /**
             *  Récupération du nom du paramètre (name de l'input) et sa valeur (saisie de l'input)
             */
            var param_name = $(this).attr('param-name');
            /**
             *  Si l'input est une checkbox et qu'elle est cochée alors sa valeur sera 'yes'
             *  Si elle n'est pas cochée alors sa valeur sera 'no'
             */
            if ($(this).attr('type') == 'checkbox') {
                if ($(this).is(":checked")) {
                    var param_value = 'yes';
                } else {
                    var param_value = 'no';
                }
            /**
             *  Si l'input est un bouton radio alors on récupère sa valeur uniquement si elle est cochée, sinon on passe au paramètre suivant
             */
            } else if ($(this).attr('type') == 'radio') {
                if ($(this).is(":checked")) {
                    var param_value = $(this).val();
                } else {
                    return; // return est l'équivalent de 'continue' pour les loop jquery .each()
                }
            } else {
                /**
                 *  Si l'input n'est pas une checkbox on récupère sa valeur
                 */
                var param_value = $(this).val();
            }

            obj[param_name] = param_value;
        });

        /**
         *  On pousse chaque paramètres de repo dans l'array principal
         */
        operation_params.push(obj)
    });

    /**
     *  On envoi l'array principal au format JSON à php pour vérification des paramètres
     */
    var operation_params_json  = JSON.stringify(operation_params);

    validateExecuteForm(operation_params_json);

    return false;
});

/**
 *  Event : génération de la configuration du repo à installer sur la machine cliente
 */
$(document).on('click','.client-configuration-btn',function(){
    /**
     *  Suppression de tout autre éventuel div déjà affiché
     */
    $(".divReposConf").remove();

    /**
     *  Récupération des infos du repo
     */
    var os_family = $(this).attr('os_family');
    var repoName = $(this).attr('repo');
    var repoEnv = $(this).attr('env');
    /**
     *  Sur Debian on récupère également la distribution et la section
     */
    if (os_family == "Debian") {
        var repoDist = $(this).attr('dist');
        var repoSection = $(this).attr('section');

        /**
         *  Si le nom de la distribution contient un slash, on le remplace
         */
        var repoDistFormatted = repoDist.replace('/', '--slash--');
    }
    var repo_dir_url = $(this).attr('repo_dir_url');
    var repo_conf_files_prefix = $(this).attr('repo_conf_files_prefix');
    var www_hostname = $(this).attr('www_hostname');

    if (os_family == "Redhat") {
        var commands = 'echo -e "# Repo '+repoName+' ('+repoEnv+') sur '+www_hostname+'\n['+repo_conf_files_prefix+''+repoName+'_'+repoEnv+']\nname=Repo '+repoName+' sur '+www_hostname+'\ncomment=Repo '+repoName+' sur '+www_hostname+'\nbaseurl='+repo_dir_url+'/'+repoName+'_'+repoEnv+'\nenabled=1\ngpgkey='+repo_dir_url+'/gpgkeys/'+www_hostname+'.pub\ngpgcheck=1" > /etc/yum.repos.d/'+repo_conf_files_prefix+''+repoName+'.repo';
    }
    if (os_family == "Debian") {
        var commands = 'wget -qO '+repo_dir_url+'/gpgkeys/'+www_hostname+'.pub | sudo apt-key add -\n\necho "deb '+repo_dir_url+'/'+repoName+'/'+repoDist+'/'+repoSection+'_'+repoEnv+' '+repoDist+' '+repoSection+'" > /etc/apt/sources.list.d/'+repo_conf_files_prefix+''+repoName+'_'+repoDistFormatted+'_'+repoSection+'.list';
    }
    
    /**
     *  Génération du div
     */
    $('body').append('<div class="divReposConf hide"><span><img title="Fermer" class="divReposConf-close icon-lowopacity" src="ressources/icons/close.png" /></span><h3>INSTALLATION</h3><h5>Installer ce repo sur une machine cliente</h5><div id="divReposConfCommands-container"><pre id="divReposConfCommands">'+commands+'</pre><img src="ressources/icons/duplicate.png" class="icon-lowopacity" title="Copier" onclick="copyToClipboard(divReposConfCommands)" /></div></div>');

    /**
     *  Affichage
     */
    $('.divReposConf').show();
});

/**
 *  Event : fermeture de la configuration client
 */
$(document).on('click','.divReposConf-close',function(){
    $(".divReposConf").remove();
});

/**
 *  Event : modifier les paramètres d'affichage de la liste des repos
 */
$(document).on('click','#repos-display-conf-btn',function(){
    /**
     *  Récupération des paramètres (checkbox)
     */
    if ($("input[type=checkbox][name=printRepoSize]").is(":checked")) {
        var printRepoSize = 'yes';
    } else {
        var printRepoSize = 'no';
    }

    if ($("input[type=checkbox][name=printRepoType]").is(":checked")) {
        var printRepoType = 'yes';
    } else {
        var printRepoType = 'no';
    }

    if ($("input[type=checkbox][name=printRepoSignature]").is(":checked")) {
        var printRepoSignature = 'yes';
    } else {
        var printRepoSignature = 'no';
    }

    if ($("input[type=checkbox][name=cacheReposList]").is(":checked")) {
        var cacheReposList = 'yes';
    } else {
        var cacheReposList = 'no';
    }

    configureReposListDisplay(printRepoSize, printRepoType, printRepoSignature, cacheReposList);
});


/**
 *  Ajax : Modifier la description d'un repo
 *  @param {string} repoId
 *  @param {string} repoStatus 
 *  @param {string} repoDescription 
 */
function setRepoDescription(repoId, repoStatus, repoDescription) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "setRepoDescription",
            id: repoId,
            status: repoStatus,
            description: repoDescription
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
 *  Ajax : Récupération d'un formulaire d'opération
 *  @param {string} action
 *  @param {array} repos_array 
 */
 function getForm(action, repos_array) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax-operations.php",
        data: {
            action: "getForm",
            operationAction: action,
            repos_array: repos_array
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            $("#operationsDiv").append(jsonValue.message);
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 *  Ajax : Validation et exécution d'un formulaire d'opération
 *  @param {*} operation_params_json 
 */
function validateExecuteForm(operation_params_json) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax-operations.php",
        data: {
            action: "validateForm",
            operation_params: operation_params_json,
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Lorsque l'opération est lancée on masque les div d'opérations, on recharge le bandeau de navigation pour faire apparaitre l'opération en cours et on affiche un message
             */
            $("#newRepoDiv").hide();
            $("#operationsDiv").hide();
            reloadHeader();
            printAlert(jsonValue.message, 'success');
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 *  Ajax : Modifier les paramètres d'affichage de la liste des repos
 *  @param {string} printRepoSize 
 *  @param {string} printRepoType 
 *  @param {string} printRepoSignature 
 *  @param {string} cacheReposList 
 */
function configureReposListDisplay(printRepoSize, printRepoType, printRepoSignature, cacheReposList) {
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "configureReposListDisplay",
            printRepoSize: printRepoSize,
            printRepoType: printRepoType,
            printRepoSignature: printRepoSignature,
            cacheReposList: cacheReposList
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadContentByClass('reposList');
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

