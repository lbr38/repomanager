/**
 *  Event: Toggle element visibility
 */
$(document).on('click','.toggle-btn',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    // Toggle element
    $($(this).attr('toggle')).slideToggle();

    // Change icon if exists
    let icon = $(this).find('.toggle-icon');
    icon.attr('src', icon.attr('src') === '/assets/icons/next.svg' ? '/assets/icons/down.svg' : '/assets/icons/next.svg');

    // Change opacity
    $(this).toggleClass('mediumopacity');
});
