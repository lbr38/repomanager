/**
 *  Event: enable or disable debug mode
 */
$(document).on('click','#debug-mode-btn',function () {
    if ($(this).is(':checked')) {
        var enable = 'true';
    } else {
        var enable = 'false';
    }

    ajaxRequest(
        // Controller:
        'settings/debug-mode',
        // Action:
        'enable',
        // Data:
        {
            enable: enable
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    );
});
