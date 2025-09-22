/**
 *  Event: show tooltip on hover on element with .tooltip class
 */
$(document).on('mouseenter','.tooltip',function (e) {
    const text = $(this).attr('tooltip');

    if (text === undefined || text === '') {
        return;
    }

    // Print tooltip loading
    mytooltip.loading(e);

    // Print tooltip
    mytooltip.print('<p>' + text + '</p>', e);
});
