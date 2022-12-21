$(document).ready(function () {
    /**
     *  Charge tous le nécessaire pour le formulaire de création d'un nouveau repo
     */
    loadNewRepoFormJS();
});

/**
 *  Fonctions
 */
function loadNewRepoFormJS()
{
    /**
     *  Affiche la description uniquement si un environnement est spécifié
     */
    $(document).on('change','#new-repo-target-env-select',function () {
        if ($('#new-repo-target-env-select').val() == "") {
            $('#new-repo-target-description-tr').hide();
        } else {
            $('#new-repo-target-description-tr').show();
        }
    }).trigger('change');

    /**
     *  Convert select to select2
     */
    classToSelect2('.targetArchSelect');
    idToSelect2('#targetPackageTranslationSelect');

    /**
     *  Affiche/masque les champs nécessaires
     */
    newRepoFormPrintFields();
}

/**
 *  Rechargement de la div 'nouveau repo'
 */
function reloadNewRepoDiv()
{
    $("#newRepoDiv").load(" #newRepoDiv > *", function () {
        loadNewRepoFormJS();
    });
}

/**
 *  Afficher / masquer les champs de saisie en fonction du type de paquets sélectionné (rpm ou deb)
 */
function newRepoFormPrintFields()
{
    /**
     *  Recherche du type de repo et du type de paquets sélectionné dans le formulaire d'opération dont
     *  l'action est 'new' (formulaire de création d'un nouveau repo)
     */

    /**
     *  Récupération de la valeur du bouton radio 'repoType'
     */
    var repoType =  $('.operation-form-container').find('.operation-form[action=new]').find('input:radio[name="repoType"]:checked').val();

    /**
     *  Récupération de la valeur du bouton radio 'packageType'
     */
    var packageType = $('.operation-form-container').find('.operation-form[action=new]').find('input:radio[name="packageType"]:checked').val();

    /**
     *  On masque tous les champs
     */
    $('.operation-form-container').find('[field-type]').hide();

    /**
     *  En fonction du type de repo et de paquets sélectionné, affiche uniquement les champs en lien avec ce type de repo et de paquets.
     */
    $('.operation-form-container').find('[field-type~='+repoType+'][field-type~='+packageType+']').show();
}

/**
 *  Fonction permettant de compter le nb de checkbox cochée
 */
function countChecked()
{
    var countTotal = $('.reposList').find('input[name=checkbox-repo\\[\\]]:checked').length;
    return countTotal;
};


/**
 *  Events listeners
 */

/**
 *  Event : afficher ou masquer la div qui gère les paramètres d'affichage (bouton "Affichage")
 */
$(document).on('click','#ReposListDisplayToggleButton',function () {
    openSlide("#displayDiv");
});

$(document).on('click','#displayDivCloseButton',function () {
    closeSlide("#displayDiv");
});

/**
 *  Event : affichage du div permettant de créer un nouveau repo/section
 */
$(document).on('click','#newRepoToggleButton',function () {
    openSlide("#newRepoDiv");
});

/**
 *  Event : masquage du div permettant de créer un nouveau repo/section
 */
$(document).on('click','#newRepoCloseButton',function () {
    closeSlide("#newRepoDiv");
});

/**
 *  Event : masquage du div permettant d'exécuter un opération
 */
$(document).on('click','#operationsDivCloseButton',function () {
    /**
     *  Suppression du contenu de la div
     */
    $("#op-forms-container").html('');

    closeSlide("#operationsDiv");
});

/**
 *  Event : afficher/masquer le contenu de tous les groupes de repos actifs
 */
$(document).on('click','#hideAllReposGroups',function () {
    var state = $(this).attr('state');

    if (state == 'visible') {
        $(this).attr('state', 'hidden');
        $(this).find('img').attr('src', 'resources/icons/down.svg');
        $('.repos-list-group-flex-div').slideUp();
    }

    if (state == 'hidden') {
        $(this).attr('state', 'visible');
        $(this).find('img').attr('src', 'resources/icons/up.svg');
        $('.repos-list-group-flex-div').slideDown();
    }
});

/**
 *  Event : afficher/masquer le contenu d'un groupe de repos
 */
$(document).on('click','.hideGroup',function () {
    var groupname = $(this).attr('group');
    var state = $(this).attr('state');

    if (state == 'visible') {
        $(this).attr('state', 'hidden');
        $(this).attr('src', 'resources/icons/down.svg');
    }

    if (state == 'hidden') {
        $(this).attr('state', 'visible');
        $(this).attr('src', 'resources/icons/up.svg');
    }

    $('.repos-list-group[group=' + groupname + ']').find('.repos-list-group-flex-div').slideToggle();
});


/**
 *  Event : affiche/masque des inputs en fonction du type de repo ou du type de paquets sélectionné
 */
$(document).on('change','input:radio[name="repoType"], input:radio[name="packageType"]',function () {
    newRepoFormPrintFields();
});

/**
 *  Event : clic sur le bouton de suppression d'un environnement
 */
$(document).on('click','.delete-env-btn',function () {
    /**
     *  Récupération de l'Id du repo, du snapshot et de l'env
     */
    var repoId = $(this).attr('repo-id');
    var snapId = $(this).attr('snap-id');
    var envId = $(this).attr('env-id');
    var envName = $(this).attr('env-name');

    confirmBox('Remove environment ' + envName + '?', function () {
        removeEnv(repoId, snapId, envId)});
});

/**
 *  Event : modification de la description d'un repo
 */
$(document).on('keypress','.repoDescriptionInput',function () {
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if (keycode == '13') {
        /**
         *  Récupération des valeurs suivantes :
         *   - L'Id du repo à modifier
         *   - Le status du repo
         *   - La description
         */
        var envId = $(this).attr('env-id');
        var description = $(this).val();

        setRepoDescription(envId, description);
    }
    event.stopPropagation();
});

/**
 *  Event : lorsqu'une checkbox est cochée/décochée
 */
$(document).on('click',"input[name=checkbox-repo\\[\\]]",function () {
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
     *  A partir du moment où il y a au moins 1 checkbox cochée, on affiche toutes les autres
     *  Toutes les checkbox cochées sont passées en opacity = 1
     */
    $('.reposList').find('input[name=checkbox-repo\\[\\]]').css("visibility", "visible");
    $('.reposList').find('input[name=checkbox-repo\\[\\]]:checked').css("opacity", "1");

    /**
     *  Si un repo 'local' est coché alors on masque le bouton 'mettre à jour'
     */
    if ($('.reposList').find('input[name=checkbox-repo\\[\\]][repo-type=local]:checked').length > 0) {
        $('.repo-action-btn[action=update]').hide();
    } else {
        $('.repo-action-btn[action=update]').show();
    }
});

/**
 *  Event : Lorsqu'on clique sur un bouton d'action
 */
$(document).on('click',".repo-action-btn",function () {
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
    $('.reposList').find('input[name=checkbox-repo\\[\\]]:checked').each(function () {
        var obj = {};

        /**
         *  Récupération de l'id et du status du ou des repos sélectionnés
         */
        obj['repoId'] = $(this).attr('repo-id');
        obj['snapId'] = $(this).attr('snap-id');
        obj['envId'] = $(this).attr('env-id');
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
    $("#operationsDiv").load(" #operationsDiv > *",function () {
        getForm(action, repos_array);
        openSlide("#operationsDiv");
    });

    /**
     *  Scroll vers le haut de la page
     */
    $('html, body').animate({ scrollTop: 0 }, 'fast');
});

/**
 *  Event : validation / exécution d'une opération
 */
$(document).on('submit','.operation-form-container',function () {
    event.preventDefault();

    /**
     *  Array principal qui contiendra tous les paramètres de chaque repo à traiter (1 ou plusieurs repos selon la sélection de l'utilisateur)
     */
    var operation_params = [];

    /**
     *  Récupération des paramètres saisis dans le formulaire
     */
    $(this).find('.operation-form').each(function () {
        var obj = {};

        /**
         *  Objet qui contiendra les paramètres saisis dans le formulaire pour ce repo
         */
        obj['action'] = $(this).attr('action');
        if (obj['action'] != 'new') {
            obj['snapId'] = $(this).attr('snap-id');
            obj['envId'] = $(this).attr('env-id');
        }

        /**
         *  Si l'action est 'new' alors on récupère le type de paquet du repo à créer.
         *  Puis en fonction du type de paquet on va uniquement récupérer certains paramètres.
         */
        if (obj['action'] == 'new') {
            var packageType = $(this).find('.operation_param[param-name=packageType]:checked').val();
            obj['packageType'] = packageType;
        }

        /**
         *  Puis on récupère chaque paramètres saisis par l'utilisateur et on les poussent à la suite
         *  Il n'existe pas de tableau associatif en js donc on pousse un objet.
         *  Dans le cas où l'action est 'new', ce sont uniquement les paramètres ayant l'attribut package-type=all OU package-type=packageType qui sont récupérés
         */
        if (obj['action'] == 'new') {
            var operation_param = $(this).find('.operation_param[package-type=all],.operation_param[package-type='+packageType+']');
        }
        if (obj['action'] != 'new') {
            var operation_param = $(this).find('.operation_param');
        }

        operation_param.each(function () {
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
    var operation_params_json = JSON.stringify(operation_params);

    validateExecuteForm(operation_params_json);

    return false;
});

/**
 *  Event : génération de la configuration du repo à installer sur la machine cliente
 */
$(document).on('click','.client-configuration-btn',function () {
    /**
     *  Suppression de tout autre éventuel div déjà affiché
     */
    $(".divReposConf").remove();

    /**
     *  Récupération des infos du repo
     */
    var packageType = $(this).attr('package-type');
    var repoName = $(this).attr('repo');
    var repoEnv = $(this).attr('env');

    /**
     *  Sur Debian on récupère également la distribution et la section
     */
    if (packageType == "deb") {
        var repoDist = $(this).attr('dist');
        var repoSection = $(this).attr('section');

        /**
         *  Si le nom de la distribution contient un slash, on le remplace
         */
        var repoDistFormatted = repoDist.replace('/', '--slash--');
    }
    var repo_dir_url = $(this).attr('repo-dir-url');
    var repo_conf_files_prefix = $(this).attr('repo-conf-files-prefix');
    var www_hostname = $(this).attr('www-hostname');

    if (packageType == "rpm") {
        var commands = 'echo -e "# ' + repoName + ' repo (' + repoEnv + ') on ' + www_hostname + '\n[' + repo_conf_files_prefix + '' + repoName + '_' + repoEnv + ']\nname=' + repoName + ' repo on ' + www_hostname + '\ncomment=' + repoName + ' repo on ' + www_hostname + '\nbaseurl=' + repo_dir_url + '/' + repoName + '_' + repoEnv + '\nenabled=1\ngpgkey=' + repo_dir_url + '/gpgkeys/' + www_hostname + '_rpm.pub\ngpgcheck=1" > /etc/yum.repos.d/' + repo_conf_files_prefix + '' + repoName + '.repo';
    }
    if (packageType == "deb") {
        var commands = 'wget -qO - ' + repo_dir_url + '/gpgkeys/' + www_hostname + '_deb.pub | sudo apt-key add -\n\necho "deb ' + repo_dir_url + '/' + repoName + '/' + repoDist + '/' + repoSection + '_' + repoEnv + ' ' + repoDist + ' ' + repoSection + '" > /etc/apt/sources.list.d/' + repo_conf_files_prefix + '' + repoName + '_' + repoDistFormatted + '_' + repoSection + '.list';
    }

    /**
     *  Génération du div
     */
    $('body').append('<div class="divReposConf hide"><span><img title="Close" class="divReposConf-close close-btn lowopacity" src="resources/icons/close.svg" /></span><h3>INSTALLATION</h3><h5>Use the code below to install the repo on a host:</h5><div id="divReposConfCommands-container"><pre id="divReposConfCommands">' + commands + '</pre><img src="resources/icons/duplicate.svg" class="icon-lowopacity" title="Copy to clipboard" onclick="copyToClipboard(divReposConfCommands)" /></div></div>');

    /**
     *  Affichage
     */
    $('.divReposConf').show();
});

/**
 *  Event : fermeture de la configuration client
 */
$(document).on('click','.divReposConf-close',function () {
    $(".divReposConf").remove();
});

/**
 *  Event : modifier les paramètres d'affichage de la liste des repos
 */
$(document).on('click','#repos-display-conf-btn',function () {
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
 *  @param {string} repoDescription
 */
function removeEnv(repoId, snapId, envId)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "operation",
            action: "removeEnv",
            repoId: repoId,
            snapId: snapId,
            envId: envId
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
 *  Ajax : Modifier la description d'un repo
 *  @param {string} repoId
 *  @param {string} repoDescription
 */
function setRepoDescription(envId, repoDescription)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "repo",
            action: "setRepoDescription",
            envId: envId,
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
function getForm(action, repos_array)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "operation",
            action: "getForm",
            operationAction: action,
            repos_array: repos_array
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            $("#operationsDiv").find('.param-slide').append(jsonValue.message);
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
function validateExecuteForm(operation_params_json)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "operation",
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
function configureReposListDisplay(printRepoSize, printRepoType, printRepoSignature, cacheReposList)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "repo",
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