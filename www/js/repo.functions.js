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
 *  Rechargement de la liste des repos
 */
/*function reloadReposList() {

}*/

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