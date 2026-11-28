/**
 *  Hosts search input (see Search for the generic part: parsing, suggestions, URL, clear button)
 *  Host filters are applied locally, package filters require a request to the server
 */
class HostSearch extends Search
{
    static selectors = {
        input: '#search-host-input',
        clear: '#search-host-clear',
        suggestions: '#search-host-suggestions',
        count: '#search-host-count',
        item: '.host-line'
    };

    /**
     * Host filters: filter name => host line HTML attribute
     */
    static filters = {
        'hostname': 'hostname',
        'os': 'os',
        'os-version': 'os_version',
        'os-family': 'os_family',
        'type': 'type',
        'kernel': 'kernel',
        'arch': 'arch',
        'profile': 'profile',
        'env': 'env',
        'agent-status': 'agent-status',
        'agent-version': 'agent-version',
        'reboot-required': 'reboot_required',
        'compliant': 'compliant',
        'tag': 'tags'
    };

    /**
     * Host line HTML attributes holding a comma-separated list of values, a filter must match one of them exactly
     */
    static listAttributes = { 'tags': ',' };
    static exactAttributes = ['tags'];

    /**
     * Package filters with a value, and package flags (no value)
     */
    static extraFilters = ['package', 'version'];
    static flags = ['strict', 'strict-name', 'strict-version', 'absent'];

    static itemName = ['host', 'hosts'];
    static notes = {
        filter: 'host filter',
        extraFilter: 'package filter',
        flag: 'package flag'
    };

    // Id of the last package request (to ignore outdated responses) and package results cache
    static requestId = 0;
    static cache = {};
    static cacheTtl = 60000;

    /**
     * A package search requires a request to the server, so it waits longer than a local search
     * @param {object} query
     * @returns {number}
     */
    static getDelay(query)
    {
        return query.package ? 600 : 200;
    }

    /**
     * Parse package filters: package=nginx, package!=nginx, version=1.2
     * @param {object} query
     * @param {string} key
     * @param {string} value
     * @param {boolean} negate
     * @returns {boolean}
     */
    static parseParam(query, key, value, negate)
    {
        if (key === 'package') {
            query.params.package = value;
            query.params.absent = negate;
            return true;
        }

        if (key === 'version') {
            // Excluding a version is not supported by the server, version!= is ignored
            if (!negate) {
                query.params.version = value;
            }
            return true;
        }

        return false;
    }

    /**
     * Build the package search from the package filters and flags (flags are only used along with a package name)
     * @param {object} query
     * @returns {object}
     */
    static completeQuery(query)
    {
        const flags = query.flags;

        query.package = null;

        if (query.params.package) {
            query.package = {
                package: query.params.package,
                version: query.params.version || '',
                strictName: flags.includes('strict') || flags.includes('strict-name') ? 1 : 0,
                strictVersion: flags.includes('strict') || flags.includes('strict-version') ? 1 : 0,
                absent: query.params.absent || flags.includes('absent') ? 1 : 0
            };
        }

        return query;
    }

    /**
     * Apply a parsed search to the hosts list
     * @param {object} query
     */
    static apply(query)
    {
        const requestId = ++HostSearch.requestId;

        HostSearch.updateInput(query.raw);

        // No package filter: filter locally
        if (!query.package) {
            HostSearch.hideSearchSpinner();
            HostSearch.filter(query, null);
            return;
        }

        // Package filter: get the hosts that match from the server (or from the cache)
        const cacheKey = JSON.stringify(query.package);
        const cached = HostSearch.cache[cacheKey];

        if (cached && Date.now() - cached.time < HostSearch.cacheTtl) {
            HostSearch.filter(query, cached.results);
            return;
        }

        HostSearch.showSearchSpinner();

        ajaxRequest(
            // Controller:
            'host',
            // Action:
            'get-by-package',
            // Data:
            query.package,
            // Print success alert:
            false,
            // Print error alert:
            true
        ).then(() => {
            const results = jQuery.parseJSON(jsonValue.message);

            HostSearch.cache[cacheKey] = { time: Date.now(), results: results };

            // Ignore the response if the search has changed in the meantime
            if (requestId !== HostSearch.requestId) {
                return;
            }

            HostSearch.hideSearchSpinner();
            HostSearch.filter(query, results);
        }).catch(() => {
            if (requestId === HostSearch.requestId) {
                HostSearch.hideSearchSpinner();
            }
        });
    }

    /**
     * Show the hosts matching the search, hide the others
     * @param {object} query
     * @param {array|null} packageResults hosts matching the package filter, null if no package filter
     */
    static filter(query, packageResults = null)
    {
        const hosts = $('.host-line');
        const active = HostSearch.isActive(query) || packageResults !== null;
        let packagesByHost = null;
        let count = 0;

        // Clear package results of a previous search
        $('div.host-additionnal-info').html('').hide();

        if (packageResults !== null) {
            packagesByHost = {};
            packageResults.forEach(function (host) {
                packagesByHost[host.id] = host.packages || null;
            });
        }

        hosts.each(function () {
            const host = $(this);
            const id = host.attr('hostid');

            // Package filter, then host filters and free text
            const visible = (packagesByHost === null || id in packagesByHost) && HostSearch.matches(host, query);

            host.toggle(visible);

            if (!visible) {
                return;
            }

            count++;

            // Print the matching packages (not when searching for absent packages)
            if (packagesByHost !== null && packagesByHost[id]) {
                const info = host.find('div.host-additionnal-info').append('<h6>PACKAGES FOUND</h6>');

                for (const [packageName, packageVersion] of Object.entries(packagesByHost[id])) {
                    $('<div class="flex align-item-center column-gap-5"><img src="/assets/icons/package.svg" class="icon-np"><span></span></div>')
                        .find('span').text(packageName + ' (' + packageVersion + ')').end()
                        .appendTo(info);
                }

                info.css('display', 'flex');
            }
        });

        // Print the number of hosts found
        HostSearch.printCount(count, hosts.length, active);

        HostSearch.hideGroupDiv();
    }

    /**
     * Clear the search and show all hosts (ignore a pending package request)
     */
    static clear()
    {
        HostSearch.requestId++;
        HostSearch.hideSearchSpinner();
        super.clear();
    }

    /**
     * Show search loading spinner
     */
    static showSearchSpinner()
    {
        $('#hosts-search').removeClass('hide').addClass('flex');
        $('#hosts').hide();
    }

    /**
     * Hide search loading spinner
     */
    static hideSearchSpinner()
    {
        $('#hosts-search').removeClass('flex').addClass('hide');
        $('#hosts').show();
    }

    /**
     *  Hide host groups whose all hosts have been hidden (during a search)
     */
    static hideGroupDiv()
    {
        const active = $('#search-host-input').val().trim() !== '';

        $('.hosts-group-container').each(function () {
            // No search: show all groups
            if (!active) {
                $(this).show();
                return;
            }

            // Empty group ("no host in this group"), or no host matching the search: hide the group
            const empty = $(this).find('.hosts-table-empty').length == 1;
            const visible = $(this).find('.host-line').filter(function () {
                return this.style.display !== 'none';
            }).length > 0;

            $(this).toggle(!empty && visible);
        });
    }
}
