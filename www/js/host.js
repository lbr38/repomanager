$(document).ready(function(){
	/**
	 *	Autorechargement de l'état des hôtes (ping)
	 */
    /*setInterval(function(){
        $('article').load('hosts.php?auto article');
	}, 60000);*/
  

    /**
     *  Script Select2 pour transformer un select multiple en liste déroulante
     */
    $('.hostsSelectList').select2({
        closeOnSelect: false,
        placeholder: 'Ajouter un hôte...'
    });
});

/**
 *  Fonctions
 */

/**
 *  Gestion des checkbox
 */
// Fonction permettant de compter le nb de checkbox cochée pour un groupe, permets d'afficher un bouton 'Tout sélectionner'
function countChecked(group) {
    var countTotal = $('body').find('input[name=checkbox-host\\[\\]][group='+group+']:checked').length
    return countTotal;
};
// Fonction permettant de compter la totalité des checkbox d'un groupe, cochées ou non
function countTotalCheckboxInGroup(group) {
    var countTotal = $('body').find('input[name=checkbox-host\\[\\]][group='+group+']').length
    return countTotal;
};


/**
 *  Events listeners
 */

/*$("#GroupsListToggleButton").on("click", function() {         
    // affichage du div permettant de gérer les groupes
    $("#hostGroupsDiv").slideToggle('200');
});
    
$("#GroupsListCloseButton").on("click", function() {
    // masquage du div permettant de gérer les groupes
    $("#hostGroupsDiv").slideToggle('200');
});*/

/**
 *  Event : lorsqu'une checkbox d'hôte est cochée
 */
$(document).on('click',"input[name=checkbox-host\\[\\]]",function(){
    // On récupère le nom du groupe de l'hote dont la checkbox a été cochée
    var group = $(this).attr('group');
    // Puis on compte le nombre de checkbox cochées dans ce groupe
    var count_checked = countChecked(group);
    // Si il y a au moins 1 checkbox cochée alors on affiche les boutons 'Mettre à jour' et 'Supprimer' pour le groupe en question
    if (count_checked > 0) {
        $(".js-buttons-"+group).show('200');
    } else {
        $(".js-buttons-"+group).hide('200');
    }
});

/**
 *  Event : lorsqu'on clique sur le bouton 'Tout sélectionner', cela sélectionne toutes les checkbox-host[] du groupe
 */
$(document).on('click',".js-select-all-button",function(){
    // On récupère le nom du groupe du button 'Tout sélectionner' qui a été cliqué
    var group = $(this).attr('group');

    // On compte le nombre total de checkbox du groupe (cochées ou non)
    // Si le nombre de checkbox sélectionnées = nombre de checbox totales, alors le bouton Tout sélectionner aura pour effet de décocher, sinon il coche tout.
    var countTotal = countTotalCheckboxInGroup(group);
    var count_checked = countChecked(group);
    if (countTotal == count_checked) {
        $('input[name=checkbox-host\\[\\]][group='+group+']').prop('checked', false);
    } else {
        // On coche toutes les checkbox-host[] appartenant au même groupe
        $('input[name=checkbox-host\\[\\]][group='+group+']').prop('checked', true);
    }

    // On recompte de nouveau le nombre de checkbox sélectionnées
    var count_checked = countChecked(group);
    // Si il y a au moins 1 checkbox sélectionnée alors on affiche les boutons 'Mise à jour' / 'Désactiver' / 'Supprimer'
    if (count_checked >= 1) {
        $(".js-buttons-"+group).show('200');
    }
    // Si aucune checkbox n'est sélectionnée alors on masque les boutons 'Mise à jour' / 'Désactiver' / 'Supprimer'
    if (count_checked == 0) {
        $(".js-buttons-"+group).hide('200');
    }
});

/**
 *  Event :
 *  Affichage / masquage de l'inventaire des paquets présents sur l'hôte
 *  Affichage / masquage de la liste des paquets disponibles sur l'hôte
 */
$(document).on('click','#packagesAvailableButton',function(){
    $("#packagesInstalledDiv").hide();
    if($("#packagesAvailableDiv").is(":visible")){
        $("#packagesAvailableDiv").hide();
    } else {
        $("#packagesAvailableDiv").slideDown('slow');
    }
});
$(document).on('click','#packagesInstalledButton',function(){
    $("#packagesAvailableDiv").hide();
    if($("#packagesInstalledDiv").is(":visible")){
        $("#packagesInstalledDiv").hide();
    } else {
        $("#packagesContainerLoader").show();
        setTimeout(function(){
            $("#packagesInstalledDiv").slideDown('slow');
            $("#packagesContainerLoader").hide();
        },100);
    }
});

/**
*	Event : afficher tous les évènements
*/
$(document).on('click','#print-all-events-btn',function(){
	$(".hidden-event").show();		// On affiche les évènements masqués
	$("#print-all-events-btn").hide();	// On masque le bouton "Afficher tout"
});

/**
 *  Event : récupérer l'historique d'un paquet
 */
$(document).on('click','.getPackageTimeline',function(){
    /**
     *  Si un historique est déjà affiché à l'écran on le détruit
     */
    $(".packageDetails").remove();

    /**
     *  Récupération de l'Id du package
     */
    var hostid = $(this).attr('hostid');
    var packagename = $(this).attr('packagename');

    getPackageTimeline(hostid, packagename);
});


$(document).on('mouseenter', '.showEventDetailsBtn', function() {
    /**
     *  Si un span showEventDetails a déjà été généré dans le DOM alors on le détruit
     */
    $('.showEventDetails').remove();

    /**
     *  On récupère l'Id de l'hôte
     */
    var hostId = $(this).attr('host-id');

    /**
     *  On récupère l'Id de l'event et le type de paquet qu'on souhaite afficher (installation de paquet, mise à jour)
     */
    var eventId = $(this).attr('event-id');
    var packageState = $(this).attr('package-state');

    /**
     *  On crée un nouveau span showEventDetails
     */
    $(this).append('<span class="showEventDetails">Chargement<img src="../ressources/images/loading.gif" class="icon"/></span>');
    $('.showEventDetails').show();

    getEventDetails(hostId, eventId, packageState);
});

/**
 *  Event : recherche d'un hôte dans le champ prévu à cet effet
 */
// $(document).on('keypress','#searchHostInput',function(){
//     var keycode = (event.keyCode ? event.keyCode : event.which);
//     if(keycode == '13'){
//         /**
//          *  Récupération des valeurs suivantes :
//          *   - L'Id du repo à modifier
//          *   - Le status su repo
//          *   - La description 
//          */
//         var search = $(this).val();
//         searchHost(search);
//     }
//     //Stop the event from propogation to other handlers
//     //If this line will be removed, then keypress event handler attached 
//     //at document level will also be triggered
//     event.stopPropagation();
// });

/**
 *  Event : afficher les détails d'un hôte
 */
$(document).on('click','.printHostDetails',function(){
    /**
     *  Récupération des infos de l'hôte
     */
    var host_id = $(this).attr('host_id');

    /**
     *  Appelle host.inc.php avec l'id de l'hote et affiche le résultat contenant les informations détaillées l'hôte
     */
    $.get('host.inc.php', {id:host_id}, 
    function (data, status, jqXHR){
        $('body').append('<div class="hostDetails"><span class="hostDetails-close"><img title="Fermer" class="icon-lowopacity" src="ressources/icons/close.png" /></span>'+data+'</div>');
    });

    /**
     *  Le div est alors créé mais il est masqué par défaut (hide), ceci afin de pouvoir l'afficher avec une animation show
     */
    $('.hostDetails').show('slow');
});

/**
 *  Event : fermeture de .hostDetails généré par la fonction ci-dessus
 *  D'abord on masque le div avec une animation, puis on détruit le div
 */
$(document).on('click','.hostDetails-close',function(){
    $(".hostDetails").hide('200');
    $(".hostDetails").remove();
});

/**
 *  Event : fermeture de .packageDetails
 *  D'abord on masque le div avec une animation, puis on détruit le div
 */
 $(document).on('click','.packageDetails-close',function(){
    $(".packageDetails").hide('200');
    $(".packageDetails").remove();
});

/**
 *  Rechercher un paquet dans le tableau des paquets installés sur l'hôte
 */
function searchPackage() {
    // Declare variables
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("packagesIntalledSearchInput");
    filter = input.value.toUpperCase();
    table = document.getElementById("packagesIntalledTable");
    tr = table.getElementsByClassName("pkg-row");

    // Loop through all table rows, and hide those who don't match the search query
    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td")[0];
        if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}

/**
 * Ajax : récupérer l'historique d'un paquet en base de données
 * @param {*} hostid
 * @param {*} packagename
 */
function getPackageTimeline(hostid, packagename){
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "getPackageTimeline",
            hostid: hostid,
            packagename: packagename
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            $('body').append('<div class="packageDetails"><span class="packageDetails-close"><img title="Fermer" class="icon-lowopacity" src="ressources/icons/close.png" /></span>'+jsonValue.message+'</div>');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax : récupérer les détails d'un évènement (la liste des paquets installés, mis à jour...)
 * @param {*} hostId
 * @param {*} eventId
 * @param {*} packageState
 */
 function getEventDetails(hostId, eventId, packageState){
    $.ajax({
        type: "POST",
        url: "controllers/ajax.php",
        data: {
            action: "getEventDetails",
            hostId: hostId,
            eventId: eventId,
            packageState: packageState
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            $('.showEventDetails').html('<div>'+jsonValue.message+'</div>');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}