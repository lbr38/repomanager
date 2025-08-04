/**
 *  Event: switch to compact view
 */
$(document).on('click','#compact-view-btn',function () {
    // Get current view mode from cookie
    var viewMode = mycookie.get('hosts/compact-view');

    // If view mode is set to true, then set it to false
    if (viewMode == 1) {
        mycookie.set('hosts/compact-view', 0, 365);
    }

    // If there was no cookie or if view mode is set to false, then set it to true
    if (viewMode == "" || viewMode == 0) {
        mycookie.set('hosts/compact-view', 1, 365);
    }

    // Reload the container
    mycontainer.reload('hosts/list');
});
