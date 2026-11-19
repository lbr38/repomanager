class HostSearch
{
    /**
     * Host filters: filter name => host line HTML attribute
     */
    static hostFilters = {
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
    static listAttributes = ['tags'];

    /**
     * Package filters with a value, and package flags (no value)
     */
    static packageFilters = ['package', 'version'];
    static packageFlags = ['strict', 'strict-name', 'strict-version', 'absent'];

    // Debounce timer, id of the last package request (to ignore outdated responses) and package results cache
    static timer = null;
    static requestId = 0;
    static cache = {};
    static cacheTtl = 60000;

    // Suggestions currently printed under the search input, the token they apply to and the highlighted one
    static suggestions = [];
    static suggestionToken = null;
    static suggestionIndex = -1;

    /**
     * Search hosts, debounced while the user is typing
     * A package search requires a request to the server, so it waits longer than a local search
     * @param {boolean} immediate
     */
    static search(immediate = false)
    {
        clearTimeout(HostSearch.timer);

        const query = HostSearch.parse($('#search-host-input').val());

        if (immediate) {
            HostSearch.apply(query);
            return;
        }

        HostSearch.timer = setTimeout(function () {
            HostSearch.apply(query);
        }, query.package ? 600 : 200);
    }

    /**
     * Restore the search from the page URL (?search=...) if the input is empty, then apply it
     * Used on page load and after the hosts list has been reloaded
     */
    static restore()
    {
        const input = $('#search-host-input');

        if (!input.length) {
            return;
        }

        if (input.val().trim() === '') {
            const search = new URLSearchParams(window.location.search).get('search');

            if (search) {
                input.val(search);
            }
        }

        if (input.val().trim() !== '') {
            HostSearch.search(true);
        }
    }

    /**
     * Parse the search input
     * Syntax: free text, host filters (os=ubuntu, os!=ubuntu), package filters (package=nginx, package!=nginx, version=1.2)
     * and package flags (strict, absent). Values containing spaces must be quoted: os="Linux Mint"
     * @param {string} input
     * @returns {object}
     */
    static parse(input)
    {
        const query = {
            raw: (input || '').trim(),
            terms: [],
            filters: [],
            package: null
        };
        const packageParams = {};
        const flags = [];
        const regex = /([a-z-]+)(!?=)(?:"([^"]*)"?|(\S*))|"([^"]*)"?|(\S+)/gi;
        let match;

        while ((match = regex.exec(query.raw)) !== null) {
            // key=value or key!=value
            if (match[1] !== undefined) {
                const key = match[1].toLowerCase();
                const negate = match[2] === '!=';
                const value = (match[3] !== undefined ? match[3] : match[4]).trim();

                // Filters without value are ignored (the user is still typing)
                if (value === '') {
                    continue;
                }

                if (key in HostSearch.hostFilters) {
                    query.filters.push({ attribute: HostSearch.hostFilters[key], value: value.toUpperCase(), negate: negate });
                } else if (key === 'package') {
                    packageParams.package = value;
                    packageParams.absent = negate;
                } else if (key === 'version') {
                    // Excluding a version is not supported by the server, version!= is ignored
                    if (!negate) {
                        packageParams.version = value;
                    }
                } else {
                    // Unknown filter, search it as free text
                    query.terms.push(match[0].toUpperCase());
                }
                continue;
            }

            // "quoted free text"
            if (match[5] !== undefined) {
                if (match[5].trim() !== '') {
                    query.terms.push(match[5].trim().toUpperCase());
                }
                continue;
            }

            // Single word: package flag or free text
            if (HostSearch.packageFlags.includes(match[6].toLowerCase())) {
                flags.push(match[6].toLowerCase());
            } else {
                query.terms.push(match[6].toUpperCase());
            }
        }

        // Package flags are only used along with a package name
        if (packageParams.package) {
            query.package = {
                package: packageParams.package,
                version: packageParams.version || '',
                strictName: flags.includes('strict') || flags.includes('strict-name') ? 1 : 0,
                strictVersion: flags.includes('strict') || flags.includes('strict-version') ? 1 : 0,
                absent: packageParams.absent || flags.includes('absent') ? 1 : 0
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

        $('#search-host-clear').toggleClass('hide', query.raw === '');
        HostSearch.updateUrl(query.raw);

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
    static filter(query, packageResults)
    {
        const hosts = $('.host-line');
        const active = query.terms.length > 0 || query.filters.length > 0 || packageResults !== null;
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
            let visible = true;

            // Package filter
            if (packagesByHost !== null && !(id in packagesByHost)) {
                visible = false;
            }

            // Host filters, all must match (or must not match when negated)
            if (visible) {
                visible = query.filters.every(function (filter) {
                    const attributeValue = host.attr(filter.attribute);
                    let matches = attributeValue !== undefined && attributeValue.toUpperCase().indexOf(filter.value) > -1;

                    if (HostSearch.listAttributes.includes(filter.attribute)) {
                        matches = (attributeValue || '').toUpperCase().split(',').includes(filter.value);
                    }

                    return filter.negate ? !matches : matches;
                });
            }

            // Free text, all terms must match somewhere in the host line (visible text or attributes, e.g. env is not always printed)
            if (visible && query.terms.length > 0) {
                const text = (host.text() + ' ' + Object.values(HostSearch.hostFilters).map(function (attribute) {
                    return host.attr(attribute) || '';
                }).join(' ')).toUpperCase();

                visible = query.terms.every(function (term) {
                    return text.indexOf(term) > -1;
                });
            }

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
        if (active) {
            $('#search-host-count').text(count + ' / ' + hosts.length + ' host' + (hosts.length > 1 ? 's' : '')).removeClass('hide');
        } else {
            $('#search-host-count').text('').addClass('hide');
        }

        HostSearch.hideGroupDiv();
    }

    /**
     * Keep the search in the page URL (?search=...) so that it can be shared and survives a page reload
     * @param {string} search
     */
    static updateUrl(search)
    {
        const url = new URL(window.location.href);

        if (search) {
            url.searchParams.set('search', search);
        } else {
            url.searchParams.delete('search');
        }

        if (url.href !== window.location.href) {
            history.replaceState(history.state, '', url.href);
        }
    }

    /**
     * Add a filter (e.g. 'os') or a flag (e.g. 'absent') to the search input
     * Filters already present are kept, as filters can be combined
     * @param {string} filter
     */
    static addFilter(filter)
    {
        const input = $('#search-host-input');
        const isFlag = HostSearch.packageFlags.includes(filter);
        let search = input.val();

        // Remove filters left without value (e.g. a filter previously added but not completed)
        search = search.replace(/(^|\s)[a-z-]+!?=(?=\s|$)/gi, ' ').replace(/\s{2,}/g, ' ').trim();

        input.val(search).trigger('focus');

        if (isFlag) {
            // Add the flag if not already present
            if (!new RegExp('(^|\\s)' + filter + '(\\s|$)', 'i').test(search)) {
                search = (search ? search + ' ' : '') + filter;
            }

            input.val(search + ' ');
            input[0].setSelectionRange(search.length + 1, search.length + 1);
            HostSearch.search();
            return;
        }

        // If the filter is already present, select its value so that the user can change it
        const match = new RegExp('(^|\\s)' + filter + '!?=("[^"]*"|[^"\\s]*)', 'i').exec(search);

        if (match) {
            const end = match.index + match[0].length;
            input[0].setSelectionRange(end - match[2].length, end);
            return;
        }

        // Otherwise add the filter at the end, with the cursor ready to type its value (suggestions are printed)
        search = (search ? search + ' ' : '') + filter + '=';
        input.val(search);
        input[0].setSelectionRange(search.length, search.length);
        HostSearch.updateSuggestions();
    }

    /**
     * Add a filter with its value (e.g. tag=web) to the search input, if not already present, and search
     * @param {string} filter
     * @param {string} value
     */
    static addFilterValue(filter, value)
    {
        const input = $('#search-host-input');
        const term = filter + '=' + (/[\s"]/.test(value) ? '"' + value.replace(/"/g, '') + '"' : value);
        const terms = input.val().trim().split(/\s+/).filter(function (item) {
            return item !== '';
        });

        if (!terms.some(function (item) {
            return item.toUpperCase() === term.toUpperCase();
        })) {
            terms.push(term);
        }

        input.val(terms.join(' '));
        HostSearch.search(true);
    }

    /**
     * Get the token being typed at the cursor position: a filter value (os=ubu) or a word that may be a filter name (o)
     * @returns {object|null}
     */
    static currentToken()
    {
        const input = $('#search-host-input')[0];

        // No token if text is selected
        if (input.selectionStart !== input.selectionEnd) {
            return null;
        }

        const value = input.value;
        const before = value.slice(0, input.selectionStart);
        const after = value.slice(input.selectionStart);
        let match;

        // Quoted value: os="Linux Mi
        if ((match = before.match(/(^|\s)([a-z-]+)(!?=)"([^"]*)$/i)) !== null) {
            const closingQuote = after.indexOf('"');

            return {
                type: 'value',
                key: match[2].toLowerCase(),
                operator: match[3],
                partial: match[4],
                start: match.index + match[1].length,
                end: input.selectionStart + (closingQuote > -1 ? closingQuote + 1 : after.length)
            };
        }

        // Unquoted value: os=ubu
        if ((match = before.match(/(^|\s)([a-z-]+)(!?=)([^\s"]*)$/i)) !== null) {
            return {
                type: 'value',
                key: match[2].toLowerCase(),
                operator: match[3],
                partial: match[4],
                start: match.index + match[1].length,
                end: input.selectionStart + after.match(/^\S*/)[0].length
            };
        }

        // Word that may be the beginning of a filter name: o
        if ((match = before.match(/(^|\s)([a-z-]+)$/i)) !== null) {
            return {
                type: 'filter',
                partial: match[2].toLowerCase(),
                start: match.index + match[1].length,
                end: input.selectionStart + after.match(/^\S*/)[0].length
            };
        }

        return null;
    }

    /**
     * Get the values of a host filter found in the hosts list, with the number of hosts for each value
     * @param {string} attribute
     * @param {string} partial
     * @returns {array}
     */
    static getFilterValues(attribute, partial)
    {
        const values = {};

        $('.host-line').each(function () {
            const value = ($(this).attr(attribute) || '').trim();
            const hostValues = HostSearch.listAttributes.includes(attribute) ? value.split(',') : [value];

            hostValues.forEach(function (hostValue) {
                const key = hostValue.toUpperCase();

                if (hostValue === '' || key.indexOf(partial.toUpperCase()) === -1) {
                    return;
                }

                if (!(key in values)) {
                    values[key] = { value: hostValue, count: 0 };
                }

                values[key].count++;
            });
        });

        return Object.values(values).sort(function (a, b) {
            return b.count - a.count || a.value.localeCompare(b.value);
        });
    }

    /**
     * Print the suggestions matching the token being typed
     */
    static updateSuggestions()
    {
        const container = $('#search-host-suggestions');
        const token = HostSearch.currentToken();
        let suggestions = [];

        if (!container.length) {
            return;
        }

        // Suggest the values of a host filter
        if (token && token.type === 'value' && token.key in HostSearch.hostFilters) {
            suggestions = HostSearch.getFilterValues(HostSearch.hostFilters[token.key], token.partial).map(function (item) {
                const value = /[\s"]/.test(item.value) ? '"' + item.value.replace(/"/g, '') + '"' : item.value;

                return {
                    label: item.value,
                    note: item.count + ' host' + (item.count > 1 ? 's' : ''),
                    insert: token.key + token.operator + value + ' '
                };
            });
        }

        // Suggest filter names (from 2 characters, to avoid suggesting on every free text search)
        if (token && token.type === 'filter' && token.partial.length >= 2) {
            Object.keys(HostSearch.hostFilters).concat(HostSearch.packageFilters).forEach(function (name) {
                if (name.startsWith(token.partial)) {
                    suggestions.push({ label: name + '=', note: HostSearch.packageFilters.includes(name) ? 'package filter' : 'host filter', insert: name + '=' });
                }
            });

            HostSearch.packageFlags.forEach(function (flag) {
                if (flag.startsWith(token.partial) && flag !== token.partial) {
                    suggestions.push({ label: flag, note: 'package flag', insert: flag + ' ' });
                }
            });
        }

        suggestions = suggestions.slice(0, 10);

        HostSearch.suggestions = suggestions;
        HostSearch.suggestionToken = token;
        HostSearch.suggestionIndex = -1;

        if (suggestions.length === 0) {
            container.html('').addClass('hide');
            return;
        }

        container.html('');

        suggestions.forEach(function (suggestion, index) {
            $('<div class="search-host-suggestion"><span></span><span class="note"></span></div>')
                .attr('index', index)
                .find('span:first').text(suggestion.label).end()
                .find('span.note').text(suggestion.note).end()
                .appendTo(container);
        });

        container.removeClass('hide');
    }

    /**
     * Hide the suggestions
     */
    static hideSuggestions()
    {
        HostSearch.suggestions = [];
        HostSearch.suggestionIndex = -1;
        $('#search-host-suggestions').html('').addClass('hide');
    }

    /**
     * Check if suggestions are printed
     * @returns {boolean}
     */
    static hasSuggestions()
    {
        return HostSearch.suggestions.length > 0;
    }

    /**
     * Highlight the next (1) or previous (-1) suggestion
     * @param {number} direction
     */
    static moveSuggestion(direction)
    {
        const total = HostSearch.suggestions.length;

        HostSearch.suggestionIndex = (HostSearch.suggestionIndex + direction + total + 1) % (total + 1);

        // Index equal to total means no suggestion highlighted
        if (HostSearch.suggestionIndex === total) {
            HostSearch.suggestionIndex = -1;
        }

        $('.search-host-suggestion').removeClass('active');

        if (HostSearch.suggestionIndex > -1) {
            const item = $('.search-host-suggestion[index=' + HostSearch.suggestionIndex + ']').addClass('active');
            item[0].scrollIntoView({ block: 'nearest' });
        }
    }

    /**
     * Insert a suggestion in the search input, in place of the token being typed
     * @param {number} index
     */
    static pickSuggestion(index)
    {
        const suggestion = HostSearch.suggestions[index];
        const token = HostSearch.suggestionToken;
        const input = $('#search-host-input')[0];

        if (!suggestion || !token) {
            return;
        }

        let rest = input.value.slice(token.end);

        // Avoid a double space when the suggestion already ends with a space
        if (suggestion.insert.endsWith(' ')) {
            rest = rest.replace(/^\s+/, '');
        }

        input.value = input.value.slice(0, token.start) + suggestion.insert + rest;
        input.focus();
        input.setSelectionRange(token.start + suggestion.insert.length, token.start + suggestion.insert.length);

        // A filter name has been inserted: print its values
        HostSearch.updateSuggestions();
        HostSearch.search();
    }

    /**
     * Clear the search and show all hosts
     */
    static clear()
    {
        clearTimeout(HostSearch.timer);
        HostSearch.requestId++;
        HostSearch.hideSuggestions();
        $('#search-host-input').val('');
        $('#search-host-clear').addClass('hide');
        HostSearch.updateUrl('');
        HostSearch.hideSearchSpinner();
        HostSearch.filter(HostSearch.parse(''), null);
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
