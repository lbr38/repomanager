/**
 *  Event: enable or disable debug mode
 */
$(document).on('click','#debug-mode-btn',function () {
    var enable = false;

    if ($(this).is(':checked')) {
        var enable = true;
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
    ).then(function () {
        mycontainer.reload('header/debug-mode');
    });
});
