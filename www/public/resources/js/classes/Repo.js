class Repo {
    /**
     *  Search for repositories (search input)
     */
    search()
    {
        // If input is empty, then show all repos and quit
        if (!$("#repo-search-input").val()) {
            $('.repos-list-group, .repos-list-group-flex-div').show();
            return;
        }

        mylayout.printLoading();

        /**
         *  Retrieve search input value
         *  Convert to uppercase to ignore case when searching
         */
        const search = $("#repo-search-input").val().toUpperCase().trim();

        // First, hide all repos groups
        $('.repos-list-group, .repos-list-group-flex-div').hide();

        // Then search in every repo group of there is a repo or dist or section matching the search
        $('.repos-list-group').each(function () {
            // Retrieve all repos lines
            $('.item-repo').each(function () {
                const name = $(this).attr('name');
                const dist = $(this).attr('dist');
                const section = $(this).attr('section');
                const releasever = $(this).attr('releasever');

                // If repo name contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
                if (name.toUpperCase().indexOf(search) > -1) {
                    $(this).parents('.repos-list-group-flex-div').show();
                    $(this).parents('.repos-list-group').show();
                }

                // If repo dist contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
                if (dist.toUpperCase().indexOf(search) > -1) {
                    $(this).parents('.repos-list-group-flex-div').show();
                    $(this).parents('.repos-list-group').show();
                }

                // If repo section contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
                if (section.toUpperCase().indexOf(search) > -1) {
                    $(this).parents('.repos-list-group-flex-div').show();
                    $(this).parents('.repos-list-group').show();
                }

                // If repo releasever contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
                if (releasever.toUpperCase().indexOf(search) > -1) {
                    $(this).parents('.repos-list-group-flex-div').show();
                    $(this).parents('.repos-list-group').show();
                }
            });

            // Retrieve all repos environments
            $('.item-env').each(function () {
                const env = $(this).text().trim();

                // If env is not empty
                if (env != "") {
                    // If env name contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
                    if (env.toUpperCase().indexOf(search) > -1) {
                        $(this).parents('.repos-list-group-flex-div').show();
                        $(this).parents('.repos-list-group').show();
                    }
                }
            });

            // Retrieve all repos descriptions
            $('input[type="text"].repo-description-input').each(function () {
                const description = $(this).val().trim();

                // If description is not empty
                if (description != "") {
                    // If description contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
                    if (description.toUpperCase().indexOf(search) > -1) {
                        $(this).parents('.repos-list-group-flex-div').show();
                        $(this).parents('.repos-list-group').show();
                    }
                }
            });
        });

        mylayout.hideLoading();
    }

    /**
     *  Get repositories size
     */
    getSize()
    {
        // Loop through all repos and get their size
        $('#repos-list-container').find('.item-size').each(function () {
            var repoId = $(this).attr('repo-id');
            var snapId = $(this).attr('snap-id');
            var path = $(this).attr('repo-relative-path');

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
                $("#repos-list-container").find('.item-size[repo-id="' + repoId + '"][snap-id="' + snapId + '"]').html(jsonValue.message);
            }).catch(function () {
                $("#repos-list-container").find('.item-size[repo-id="' + repoId + '"][snap-id="' + snapId + '"]').replaceWith('<img src="/assets/icons/warning.svg" class="icon" title="' + jsonValue.message + '"/>');
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
}
