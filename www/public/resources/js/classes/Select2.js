class Select2
{
    constructor()
    {
        /**
         *  Keep the dropdown inside the window when it is opened and when the selection box size changes (option added or removed)
         *  setTimeout lets select2 render the selection and position the dropdown first
         */
        $(document).on('select2:open select2:select select2:unselect', 'select', (e) => {
            setTimeout(() => this.fitDropdown(e.target), 0);
        });
    }

    /**
     *  Limit the dropdown results height to the space available above or below the select, so that it never overflows the window
     *  @param {*} element
     */
    fitDropdown(element)
    {
        const instance = $(element).data('select2');

        if (!instance || !instance.isOpen()) {
            return;
        }

        const margin = 10;
        const container = instance.$container[0].getBoundingClientRect();
        const spaceBelow = window.innerHeight - container.bottom - margin;
        const spaceAbove = container.top - margin;

        // Height of the dropdown parts other than the results list (search field, borders)
        const otherHeight = instance.$results.closest('.select2-dropdown').outerHeight() - instance.$results.outerHeight();

        // Never higher than the default max height (500px, see select2.css) and never lower than 100px to keep the list usable
        const maxHeight = Math.max(100, Math.min(500, Math.max(spaceBelow, spaceAbove) - otherHeight));

        instance.$results.css('max-height', maxHeight + 'px');

        // Let select2 recompute the dropdown position (above or below the select) with its new height
        if (typeof instance.dropdown._positionDropdown === 'function') {
            instance.dropdown._positionDropdown();
        }
    }

    /**
     *  Return select2 options
     *  @param {*} placeholder
     *  @param {*} allowNewOptions
     *  @param {*} hideSearch
     */
    options(placeholder, allowNewOptions, hideSearch)
    {
        return {
            placeholder: placeholder,
            /* Close after selecting an option */
            closeOnSelect: false,
            /* If tags is true, allow adding new options */
            tags: allowNewOptions,
            /**
             *  Do not create a new option when the typed value already exists: select2 only compares the typed value with the
             *  options text, an option whose text differs from its value (e.g. 'bookworm (Debian 12)') would be selected twice
             *  (the new option and the existing one, as they share the same value)
             */
            createTag: function (params) {
                const term = (params.term || '').trim();

                if (term === '') {
                    return null;
                }

                const exists = this.$element.find('option').filter(function () {
                    return this.value === term && this.getAttribute('data-select2-tag') !== 'true';
                }).length > 0;

                if (exists) {
                    return null;
                }

                return {
                    id: term,
                    text: term
                };
            },
            /* Search box */
            minimumResultsForSearch: hideSearch ? Infinity : 0,
            /* Clear button */
            allowClear: true
        };
    }

    /**
     *  Convert select tag to a select2 by specified element
     *  @param {*} element
     */
    convert(element, placeholder = 'Select...', allowNewOptions = false, hideSearch = true)
    {
        $(element).select2(this.options(placeholder, allowNewOptions, hideSearch));
    }

    /**
     * Update a select2 with new data
     * @param {*} select
     * @param {*} data
     * @param {*} placeholder
     * @param {*} tags
     * @returns
     */
    update(select, data, placeholder = '', allowNewOptions = false, hideSearch = true)
    {
        /**
         *  Quit if the select is not found
         */
        if (!$(select).length) {
            return;
        }

        /**
         *  Clear current select options
         */
        $(select).empty();

        /**
         *  Update select2 with new data
         */
        $(select).select2(Object.assign(this.options(placeholder, allowNewOptions, hideSearch), { data: data }));
    }
}
