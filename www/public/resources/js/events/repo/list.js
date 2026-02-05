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
        $('.repo-list-group-container').each(function () {
            // Retrieve group id
            var id = $(this).attr('group-id');

            // If the group is visible then hide it, else do nothing
            if ($(this).is(":visible")) {
                slide('.repo-list-group-container[group-id="' + id + '"]');
            }
        });

        // Change all up/down icons to 'down'
        $('img.hide-repo-group').attr('src', 'assets/icons/view-off.svg');
    }

    // If actual state is 'hidden' then show all groups
    if (state == 'hidden') {
        // Change state to 'visible'
        $(this).attr('state', 'visible');
        $(this).find('img').attr('src', 'assets/icons/view.svg');

        // Retrieve all groups and show them if they are hidden
        $('.repo-list-group-container').each(function () {
            // Retrieve group id
            var id = $(this).attr('group-id');

            // If the group is hidden then show it, else do nothing
            if ($(this).is(":hidden")) {
                slide('.repo-list-group-container[group-id="' + id + '"]');
            }
        });

        // Change all up/down icons to 'up'
        $('img.hide-repo-group').attr('src', 'assets/icons/view.svg');
    }
});

/**
 *  Event: show / hide repos group content
 */
$(document).on('click','.hide-repo-group',function () {
    var id = $(this).attr('group-id');
    var state = $(this).attr('state');

    if (state == 'visible') {
        $(this).attr('state', 'hidden');
        $(this).attr('src', 'assets/icons/view-off.svg');
    }

    if (state == 'hidden') {
        $(this).attr('state', 'visible');
        $(this).attr('src', 'assets/icons/view.svg');
    }

    slide('.repo-list-group-container[group-id="' + id + '"]');
});
