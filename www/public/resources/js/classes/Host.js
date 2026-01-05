class Host
{
    /**
     * Get the action box for a list of hosts
     * @param {*} hosts
     */
    getActionBox()
    {
        var hosts = [];

        // The buttons that will be displayed in the confirm box
        var buttons = [];

        /**
         *  The list of allowed actions the user can execute on the selected hosts
         *  By default: all, unless the user has specific permissions
         *  Those permissions are later verified by the server so even if the user tries to execute an action he is not allowed to, it will not work
         */
        var allowedActions = ['request-general-infos', 'request-packages-infos', 'update-packages', 'reset', 'delete'];

        // Get all checked checkbox
        $('input[type="checkbox"][name="checkbox-host[]"]').each(function () {
            // If checkbox is checked then add host id to hosts
            if (this.checked) {
                hosts.push($(this).val());
            }
        });

        // If no hosts are selected, close the confirmation box and exit
        if (hosts.length == 0) {
            myconfirmbox.close();
            return;
        }

        // Get permissions from cookie
        if (mycookie.exists('user_permissions')) {
            var userPermissions = JSON.parse(mycookie.get('user_permissions'));

            // Reset allowed actions array
            var allowedActions = [];

            // Loop through all permissions and check if the user has the permission to execute the action
            if (userPermissions.hosts && userPermissions.hosts['allowed-actions'] && userPermissions.hosts['allowed-actions']) {
                var allowedActions = userPermissions.hosts['allowed-actions'];
            }
        }

        // Define confirm box buttons depending on the allowed actions
        if (allowedActions.includes('request-general-infos')) {
            buttons.push(
                {
                    'text': 'Request general informations',
                    'color': 'blue-alt',
                    'callback': function () {
                        executeAction('request-general-infos', hosts);
                    }
                }
            );
        }

        if (allowedActions.includes('request-packages-infos')) {
            buttons.push(
                {
                    'text': 'Request packages informations',
                    'color': 'blue-alt',
                    'callback': function () {
                        executeAction('request-packages-infos', hosts);
                    }
                }
            );
        }

        if (allowedActions.includes('update-packages')) {
            buttons.push(
                {
                    'text': 'Update packages',
                    'color': 'blue-alt',
                    'callback': function () {
                        mypanel.get('hosts/requests/update-packages', {
                            hostsId: hosts
                        });
                    }
                }
            );
        }

        // Export to CSV is always allowed by default
        buttons.push(
            {
                'text': 'Export to CSV',
                'color': 'blue-alt',
                'callback': function () {
                    myhost.export(hosts);
                }
            }
        );

        if (allowedActions.includes('reset')) {
            buttons.push(
                {
                    'text': 'Reset',
                    'color': 'red',
                    'callback': function () {
                        executeAction('reset', hosts);
                    }
                }
            );
        }

        if (allowedActions.includes('delete')) {
            buttons.push(
                {
                    'text': 'Delete',
                    'color': 'red',
                    'callback': function () {
                        executeAction('delete', hosts);
                    }
                }
            );
        }

        myconfirmbox.print(
            {
                'title': 'Execute an action',
                'message': hosts.length + ' host' + (hosts.length > 1 ? 's' : '') + ' selected',
                'id': 'hosts-actions-confirm-box',
                'buttons': buttons
            }
        );
    }

    /**
     * Search hosts based on the input value
     * @returns
     */
    search()
    {
        var div;
        var filter_hostname;
        var filter_os;
        var filter_os_version;
        var filter_os_family;
        var filter_type;
        var filter_kernel;
        var filter_arch;
        var filter_profile;
        var filter_env;
        var filter_agent_version;
        var filter_reboot_required;
        var line;

        /**
         *  If the input is empty, quit
         */
        if (!$("#search-host-input").val()) {
            // Show all containers and host lines before quit
            $('.hosts-group-container').show();
            $('.host-line').addClass('flex').show();
            return;
        }

        mylayout.printLoading();

        /**
         *  Retrieve the search term from the input
         *  Convert the search term to uppercase to ignore case when searching
         */
        var search = $("#search-host-input").val().toUpperCase();

        /**
         *  Print all group containers (in case they were hidden during a previous search)
         */
        $(".hosts-group-container").show();

        /**
         *  Hide all host lines, only those corresponding to the search will be re-displayed
         */
        $('.host-line').removeClass('flex').hide();

        /**
         *  Check if the user has entered a filter in his search, different filters are possible:
         *  os, os_version, os_family, type, kernel, arch, profile, env, agent_version, reboot_required
         *
         *  e.g:
         *  os=ubuntu 192.168
         *  os="Linux Mint" os_version="21" 192.168
         *
         *  As the input retrieved has been converted to uppercase, we search for the presence of a filter in uppercase
         */
        const filters = ['HOSTNAME', 'OS', 'OS-VERSION', 'OS-FAMILY', 'TYPE', 'KERNEL', 'ARCH', 'PROFILE', 'ENV', 'AGENT-VERSION', 'REBOOT-REQUIRED'];
        filters.forEach(function (filter) {
            // Match filter value: if quoted, allow spaces; if not, stop at first space
            // e.g. os="Linux Mint" or os=Ubuntu
            var regex = new RegExp(filter + '=(?:"([^"]+)"|([^" ]+))');
            var match = search.match(regex);
            if (match) {
                var filterValue = match[1] !== undefined ? match[1] : match[2];

                // Remove the filter from the global search (preserve spaces outside quotes)
                search = search.replace(regex, '').replace(/\s{2,}/g, ' ').trim();

                switch (filter) {
                    case 'HOSTNAME':
                        filter_hostname = filterValue.toUpperCase();
                    break;
                    case 'OS':
                        filter_os = filterValue.toUpperCase();
                    break;
                    case 'OS-VERSION':
                        filter_os_version = filterValue.toUpperCase();
                    break;
                    case 'OS-FAMILY':
                        filter_os_family = filterValue.toUpperCase();
                    break;
                    case 'TYPE':
                        filter_type = filterValue.toUpperCase();
                    break;
                    case 'KERNEL':
                        filter_kernel = filterValue.toUpperCase();
                    break;
                    case 'ARCH':
                        filter_arch = filterValue.toUpperCase();
                    break;
                    case 'PROFILE':
                        filter_profile = filterValue.toUpperCase();
                    break;
                    case 'ENV':
                        filter_env = filterValue.toUpperCase();
                    break;
                    case 'AGENT-VERSION':
                        filter_agent_version = filterValue.toUpperCase();
                    break;
                    case 'REBOOT-REQUIRED':
                        filter_reboot_required = filterValue.toUpperCase();
                    break;
                    default:
                        console.warn('Unknown filter:', filter);
                    break;
                }
            }
        });

        search = search.trim();

        var hosts = $('.host-line');

        if (!empty(filter_os)) {
            hosts = hosts.filter(function () {
                return $(this).attr('os').toUpperCase().indexOf(filter_os) > -1;
            });
        }
        if (!empty(filter_os_version)) {
            hosts = hosts.filter(function () {
                return $(this).attr('os_version').toUpperCase().indexOf(filter_os_version) > -1;
            });
        }
        if (!empty(filter_os_family)) {
            hosts = hosts.filter(function () {
                return $(this).attr('os_family').toUpperCase().indexOf(filter_os_family) > -1;
            });
        }
        if (!empty(filter_type)) {
            hosts = hosts.filter(function () {
                return $(this).attr('type').toUpperCase().indexOf(filter_type) > -1;
            });
        }
        if (!empty(filter_kernel)) {
            hosts = hosts.filter(function () {
                return $(this).attr('kernel').toUpperCase().indexOf(filter_kernel) > -1;
            });
        }
        if (!empty(filter_arch)) {
            hosts = hosts.filter(function () {
                return $(this).attr('arch').toUpperCase().indexOf(filter_arch) > -1;
            });
        }
        if (!empty(filter_profile)) {
            hosts = hosts.filter(function () {
                return $(this).attr('profile').toUpperCase().indexOf(filter_profile) > -1;
            });
        }
        if (!empty(filter_env)) {
            hosts = hosts.filter(function () {
                return $(this).attr('env').toUpperCase().indexOf(filter_env) > -1;
            });
        }
        if (!empty(filter_agent_version)) {
            hosts = hosts.filter(function () {
                return $(this).attr('agent_version').toUpperCase().indexOf(filter_agent_version) > -1;
            });
        }
        if (!empty(filter_reboot_required)) {
            hosts = hosts.filter(function () {
                return $(this).attr('reboot_required').toUpperCase().indexOf(filter_reboot_required) > -1;
            });
        }

        // Process each host line to check if it matches the search term
        $.each(hosts, function () {
            div = $(this).find('div')[0];

            if (div) {
                var txtValue = div.textContent || div.innerText;
                if (txtValue.toUpperCase().indexOf(search) > -1) {
                    $(this).addClass('flex').show();
                }
            }
        });

        // Hide group divs whose all divs have been hidden
        hideGroupDiv();

        mylayout.hideLoading();
    }

    /**
     * Search hosts with a specific package
     * @returns
     */
    searchPackage()
    {
        var hosts = [];
        var name;
        var version = null;
        var strictName = false;
        var strictVersion = false;

        // If a search is already in progress, exit
        if (self.packagesearchlock === true) {
            return;
        }

        // Set a lock to prevent multiple searches at the same time that could slow down the database
        self.packagesearchlock = true;

        // Print a loading icon
        mylayout.printLoading();

        // On every input, (re)-display all hidden elements and remove any info in 'host-additionnal-info'
        $('.hosts-group-container').show();
        $('.host-line').show();
        $('div.host-additionnal-info').html('');
        $('div.host-additionnal-info').hide();

        // If the input is empty, quit
        if (!$("#search-package-input").val()) {
            self.packagesearchlock = false;
            return;
        }

        // Use a setTimeout to give the user time to finish typing before searching
        setTimeout(function () {
            // If the input is empty, quit
            if (!$("#search-package-input").val()) {
                self.packagesearchlock = false;
                return;
            }

            // Retrieve the search term from the input
            var search = $("#search-package-input").val().trim();

            // Search format is: name=package_name version=package_version strict-name=true/false strict-version=true/false
            // Split the search term by spaces
            var searchParts = search.split(' ');
            searchParts.forEach(function (part) {
                // If the part starts with 'name=', extract the package name
                if (part.startsWith('name=')) {
                    name = part.substring(5).trim();
                // If the part starts with 'version=', extract the package version
                } else if (part.startsWith('version=')) {
                    version = part.substring(8).trim();
                // If the part starts with 'strict-name='
                } else if (part.startsWith('strict-name=')) {
                    strictName = part.substring(12).trim().toLowerCase() === 'true';
                // If the part starts with 'strict-version='
                } else if (part.startsWith('strict-version=')) {
                    strictVersion = part.substring(15).trim().toLowerCase() === 'true';
                }
            });

            // Wait until name= is fully defined
            // This is to allow the user to finish typing before searching
            if (name === undefined || name.trim() === '') {
                self.packagesearchlock = false;
                return;
            }

            // For each Id, get the hostid and add it to the hosts array
            $('.hosts-table').find('.host-line').each(function () {
                var hostid = $(this).attr('hostid');
                hosts.push(hostid);
            });

            // Get hosts with the package
            ajaxRequest(
                // Controller:
                'host',
                // Action:
                'getHostsWithPackage',
                // Data:
                {
                    hosts: hosts,
                    package: name,
                    version: version,
                    strictName: strictName ? 1 : 0, // Convert strict to PHP-compatible boolean (1 or 0)
                    strictVersion: strictVersion ? 1 : 0 // Convert strict to PHP-compatible boolean (1 or 0)
                },
                // Print success alert:
                false,
                // Print error alert:
                true
            ).then(() => {
                const hosts = jQuery.parseJSON(jsonValue.message);

                for (const [hostId, subArray] of Object.entries(hosts)) {
                    var packagesFound = '';

                    // If one or multiple packages are found on the host
                    if (Object.keys(subArray).length > 0) {
                        for (const [packageName, packageVersion] of Object.entries(subArray)) {
                            // Build package list
                            packagesFound += '<div class="flex align-item-center column-gap-5"><img src="/assets/icons/package.svg" class="icon-np">   <span>' + packageName + ' (' + packageVersion + ')</span></div>';
                        }

                        // Display the host and print the package(s) found
                        $('.host-line[hostid=' + hostId + ']').find('div.host-additionnal-info').html('<h6>RESULTS</h6>' + packagesFound);
                        $('.host-line[hostid=' + hostId + ']').find('div.host-additionnal-info').css('display', 'flex');
                        $('.host-line[hostid=' + hostId + ']').show();
                    // If no package is found on the host, hide it
                    } else {
                        $('.host-line[hostid=' + hostId + ']').removeClass('flex').hide();
                    }
                }

                hideGroupDiv();
            });

            // Release the lock after the search is done
            self.packagesearchlock = false;

            mylayout.hideLoading();
        }, 1000);
    }

    /**
     * Export a list of hosts to a CSV format
     * @param {*} hosts
     */
    export(hosts)
    {
        myalert.print('Exporting hosts...');

        ajaxRequest(
            // Controller:
            'host/export',
            // Action:
            'export',
            // Data:
            {
                hosts: hosts,
            },
            // Print success alert:
            false,
            // Print error alert:
            true
        ).then(function () {
            // Convert the JSON response to CSV format, replacing null values with empty strings and escaping quotes
            const lines = JSON.parse(jsonValue.message).map(row => row.map(field => `"${(field ?? '').toString().replace(/"/g,'""')}"`).join(','));

            // Join the lines with newline characters
            const csv = lines.join('\n');
            const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);

            // Create a link to download the CSV file
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', 'hosts.csv');
            document.body.appendChild(link);

            // Trigger the download
            link.click();

            // Clean up
            document.body.removeChild(link);
    });
}
}
