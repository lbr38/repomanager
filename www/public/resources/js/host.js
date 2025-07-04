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
    var countTotal = $('body').find('input[name="checkbox-host[]"][group="' + group + '"]:checked').length
    return countTotal;
};

/**
 * Fonction permettant de compter la totalité des checkbox d'un groupe, cochées ou non
 * @param {string} group
 */
function countTotalCheckboxInGroup(group)
{
    var countTotal = $('body').find('input[name="checkbox-host[]"][group="' + group + '"]').length
    return countTotal;
};

/**
 *  Search host in the host list
 */
function searchHost()
{
    var div, txtValue;
    var filter_os = '';
    var filter_os_version = '';
    var filter_os_family = '';
    var filter_type = '';
    var filter_kernel = '';
    var filter_arch = '';
    var filter_profile = '';
    var filter_env = '';
    var filter_agent_version = '';
    var filter_reboot_required = '';

    /**
     *  If the input is empty, quit
     */
    if (!$("#search-host-input").val()) {
        // Show all containers and host lines before quit
        $('.hosts-group-container, .js-select-all-button').show();
        $('.host-line').addClass('flex').show();

        return;
    }

    printLoading();

    /**
     *  Retrieve the search term from the input
     *  Convert the search term to uppercase to ignore case when searching
     */
    search = $("#search-host-input").val().toUpperCase();

    /**
     *  Print all group containers (in case they were hidden during a previous search)
     */
    $(".hosts-group-container").show();

    /**
     *  Hide all host lines, only those corresponding to the search will be re-displayed
     */
    $('.js-select-all-button').hide();
    $('.host-line, .js-select-all-button').removeClass('flex').hide();

    /**
     *  Check if the user has entered a filter in his search, different filters are possible:
     *  os:
     *  os_version:
     *  kernel:
     *  arch:
     *  ...
     *
     *  As the input retrieved has been converted to uppercase, we search for the presence of a filter in uppercase
     */
    if (search.includes("OS:")) {
        // Retrieve the os searched by getting the term following 'os:'
        filter_os = search.split('OS:')[1].split(" ")[0];
        // Remove the filter from the global search
        search = search.replaceAll('OS:' + filter_os, '');
    }
    if (search.includes("OS_VERSION:")) {
        // Retrieve the os version searched by getting the term following 'os_version:'
        filter_os_version = search.split('OS_VERSION:')[1].split(" ")[0];
        // Remove the filter from the global search
        search = search.replaceAll('OS_VERSION:' + filter_os_version, '');
    }
    if (search.includes("OS_FAMILY:")) {
        // Retrieve the os family searched by getting the term following 'os_family:'
        filter_os_family = search.split('OS_FAMILY:')[1].split(" ")[0];
        // Remove the filter from the global search
        search = search.replaceAll('OS_FAMILY:' + filter_os_family, '');
    }
    if (search.includes("TYPE:")) {
        // Retrieve the type searched by getting the term following 'type:'
        filter_type = search.split('TYPE:')[1].split(" ")[0];
        // Remove the filter from the global search
        search = search.replaceAll('TYPE:' + filter_type, '');
    }
    if (search.includes("KERNEL:")) {
        // Retrieve the kernel searched by getting the term following 'kernel:'
        filter_kernel = search.split('KERNEL:')[1].split(" ")[0];
        // Remove the filter from the global search
        search = search.replaceAll('KERNEL:' + filter_kernel, '');
    }
    if (search.includes("ARCH:")) {
        // Retrieve the arch searched by getting the term following 'arch:'
        filter_arch = search.split('ARCH:')[1].split(" ")[0];
        // Remove the filter from the global search
        search = search.replaceAll('ARCH:' + filter_arch, '');
    }
    if (search.includes("PROFILE:")) {
        // Retrieve the profile searched by getting the term following 'profile:'
        filter_profile = search.split('PROFILE:')[1].split(" ")[0];
        // Remove the filter from the global search
        search = search.replaceAll('PROFILE:' + filter_profile, '');
    }
    if (search.includes("ENV:")) {
        // Retrieve the env searched by getting the term following 'env:'
        filter_env = search.split('ENV:')[1].split(" ")[0];
        // Remove the filter from the global search
        search = search.replaceAll('ENV:' + filter_env, '');
    }
    if (search.includes("AGENT_VERSION:")) {
        // Retrieve the agent version searched by getting the term following 'agent_version:'
        filter_agent_version = search.split('AGENT_VERSION:')[1].split(" ")[0];
        // Remove the filter from the global search
        search = search.replaceAll('AGENT_VERSION:' + filter_agent_version, '');
    }
    if (search.includes("REBOOT_REQUIRED:")) {
        // Retrieve the reboot required searched by getting the term following 'reboot_required:'
        filter_reboot_required = search.split('REBOOT_REQUIRED:')[1].split(" ")[0];
        // Remove the filter from the global search
        search = search.replaceAll('REBOOT_REQUIRED:' + filter_reboot_required, '');
    }

    /**
     *  Using filters can leave white spaces, remove all white spaces from the global search
     */
    search = search.replaceAll(' ', '');

    /**
     *  If a filter has been specified then we only retrieve the '.host-line' divs corresponding to this filter
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
    } else if (filter_profile != "") {
        line = $('.host-line').filter(function () {
            return $(this).attr('profile').toUpperCase().indexOf(filter_profile) > -1;
        });
    } else if (filter_env != "") {
        line = $('.host-line').filter(function () {
            return $(this).attr('env').toUpperCase().indexOf(filter_env) > -1;
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
     *  If no filter has been specified then we retrieve all the '.host-line' divs
     */
    } else {
        line = $(".host-line");
    }

    /**
     *  Then we process each div retrieved and display only those corresponding to the search
     */
    $.each(line, function () {
        div = $(this).find("div")[0];

        if (div) {
            txtValue = div.textContent || div.innerText;
            if (txtValue.toUpperCase().indexOf(search) > -1) {
                $(this).addClass('flex').show();
            } else {
                $(this).removeClass('flex').hide();
            }
        }
    });

    /**
     *  Hide group divs whose all divs have been hidden
     */
    hideGroupDiv();
    hideLoading();
}

/**
 *  Research hosts with a package
 */
var getHostsWithPackage_locked = false;

function getHostsWithPackage()
{
    /**
     *  If a search is already in progress, exit
     */
    if (getHostsWithPackage_locked === true) {
        return;
    }

    getHostsWithPackage_locked = true;

    printLoading();

    /**
     *  On every input, (re)-display all hidden elements and remove any info in 'host-additionnal-info'
     */
    $('.hosts-group-container').show();
    $('.host-line').show();
    $('div.host-additionnal-info').html('');
    $('div.host-additionnal-info').hide();

    /**
     *  Use a setTimeout to give the user time to finish typing before searching
     */
    setTimeout(function () {
        /**
         *  If the input is empty, quit
         */
        if (!$("#getHostsWithPackageInput").val()) {
            getHostsWithPackage_locked = false;
            return;
        }

        /**
         *  Retrieve the search term from the input
         */
        var package = $("#getHostsWithPackageInput").val();
        var hosts = [];

        /**
         *  For each Id, call the getHostsWithPackageAjax function to check if the package exists on the host
         */
        $('.hosts-table').find(".host-line").each(function () {
            var hostid = $(this).attr('hostid');
            hosts.push(hostid);
        });

        getHostsWithPackageAjax(hosts, package);

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
$(document).on('submit','#newGroupForm',function () {
    event.preventDefault();
    /**
     *  Retrieve group name from input
     */
    var name = $("#newGroupInput").val();

    ajaxRequest(
        // Controller:
        'group',
        // Action:
        'new',
        // Data:
        {
            name: name,
            type: 'host'
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        ['hosts/list']
    ).then(function () {
        // Reload group panel
        mypanel.reload('hosts/groups/list');
    });

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

    confirmBox(
        {
            'title': 'Delete group',
            'message': 'Are you sure you want to delete group <b>' + name + '</b>?',
            'buttons': [
            {
                'text': 'Delete',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'group',
                        // Action:
                        'delete',
                        // Data:
                        {
                            id: id,
                            type: 'host'
                        },
                        // Print success alert:
                        true,
                        // Print error alert:
                        true,
                        // Reload container:
                        ['hosts/list']
                    ).then(function () {
                        // Reload group panel
                        mypanel.reload('hosts/groups/list');
                    });
                }
            }]
        }
    );
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
        true,
        // Reload container:
        ['hosts/list']
    ).then(function () {
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
 *  Event: when a 'Select all' button is clicked, it selects all checkbox-host[] of the group
 */
$(document).on('click','input[type="checkbox"].js-select-all-button',function () {
    // Retrieve the group name of the button which has been clicked
    var group = $(this).attr('group');

    // Count the total number of checkbox in the group (checked or not)
    // If the number of checked checkbox = total number of checkbox, then the 'Select all' button will uncheck all checkbox, else it will check all checkbox
    var countTotalCheckboxes = countTotalCheckboxInGroup(group);
    var countCheckedCheckboxes = countChecked(group);

    if (countTotalCheckboxes == countCheckedCheckboxes) {
        $('input[name="checkbox-host[]"][group="' + group + '"]').each(function () {
            if ($(this).is(':checked')) {
                // Simulate a click on the checkbox to trigger confirm box
                $(this).click();
            }
        });
    } else {
        // Check all checkbox-host[] of the same group
        $('input[name="checkbox-host[]"][group="' + group + '"]').each(function () {
            if (!$(this).is(':checked')) {
                // Simulate a click on the checkbox to trigger confirm box
                $(this).click();
            }
        });
    }

    // Count again the number of checked checkbox
    var countCheckedCheckboxes = countChecked(group);

    // If no checkbox is checked then close confirm box
    if (countCheckedCheckboxes == 0) {
        closeConfirmBox();
    }
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
        'host',
        // Action:
        'executeAction',
        // Data:
        {
            exec: action,
            hosts: hosts
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        ['hosts/list', 'host/requests', 'host/history'],
        // Execute functions on success:
        []
    )
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
        printModalWindow(jsonValue.message, 'LOG', true, false);
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
        printModalWindow(jsonValue.message, 'LOG', true, false);
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
        printModalWindow(jsonValue.message, title, false, false)
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

/**
 * Ajax: get hosts with a specific package
 * @param {array} hosts
 * @param {string} package
 */
function getHostsWithPackageAjax(hosts, package)
{
    ajaxRequest(
        // Controller:
        'host',
        // Action:
        'getHostsWithPackage',
        // Data:
        {
            hostsIdArray: hosts,
            package: package
        },
        // Print success alert:
        false,
        // Print error alert:
        true
    ).then(() => {
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
                    packagesFound += '<div class="flex align-item-center column-gap-5"><img src="/assets/icons/package.svg" class="icon-np">   <span>' + packageName + ' (' + packageVersion + ')</span></div>';
                }

                /**
                 *  Show the host and print the package(s) found
                 */
                $('.host-line[hostid=' + hostId + ']').find('div.host-additionnal-info').html('<h6>RESULTS</h6>' + packagesFound);
                $('.host-line[hostid=' + hostId + ']').find('div.host-additionnal-info').css('display', 'flex');
                $('.host-line[hostid=' + hostId + ']').show();
            } else {
                /**
                 *  Else hide the host
                 */
                $('.host-line[hostid=' + hostId + ']').removeClass('flex').hide();
            }
        }

        hideGroupDiv();
    });
}
