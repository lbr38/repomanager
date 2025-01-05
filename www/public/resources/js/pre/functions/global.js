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

    return new Promise((resolve, reject) => {
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

                /**
                 *  Print success message
                 */
                // Print alert
                if (printSuccessAlert === true) {
                    printAlert(jsonValue.message, 'success');
                }
                // Print to console
                if (printSuccessAlert == 'console') {
                    console.log(jsonValue.message);
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

                resolve('Ajax request executed successfully');
            },

            error: function (jqXHR, textStatus, thrownError) {
                /**
                 *  Retrieve and print error message
                 */
                jsonValue = jQuery.parseJSON(jqXHR.responseText);

                /**
                 *  Print error message
                 */
                // Print alert
                if (printErrorAlert === true) {
                    printAlert(jsonValue.message, 'error');
                }
                // Print to console
                if (printErrorAlert == 'console') {
                    console.log(jsonValue.message);
                }

                /**
                 *  Execute function(s) if specified
                 */
                if (execOnError != null) {
                    for (let i = 0; i < execOnError.length; i++) {
                        eval(execOnError[i]);
                    }
                }

                reject('Failed to execute ajax request');
            },
        });
    });
}

/**
 *  Convert select tag to a select2 by specified element
 *  @param {*} element
 */
function selectToSelect2(element, placeholder = 'Select...', tags = false)
{
    $(element).select2({
        closeOnSelect: false,
        placeholder: placeholder,
        tags: tags,
        minimumResultsForSearch: Infinity, /* disable search box */
        allowClear: true /* add a clear button */
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
function updateSelect2(select, data, placeholder = '', tags = false)
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
        closeOnSelect: false,
        placeholder: placeholder,
        tags: tags,
        minimumResultsForSearch: Infinity, /* disable search box */
        allowClear: true
    })
}
