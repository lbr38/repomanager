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