
/**
 *  Search for a repo (search input)
 */
function searchRepo()
{
    /**
     *  If input is empty, then show all repos and quit
     */
    if (!$("#repo-search-input").val()) {
        $('.repos-list-group, .repos-list-group-flex-div').show();
        return;
    }

    mylayout.printLoading();

    /**
     *  Retrieve search input value
     *  Convert to uppercase to ignore case when searching
     */
    search = $("#repo-search-input").val().toUpperCase().trim();

    /**
     *  First, hide all repos groups
     */
    $('.repos-list-group, .repos-list-group-flex-div').hide();

    /**
     *  Then search in every repo group of there is a repo or dist or section matching the search
     */
    $('.repos-list-group').each(function () {
        /**
         *  Retrieve all repos lines
         */
        $('.item-repo').each(function () {
            var name = $(this).attr('name');
            var dist = $(this).attr('dist');
            var section = $(this).attr('section');
            var releasever = $(this).attr('releasever');

            /**
             *  If repo name contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
             */
            if (name.toUpperCase().indexOf(search) > -1) {
                // $(this).show();
                $(this).parents('.repos-list-group-flex-div').show();
                $(this).parents('.repos-list-group').show();
            }

            /**
             *  If repo dist contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
             */
            if (dist.toUpperCase().indexOf(search) > -1) {
                // $(this).show();
                $(this).parents('.repos-list-group-flex-div').show();
                $(this).parents('.repos-list-group').show();
            }

            /**
             *  If repo section contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
             */
            if (section.toUpperCase().indexOf(search) > -1) {
                // $(this).show();
                $(this).parents('.repos-list-group-flex-div').show();
                $(this).parents('.repos-list-group').show();
            }

            /**
             *  If repo releasever contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
             */
            if (releasever.toUpperCase().indexOf(search) > -1) {
                // $(this).show();
                $(this).parents('.repos-list-group-flex-div').show();
                $(this).parents('.repos-list-group').show();
            }
        });

        /**
         *  Retrieve all repos environments
         */
        $('.item-env').each(function () {
            var name = $(this).text().trim();

            // Ignore if name is empty
            if (name == "") {
                return;
            }

            /**
             *  If env name contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
             */
            if (name.toUpperCase().indexOf(search) > -1) {
                $(this).parents('.repos-list-group-flex-div').show();
                $(this).parents('.repos-list-group').show();
            }
        });

        /**
         *  Retrieve all repos descriptions
         */
        $('input.repoDescriptionInput').each(function () {
            var description = $(this).val().trim();

            // Ignore if description is empty
            if (description == "") {
                return;
            }

            /**
             *  If description contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
             */
            if (description.toUpperCase().indexOf(search) > -1) {
                $(this).parents('.repos-list-group-flex-div').show();
                $(this).parents('.repos-list-group').show();
            }
        });
    });

    mylayout.hideLoading();
}

/**
 *  Events listeners
 */

/**
 *  Event: create new repo: print description field only if an env is specified
 */
$(document).on('change','#new-repo-target-env-select',function () {
    if ($('#new-repo-target-env-select').val() == "") {
        $('#new-repo-target-description-tr').hide();
    } else {
        $('#new-repo-target-description-tr').show();
    }
}).trigger('change');

/**
 *  Event: print/hide all repos groups
 */
$(document).on('click','#hideAllReposGroups',function () {
    var state = $(this).attr('state');

    /**
     *  If actual state is 'visible' then hide all groups
     */
    if (state == 'visible') {
        /**
         *  Change state to 'hidden'
         */
        $(this).attr('state', 'hidden');
        $(this).find('img').attr('src', 'assets/icons/view-off.svg');

        /**
         *  Retrieve all groups and hide them if they are visible
         */
        $('.repo-list-group-container').each(function () {
            /**
             *  Retrieve group id
             */
            var id = $(this).attr('group-id');

            /**
             *  If the group is visible then hide it, else do nothing
             */
            if ($(this).is(":visible")) {
                slide('.repo-list-group-container[group-id="' + id + '"]');
            }
        });

        /**
         *  Change all up/down icons to 'down'
         */
        $('img.hideGroup').attr('src', 'assets/icons/view-off.svg');
    }

    /**
     *  If actual state is 'hidden' then show all groups
     */
    if (state == 'hidden') {
        /**
         *  Change state to 'visible'
         */
        $(this).attr('state', 'visible');
        $(this).find('img').attr('src', 'assets/icons/view.svg');

        /**
         *  Retrieve all groups and show them if they are hidden
         */
        $('.repo-list-group-container').each(function () {
            /**
             *  Retrieve group id
             */
            var id = $(this).attr('group-id');

            /**
             *  If the group is hidden then show it, else do nothing
             */
            if ($(this).is(":hidden")) {
                slide('.repo-list-group-container[group-id="' + id + '"]');
            }
        });

        /**
         *  Change all up/down icons to 'up'
         */
        $('img.hideGroup').attr('src', 'assets/icons/view.svg');
    }
});

/**
 *  Event: show / hide repos group content
 */
$(document).on('click','.hideGroup',function () {
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
