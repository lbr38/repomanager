class Snapshot {
    /**
     *  Download package from a snapshot
     */
    download()
    {
        // Package to download
        var packages = [];

        // Get all selected checkboxes and their file-id (media) attribute
        $('#packages-list, #browse-search-results').find('input[name=packageName\\[\\]]:checked').each(function () {
            packages.push({ filename: $(this).attr('filename'), path: $(this).attr('path') });
        });

        // Append a temporary <a> element to download files
        var temporaryDownloadLink = document.createElement("a");
        temporaryDownloadLink.style.display = 'none';

        document.body.appendChild(temporaryDownloadLink);

        for (var n = 0; n < packages.length; n++) {
            var download = packages[n];
            temporaryDownloadLink.setAttribute('href', '/repo/' + download.path);
            temporaryDownloadLink.setAttribute('download', download.filename);

            // Click on the <a> element to start download
            temporaryDownloadLink.click();
        }

        // Remove temporary <a> element
        document.body.removeChild(temporaryDownloadLink);
    }

    /**
     *  Upload packages to a snapshot via ajax
     *  @param {HTMLFormElement} form
     */
    upload(form)
    {
        // Build multipart form data from the form, including the selected package files
        const formData = new FormData(form);

        // Add the parameters required by the ajax controller
        formData.append('controller', 'repo/snapshot/browse');
        formData.append('sourceUrl', window.location.href);
        formData.append('sourceUri', window.location.pathname);

        myalert.print('Uploading packages...');  

        return new Promise((resolve, reject) => {
            $.ajax({
                type: 'POST',
                url: '/ajax/controller.php',
                data: formData,
                // Required for file upload: let the browser set the multipart content type
                // and prevent jQuery from serializing the FormData object
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (data, textStatus, jqXHR) {
                    jsonValue = jQuery.parseJSON(jqXHR.responseText);

                    // Reload the packages list and actions to show the newly uploaded packages
                    mycontainer.reload('snapshot/kpi');
                    mycontainer.reload('snapshot/list');

                    // Close the upload slide panel
                    mypanel.close('snapshot/upload');

                    // Print the list of uploaded packages
                    myalert.print(mysnapshot._formatUploadMessage(jsonValue.message), 'success');

                    resolve('Packages uploaded successfully');
                },

                error: function (jqXHR, textStatus, thrownError) {
                    jsonValue = jQuery.parseJSON(jqXHR.responseText);

                    // Print the error details
                    myalert.print(mysnapshot._formatUploadMessage(jsonValue.message), 'error');

                    reject('Failed to upload packages');
                }
            });
        });
    }

    /**
     * Delete packages
     * @param {*} snapId
     */
    delete(snapId)
    {
        // Package to delete
        var packages = [];

        myalert.print('Deleting packages...');

        // Get the path of the selected packages
        $('body').find('input[name=packageName\\[\\]]:checked').each(function () {
            packages.push($(this).attr('path'));
        });

        ajaxRequest(
            // Controller:
            'repo/snapshot/browse',
            // Action:
            'delete-package',
            {
                snapId: snapId,
                packages: packages
            },
            // Print success alert:
            false,
            // Print error alert:
            true
        ).then(function () {
            // Reload packages list and actions
            mycontainer.reload('snapshot/kpi');
            mycontainer.reload('snapshot/list');

            // Print packages that have been deleted
            var deletedNames = jsonValue.message.map(function (pkg) { return pkg.name; });
            var maxDisplay = 10;
            var message = 'Packages deleted: <br>' + deletedNames.slice(0, maxDisplay).join('<br>');
            if (deletedNames.length > maxDisplay) {
                message += '<br>+' + (deletedNames.length - maxDisplay) + ' more...';
            }
            myalert.print(message, 'success');
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
            // $('#browse-search-bar').show();
            $('#explorer').show();
        });
    }

    /**
     *  Format an upload result/error message for display
     *  The message can be a plain string or an object { title: [item, ...], ... }
     *  @param {*} message
     *  @returns {string}
     */
    _formatUploadMessage(message)
    {
        // Plain string message (e.g. a generic error)
        if (typeof message === 'string') {
            return message;
        }

        // Structured message: { title: [item, ...] }
        var lines = [];

        $.each(message, function (title, items) {
            lines.push(title + ':');

            if (Array.isArray(items)) {
                items.forEach(function (item) {
                    lines.push(item);
                });
            }
        });

        return lines.join('<br>');
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
