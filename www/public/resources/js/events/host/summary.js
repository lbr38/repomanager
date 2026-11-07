/**
 *  Event: show / hide the network interfaces exceeding the limit
 */
$(document).on('click','.host-network-interfaces-more-btn',function () {
    const hidden = $('.host-network-interface-hidden');

    // If interfaces are hidden then show them
    if (hidden.first().hasClass('hide')) {
        hidden.removeClass('hide');
        $(this).text('Show less');
        return;
    }

    hidden.addClass('hide');
    $(this).text($(this).attr('more'));
});
