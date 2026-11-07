class Select2
{
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
