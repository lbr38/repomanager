/**
 *  Event: When a "select all" checkbox is clicked
 */
$(document).on('click','.select-all-checkbox',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    // Select/deselect all child checkboxes
    mycheckbox.selectAll($(this).attr('checkbox-id'));
});

/**
 *  Event: When a child checkbox is clicked
 */
$(document).on('click','.child-checkbox',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    // Select/deselect all child checkboxes
    mycheckbox.select($(this));
});

/**
 *  Event: when a checkbox is checked/unchecked, save its state to sessionStorage
 *  This is useful when containers are reloaded and we want to keep the checkbox state
 */
$(document).on('click','input[type="checkbox"]',function () {
    // If the checkbox as no unique id (cid), ignore it
    if ($(this).attr('cid') === undefined) {
        return;
    }

    // Save the checkbox state (checked or unchecked) to sessionStorage
    sessionStorage.setItem('checkbox/' + $(this).attr('cid') + '/checked', $(this).is(':checked'));
});