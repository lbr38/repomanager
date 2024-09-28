/**
 * Execute an ajax request
 * @param {*} controller
 * @param {*} action
 * @param {*} additionalData
 * @param {*} reloadContainers
 */
function ajaxRequest(controller, action, additionalData = null, printSuccessAlert = true, printErrorAlert = true, reloadContainers = null, execOnSuccess = null, execOnError = null)
{
    /**
     *  Default data
     */
    var data = {
        sourceUrl: window.location.href,
        sourceUri: window.location.pathname,
        controller: controller,
        action: action,
    };

    /**
     *  If additional data is specified, merge it with default data
     */
    if (additionalData != null) {
        data = $.extend(data, additionalData);
    }

    /**
     *  For debug only
     */
    // console.log(data);

    /**
     *  Ajax request
     */
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: data,
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            /**
             *  Retrieve and print success message
             */
            jsonValue = jQuery.parseJSON(jqXHR.responseText);

            if (printSuccessAlert) {
                printAlert(jsonValue.message, 'success');
            }

            /**
             *  Reload containers if specified
             */
            if (reloadContainers != null) {
                for (let i = 0; i < reloadContainers.length; i++) {
                    reloadContainer(reloadContainers[i]);
                }
            }

            /**
             *  Execute function(s) if specified
             */
            if (execOnSuccess != null) {
                for (let i = 0; i < execOnSuccess.length; i++) {
                    eval(execOnSuccess[i]);
                }
            }
        },
        error: function (jqXHR, textStatus, thrownError) {
            /**
             *  Retrieve and print error message
             */
            jsonValue = jQuery.parseJSON(jqXHR.responseText);

            if (printErrorAlert) {
                printAlert(jsonValue.message, 'error');
            }
        },
    });
}