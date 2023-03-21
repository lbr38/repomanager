/**
 *  Reload header to get running operations
 */
setInterval(function () {
    reloadHeader();
}, 5000);

/**
 *  Slide panel opening
 */
$(document).on('click','.param-slide-btn',function () {
    var name = $(this).attr('param-slide');

    openPanel(name);
});

/**
 *  Slide panel closing
 */
$(document).on('click','.param-slide-close-btn',function () {
    closePanel();
});