/**
 *  Event: print notification div
 */
$(document).on('click','#print-notification-btn',function () {
    openSlide('#notification-div');
});

/**
 *  Event: hide notification div
 */
$(document).on('click','#hide-notification-btn',function () {
    closeSlide('#notification-div');
});

/**
 *  Event: mark notification as read
 */
$(document).on('click','.acquit-notification-btn',function () {
    var id = $(this).attr('notification-id');

    acquitNotification(id);
});

/**
 * Ajax: Mark notification as read
 * @param {string} id
 */
function acquitNotification(id)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "notification",
            action: "acquit",
            id: id
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
            reloadContentById('notification-reloadable-div');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}