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

    /**
     *  Print packages tree (root level only – sub-directories are lazy-loaded on expand)
     */
    printTree(path)
    {
        const self = this;

        ajaxRequest(
            // Controller:
            'repo/snapshot/browse',
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
            $('#packages-list').html(jsonValue.message);
            self._initTreeLevel($('#packages-list'));
            self._initLoadMore($('#packages-list'));
            self._initSearch(path);
            $('#loading-tree').remove();
            $('#browse-search-bar').show();
            $('#explorer').show();
        });
    }

    /**
     *  Initialise click handlers for one tree level.
     *  Directories are fetched from the server the first time they are expanded.
     *  Call this again on any newly injected subtree container.
     */
    _initTreeLevel($container)
    {
        const self = this;

        $container.find('div.explorer-toggle').each(function () {
            const $toggle = $(this);

            $toggle.prepend('+ ');

            $toggle.on('click', function () {
                const $subtree = $toggle.next('ul');

                // Already loaded: just toggle visibility
                if ($toggle.data('loaded')) {
                    $subtree.toggle(100);
                    const v = $toggle.html().substring(0, 1);
                    if (v == '+') {
                        $toggle.html('-' + $toggle.html().substring(1));
                    } else if (v == '-') {
                        $toggle.html('+' + $toggle.html().substring(1));
                    }
                    return;
                }

                // First expand: fetch this directory level from the server
                $toggle.data('loaded', true);
                $toggle.html('-' + $toggle.html().substring(1));

                // Show loading indicator inside the empty placeholder
                $subtree.html('<li class="flex align-item-center column-gap-5 padding-top-10 padding-bottom-10"><img src="/assets/icons/loading.svg" class="icon" /><span class="lowopacity-cst">Loading</span></li>');
                $subtree.show();

                ajaxRequest(
                    // Controller:
                    'repo/snapshot/browse',
                    // Action:
                    'tree',
                    // Data:
                    {
                        path: $toggle.data('path')
                    },
                    // Print success alert:
                    false,
                    // Print error alert:
                    true
                ).then(function () {
                    // The server returns a <ul>…</ul>; replace the placeholder <ul>
                    const $newSubtree = $(jsonValue.message).hide();
                    $subtree.replaceWith($newSubtree);
                    self._initTreeLevel($newSubtree);
                    $newSubtree.show(100);
                }).catch(function () {
                    // Revert state so the user can retry
                    $toggle.data('loaded', false);
                    $toggle.html('+' + $toggle.html().substring(1));
                    $subtree.hide().empty();
                });
            });
        });
    }

    /**
     *  Set up the file search bar.
     *  Debounces keystrokes and queries the server for filename matches.
     *  Clears back to the tree when the input is emptied.
     */
    _initSearch(rootPath)
    {
        let debounceTimer = null;

        $('#browse-search-input').on('input', function () {
            clearTimeout(debounceTimer);
            const query = $(this).val().trim();

            if (query.length < 2) {
                $('#browse-search-results').hide().empty();
                $('#explorer').show();
                return;
            }

            debounceTimer = setTimeout(function () {
                // Show loading state
                $('#explorer').hide();
                $('#browse-search-results')
                    .html('<div class="flex align-item-center column-gap-5 padding-top-5"><img src="/assets/icons/loading.svg" class="icon" /><span class="lowopacity-cst">Searching...</span></div>')
                    .show();

                ajaxRequest(
                    // Controller:
                    'repo/snapshot/browse',
                    // Action:
                    'tree/search',
                    // Data:
                    {
                        path: rootPath, query: query
                    },
                    // Print success alert:
                    false,
                    // Print error alert:
                    false
                ).then(function () {
                    $('#browse-search-results').html(jsonValue.message);
                }).catch(function () {
                    $('#browse-search-results').html('<p class="note">Search failed. Please try again.</p>');
                });
            }, 350);
        });
    }

    /**
     *  Set up delegated "load more" handler on a root container.
     *  Must be called once on the #packages-list root; event delegation handles
     *  all .load-more-files buttons inserted dynamically inside it.
     */
    _initLoadMore($root)
    {
        const self = this;

        $root.on('click', 'li.load-more-files', function () {
            const $li = $(this);
            const path = $li.data('path');
            const offset = $li.data('offset');

            // Show loading state in place of the button
            $li.find('div').html('<div class="flex align-item-center column-gap-5"><img src="/assets/icons/loading.svg" class="icon" /><span class="lowopacity-cst">Loading</span></div>');
            $li.off('click');

            ajaxRequest(
                // Controller:
                'repo/snapshot/browse',
                // Action:
                'tree/page',
                // Data:
                {
                    path: path, offset: offset
                },
                // Print success alert:
                false,
                // Print error alert:
                true
            ).then(function () {
                // Response is a fragment of <li> elements; insert before the button then remove it
                const $items = $(jsonValue.message);
                $li.before($items);
                $li.remove();
            }).catch(function () {
                // Restore button text so the user can retry
                $li.find('div').html('<p>Load more files (click to retry)</p>');
                $li.on('click', arguments.callee);
            });
        });
    }
}
