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

    // Print tooltip loading
    mytooltip.loading(e);

    ajaxRequest(
        // Controller:
        'host',
        // Action:
        'getHostWithKernel',
        // Data:
        {
            kernel: kernel
        },
        // Print success alert:
        false,
        // Print error alert:
        true
    ).then(function () {
        content = '<p class="margin-bottom-10">Hosts with kernel <code>' + kernel + '</code></p>';
        hosts = jQuery.parseJSON(jsonValue.message);

        /**
         *  Loop through each host
         */
        hosts.forEach(obj => {
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

            content += '<div class="flex align-item-center column-gap-10 div-generic-blue margin-bottom-0">';
            content += '<div>' + printOsIcon(os, os_family) + '</div>';
            content += '<div class="flex flex-direction-column row-gap-4">';
            content += '<span class="copy"><a href="/host/' + id + '" target="_blank" rel="noopener noreferrer">' + hostname + '</a></span>';
            content += '<span class="copy font-size-12 lowopacity-cst">' + ip + '</span>';
            content += '</div></div>';
        });

        // Print tooltip
        mytooltip.print(content, e);
    });
});

/**
 *  Event: Search hosts on 'profile' mouse hover
 */
$(document).on('mouseenter',".hosts-charts-list-label[chart-type=profile]",function (e) {
    const profile = $(this).attr('profile');

    // Print tooltip loading
    mytooltip.loading(e);

    ajaxRequest(
        // Controller:
        'host',
        // Action:
        'getHostWithProfile',
        // Data:
        {
            profile: profile
        },
        // Print success alert:
        false,
        // Print error alert:
        true
    ).then(function () {
        hosts = jQuery.parseJSON(jsonValue.message);
        content = '<p class="margin-bottom-10">Hosts with profile <code>' + profile + '</code></p>';

        /**
         *  Loop through each host
         */
        hosts.forEach(obj => {
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

            content += '<div class="flex align-item-center column-gap-10 div-generic-blue margin-bottom-0">';
            content += '<div>' + printOsIcon(os, os_family) + '</div>';
            content += '<div class="flex flex-direction-column row-gap-4">';
            content += '<span class="copy"><a href="/host/' + id + '" target="_blank" rel="noopener noreferrer">' + hostname + '</a></span>';
            content += '<span class="copy font-size-12 lowopacity-cst">' + ip + '</span>';
            content += '</div></div>';
        });

        // Print tooltip
        mytooltip.print(content, e);
    });
});

/**
 *  Event: Create new group
 */
$(document).on('submit','#newGroupForm',function (e) {
    e.preventDefault();

    ajaxRequest(
        // Controller:
        'group',
        // Action:
        'new',
        // Data:
        {
            name: $("#newGroupInput").val(),
            type: 'host'
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        // Reload hosts list
        mycontainer.reload('hosts/list');
        // Reload group panel
        mypanel.reload('hosts/groups/list');
    });

    return false;
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
$(document).on('submit','.group-form',function (e) {
    e.preventDefault();

    /**
     *  Retrieve group name (from <form>) and hosts list (from <select>)
     */
    var id = $(this).attr('group-id');
    var name = $(this).find('.group-name-input[group-id="' + id + '"]').val();
    var hostsId = $(this).find('select.group-hosts-list[group-id="' + id + '"]').val();

    ajaxRequest(
        // Controller:
        'group',
        // Action:
        'edit',
        // Data:
        {
            id: id,
            name: name,
            data: hostsId,
            type: 'host'
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        // Reload hosts list
        mycontainer.reload('hosts/list');
        // Reload group panel
        mypanel.reload('hosts/groups/list');
    });

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
 * Execute an action on selected hosts
 * @param {*} action
 * @param {*} hosts
 */
function executeAction(action, hosts)
{
    ajaxRequest(
        // Controller:
        'host/execute',
        // Action:
        'action',
        // Data:
        {
            exec: action,
            hosts: hosts
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        // Reload containers
        mycontainer.reload('hosts/list');
        mycontainer.reload('host/requests');
        mycontainer.reload('host/history');
    });
}

/**
 *  Event: show/hide the list of packages available on the host
 */
$(document).on('click','#available-packages-btn',function () {
    $("#installed-packages-div").hide();

    if ($("#available-packages-div").is(":visible")) {
        $("#available-packages-div").hide();
    } else {
        $("#available-packages-div").show();
    }
});

/**
 *  Event: show/hide the inventory of packages installed on the host
 */
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
 *  Event: click 'Select all' available packages
 */
$(document).on('click','.available-package-select-all',function () {
    /**
     *  Retrieve all available packages checkboxes
     */
    var checkboxes = $('.available-package-checkbox');

    /**
     *  Retrieve select btn status
     */
    var selectStatus = $(this).attr('status');

    /**
     *  If current status is not 'selected', then select all the packages
     */
    if (selectStatus != 'selected') {
        /**
         *  Loop through all checkboxes and check them
         */
        checkboxes.each(function () {
            /**
             *  If package is excluded then don't select it
             */
            if ($(this).attr('excluded') == 'true') {
                return;
            }

            /**
             *  Check the checkbox if it is not already checked
             */
            if (!$(this).is(':checked')) {
                $(this).click();
            }
        });

        // Set status
        $(this).attr('status', 'selected');

    /**
     *  Otherwise, uncheck all checkboxes
     */
    } else {
        checkboxes.each(function () {
            if ($(this).is(':checked')) {
                $(this).click();
            }
        });

        // Set status
        $(this).attr('status', '');
    }
});

/**
 *  Event: show request log details
 */
$(document).on('click','.request-show-log-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    /**
     *  Retrieve request id
     */
    var id = $(this).attr('request-id');

    ajaxRequest(
        // Controller:
        'host',
        // Action:
        'getRequestLog',
        // Data:
        {
            id: id
        },
        // Print success alert:
        false,
        // Print error alert:
        true
    ).then(function () {
        // Print the modal window with the log
        mymodal.print(jsonValue.message, 'LOG', true, false);
    });
});

/**
 *  Event: show package log details
 */
$(document).on('click','.request-show-package-log-btn',function (e) {
    /**
     *  Retrieve request id, package name and status
     */
    var id = $(this).attr('request-id');
    var package = $(this).attr('package');
    var status = $(this).attr('status');

    ajaxRequest(
        // Controller:
        'host',
        // Action:
        'getRequestPackageLog',
        // Data:
        {
            id: id,
            package: package,
            status: status
        },
        // Print success alert:
        false,
        // Print error alert:
        true
    ).then(function () {
        mymodal.print(jsonValue.message, 'LOG', true, false);
    });
});

/**
 *  Event: show request log details
 */
$(document).on('click','.request-show-more-info-btn',function () {
    var id = $(this).attr('request-id');
    $('div.request-details[request-id="' + id + '"]').toggle();
});

/**
 *  Event: cancel a request sent to a host
 */
$(document).on('click','.cancel-request-btn',function () {
    /**
     *  Retrieve request id
     */
    var id = $(this).attr('request-id');

    /**
     *  Cancel request
     */
    ajaxRequest(
        // Controller:
        'host',
        // Action:
        'cancelRequest',
        // Data:
        {
            id: id
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        ['hosts/list', 'host/requests']
    );
});

/**
 *  Event: print package history
 */
$(document).on('click','.get-package-timeline',function () {
    /**
     *  Retrieve id of the host and the package name
     */
    var hostid = $(this).attr('hostid');
    var packageName = $(this).attr('packagename');
    var title = packageName.toUpperCase() + ' HISTORY';

    ajaxRequest(
        // Controller:
        'host',
        // Action:
        'getPackageTimeline',
        // Data:
        {
            hostid: hostid,
            packagename: packageName
        },
        // Print success alert:
        false,
        // Print error alert:
        true
    ).then(function () {
        mymodal.print(jsonValue.message, title, false, false)
    });
});

/**
 *  Event: Print the event details when mouse is over: list of installed or updated packages, etc...
 */
$(document).on('mouseenter', '.event-packages-btn', function (e) {
    /**
     *  Retrieve host id
     */
    var hostId = $(this).attr('host-id');

    /**
     *  Retrieve the event id and the package state (installed, updated, removed)
     */
    var eventId = $(this).attr('event-id');
    var packageState = $(this).attr('package-state');

    mytooltip.loading(e);

    ajaxRequest(
        // Controller:
        'host',
        // Action:
        'getEventDetails',
        // Data:
        {
            hostId: hostId,
            eventId: eventId,
            packageState: packageState
        },
        // Print success alert:
        false,
        // Print error alert:
        true
    ).then(() => {
        // Print the tooltip with the content
        mytooltip.print(jsonValue.message, e);
    });
});
