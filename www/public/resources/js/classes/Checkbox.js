class Checkbox
{
    /**
     * When the 'Select all' checkbox is clicked, check or uncheck all child checkboxes depending on the status of the 'Select all' checkbox
     * 'Select all' checkbox must have a 'checkbox-id' attribute which must be the same for all child checkboxes
     * @param {*} identifier 
     */
    selectAll(identifier)
    {
        // Get status of the 'Select all' checkbox
        const status = $('input[type="checkbox"][checkbox-id="' + identifier + '"].select-all-checkbox').is(':checked');

        // Get all unchecked child checkboxes when 'Select all' is checked, and all checked child checkboxes when 'Select all' is unchecked
        const checkboxes = $('input[type="checkbox"][checkbox-id="' + identifier + '"].child-checkbox' + (status ? ':not(:checked)' : ':checked'));

        // Count of affected checkboxes
        var count = checkboxes.length;

        // If the 'Select all' checkbox is checked, check the child checkbox and make it visible, else uncheck the child checkbox and remove any custom visibility so it returns to default
        checkboxes.each(function () {
            // If the checkbox is the latest one, directly simulate a click on it to trigger any event attached to it
            if (--count === 0) {
                $(this).trigger('click');
                return;
            }

            if (status) {
                $(this).prop('checked', true);
                $(this).css('opacity', '1');
            } else {
                $(this).prop('checked', false);
                $(this).css('opacity', '');
            }
        });
    }

    /**
     * When a child checkbox is clicked, check if there is at least one checkbox checked, if yes, execute the callback function defined in checkbox.js
     * Each child checkbox must have a 'checkbox-id' attribute which must be the same for all child checkboxes and the 'Select all' checkbox
     * Each child checkbox must have a 'checkbox-data-attribute' attribute which contains the name of the data attribute to retrieve the value
     * @param {*} checkbox 
     * @returns 
     */
    select(checkbox)
    {
        var id = [];

        // Get checkbox identifier
        const identifier = checkbox.attr('checkbox-id');

        // Get the data attribute name which contains the value to send to the callback function
        const dataAttribute = checkbox.attr('checkbox-data-attribute');

        // If the checkbox is checked, make it visible
        if (checkbox.is(':checked')) {
            $(this).css('opacity', '1');
        } else {
            $(this).css('opacity', '');
        }

        // Get all checked checkboxes
        const count = $('input[type="checkbox"][checkbox-id="' + identifier + '"].child-checkbox:checked');

        if (count.length == 0) {
            myconfirmbox.close();
            return;
        }

        // For each checked checkbox, get its data attribute value and add it to the id array
        count.each(function () {
            id.push($(this).attr(dataAttribute));
        });

        // Execute the callback function defined in checkbox.js
        if (typeof checkboxesCallback[identifier] === 'function') {
            checkboxesCallback[identifier](id, count);
        }
    }
}
