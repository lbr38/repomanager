/**
 *  Events listeners
 */
/**
 *  Event : affichage du div permettant de gérer les sources
 */
$(document).on('click','#ReposSourcesToggleButton',function(){
    $("#sourcesDiv").slideToggle().show("slow");
});

/**
 *  Event : masquage du div permettant de gérer les sources
 */
$(document).on('click','#ReposSourcesCloseButton',function(){
    $("#sourcesDiv").hide("slow");
});

/**
 *  Event : affichage du div permettant de créer un nouveau repo/section
 */
$(document).on('click','#newRepoToggleButton',function(){
    $("#newRepoSlideDiv").slideToggle().show("slow");
});

/**
 *  Event : masquage du div permettant de créer un nouveau repo/section
 */
$(document).on('click','#newRepoCloseButton',function(){
    $("#newRepoSlideDiv").hide("slow");
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
    //Stop the event from propogation to other handlers
    //If this line will be removed, then keypress event handler attached 
    //at document level will also be triggered
    event.stopPropagation();
});

/**
 *  Event : génération de la configuration du repo à installer sur la machine cliente
 */
 $(".client-configuration-button").click(function(){
    /**
     *  Récupération des infos du repo
     */
    var os_family = $(this).attr('os_family');
    var repoName = $(this).attr('repo');
    var repoEnv = $(this).attr('env');
    if (os_family == "Debian") {
        var repoDist = $(this).attr('dist');
        var repoSection = $(this).attr('section');
    }
    var repo_dir_url = $(this).attr('repo_dir_url');
    var repo_conf_files_prefix = $(this).attr('repo_conf_files_prefix');
    var www_hostname = $(this).attr('www_hostname');

    if (os_family == "Redhat") {
        $('footer').append('<div class="divReposConf hide"><span><img title="Fermer" class="divReposConf-close icon-lowopacity" src="ressources/icons/close.png" /></span><h3>INSTALLATION</h3><p>Exécuter ces commandes directement dans le terminal de la machine cliente :</p><pre>echo -e "# Repo '+repoName+' ('+repoEnv+') sur '+www_hostname+'\n['+repo_conf_files_prefix+''+repoName+'_'+repoEnv+']\nname=Repo '+repoName+' sur '+www_hostname+'\ncomment=Repo '+repoName+' sur '+www_hostname+'\nbaseurl='+repo_dir_url+'/'+repoName+'_'+repoEnv+'\nenabled=1\ngpgkey='+repo_dir_url+'/gpgkeys/'+www_hostname+'.pub\ngpgcheck=1" > /etc/yum.repos.d/'+repo_conf_files_prefix+''+repoName+'.repo</pre></div>');
    }
    if (os_family == "Debian") {
        $('footer').append('<div class="divReposConf hide"><span><img title="Fermer" class="divReposConf-close icon-lowopacity" src="ressources/icons/close.png" /></span><h3>INSTALLATION</h3><p>Exécuter ces commandes directement dans le terminal de la machine cliente :</p><pre>wget -qO '+repo_dir_url+'/gpgkeys/'+www_hostname+'.pub | sudo apt-key add -\n\necho -e "# Repo '+repoName+' ('+repoEnv+') sur '+www_hostname+'\ndeb '+repo_dir_url+'/'+repoName+'/'+repoDist+'/'+repoSection+'_'+repoEnv+' '+repoDist+' '+repoSection+'" > /etc/apt/sources.list.d/'+repo_conf_files_prefix+''+repoName+'_'+repoDist+'_'+repoSection+'.list</pre></div>');
    }

    /**
     *  Le div est créé mais il est masqué par défaut (hide), ceci afin de pouvoir l'afficher avec une animation show
     */
    $('.divReposConf').show(200);

    /**
     *  Fermeture de la configuration du repo générée par la fonction ci-dessus
     *  D'abord on masque le div avec une animation, puis on détruit le div
     */
    $(".divReposConf-close").click(function(){
        $(".divReposConf").hide(200);
        $(".divReposConf").remove();
    }); 
});

/**
 * Ajax : Modifier la description d'un repo
 * @param {string} repoId
 * @param {string} repoStatus 
 * @param {string} repoDescription 
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