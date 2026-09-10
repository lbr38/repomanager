/**
 *  Event: edit user personnal informations
 */
$(document).on('submit','form#repos-list-preferences',function (e) {
    e.preventDefault();

    var preferences = {};

    // Get all values from the form
    $(this).find('input').each(function () {
        var name = $(this).attr('name');
        
        // If the input is a checkbox, get its checked state
        if ($(this).attr('type') === 'checkbox') {
            preferences[name] = $(this).is(':checked');
        } else {
            preferences[name] = $(this).val();
        }
    });

    ajaxRequest(
        // Controller:
        'user/preferences',
        // Action:
        'save',
        // Data:
        {
            preferences: preferences
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mycontainer.reload('repos/list');
    });

    return false;
});