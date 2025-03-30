/**
 *  Convert some select to select2
 */
selectToSelect2('#emailRecipientSelect', 'Select recipients...', true);
selectToSelect2('#debArchitectureSelect', 'Select architectures...');
selectToSelect2('#rpmArchitectureSelect', 'Select architectures...');

/**
 *  Event: send a test email
 */
$(document).on('click','#send-test-email-btn',function () {
    ajaxRequest(
        // Controller:
        'settings',
        // Action:
        'sendTestEmail',
        // Data:
        {},
        // Print success alert:
        true,
        // Print error alert:
        true
    );
});

/**
 *  Event: apply settings
 */
$(document).on('submit','.settings-form',function () {
    event.preventDefault();

    var settings_params_obj = {};

    /**
     *  Loop through each input in the settings form
     */
    $('.reloadable-container[container="settings/settings"]').find('.settings-param').each(function () {
        /**
         *  Getting param name in the 'param-name' attribute of each input
         */
        var param_name = $(this).attr('param-name');

        /**
         *  If input is a checkbox and it is checked then its value is 'true'
         *  Else its value is 'false'
         */
        if ($(this).attr('type') == 'checkbox') {
            if ($(this).is(":checked")) {
                var param_value = 'true';
            } else {
                var param_value = 'false';
            }

        /**
         *  If input is a radio then get its value only if it is checked, else process the next param
         */
        } else if ($(this).attr('type') == 'radio') {
            if ($(this).is(":checked")) {
                var param_value = $(this).val();
            } else {
                return; // In jquery '.each()' loops, return is like 'continue'
            }
        } else {
            /**
             *  If input is not a checkbox nor a radio then get its value
             */
            var param_value = $(this).val();
        }

        /**
         *  Add param name and value to the global object array
         */
        settings_params_obj[param_name] = param_value;
    });

    /**
     *  Convert object array to JSON before sending
     */
    var settings_params_json = JSON.stringify(settings_params_obj);

    ajaxRequest(
        // Controller:
        'settings',
        // Action:
        'applySettings',
        // Data:
        {
            settings_params: settings_params_json,
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        [
            'header/menu',
            'header/general-error-messages',
            'settings/settings',
        ]
    );

    return false;
});

/**
 *  Event: select and view websocket server log file
 */
$(document).on('click','#websocket-log-btn',function () {
    // Retrieve log file name
    var logfile = $('select#websocket-log-select').val();

    ajaxRequest(
        // Controller:
        'settings',
        // Action:
        'get-wss-log',
        // Data:
        {
            logfile: logfile
        },
        // Print success alert:
        false,
        // Print error alert:
        true,
        // Reload container:
        [],
        // Execute functions on success:
        [
            'printModalWindow(jsonValue.message, "' + logfile + '")'
        ]
    );
});
