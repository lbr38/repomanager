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
    ajaxRequest(
        // Controller:
        'notification',
        // Action:
        'acquit',
        // Data:
        {
            id: id
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        ['header/menu'],
        // Execute functions on success:
        [
            // Reload notification panel
            "reloadPanel('general/notification')"
        ]
    );
}