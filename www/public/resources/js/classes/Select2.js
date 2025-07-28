class Select2
{
    /**
     *  Convert select tag to a select2 by specified element
     *  @param {*} element
     */
    convert(element, placeholder = 'Select...', allowNewOptions = false, hideSearch = true)
    {
        $(element).select2({
            placeholder: placeholder,
            /* Close after selecting an option */
            closeOnSelect: false,
            /* If tags is true, allow adding new options */
            tags: allowNewOptions,
            /* Search box */
            minimumResultsForSearch: hideSearch ? Infinity : 0,
            /* Clear button */
            allowClear: true
        });
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
        $(select).select2({
            data: data,
            placeholder: placeholder,
            /* Close after selecting an option */
            closeOnSelect: false,
            /* If tags is true, allow adding new options */
            tags: allowNewOptions,
            /* Search box */
            minimumResultsForSearch: hideSearch ? Infinity : 0,
            /* Clear button */
            allowClear: true
        })
    }
}
