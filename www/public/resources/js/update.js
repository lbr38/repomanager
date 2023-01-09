/**
 *  Event: acquit repomanager update log and close window
 */
$(document).on('click','#update-continue-btn',function () {
    /**
     *  Acquit and close window
     */
    continueUpdate();

    /**
     *  Reload current page
     */
    setTimeout(function () {
        window.location = window.location.href.split("?")[0];
    }, 500);
});

/**
 *  Ajax: acquit repomanager update log and close window
 */
function continueUpdate()
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "general",
            action: "continueUpdate"
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
        },
        error : function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
        },
    });
}