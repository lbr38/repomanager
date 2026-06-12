class Repo {
    /**
     *  Search for repositories (search input)
     */
    search()
    {
        // If input is empty, then show all repos and quit
        if (!$("#repo-search-input").val()) {
            $('.repos-list-group, .repo-item, .group-repo-name').show();
            return;
        }

        mylayout.printLoading();

        /**
         *  Retrieve search input value
         *  Split into individual terms for multi-word search
         */
        const terms = $("#repo-search-input").val().toUpperCase().trim().split(/\s+/);

        // First, hide all groups and repo items
        $('.repos-list-group, .repo-item, .group-repo-name').hide();

        // Search through all repo items using data attributes
        $('.repo-item').each(function () {
            const name = ($(this).attr('data-name') || '').toUpperCase();
            const dist = ($(this).attr('data-dist') || '').toUpperCase();
            const section = ($(this).attr('data-section') || '').toUpperCase();
            const releasever = ($(this).attr('data-releasever') || '').toUpperCase();
            const type = ($(this).attr('data-type') || '').toUpperCase();
            const packageType = ($(this).attr('data-package-type') || '').toUpperCase();
            const description = ($(this).attr('data-description') || '').toUpperCase();
            const tags = ($(this).attr('data-tags') || '').toUpperCase();

            // Collect snapshot dates and environment names as searchable text
            var snapDates = '';
            $(this).find('.snap-date').each(function () {
                snapDates += ' ' + $(this).text().toUpperCase();
            });

            var envNames = '';
            $(this).find('.snap-env').each(function () {
                envNames += ' ' + $(this).text().toUpperCase();
            });

            // All searchable content for this repo
            const searchable = name + ' ' + dist + ' ' + section + ' ' + releasever + ' ' + type + ' ' + packageType + ' ' + description + ' ' + tags + snapDates + envNames;

            // Check that ALL terms match somewhere in the searchable content
            const allMatch = terms.every(function (term) {
                return searchable.indexOf(term) > -1;
            });

            if (allMatch) {
                $(this).show();
                $(this).parent().prev('.group-repo-name').show();
                $(this).closest('.repos-list-group').show();
            }
        });

        mylayout.hideLoading();
    }

    /**
     *  Get repositories size
     */
    getSize()
    {
        // Loop through all repos and get their size
        // item-size is the legacy class for snap-size
        $('#repos-list-container').find('.snap-size, .item-size').each(function () {
            var repoId = $(this).attr('repo-id');
            var snapId = $(this).attr('snap-id');
            var path = $(this).attr('repo-relative-path');
            let element = $(this);

            ajaxRequest(
                // Controller:
                'repo/get',
                // Action:
                'size',
                // Data:
                {
                    path: path
                },
                // Print success alert:
                false,
                // Print error alert:
                false
            ).then(function () {
                $(element).html(jsonValue.message);
            }).catch(function () {
                $(element).replaceWith('<img src="/assets/icons/warning.svg" class="icon" title="' + jsonValue.message + '"/>');
            });
        });
    }

    /**
     *  Get latest task status for all repos
     */
    getLatestTaskStatus()
    {
        // Loop through all repos
        $('#repos-list-container').find('input[type="checkbox"][name="checkbox-repo"]').each(function () {
            const snapId = $(this).attr('snap-id');

            ajaxRequest(
                // Controller:
                'repo/get',
                // Action:
                'latest-task-status',
                // Data:
                {
                    snapId: snapId
                },
                // Print success alert:
                false,
                // Print error alert:
                false
            ).then(function () {
                const results = jsonValue.message;
                const id = results['Id'];
                const status = results['Status'];

                // Print an error icon with a link to the task details if the last task ended with an error
                if (status == 'error') {
                    const icon = 'warning-red.svg';
                    const title = 'Latest task failed on this snapshot. Click to view details.';
                    $("#repos-list-container").find('.item-task-status[snap-id="' + snapId + '"]').html('<a href="/run/' + id + '"><img src="/assets/icons/' + icon + '" class="icon" title="' + title + '"/></a>');
                }
            });
        });
    }

    /**
     * Update repository description
     * @param {*} id
     * @param {*} description
     */
    updateDescription(id, description)
    {
        ajaxRequest(
            // Controller:
            'repo/edit',
            // Action:
            'description',
            // Data:
            {
                repoId: id,
                description: description
            },
            // Print success alert:
            true,
            // Print error alert:
            true
        );
    }

    /**
     *  Print packages tree
     */
    printTree(path)
    {
        ajaxRequest(
            // Controller:
            'repo/browse',
            // Action:
            'tree',
            // Data:
            {
                path: path
            },
            // Print success alert:
            false,
            // Print error alert:
            true
        ).then(function () {
            // Replace loading icon with the tree structure
            $('#packages-list').html(jsonValue.message);

            // Hide all the sub-menus
            $('div.explorer-toggle').next().hide();

            // Set the cursor of the toggling span elements
            $('div.explorer-toggle').css('cursor', 'pointer');

            // Prepend a plus sign to signify that the sub-menus aren't expanded
            $('div.explorer-toggle').prepend('+ ');

            // Add a click function that toggles the sub-menu when the corresponding span element is clicked
            $('div.explorer-toggle').click(function () {
                $(this).next().toggle(100);

                // Switch the plus to a minus sign or vice-versa
                var v = $(this).html().substring(0, 1);
                if (v == '+') {
                    $(this).html('-' + $(this).html().substring(1));
                } else if (v == '-') {
                    $(this).html('+' + $(this).html().substring(1));
                }
            });

            $('#loading-tree').remove();
            $('#explorer').show();
        });
    }
}
