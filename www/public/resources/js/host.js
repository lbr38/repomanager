selectToSelect2('select.group-hosts-list', 'Add host...');

/**
 *  Fonctions
 */

/**
 *  Rechercher un paquet dans le tableau des paquets installés sur l'hôte
 */
function filterPackage()
{
    var input, filter, i

    /**
     *  Retrieve the input value
     */
    input = document.getElementById("installed-packages-search");
    filter = input.value.toUpperCase();

    /**
     *  Retrieve package rows
     */
    container = document.getElementById("installed-packages-container");
    packageRow = container.getElementsByClassName("package-row");

    /**
     *  Loop through all rows, and hide those who don't match the search query
     */
    for (i = 0; i < packageRow.length; i++) {
        /**
         *  Retrieve current row package name and version
         */
        packageName = packageRow[i].getAttribute('packagename');
        packageVersion = packageRow[i].getAttribute('packageversion');

        /**
         *  If a package name or version matches the filter, then show the row, else hide it
         */
        if (packageName && packageVersion) {
            if (packageName.toUpperCase().indexOf(filter) > -1 || packageVersion.toUpperCase().indexOf(filter) > -1) {
                packageRow[i].style.display = "";
            } else {
                packageRow[i].style.display = "none";
            }
        }
    }
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
    var filter_agent_version = '';
    var filter_reboot_required = '';

    /**
     *  Si l'input est vide, on quitte
     */
    if (!$("#searchHostInput").val()) {
        /**
         *  On ré-affiche tout avant de quitter
         */
        $('.hosts-group-container, .host-line, .js-select-all-button').show();
        return;
    }

    printLoading();

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
    $('.host-line, .js-select-all-button').hide();

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
    if (search.includes("AGENT_VERSION:")) {
        // On récupère l'arch recherchée en récupérant le terme qui suit 'arch:'
        filter_agent_version = search.split('AGENT_VERSION:')[1].split(" ")[0];
        // On supprime le filtre de la recherche globale
        search = search.replaceAll('AGENT_VERSION:' + filter_agent_version, '');
    }
    if (search.includes("REBOOT_REQUIRED:")) {
        // On récupère l'arch recherchée en récupérant le terme qui suit 'arch:'
        filter_reboot_required = search.split('REBOOT_REQUIRED:')[1].split(" ")[0];
        // On supprime le filtre de la recherche globale
        search = search.replaceAll('REBOOT_REQUIRED:' + filter_reboot_required, '');
    }

    /**
     *  L'utilisation de filtre peut laisser des espaces blancs
     *  Suppression de tous les espaces blancs de la recherche globale
     */
    search = search.replaceAll(' ', '');

    /**
     *  Si un filtre a été précisé alors on récupère uniquement les div '.host-line' correspondant à ce filtre
     */
    if (filter_os != "") {
        line = $('.host-line').filter(function () {
            return $(this).attr('os').toUpperCase().indexOf(filter_os) > -1;
        });
    } else if (filter_os_version != "") {
        line = $('.host-line').filter(function () {
            return $(this).attr('os_version').toUpperCase().indexOf(filter_os_version) > -1;
        });
    } else if (filter_os_family != "") {
        line = $('.host-line').filter(function () {
            return $(this).attr('os_family').toUpperCase().indexOf(filter_os_family) > -1;
        });
    } else if (filter_type != "") {
        line = $('.host-line').filter(function () {
            return $(this).attr('type').toUpperCase().indexOf(filter_type) > -1;
        });
    } else if (filter_kernel != "") {
        line = $('.host-line').filter(function () {
            return $(this).attr('kernel').toUpperCase().indexOf(filter_kernel) > -1;
        });
    } else if (filter_arch != "") {
        line = $('.host-line').filter(function () {
            return $(this).attr('arch').toUpperCase().indexOf(filter_arch) > -1;
        });
    } else if (filter_agent_version != "") {
        line = $('.host-line').filter(function () {
            return $(this).attr('agent_version').toUpperCase().indexOf(filter_agent_version) > -1;
        });
    } else if (filter_reboot_required != "") {
        line = $('.host-line').filter(function () {
            return $(this).attr('reboot_required').toUpperCase().indexOf(filter_reboot_required) > -1;
        });
    /**
     *  Si aucun filtre n'a été précisé alors on récupère tous les div .host-line
     */
    } else {
        line = $(".host-line");
    }

    /**
     *  Puis on traite chaque div récupéré et on affiche uniquement ceux correspondant à la recherche
     */
    $.each(line, function () {
        div = $(this).find("div")[2];
        if (div) {
            txtValue = div.textContent || div.innerText;
            if (txtValue.toUpperCase().indexOf(search) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        }
    });

    /**
     *  Masquage des div de groupes dont tous les div ont été masqués
     */
    hideGroupDiv();

    hideLoading();
}

/**
 *  Rechercher les hôtes possédant un paquet
 */
var getHostsWithPackage_locked = false;

function getHostsWithPackage()
{
    /**
     *  Si une recherche est déjà en cours, on sort
     */
    if (getHostsWithPackage_locked === true) {
        return;
    }

    getHostsWithPackage_locked = true;

    printLoading();

    /**
     *  A chaque saisie on (ré)-affiche tous les éléments masquées
     *  et on supprime les éventuelles infos dans le 'host-additionnal-info'
     */
    $('.hosts-group-container').show();
    $('.host-line').css('align-items', 'center');
    $('.host-line').show();
    $('div.host-update-status').show();
    $('div.host-additionnal-info').html('');
    $('div.host-additionnal-info').hide();

    /**
     *  On utilise un setTimeout pour laisser le temps à l'utilisateur de terminer sa saisie avant de rechercher
     */
    setTimeout(function () {
        /**
             *  Si l'input est vide, on quitte
             */
        if (!$("#getHostsWithPackageInput").val()) {
            getHostsWithPackage_locked = false;
            return;
        }

        /**
         *  Récupération du terme recherché dans l'input
         */
        var package = $("#getHostsWithPackageInput").val();
        var hostsId_array = [];

        $("div.host-update-status").hide();

        /**
         *  Pour chaque id, on fait appel à la fonction getHostsWithPackage pour vérifier si le paquet existe sur l'hôte
         */
        $('.hosts-table').find(".host-line").each(function () {
            var hostid = $(this).attr('hostid');
            hostsId_array.push(hostid);
        });

        getHostsWithPackageAjax(hostsId_array, package);

        getHostsWithPackage_locked = false;

        hideLoading();

    },1000);
}

/**
 *  Masquer les groupes d'hôtes dont les hôtes ont tous été masqués (au cours d'une recherche)
 */
function hideGroupDiv()
{
    /**
     *  Pour chaque div 'hosts-group-container' on recherche toutes les listes d'hôtes
     */
    $(".hosts-group-container").each(function () {
        /**
         *  Si le div a une classe hosts-table-empty alors il est forcément vide ("aucun hote dans ce groupe"), donc on masque la div entière du résultat de recherche
         */
        if ($(this).find(".hosts-table-empty").length == 1) {
            $(this).hide();

        /**
         *  Si la liste contient des hôtes, alors on vérifie si il y a au moins 1 div d'affiché (qui correspond au résultat de recherche)
         *  Si c'est le cas alors on laisse le div affiché
         *  Si ce n'est pas le cas on masque la div entière
         */
        } else {
            var nb = $(this).find(".host-line:visible").length;
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
 *  Event: Search hosts on 'kernel' mouse hover
 */
$(document).on('mouseenter',".hosts-charts-list-label[chart-type=kernel]",function (e) {
    var kernel = $(this).attr('kernel');

    /**
     *  Create a new <div> hosts-charts-list-label-hosts-list
     */
    $('footer').append('<div class="hosts-charts-list-label-hosts-list"><span>Loading<img src="/assets/images/loading.gif" class="icon"/></span></div>');

    /**
     *  Get screen width
     *  Then reduce the width of screen by 50px to have some margin
     */
    var screenWidth = window.screen.width;
    screenWidth = screenWidth - 50;

    /**
     *  If hosts-charts-list-label-hosts-list is outside the screen on the right
     *  Then print it on the left of the mouse cursor
     */
    if (e.pageX + $('.hosts-charts-list-label-hosts-list').width() >= screenWidth) {
        $('.hosts-charts-list-label-hosts-list').css({
            top: e.pageY - $('.hosts-charts-list-label-hosts-list').height() / 2,
            left: e.pageX - $('.hosts-charts-list-label-hosts-list').width() - 10
        });
    /**
     *  Else print it on the right of the mouse cursor
     */
    } else {
        $('.hosts-charts-list-label-hosts-list').css({
            top: e.pageY - $('.hosts-charts-list-label-hosts-list').height() / 2,
            left: e.pageX
        });
    }

    $('.hosts-charts-list-label-hosts-list').css('display', 'flex');

    getHostWithKernel(kernel);
});

/**
 *  Event: Search hosts on 'profile' mouse hover
 */
$(document).on('mouseenter',".hosts-charts-list-label[chart-type=profile]",function (e) {
    var profile = $(this).attr('profile');

    /**
     *  Create a new <div> hosts-charts-list-label-hosts-list
     */
    $('footer').append('<div class="hosts-charts-list-label-hosts-list"><span>Loading<img src="/assets/images/loading.gif" class="icon"/></span></div>');

    /**
     *  Get screen width
     *  Then reduce the width of screen by 50px to have some margin
     */
    var screenWidth = window.screen.width;
    screenWidth = screenWidth - 50;

    /**
     *  If hosts-charts-list-label-hosts-list is outside the screen on the right
     *  Then print it on the left of the mouse cursor
     */
    if (e.pageX + $('.hosts-charts-list-label-hosts-list').width() >= screenWidth) {
        $('.hosts-charts-list-label-hosts-list').css({
            top: e.pageY - $('.hosts-charts-list-label-hosts-list').height() / 2,
            left: e.pageX - $('.hosts-charts-list-label-hosts-list').width() - 10
        });
    /**
     *  Else print it on the right of the mouse cursor
     */
    } else {
        $('.hosts-charts-list-label-hosts-list').css({
            top: e.pageY - $('.hosts-charts-list-label-hosts-list').height() / 2,
            left: e.pageX
        });
    }

    $('.hosts-charts-list-label-hosts-list').css('display', 'flex');

    getHostWithProfile(profile);
});

/**
 *  Event: Remove all hosts list <div> from the DOM when mouse has leave
 */
$(document).on('mouseleave',".hosts-charts-list-label",function () {
    if ($('.hosts-charts-list-label-hosts-list:hover').length == 0) {
        $('.hosts-charts-list-label-hosts-list').remove();
    }
});

$(document).on('mouseleave',".hosts-charts-list-label-hosts-list",function () {
    $('.hosts-charts-list-label-hosts-list').remove();
});

/**
 *  Event: Create new group
 */
$(document).on('submit','#newGroupForm',function () {
    event.preventDefault();
    /**
     *  Retrieve group name from input
     */
    var name = $("#newGroupInput").val();

    newGroup(name);

    return false;
});

/**
 *  Event: Delete group
 */
$(document).on('click','.delete-group-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var id = $(this).attr('group-id');
    var name = $(this).attr('group-name');

    confirmBox('Are you sure you want to delete group ' + name + '?', function () {
        deleteGroup(id)});
});

/**
 *  Event: Print group configuration div
 */
$(document).on('click','.group-config-btn',function () {
    var id = $(this).attr('group-id');

    slide('.group-config-div[group-id="' + id + '"]');
});

/**
 *  Event: Edit group
 */
$(document).on('submit','.group-form',function () {
    event.preventDefault();

    /**
     *  Retrieve group name (from <form>) and hosts list (from <select>)
     */
    var id = $(this).attr('group-id');
    var name = $(this).find('.group-name-input[group-id="' + id + '"]').val();
    var hostsId = $(this).find('select.group-hosts-list[group-id="' + id + '"]').val();

    editGroup(id, name, hostsId);

    return false;
});

/**
 *  Event: Edit hosts settings
 */
$(document).on('submit','#hostsSettingsForm',function () {
    event.preventDefault();

    var packagesConsideredOutdated = $('input[name="settings-pkgs-considered-outdated"').val();
    var packagesConsideredCritical = $('input[name="settings-pkgs-considered-critical"').val();

    ajaxRequest(
        // Controller:
        'host',
        // Action:
        'editSettings',
        // Data:
        {
            packagesConsideredOutdated: packagesConsideredOutdated,
            packagesConsideredCritical: packagesConsideredCritical
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        ['hosts/list']
    );

    return false;
});

/**
 *  Event: when a host checkbox is checked
 */
$(document).on('click',"input[name=checkbox-host\\[\\]]",function () {
    // Get the group name of the host which checkbox has been checked
    var group = $(this).attr('group');

    // Then we count the number of checked checkbox in this group
    var count = countChecked(group);

    // If there is at least 1 checkbox checked then display actions buttons
    if (count > 0) {
        getConfirmBox('hostsActionSelect');
    } else {
        closeConfirmBox();
    }
});

/**
 *  Event: when a 'Select all' button is clicked, it select all checkbox-host[] of the group
 */
$(document).on('click',".js-select-all-button",function () {
    // Retrieve the group name of the button which has been clicked
    var group = $(this).attr('group');

    // Count the total number of checkbox in the group (checked or not)
    // If the number of checked checkbox = total number of checkbox, then the 'Select all' button will uncheck all checkbox, else it will check all checkbox
    var countTotal = countTotalCheckboxInGroup(group);
    var count_checked = countChecked(group);

    if (countTotal == count_checked) {
        $('input[name=checkbox-host\\[\\]][group=' + group + ']').prop('checked', false);
    } else {
        // Check all checkbox-host[] of the same group
        $('input[name=checkbox-host\\[\\]][group=' + group + ']').prop('checked', true);
    }

    // Count again the number of checked checkbox
    var count_checked = countChecked(group);

    // If there is at least 1 checkbox checked then display action buttons
    if (count_checked >= 1) {
        getConfirmBox('hostsActionSelect');
    }

    // If no checkbox is checked then hide action buttons
    if (count_checked == 0) {
        closeConfirmBox();
    }
});

/**
 *  Event: When a host action button is clicked
 */
$(document).on('click','.hostsActionBtn',function () {

    var hostsArray = [];

    /**
     *  Retrieve action to execute
     */
    var action = $(this).attr('action');

    /**
     *  Get all checked checkbox
     */
    $('input[type="checkbox"][name="checkbox-host[]"]').each(function () {
        /**
         *  If checkbox is checked then add host id to hostsArray
         */
        if (this.checked) {
            hostId = $(this).val();
            hostsArray.push(hostId);
        }
    });

    /**
     *  Depending on the action we ask for a confirmation
     */
    if (action == 'update') {
        confirmBox('Request selected hosts to update their packages?', function () {
            execAction(action, hostsArray)}, 'Update');
    } else if (action == 'delete') {
        confirmBox('Delete selected hosts?', function () {
            execAction(action, hostsArray)});
    } else if (action == 'reset') {
        confirmBox('Reset selected hosts?', function () {
            execAction(action, hostsArray)}, 'Reset');
    } else {
        execAction(action, hostsArray);
    }
});

/**
 *  Event : lorsqu'on clique sur un bouton d'action 'Mettre à jour les paquets'... depuis la page d'un hote
 */
$(document).on('click','.host-action-btn',function () {

    var hosts_array = [];

    /**
     *  Récupère l'id de l'hôte
     */
    hosts_array.push($(this).attr('hostid'));

    /**
     *  Récupère l'action à exécuter
     */
    var action = $(this).attr('action');

    if (action == 'update') {
        confirmBox('Request host to update its packages?', function () {
            execAction(action, hosts_array);}, 'Update');
    } else if (action == 'reset') {
        confirmBox('Reset host?', function () {
            execAction(action, hosts_array);}, 'Reset');
    } else if (action == 'delete') {
        confirmBox('Delete host?', function () {
            execAction(action, hosts_array);}, 'Delete');
    } else {
        execAction(action, hosts_array);
    }
});

/**
 *  Event :
 *  Affichage / masquage de l'inventaire des paquets présents sur l'hôte
 *  Affichage / masquage de la liste des paquets disponibles sur l'hôte
 */
$(document).on('click','#available-packages-btn',function () {
    $("#installed-packages-div").hide();

    if ($("#available-packages-div").is(":visible")) {
        $("#available-packages-div").hide();
    } else {
        $("#available-packages-div").show();
    }
});
$(document).on('click','#installed-packages-btn',function () {
    $("#available-packages-div").hide();

    if ($("#installed-packages-div").is(":visible")) {
        $("#installed-packages-div").hide();
    } else {
        $("#packagesContainerLoader").show();
        setTimeout(function () {
            $("#installed-packages-div").show();
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
    $("tr.event").css('display', 'table-row');
    /**
     *  On affiche les évènements masqués de type 'update-request' (si il y en a)
     */
    $("tr.update-request").css('display', 'table-row');
    /**
     *  On masque le bouton "Afficher tout"
     */
    $("#print-all-events-btn").hide();
});

/**
 *  Event : récupérer l'historique d'un paquet
 */
$(document).on('click','.get-package-timeline',function () {
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
 *  Event: Print the event details when mouse is over: list of installed or updated packages, etc...
 */
$(document).on('mouseenter', '.event-packages-btn', function (e) {
    /**
     *  If a span event-packages-details has already been generated in the DOM then we destroy it
     */
    $('.event-packages-details').remove();

    /**
     *  Retrieve host id
     */
    var hostId = $(this).attr('host-id');

    /**
     *  Retrieve the event id and the package state (installed, updated, removed)
     */
    var eventId = $(this).attr('event-id');
    var packageState = $(this).attr('package-state');

    /**
     *  Create a new <div> event-packages-details
     */
    $('footer').append('<div class="event-packages-details">Loading<img src="/assets/images/loading.gif" class="icon"/></div>');

    /**
     *  Get screen width
     *  Then reduce the width of screen by 200px to have some margin
     */
    var screenWidth = window.screen.width;
    screenWidth = screenWidth - 200;

    /**
     *  If event-packages-details is outside the screen on the right
     *  Then print it on the left of the mouse cursor
     */
    if (e.pageX + $('.event-packages-details').width() >= screenWidth) {
        $('.event-packages-details').css({
            top: e.pageY - $('.event-packages-details').height() / 2,
            left: e.pageX - $('.event-packages-details').width() - 10
        });
    /**
     * Else print it on the right of the mouse cursor
     */
    } else {
        $('.event-packages-details').css({
            top: e.pageY - $('.event-packages-details').height() / 2,
            left: e.pageX
        });
    }

    $('.event-packages-details').show();

    getEventDetails(hostId, eventId, packageState);
});

/**
 *  Event: Remove event-packages-details <div> from the DOM when mouse has leave
 */
$(document).on('mouseleave', '.event-packages-details', function () {
    $('.event-packages-details').remove();
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
 * Ajax: Create a new group
 * @param {string} name
 */
function newGroup(name)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "group",
            action: "new",
            name: name,
            type: "host"
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadPanel('hosts/groups', function () {
                selectToSelect2('select.group-hosts-list', 'Add host...'); });
            reloadContainer('hosts/list');
        },
        error: function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Delete a group
 * @param {string} id
 */
function deleteGroup(id)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "group",
            action: "delete",
            id: id,
            type: "host"
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadPanel('hosts/groups', function () {
                selectToSelect2('select.group-hosts-list', 'Add host...'); });
            reloadContainer('hosts/list');
        },
        error: function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Edit a group
 * @param {string} id
 * @param {string} name
 * @param {string} hostsId
 */
function editGroup(id, name, hostsId)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "group",
            action: "edit",
            id: id,
            name: name,
            data: hostsId,
            type: "host"
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadPanel('hosts/groups', function () {
                selectToSelect2('select.group-hosts-list', 'Add host...'); });
            reloadContainer('hosts/list');
        },
        error: function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Execute an action on selected host(s)
 * @param {string} action
 * @param {array} hosts_array
 */
function execAction(action, hosts_array)
{
    printAlert('Request being sent <img src="/assets/images/loading.gif" class="icon" />');
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "host",
            action: "hostExecAction",
            exec: action,
            hosts_array: hosts_array
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadContainer('hosts/list');
        },
        error: function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: get hosts with a specific package
 * @param {array} hostsId_array
 * @param {string} package
 */
function getHostsWithPackageAjax(hostsId_array, package)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "host",
            action: "getHostsWithPackage",
            hostsIdArray: hostsId_array,
            package: package
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            const hostsArray = jQuery.parseJSON(jsonValue.message);

            for (const [hostId, subArray] of Object.entries(hostsArray)) {
                packagesFound = '';

                /**
                 *  If package found
                 */
                if (Object.keys(subArray).length > 0) {
                    for (const [packageName, packageVersion] of Object.entries(subArray)) {
                        /**
                         *  Build package list
                         */
                        packagesFound += '<span><img src="/assets/icons/package.svg" class="icon-np">' + packageName + ' (' + packageVersion + ')</span>';
                    }

                    /**
                     *  Show the host and print the package(s) found
                     */
                    $('.host-line[hostid=' + hostId + ']').css('align-items', 'flex-start');
                    $('.host-line[hostid=' + hostId + ']').find('div.host-additionnal-info').html(packagesFound);
                    $('.host-line[hostid=' + hostId + ']').find('div.host-additionnal-info').css('display', 'flex');
                    $('.host-line[hostid=' + hostId + ']').show();
                } else {
                    /**
                     *  Else hide the host
                     */
                    $('.host-line[hostid=' + hostId + ']').hide();
                }
            }

            hideGroupDiv();
        },
        error: function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
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
        url: "/ajax/controller.php",
        data: {
            controller: "host",
            action: "getPackageTimeline",
            hostid: hostid,
            packagename: packagename
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            $('body').append('<div class="packageDetails"><span class="packageDetails-close"><img title="Close" class="close-btn lowopacity" src="/assets/icons/close.svg" /></span>' + jsonValue.message + '</div>');
        },
        error: function (jqXHR, textStatus, thrownError) {
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
        url: "/ajax/controller.php",
        data: {
            controller: "host",
            action: "getEventDetails",
            hostId: hostId,
            eventId: eventId,
            packageState: packageState
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            $('.event-packages-details').html('<div>' + jsonValue.message + '</div>');
        },
        error: function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 *  Ajax: get all hosts that have the specified kernel
 *  @param {string} kernel
 */
function getHostWithKernel(kernel)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "host",
            action: "getHostWithKernel",
            kernel: kernel
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            hostsArray = jQuery.parseJSON(jsonValue.message);

            hosts = '<p class="margin-bottom-10">Hosts with kernel <code>' + kernel + '</code></p>';

            /**
             *  Loop through each host
             */
            hostsArray.forEach(obj => {
                Object.entries(obj).forEach(([key, value]) => {
                    if (key == 'Id') {
                        id = value;
                    }
                    if (key == 'Hostname') {
                        hostname = value;
                    }
                    if (key == 'Ip') {
                        ip = value;
                    }
                    if (key == 'Os') {
                        os = value;
                    }
                    if (key == 'Os_family') {
                        os_family = value;
                    }
                });

                hosts += '<div class="flex align-item-center column-gap-10 div-generic-blue margin-bottom-0">';
                hosts += '<div>' + printOsIcon(os, os_family) + '</div>';
                hosts += '<div class="flex flex-direction-column row-gap-4">';
                hosts += '<span class="copy"><a href="/host/' + id + '" target="_blank" rel="noopener noreferrer">' + hostname + '</a></span>';
                hosts += '<span class="copy font-size-12 lowopacity-cst">' + ip + '</span>';
                hosts += '</div></div>';
            });

            $('.hosts-charts-list-label-hosts-list').html(hosts);
        },
        error: function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 *  Ajax: get all hosts that have the specified profile
 *  @param {string} profile
 */
function getHostWithProfile(profile)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "host",
            action: "getHostWithProfile",
            profile: profile
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            hostsArray = jQuery.parseJSON(jsonValue.message);

            hosts = '<p class="margin-bottom-10">Hosts with profile <code>' + profile + '</code></p>';

            /**
             *  Loop through each host
             */
            hostsArray.forEach(obj => {
                Object.entries(obj).forEach(([key, value]) => {
                    if (key == 'Id') {
                        id = value;
                    }
                    if (key == 'Hostname') {
                        hostname = value;
                    }
                    if (key == 'Ip') {
                        ip = value;
                    }
                    if (key == 'Os') {
                        os = value;
                    }
                    if (key == 'Os_family') {
                        os_family = value;
                    }
                });

                hosts += '<div class="flex align-item-center column-gap-10 div-generic-blue margin-bottom-0">';
                hosts += '<div>' + printOsIcon(os, os_family) + '</div>';
                hosts += '<div class="flex flex-direction-column row-gap-4">';
                hosts += '<span class="copy"><a href="/host/' + id + '" target="_blank" rel="noopener noreferrer">' + hostname + '</a></span>';
                hosts += '<span class="copy font-size-12 lowopacity-cst">' + ip + '</span>';
                hosts += '</div></div>';
            });

            $('.hosts-charts-list-label-hosts-list').html(hosts);
        },
        error: function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}