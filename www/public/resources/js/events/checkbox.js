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