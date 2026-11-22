/**
 *  Generic search input: free text and filters (filter=value, filter!=value), with suggestions, a clear button
 *  and the search kept in the page URL (?search=...)
 *  Child classes (HostSearch, RepoSearch) define the configuration below and the filter() method that shows / hides the items
 */
class Search
{
    /**
     * Search input, clear button, suggestions container, results count and searched items
     */
    static selectors = {
        input: null,
        clear: null,
        suggestions: null,
        count: null,
        item: null
    };

    /**
     * Filters: filter name => item HTML attribute
     */
    static filters = {};

    /**
     * Item HTML attributes holding a list of values: attribute => separator
     */
    static listAttributes = {};

    /**
     * Item HTML attributes whose values must match a filter exactly (instead of containing it)
     */
    static exactAttributes = [];

    /**
     * Additional filters handled by the child class (see parseParam()), and flags (words without value)
     */
    static extraFilters = [];
    static flags = [];

    /**
     * Item name (singular, plural), used in the results count and in the suggestions, and suggestions notes
     */
    static itemName = ['item', 'items'];
    static notes = {
        filter: 'filter',
        extraFilter: 'filter',
        flag: 'flag'
    };

    /**
     * Search debounce delay (ms) while the user is typing
     */
    static delay = 200;

    // Debounce timer, suggestions currently printed under the search input, the token they apply to and the highlighted one
    static timer = null;
    static suggestions = [];
    static suggestionToken = null;
    static suggestionIndex = -1;

    /**
     * Search, debounced while the user is typing
     * @param {boolean} immediate
     */
    static search(immediate = false)
    {
        clearTimeout(this.timer);

        const query = this.parse($(this.selectors.input).val());

        if (immediate) {
            this.apply(query);
            return;
        }

        this.timer = setTimeout(() => {
            this.apply(query);
        }, this.getDelay(query));
    }

    /**
     * Get the search debounce delay, child classes can wait longer for a search requiring a request to the server
     * @param {object} query
     * @returns {number}
     */
    static getDelay(query)
    {
        return this.delay;
    }

    /**
     * Restore the search from the page URL (?search=...) if the input is empty, then apply it
     * Used on page load and after the items list has been reloaded
     */
    static restore()
    {
        const input = $(this.selectors.input);

        if (!input.length) {
            return;
        }

        this.updatePadding();

        if (input.val().trim() === '') {
            const search = new URLSearchParams(window.location.search).get('search');

            if (search) {
                input.val(search);
            }
        }

        if (input.val().trim() !== '') {
            this.search(true);
        }
    }

    /**
     * Parse the search input
     * Syntax: free text, filters (os=ubuntu, os!=ubuntu) and flags (strict)
     * Values containing spaces must be quoted: os="Linux Mint"
     * @param {string} input
     * @returns {object}
     */
    static parse(input)
    {
        const query = {
            raw: (input || '').trim(),
            terms: [],
            filters: [],
            flags: [],
            params: {}
        };
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

                if (key in this.filters) {
                    query.filters.push({ attribute: this.filters[key], value: value.toUpperCase(), negate: negate });
                } else if (!this.parseParam(query, key, value, negate)) {
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

            // Single word: flag or free text
            if (this.flags.includes(match[6].toLowerCase())) {
                query.flags.push(match[6].toLowerCase());
            } else {
                query.terms.push(match[6].toUpperCase());
            }
        }

        return this.completeQuery(query);
    }

    /**
     * Parse an additional filter (see extraFilters), to override in child classes
     * @param {object} query
     * @param {string} key
     * @param {string} value
     * @param {boolean} negate
     * @returns {boolean} true if the filter has been handled
     */
    static parseParam(query, key, value, negate)
    {
        return false;
    }

    /**
     * Complete the parsed query (e.g. from its params and flags), to override in child classes
     * @param {object} query
     * @returns {object}
     */
    static completeQuery(query)
    {
        return query;
    }

    /**
     * Apply a parsed search
     * @param {object} query
     */
    static apply(query)
    {
        this.updateInput(query.raw);
        this.filter(query);
    }

    /**
     * Show the items matching the search, hide the others, to define in child classes
     * @param {object} query
     */
    static filter(query)
    {
    }

    /**
     * Check if the search has terms or filters
     * @param {object} query
     * @returns {boolean}
     */
    static isActive(query)
    {
        return query.terms.length > 0 || query.filters.length > 0;
    }

    /**
     * Get the values of an item HTML attribute (split if it holds a list of values)
     * @param {jQuery} item
     * @param {string} attribute
     * @returns {array}
     */
    static getValues(item, attribute)
    {
        const value = (item.attr(attribute) || '').trim();

        if (!(attribute in this.listAttributes)) {
            return [value];
        }

        return value.split(this.listAttributes[attribute]).map(function (item) {
            return item.trim();
        }).filter(function (item) {
            return item !== '';
        });
    }

    /**
     * Check if an item matches all the filters (or does not match them when negated) and all the free text terms
     * Free text is searched in the item text and in its attributes (some of them are not always printed)
     * @param {jQuery} item
     * @param {object} query
     * @returns {boolean}
     */
    static matches(item, query)
    {
        const filtersMatch = query.filters.every((filter) => {
            const exact = this.exactAttributes.includes(filter.attribute);
            const matches = this.getValues(item, filter.attribute).some(function (value) {
                value = value.toUpperCase();
                return exact ? value === filter.value : value.indexOf(filter.value) > -1;
            });

            return filter.negate ? !matches : matches;
        });

        if (!filtersMatch || query.terms.length === 0) {
            return filtersMatch;
        }

        const text = (item.text() + ' ' + Object.values(this.filters).map((attribute) => {
            return this.getValues(item, attribute).join(' ');
        }).join(' ')).toUpperCase();

        return query.terms.every(function (term) {
            return text.indexOf(term) > -1;
        });
    }

    /**
     * Print the number of items found
     * @param {number} count
     * @param {number} total
     * @param {boolean} active
     */
    static printCount(count, total, active)
    {
        if (active) {
            $(this.selectors.count).text(count + ' / ' + total + ' ' + this.itemName[total > 1 ? 1 : 0]).removeClass('hide');
        } else {
            $(this.selectors.count).text('').addClass('hide');
        }

        this.updatePadding();
    }

    /**
     * Keep the input text clear of the elements printed over its right side (results count, clear button, help icon)
     */
    static updatePadding()
    {
        const input = $(this.selectors.input);
        const actions = input.siblings('.search-input-actions');

        if (actions.length) {
            input.css('padding-right', actions.outerWidth() + 20);
        }
    }

    /**
     * Show the clear button if there is a search and keep the search in the page URL
     * @param {string} search
     */
    static updateInput(search)
    {
        $(this.selectors.clear).toggleClass('hide', search === '');
        this.updateUrl(search);
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
        const input = $(this.selectors.input);
        const isFlag = this.flags.includes(filter);
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
            this.search();
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
        this.updateSuggestions();
    }

    /**
     * Add a filter with its value (e.g. tag=web) to the search input, if not already present, and search
     * @param {string} filter
     * @param {string} value
     */
    static addFilterValue(filter, value)
    {
        const input = $(this.selectors.input);
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
        this.search(true);
    }

    /**
     * Get the token being typed at the cursor position: a filter value (os=ubu) or a word that may be a filter name (o)
     * @returns {object|null}
     */
    static currentToken()
    {
        const input = $(this.selectors.input)[0];

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
     * Get the values of a filter found in the items list, with the number of items for each value
     * @param {string} attribute
     * @param {string} partial
     * @returns {array}
     */
    static getFilterValues(attribute, partial)
    {
        const values = {};

        $(this.selectors.item).each((index, element) => {
            // Count an item only once per value
            const itemValues = [...new Set(this.getValues($(element), attribute))];

            itemValues.forEach(function (itemValue) {
                const key = itemValue.toUpperCase();

                if (itemValue === '' || key.indexOf(partial.toUpperCase()) === -1) {
                    return;
                }

                if (!(key in values)) {
                    values[key] = { value: itemValue, count: 0 };
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
        const container = $(this.selectors.suggestions);
        const token = this.currentToken();
        let suggestions = [];

        if (!container.length) {
            return;
        }

        // Suggest the values of a filter
        if (token && token.type === 'value' && token.key in this.filters) {
            suggestions = this.getFilterValues(this.filters[token.key], token.partial).map((item) => {
                const value = /[\s"]/.test(item.value) ? '"' + item.value.replace(/"/g, '') + '"' : item.value;

                return {
                    label: item.value,
                    note: item.count + ' ' + this.itemName[item.count > 1 ? 1 : 0],
                    insert: token.key + token.operator + value + ' '
                };
            });
        }

        // Suggest filter names (from 2 characters, to avoid suggesting on every free text search)
        if (token && token.type === 'filter' && token.partial.length >= 2) {
            Object.keys(this.filters).concat(this.extraFilters).forEach((name) => {
                if (name.startsWith(token.partial)) {
                    suggestions.push({ label: name + '=', note: this.extraFilters.includes(name) ? this.notes.extraFilter : this.notes.filter, insert: name + '=' });
                }
            });

            this.flags.forEach((flag) => {
                if (flag.startsWith(token.partial) && flag !== token.partial) {
                    suggestions.push({ label: flag, note: this.notes.flag, insert: flag + ' ' });
                }
            });
        }

        suggestions = suggestions.slice(0, 10);

        this.suggestions = suggestions;
        this.suggestionToken = token;
        this.suggestionIndex = -1;

        if (suggestions.length === 0) {
            container.html('').addClass('hide');
            return;
        }

        container.html('');

        suggestions.forEach(function (suggestion, index) {
            $('<div class="search-suggestion"><span></span><span class="note"></span></div>')
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
        this.suggestions = [];
        this.suggestionIndex = -1;
        $(this.selectors.suggestions).html('').addClass('hide');
    }

    /**
     * Check if suggestions are printed
     * @returns {boolean}
     */
    static hasSuggestions()
    {
        return this.suggestions.length > 0;
    }

    /**
     * Highlight the next (1) or previous (-1) suggestion
     * @param {number} direction
     */
    static moveSuggestion(direction)
    {
        const total = this.suggestions.length;
        const items = $(this.selectors.suggestions).find('.search-suggestion');

        this.suggestionIndex = (this.suggestionIndex + direction + total + 1) % (total + 1);

        // Index equal to total means no suggestion highlighted
        if (this.suggestionIndex === total) {
            this.suggestionIndex = -1;
        }

        items.removeClass('active');

        if (this.suggestionIndex > -1) {
            const item = items.filter('[index=' + this.suggestionIndex + ']').addClass('active');
            item[0].scrollIntoView({ block: 'nearest' });
        }
    }

    /**
     * Insert a suggestion in the search input, in place of the token being typed
     * @param {number} index
     */
    static pickSuggestion(index)
    {
        const suggestion = this.suggestions[index];
        const token = this.suggestionToken;
        const input = $(this.selectors.input)[0];

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
        this.updateSuggestions();
        this.search();
    }

    /**
     * Clear the search and show all items
     */
    static clear()
    {
        clearTimeout(this.timer);
        this.hideSuggestions();
        $(this.selectors.input).val('');
        this.updateInput('');
        this.filter(this.parse(''));
    }

    /**
     * Get the HTML of a list of clickable filters, to print in the search help tooltip
     * @param {array} filters
     * @returns {string}
     */
    static filtersList(filters)
    {
        let html = '<div class="flex align-item-center flex-wrap column-gap-5 row-gap-8 max-width-500">';

        filters.forEach(function (filter) {
            html += '<code class="search-filter" filter="' + filter.split('=')[0] + '" title="Add this filter to the search">' + filter + '</code>';
        });

        return html + '</div>';
    }

    /**
     * Bind the search input events and restore the search from the page URL on page load
     */
    static init()
    {
        const input = this.selectors.input;

        $(() => {
            this.restore();
        });

        // Search and print suggestions while typing
        $(document).on('input', input, () => {
            this.updateSuggestions();
            this.search();
        });

        // Print suggestions when the cursor moves in the search input
        $(document).on('click focus', input, () => {
            this.updateSuggestions();
        });

        $(document).on('keyup', input, (e) => {
            if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) {
                this.updateSuggestions();
            }
        });

        // Hide suggestions when leaving the search input
        $(document).on('blur', input, () => {
            this.hideSuggestions();
        });

        // Keyboard navigation in the suggestions, Enter searches immediately, Escape clears the search
        $(document).on('keydown', input, (e) => {
            if (this.hasSuggestions()) {
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    this.moveSuggestion(e.key === 'ArrowDown' ? 1 : -1);
                    return;
                }

                // Tab picks the highlighted suggestion, or the first one
                if (e.key === 'Tab') {
                    e.preventDefault();
                    this.pickSuggestion(Math.max(this.suggestionIndex, 0));
                    return;
                }

                // Enter picks the highlighted suggestion, if any
                if (e.key === 'Enter' && this.suggestionIndex > -1) {
                    e.preventDefault();
                    this.pickSuggestion(this.suggestionIndex);
                    return;
                }

                // First Escape only hides the suggestions
                if (e.key === 'Escape') {
                    this.hideSuggestions();
                    return;
                }
            }

            if (e.key === 'Enter') {
                this.hideSuggestions();
                this.search(true);
            }
            if (e.key === 'Escape') {
                this.clear();
            }
        });

        // Pick a suggestion (mousedown to keep the focus in the search input)
        $(document).on('mousedown', this.selectors.suggestions + ' .search-suggestion', (e) => {
            e.preventDefault();
            this.pickSuggestion(parseInt($(e.currentTarget).attr('index')));
        });

        // Clear the search
        $(document).on('click', this.selectors.clear, () => {
            this.clear();
            $(input).trigger('focus');
        });

        // Click on a filter in the search help tooltip, add the filter to the search input
        $(document).on('click', '.search-filter', (e) => {
            this.addFilter($(e.currentTarget).attr('filter'));

            // Hide the tooltip so that the search input is visible
            mytooltip.hide();
        });
    }
}
