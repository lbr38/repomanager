/**
 *  Event: print/hide all repos groups
 */
$(document).on('click','#hide-all-repo-groups',function () {
    var state = $(this).attr('state');

    // If actual state is 'visible' then hide all groups
    if (state == 'visible') {
        // Change state to 'hidden'
        $(this).attr('state', 'hidden');
        $(this).find('img').attr('src', 'assets/icons/view-off.svg');

        // Retrieve all groups and hide them if they are visible
        $('.repos-list-group').each(function () {
            var content = $(this).children('.group-content');

            // If the group content is visible then hide it, else do nothing
            if (content.is(":visible")) {
                content.slideUp(200);
            }
        });

        // Change all up/down icons to 'down'
        $('img.hide-repo-group').attr('src', 'assets/icons/view-off.svg');
        $('img.hide-repo-group').attr('state', 'hidden');
    }

    // If actual state is 'hidden' then show all groups
    if (state == 'hidden') {
        // Change state to 'visible'
        $(this).attr('state', 'visible');
        $(this).find('img').attr('src', 'assets/icons/view.svg');

        // Retrieve all groups and show them if they are hidden
        $('.repos-list-group').each(function () {
            var content = $(this).children('.group-content');

            // If the group content is hidden then show it, else do nothing
            if (content.is(":hidden")) {
                content.slideDown(200);
            }
        });

        // Change all up/down icons to 'up'
        $('img.hide-repo-group').attr('src', 'assets/icons/view.svg');
        $('img.hide-repo-group').attr('state', 'visible');
    }
});

/**
 *  Event: show / hide repos group content
 */
$(document).on('click','.hide-repo-group',function () {
    var state = $(this).attr('state');

    if (state == 'visible') {
        $(this).attr('state', 'hidden');
        $(this).attr('src', 'assets/icons/view-off.svg');
    }

    if (state == 'hidden') {
        $(this).attr('state', 'visible');
        $(this).attr('src', 'assets/icons/view.svg');
    }

    $(this).closest('.repos-list-group').children('.group-content').slideToggle(200);
});
