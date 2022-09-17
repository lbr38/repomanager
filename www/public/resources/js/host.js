$(document).ready(function () {
    /**
     *  Script Select2 pour transformer un select multiple en liste déroulante
     */
    $('.hostsSelectList').select2({
        closeOnSelect: false,
        placeholder: 'Add host...'
    });
});

/**
 *  Fonctions
 */

/**
 *  Rechercher un paquet dans le tableau des paquets installés sur l'hôte
 */
function filterPackage()
{
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
 *  Rechargement de la div des groupes
 *  Recharge les menus select2 en même temps
 */
function reloadGroupsDiv()
{
    $("#groupsHostDiv").load(" #groupsHostDiv > *",function () {
        $('.hostsSelectList').select2({
            closeOnSelect: false,
            placeholder: 'Add host...'
        });
    });
}

/**
 *  Rechargement de la div des hôtes
 */
function reloadHostsDiv()
{
    $("#hostsDiv").load(" #hostsDiv > *");
}

/**
 *  Gestion des checkbox
 */
/**
 * Fonction permettant de compter le nb de checkbox cochée pour un groupe, permets d'afficher un bouton 'Tout sélectionner'
 * @param {string} group
 */
function countChecked(group)
{
    var countTotal = $('body').find('input[name=checkbox-host\\[\\]][group=' + group + ']:checked').length
    return countTotal;
};
/**
 * Fonction permettant de compter la totalité des checkbox d'un groupe, cochées ou non
 * @param {string} group
 */
function countTotalCheckboxInGroup(group)
{
    var countTotal = $('body').find('input[name=checkbox-host\\[\\]][group=' + group + ']').length
    return countTotal;
};

/**
 *  Rechercher un hôte dans la liste des hôtes
 */
function searchHost()
{
    var div, tr, td, txtValue;
    var filter_os = '';
    var filter_os_version = '';
    var filter_os_family = '';
    var filter_type = '';
    var filter_kernel = '';
    var filter_arch = '';

    /**
     *  Si l'input est vide, on quitte
     */
    if (!$("#searchHostInput").val()) {
        /**
         *  On ré-affiche tout avant de quitter
         */
        $(".hosts-group-container").show();
        $(".host-tr").show();
        return;
    }

    /**
     *  Récupération du terme recherché dans l'input
     *  On converti tout en majuscule afin d'ignorer la casse lors de la recherche
     */
    search = $("#searchHostInput").val().toUpperCase();

    /**
     *  On affiche tous les containers de groupes (au cas où ils auraient été masqués lors d'une précédente recherche)
     */
    $(".hosts-group-container").show();

    /**
     *  On masque toutes les lignes de serveurs, seulles celles correspondant à la recherche seront ré-affichées
     */
    $(".host-tr").hide();

    /**
     *  On vérifie si l'utilisateur a saisi un filtre dans sa recherche
     *  les différents filtres possibles sont :
     *  os:
     *  os_version:
     *  kernel:
     *  arch:
     *
     *  Comme la saisie récupérée a été convertie en majuscule, on recherche la présence d'un filtre en majuscules
     */

    /**
     *  Si la recherche contient le filtre 'os:',
     */
    if (search.includes("OS:")) {
        // On récupère l'os recherché en récupérant le terme qui suit 'os:'
        filter_os = search.split('OS:')[1].split(" ")[0];
        // On supprime le filtre de la recherche globale
        search = search.replaceAll('OS:' + filter_os, '');
    }
    if (search.includes("OS_VERSION:")) {
        // On récupère la version d'os recherchée en récupérant le terme qui suit 'os_version:'
        filter_os_version = search.split('OS_VERSION:')[1].split(" ")[0];
        // On supprime le filtre de la recherche globale
        search = search.replaceAll('OS_VERSION:' + filter_os_version, '');
    }
    if (search.includes("OS_FAMILY:")) {
        // On récupère la famille d'os recherchée en récupérant le terme qui suit 'os_family:'
        filter_os_family = search.split('OS_FAMILY:')[1].split(" ")[0];
        // On supprime le filtre de la recherche globale
        search = search.replaceAll('OS_FAMILY:' + filter_os_family, '');
    }
    if (search.includes("TYPE:")) {
        // On récupère le type recherché en récupérant le terme qui suit 'type:'
        filter_type = search.split('TYPE:')[1].split(" ")[0];
        // On supprime le filtre de la recherche globale
        search = search.replaceAll('TYPE:' + filter_type, '');
    }
    if (search.includes("KERNEL:")) {
        // On récupère le kernel recherché en récupérant le terme qui suit 'kernel:'
        filter_kernel = search.split('KERNEL:')[1].split(" ")[0];
        // On supprime le filtre de la recherche globale
        search = search.replaceAll('KERNEL:' + filter_kernel, '');
    }
    if (search.includes("ARCH:")) {
        // On récupère l'arch recherchée en récupérant le terme qui suit 'arch:'
        filter_arch = search.split('ARCH:')[1].split(" ")[0];
        // On supprime le filtre de la recherche globale
        search = search.replaceAll('ARCH:' + filter_arch, '');
    }
    /**
     *  L'utilisation de filtre peut laisser des espaces blancs
     *  Suppression de tous les espaces blancs de la recherche globale
     */
    search = search.replaceAll(' ', '');
    /**
     *  Si un filtre a été précisé alors on récupère uniquement les tr '.host-tr' correspondant à ce filtre
     */
    if (filter_os != "") {
        tr = $('.host-tr').filter(function () {
            return $(this).attr('os').toUpperCase().indexOf(filter_os) > -1;
        });
    } else if (filter_os_version != "") {
        tr = $('.host-tr').filter(function () {
            return $(this).attr('os_version').toUpperCase().indexOf(filter_os_version) > -1;
        });
    } else if (filter_os_family != "") {
        tr = $('.host-tr').filter(function () {
            return $(this).attr('os_family').toUpperCase().indexOf(filter_os_family) > -1;
        });
    } else if (filter_type != "") {
        tr = $('.host-tr').filter(function () {
            return $(this).attr('type').toUpperCase().indexOf(filter_type) > -1;
        });
    } else if (filter_kernel != "") {
        tr = $('.host-tr').filter(function () {
            return $(this).attr('kernel').toUpperCase().indexOf(filter_kernel) > -1;
        });
    } else if (filter_arch != "") {
        tr = $('.host-tr').filter(function () {
            return $(this).attr('arch').toUpperCase().indexOf(filter_arch) > -1;
        });
    /**
     *  Si aucun filtre n'a été précisé alors on récupère tous les tr .host-tr
     */
    } else {
        tr = $(".host-tr");
    }

    /**
     *  Puis on traite chaque tr récupéré et on affiche uniquement ceux correspondant à la recherche
     */
    $.each(tr, function () {
        td = $(this).find("td")[2];
        if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(search) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        }
    });

    /**
     *  Masquage des div de groupes dont tous les tr ont été masqués
     */
    hideGroupDiv();
}

/**
 *  Rechercher les hôtes possédant un paquet
 */
var searchPackage_locked = false;

function searchHostPackage()
{
    /**
     *  Si une recherche est déjà en cours, on sort
     */
    if (searchPackage_locked === true) {
        return;
    }

    searchPackage_locked = true;

    /**
     *  A chaque saisie on (ré)-affiche tous les éléments masquées
     *  et on supprime les éventuelles infos dans le <td> 'host-additionnal-info'
     */
    $(".hosts-group-container").show();
    $(".host-tr").show();
    $("td.host-update-status").show();
    $("td.host-additionnal-info").html('');

    /**
     *  On utilise un setTimeout pour laisser le temps à l'utilisateur de terminer sa saisie avant de rechercher
     */
    setTimeout(function () {
        /**
             *  Si l'input est vide, on quitte
             */
        if (!$("#searchHostPackageInput").val()) {
            searchPackage_locked = false;
            return;
        }

        /**
         *  Récupération du terme recherché dans l'input
         */
        var package = $("#searchHostPackageInput").val();

        $("td.host-update-status").hide();

        /**
         *  Pour chaque id, on fait appel à la fonction searchHostPackage pour vérifier si le paquet existe sur l'hôte
         */
        $('.hosts-table').find(".host-tr").each(function () {
            var hostid = $(this).attr('hostid');

            /**
             *  Recherche en base de données si le paquet existe
             */
            searchPackage(hostid, package);
        });

        searchPackage_locked = false;

    },1000);
}

/**
 *  Masquer les groupes d'hôtes dont les hôtes ont tous été masqués (au cours d'une recherche)
 */
function hideGroupDiv()
{
    /**
     *  Pour chaque div 'hosts-group-container' on recherche tous les tableaux '<table>'
     */
    $(".hosts-group-container").each(function () {
        /**
         *  Si le <table> a une classe hosts-table-empty alors il est forcément vide ("aucun hote dans ce groupe"), donc on masque la div entière du résultat de recherche
         */
        if ($(this).find(".hosts-table-empty").length == 1) {
            $(this).hide();

        /**
         *  Si le <table> contient des hôtes, alors on vérifie si il y a au moins 1 tr d'affiché (qui correspond au résultat de recherche)
         *  Si c'est le cas alors on laisse le div affiché
         *  Si ce n'est pas le cas on masque la div entière
         */
        } else {
            var nb = $(this).find(".host-tr:visible").length;
            if (nb == 0) {
                $(this).hide();
            } else {
                $(this).show();
            }
        }
    });
}

/**
 *  Events listeners
 */

/**
 *  Event : Affichage du div permettant de gérer les groupes
 */
$(document).on('click',"#GroupsListToggleButton",function () {
    openSlide("#groupsHostDiv");
});
/**
 *  Event : Masquage du div permettant de gérer les groupes
 */
$(document).on('click',"#groupsDivCloseButton",function () {
    closeSlide("#groupsHostDiv")
});

/**
 *  Event : Affichage du div permettant de gérer les paramètres
 */
$(document).on('click',"#settingsToggleButton",function () {
    openSlide("#hostsSettingsDiv");
});
/**
 *  Event : Masquage du div permettant de gérer les paramètres
 */
$(document).on('click',"#hostsSettingsDivCloseButton",function () {
    closeSlide("#hostsSettingsDiv");
});

/**
 *  Event : Création d'un nouveau groupe
 */
$(document).on('submit','#newGroupForm',function () {
    event.preventDefault();
    /**
     *  Récupération du nom de groupe à créer dans l'input prévu à cet effet
     */
    var name = $("#newGroupInput").val();
    newGroup(name);

    return false;
});

/**
 *  Event : Renommage d'un groupe
 */
$(document).on('submit','.groupForm',function () {
    event.preventDefault();
    /**
     *  Récupération du nom actuel (dans <form>) et du nouveau nom (dans <input> contenant l'attribut groupname="name")
     */
    var name = $(this).attr('groupname');
    var newname = $('input[groupname=' + name + '].groupFormInput').val();
    renameGroup(name, newname);

    return false;
});

/**
 *  Event : Suppression d'un groupe
 */
$(document).on('click','.deleteGroupButton',function () {
    var name = $(this).attr('name');
    deleteConfirm('Are you sure you want to delete group ' + name + '?', function () {
        deleteGroup(name)});
});

/**
 * Event : Afficher la configuration d'un groupe
 * @param {*} name
 */
$(document).on('click','.groupConfigurationButton',function () {
    var name = $(this).attr('name');
    $('#groupConfigurationDiv-' + name).slideToggle(150);
});

/**
 *  Event : ajouter / supprimer des hotes d'un groupe
 */
$(document).on('submit','.groupHostsForm',function () {
    event.preventDefault();
    /**
     *  Récupération du nom du groupe (dans <form>) puis de la liste des repos (dans le <select>)
     */
    var name = $(this).attr('groupname');
    var hostsId = $('select[groupname=' + name + '].hostsSelectList').val();

    editGroupHosts(name, hostsId);

    return false;
});

/**
 *  Event : lorsqu'une checkbox d'hôte est cochée
 */
$(document).on('click',"input[name=checkbox-host\\[\\]]",function () {
    // On récupère le nom du groupe de l'hote dont la checkbox a été cochée
    var group = $(this).attr('group');
    // Puis on compte le nombre de checkbox cochées dans ce groupe
    var count_checked = countChecked(group);
    // Si il y a au moins 1 checkbox cochée alors on affiche les boutons 'Mettre à jour' et 'Supprimer' pour le groupe en question
    if (count_checked > 0) {
        $(".js-buttons-" + group).show('200');
    } else {
        $(".js-buttons-" + group).hide('200');
    }
});

/**
 *  Event : lorsqu'on clique sur le bouton 'Tout sélectionner', cela sélectionne toutes les checkbox-host[] du groupe
 */
$(document).on('click',".js-select-all-button",function () {
    // On récupère le nom du groupe du button 'Tout sélectionner' qui a été cliqué
    var group = $(this).attr('group');

    // On compte le nombre total de checkbox du groupe (cochées ou non)
    // Si le nombre de checkbox sélectionnées = nombre de checbox totales, alors le bouton Tout sélectionner aura pour effet de décocher, sinon il coche tout.
    var countTotal = countTotalCheckboxInGroup(group);
    var count_checked = countChecked(group);
    if (countTotal == count_checked) {
        $('input[name=checkbox-host\\[\\]][group=' + group + ']').prop('checked', false);
    } else {
        // On coche toutes les checkbox-host[] appartenant au même groupe
        $('input[name=checkbox-host\\[\\]][group=' + group + ']').prop('checked', true);
    }

    // On recompte de nouveau le nombre de checkbox sélectionnées
    var count_checked = countChecked(group);
    // Si il y a au moins 1 checkbox sélectionnée alors on affiche les boutons 'Mise à jour' / 'Désactiver' / 'Supprimer'
    if (count_checked >= 1) {
        $(".js-buttons-" + group).show('200');
    }
    // Si aucune checkbox n'est sélectionnée alors on masque les boutons 'Mise à jour' / 'Désactiver' / 'Supprimer'
    if (count_checked == 0) {
        $(".js-buttons-" + group).hide('200');
    }
});

/**
 *  Event : lorsqu'on clique sur un bouton d'action 'Supprimer', 'Reset', 'Mettre à jour les paquets'... depuis hosts.php
 */
$(document).on('click','.hostsActionBtn',function () {

    var hosts_array = [];

    /**
     *  Récupère le nom du groupe et l'action à exécuter
     */
    var group = $(this).attr('group');
    var action = $(this).attr('action');

    /**
     *  On parcourt toutes les checkbox dans ce groupe
     */
    $('.js-host-checkbox[group=' + group + ']').each(function () {
        /**
         *  Si la checkbox est cochée alors on ajoute l'id de l'hôte à hosts_array
         */
        if (this.checked) {
            host_id = $(this).val();
            hosts_array.push(host_id);
        }
    });

    /**
     *  Selon l'action on demande une confirmation
     */
    if (action == 'update') {
        deleteConfirm('Request selected hosts to update their packages?', function () {
            execAction(action, hosts_array)}, 'Update');
    } else if (action == 'delete') {
        deleteConfirm('Delete selected hosts?', function () {
            execAction(action, hosts_array)});
    } else if (action == 'reset') {
        deleteConfirm('Reset selected hosts?', function () {
            execAction(action, hosts_array)}, 'Reset');
    } else {
        execAction(action, hosts_array);
    }
});

/**
 *  Event : afficher les détails d'un hôte
 */
$(document).on('click','.printHostDetails',function () {
    /**
     *  Récupération des infos de l'hôte
     */
    var host_id = $(this).attr('host_id');

    /**
     *  Appelle host.inc.php avec l'id de l'hote et affiche le résultat contenant les informations détaillées l'hôte
     */
    $.get(
        'host.inc.php',
        {id:host_id},
        function (data, status, jqXHR) {
            $('body').append('<div class="hostDetails"><span class="hostDetails-close"><img title="Close" class="close-btn" src="resources/icons/close.svg" /></span>' + data + '</div>');
        }
    );

    /**
     *  Le div est alors créé mais il est masqué par défaut (hide), ceci afin de pouvoir l'afficher
     */
    $('.hostDetails').show();
});

/**
 *  Event : fermeture de .hostDetails généré par la fonction ci-dessus
 *  D'abord on masque le div avec une animation, puis on détruit le div
 */
$(document).on('click','.hostDetails-close',function () {
    $(".hostDetails").hide('200');
    $(".hostDetails").remove();
});

/**
 *  Event : lorsqu'on clique sur un bouton d'action 'Mettre à jour les paquets'... depuis host.php
 */
$(document).on('click','.hostActionBtn',function () {

    var hosts_array = [];

    /**
     *  Récupère l'id de l'hôte
     */
    hosts_array.push($(this).attr('hostid'));

    /**
     *  Récupère l'action à exécuter
     */
    var action = $(this).attr('action');

    execAction(action, hosts_array);
});

/**
 *  Event :
 *  Affichage / masquage de l'inventaire des paquets présents sur l'hôte
 *  Affichage / masquage de la liste des paquets disponibles sur l'hôte
 */
$(document).on('click','#packagesAvailableButton',function () {
    $("#packagesInstalledDiv").hide();
    if ($("#packagesAvailableDiv").is(":visible")) {
        $("#packagesAvailableDiv").hide();
    } else {
        $("#packagesAvailableDiv").slideDown('slow');
    }
});
$(document).on('click','#packagesInstalledButton',function () {
    $("#packagesAvailableDiv").hide();
    if ($("#packagesInstalledDiv").is(":visible")) {
        $("#packagesInstalledDiv").hide();
    } else {
        $("#packagesContainerLoader").show();
        setTimeout(function () {
            $("#packagesInstalledDiv").slideDown('slow');
            $("#packagesContainerLoader").hide();
        },100);
    }
});

/**
 *  Event : afficher tous les évènements
 */
$(document).on('click','#print-all-events-btn',function () {
    /**
     *  On affiche les évènements masqués de type 'event'
     */
    $("tr.event").show();
    /**
     *  On affiche les évènements masqués de type 'update-request' (si il y en a)
     */
    $("tr.update-request").show();
    /**
     *  On masque le bouton "Afficher tout"
     */
    $("#print-all-events-btn").hide();
});

/**
 *  Event : récupérer l'historique d'un paquet
 */
$(document).on('click','.getPackageTimeline',function () {
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

/**
 *  Event : Afficher le détail d'un évènement : liste les paquets installés ou mis à jour, etc... au passage de la souris
 */
$(document).on('mouseenter', '.showEventDetailsBtn', function () {
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
    $(this).append('<span class="showEventDetails">Loading<img src="../resources/images/loading.gif" class="icon"/></span>');
    $('.showEventDetails').show();

    getEventDetails(hostId, eventId, packageState);
});

/**
 *  Event : fermeture de .packageDetails
 *  D'abord on masque le div avec une animation, puis on détruit le div
 */
$(document).on('click','.packageDetails-close',function () {
    $(".packageDetails").hide('200');
    $(".packageDetails").remove();
});

/**
 *  Event : affichage ou non des demandes de mises à jour dans l'historique
 */
$(document).on('click','#showUpdateRequests',function () {
    /**
     *  Si le slide est coché alors on affiche
     */
    if (this.checked) {
        document.cookie = "showUpdateRequests=yes";

    /**
     *  Si le slide est décoché alors on masque
     */
    } else {
        document.cookie = "showUpdateRequests=no";
    }

    $("#eventsContainer").load(" #eventsContainer > *");
});


/**
 * Ajax: Créer un nouveau groupe d'hôtes
 * @param {string} name
 */
function newGroup(name)
{
    $.ajax({
        type: "POST",
        url: "controllers/groups/ajax.php",
        data: {
            action: "newGroup",
            name: name,
            type: "host"
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement des groupes et de la liste des repos
             */
            printAlert(jsonValue.message, 'success');
            reloadGroupsDiv();
            reloadHostsDiv();
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax : Supprimer un groupe d'hôtes
 * @param {string} name
 */
function deleteGroup(name)
{
    $.ajax({
        type: "POST",
        url: "controllers/groups/ajax.php",
        data: {
            action: "deleteGroup",
            name: name,
            type: "host"
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement des groupes et de la liste des repos
             */
            printAlert(jsonValue.message, 'success');
            reloadGroupsDiv();
            reloadHostsDiv();
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Renommer un groupe d'hôtes
 * @param {string} name
 * @param {string} newname
 */
function renameGroup(name, newname)
{
    $.ajax({
        type: "POST",
        url: "controllers/groups/ajax.php",
        data: {
            action: "renameGroup",
            name: name,
            newname : newname,
            type: "host"
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement des groupes et de la liste des repos
             */
            printAlert(jsonValue.message, 'success');
            reloadGroupsDiv();
            reloadHostsDiv();
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Ajouter ou supprimer des hôtes d'un groupe
 * @param {string} name
 * @param {string} hostsId
 */
function editGroupHosts(name, hostsId)
{
    $.ajax({
        type: "POST",
        url: "controllers/groups/ajax.php",
        data: {
            action: "editGroupHosts",
            name: name,
            hostsId : hostsId
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Affichage d'une alerte success et rechargement des groupes et de la liste des repos
             */
            printAlert(jsonValue.message, 'success');
            reloadHostsDiv();
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax : exécute une action sur le(s) hôte(s) sélectionné(s)
 * @param {string} action
 * @param {array} hosts_array
 */
function execAction(action, hosts_array)
{
    printAlert('Request being sent <img src="resources/images/loading.gif" class="icon" />');
    $.ajax({
        type: "POST",
        url: "controllers/hosts/ajax.php",
        data: {
            action: "hostExecAction",
            exec: action,
            hosts_array: hosts_array
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadHostsDiv();
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax : recherche de la présence d'un paquet sur un hôte
 * @param {string} hostid
 * @param {string} package
 */
function searchPackage(hostid, package)
{
    $.ajax({
        type: "POST",
        url: "controllers/hosts/ajax.php",
        data: {
            action: "searchHostPackage",
            hostid: hostid,
            package: package
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  On parse l'array JSON retourné avec le(s) paquet(s) trouvé(s) et leur version
             *  Puis on construit la liste des paquets à afficher, séparés par un retour à la ligne
             */
            result = jQuery.parseJSON(jsonValue.message);
            packagesFound = '';
            for (var package in result) {
                packagesFound += package + ' : ' + result[package] + '<br>';
            }
            /**
             *  Si le paquet est présent alors on affiche l'hôte dans le résultat de recherche, avec le nom du paquet et sa version dans un <td> prévu à cet effet
             */
            $('.host-tr[hostid=' + hostid + ']').show();
            $('.host-tr[hostid=' + hostid + ']').find('td.host-additionnal-info').html('<span class="yellowtext">' + packagesFound + ' </span>');
            /**
             *  Masquage des div de groupes dont tous les hôtes ont été masqués
             */
            hideGroupDiv();
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Si une exception a été retournée (paquet invalide par ex) alors on affiche l'erreur
             */
            if (jsonValue.message !== '') {
                printAlert(jsonValue.message, 'error');
            }
            /**
             *  Si le paquet n'est pas présent alors on masque la ligne de l'hôte dans le résultat de recherche
             */
            $('.host-tr[hostid=' + hostid + ']').hide();
            /**
             *  Masquage des div de groupes dont tous les hôtes ont été masqués
             */
            hideGroupDiv();
        },
    });
}

/**
 * Ajax : récupérer l'historique d'un paquet en base de données
 * @param {string} hostid
 * @param {string} packagename
 */
function getPackageTimeline(hostid, packagename)
{
    $.ajax({
        type: "POST",
        url: "controllers/hosts/ajax.php",
        data: {
            action: "getPackageTimeline",
            hostid: hostid,
            packagename: packagename
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            $('body').append('<div class="packageDetails"><span class="packageDetails-close"><img title="Close" class="close-btn" src="resources/icons/close.svg" /></span>' + jsonValue.message + '</div>');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax : récupérer les détails d'un évènement (la liste des paquets installés, mis à jour...)
 * @param {string} hostId
 * @param {string} eventId
 * @param {string} packageState
 */
function getEventDetails(hostId, eventId, packageState)
{
    $.ajax({
        type: "POST",
        url: "controllers/hosts/ajax.php",
        data: {
            action: "getEventDetails",
            hostId: hostId,
            eventId: eventId,
            packageState: packageState
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            $('.showEventDetails').html('<div>' + jsonValue.message + '</div>');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}