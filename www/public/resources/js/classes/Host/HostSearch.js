class HostSearch
{
    /**
     * Search hosts based on the input value
     * @returns
     */
    static search()
    {
        // Quit if the input is empty
        if (!$('#search-host-input').val()) {
            HostSearch.showAll();
            return;
        }

        HostSearch.showSearchSpinner();

        setTimeout(function () {
            let search = $('#search-host-input').val().toUpperCase();
            const filterValues = {};

            // Attribute name mapping for HTML attributes
            const attributeMap = {
                'HOSTNAME': 'hostname',
                'OS': 'os',
                'OS-VERSION': 'os_version',
                'OS-FAMILY': 'os_family',
                'TYPE': 'type',
                'KERNEL': 'kernel',
                'ARCH': 'arch',
                'PROFILE': 'profile',
                'ENV': 'env',
                'AGENT-VERSION': 'agent-version',
                'REBOOT-REQUIRED': 'reboot-required',
                'COMPLIANT': 'compliant'
            };

            // Show all group containers (in case they were hidden during a previous search)
            $('.hosts-group-container').show();

            //  Hide all host lines, only those corresponding to the search will be re-displayed
            $('.host-line').hide();

            /**
             * Parse filter parameters from search input.
             * Supports quoted values with spaces: os="Linux Mint" type=ubuntu
             */
            Object.keys(attributeMap).forEach(function (filterKey) {
                const regex = new RegExp(filterKey + '=(?:"([^"]+)"|([^" ]+))');
                const match = search.match(regex);
                if (match) {
                    const filterValue = match[1] !== undefined ? match[1] : match[2];
                    filterValues[filterKey] = filterValue.toUpperCase();
                    // Remove the filter from the global search
                    search = search.replace(regex, '').replace(/\s{2,}/g, ' ').trim();
                }
            });

            // Start with all hosts
            let hosts = $('.host-line');

            /**
             * Apply attribute-based filters
             */
            Object.keys(filterValues).forEach(function (filterKey) {
                const attrName = attributeMap[filterKey];
                const filterValue = filterValues[filterKey];

                hosts = hosts.filter(function () {
                    const attrValue = $(this).attr(attrName);
                    return attrValue && attrValue.toUpperCase().indexOf(filterValue) > -1;
                });
            });

            /**
             * Process each host line to check if it matches the free text search term
             */
            hosts.each(function () {
                const textContent = $(this).text().toUpperCase();
                if (textContent.indexOf(search) > -1) {
                    $(this).addClass('flex').show();
                }
            });

            // Hide the search spinner and show the hosts container
            HostSearch.hideSearchSpinner();

            // Hide group divs whose all hosts have been hidden
            HostSearch.hideGroupDiv();
        }, 500);  
    }

    /**
     * Search hosts with a specific package
     * @returns
     */
    static searchPackage()
    {
        var name;
        var version = null;
        var strictName = false;
        var strictVersion = false;
        var absent = false;

        // If a search is already in progress, exit
        if (self.packagesearchlock === true) {
            return;
        }

        // Set a lock to prevent multiple searches at the same time that could slow down the database
        self.packagesearchlock = true;

        // If the input is empty, quit
        if (!$("#search-package-input").val()) {
            self.packagesearchlock = false;
            HostSearch.showAll();
            return;
        }

        HostSearch.showSearchSpinner();

        // Use a setTimeout to give the user time to finish typing before searching
        setTimeout(function () {
            // If the input is empty, quit
            if (!$("#search-package-input").val()) {
                self.packagesearchlock = false;
                HostSearch.showAll();
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
                // If the part is 'strict-name', enable strict package name matching
                } else if (part === 'strict-name') {
                    strictName = true;
                // If the part is 'strict-version', enable strict package version matching
                } else if (part === 'strict-version') {
                    strictVersion = true;
                // If the part is 'strict', enable strict matching for both name and version
                } else if (part === 'strict') {
                    strictName = true;
                    strictVersion = true;
                // If the part is 'absent', search for hosts on which the package is NOT installed
                } else if (part === 'absent') {
                    absent = true;
                }
            });

            // Wait until name= is fully defined
            // This is to allow the user to finish typing before searching
            if (name === undefined || name.trim() === '') {
                self.packagesearchlock = false;
                return;
            }

            // On every input, (re)-display all hidden elements and remove any info in 'host-additionnal-info'
            $('.hosts-group-container, .host-line').show();
            $('div.host-additionnal-info').html('');
            $('div.host-additionnal-info').hide();

            HostSearch.showSearchSpinner();

            // Get hosts with the package
            ajaxRequest(
                // Controller:
                'host',
                // Action:
                'get-by-package',
                // Data:
                {
                    package: name,
                    version: version,
                    strictName: strictName ? 1 : 0, // Convert strict to PHP-compatible boolean (1 or 0)
                    strictVersion: strictVersion ? 1 : 0, // Convert strict to PHP-compatible boolean (1 or 0)
                    absent: absent ? 1 : 0 // Return hosts on which the package is NOT installed
                },
                // Print success alert:
                false,
                // Print error alert:
                true
            ).then(() => {
                const results = jQuery.parseJSON(jsonValue.message);

                // Get all host IDs that have matching packages
                const matchingHostIds = results.map(host => host.id.toString());

                // Hide all hosts that don't have matching packages
                $('.host-line').each(function () {
                    if (!matchingHostIds.includes($(this).attr('hostid'))) {
                        $(this).removeClass('flex').hide();
                    }
                });

                // Show hosts with matching packages and display results
                results.forEach(function (host) {
                    // When searching for absent packages, there are no package details to display
                    if (host.packages) {
                        var packagesFound = '';

                        for (const [packageName, packageVersion] of Object.entries(host.packages)) {
                            packagesFound += '<div class="flex align-item-center column-gap-5"><img src="/assets/icons/package.svg" class="icon-np">   <span>' + packageName + ' (' + packageVersion + ')</span></div>';
                        }

                        $('.host-line[hostid=' + host.id + ']').find('div.host-additionnal-info').html('<h6>RESULTS</h6>' + packagesFound);
                        $('.host-line[hostid=' + host.id + ']').find('div.host-additionnal-info').css('display', 'flex');
                    }

                    $('.host-line[hostid=' + host.id + ']').show();
                });

                // Finally, hide the search spinner and show the hosts container
                HostSearch.hideSearchSpinner();

                // Hide group divs whose all divs have been hidden
                HostSearch.hideGroupDiv();
            });

            // Release the lock after the search is done
            self.packagesearchlock = false;
        }, 1000);
    }

    /**
     * Show all hosts
     */
    static showAll()
    {
        // Hide any additionnal info
        $('div.host-additionnal-info').html('');
        $('div.host-additionnal-info').hide();

        // Hide loading spinner
        $('#hosts-search').removeClass('flex');
        $('#hosts-search').addClass('hide');

        $('.hosts-group-container, .host-line').show();
        $('#hosts').show();
    }

    /**
     * Show search loading spinner
     */
    static showSearchSpinner()
    {
        $('#hosts-search').removeClass('hide');
        $('#hosts-search').addClass('flex');
        $('#hosts').hide();
    }

    /**
     * Hide search loading spinner
     */
    static hideSearchSpinner()
    {
        $('#hosts-search').removeClass('flex');
        $('#hosts-search').addClass('hide');
        $('#hosts').show();
    }

    /**
     *  Hide host groups whose all hosts have been hidden (during a search)
     */
    static hideGroupDiv()
    {
        // For each 'hosts-group-
        // container' div we search all host lists
        $('.hosts-group-container').each(function () {
            // If the div has a class hosts-table-empty then it is necessarily empty ("no host in this group"), so we hide the entire div of the search result
            if ($(this).find('.hosts-table-empty').length == 1) {
                $(this).hide();

            /**
             *  If the list contains hosts, then we check if there is at least 1 displayed div (which corresponds to the search result)
             *  If so, we leave the div displayed
             *  If not, we hide the entire div
             */
            } else {
                if ($(this).find('.host-line:visible').length == 0) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            }
        });
    }
}
