/**
 *  Reload header to get running operations
 */
setInterval(function () {
    reloadHeader();
}, 5000);

/**
 *  Slide panel opening
 */
$(document).on('click','.slide-panel-btn',function () {
    var name = $(this).attr('slide-panel');
    openPanel(name);
});

/**
 *  Slide panel closing
 */
$(document).on('click','.slide-panel-close-btn',function () {
    closePanel();
});

/**
 *  Event: mark log as read
 */
$(document).on('click','.acquit-log-btn',function () {
    var id = $(this).attr('log-id');

    acquitLog(id);
});

/**
 *  Event: hide slided window on escape button press
 */
$(document).keyup(function (e) {
    if (e.key === "Escape") {
        closePanel();
    }
});

/**
 * Ajax: Mark log as read
 * @param {string} id
 */
function acquitLog(id)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "general",
            action: "acquitLog",
            id: id
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            reloadContentById('general-log-container');
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}