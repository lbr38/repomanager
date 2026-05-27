class Repo {
    /**
     *  Search for repositories (search input)
     */
    search()
    {
        // If input is empty, then show all repos and quit
        if (!$("#repo-search-input").val()) {
            $('.repos-list-group, .repos-row, .repo-item-wrapper, .repo-item, .group-repo-name').show();
            return;
        }

        mylayout.printLoading();

        /**
         *  Retrieve search input value
         *  Split into individual terms for multi-word search
         */
        const terms = $("#repo-search-input").val().toUpperCase().trim().split(/\s+/);

        // First, hide all groups and repo items (hide the wrapper too, otherwise
        // it stays in the grid flow as an empty cell and shifts the visible cards)
        $('.repos-list-group, .repos-row, .repo-item-wrapper, .repo-item, .group-repo-name').hide();

        // Search through all repo items using their data attributes and visible text
        $('.repo-item').each(function () {
            const name = ($(this).attr('data-name') || '').toUpperCase();
            const dist = ($(this).attr('data-dist') || '').toUpperCase();
            const section = ($(this).attr('data-section') || '').toUpperCase();
            const releasever = ($(this).attr('data-releasever') || '').toUpperCase();
            const type = ($(this).attr('data-type') || '').toUpperCase();
            const packageType = ($(this).attr('data-package-type') || '').toUpperCase();
            const description = ($(this).attr('data-description') || '').toUpperCase();
            const tags = ($(this).attr('data-tags') || '').toUpperCase();

            // Individual tags of this repo, used for exact 'tag:' matching
            const tagsList = tags.split(',').map(function (t) {
                return t.trim();
            }).filter(function (t) {
                return t.length > 0;
            });

            // Also include the whole visible text of the repo card (header line with
            // name/distribution/section, snapshot dates, environments, tags...) so the
            // search keeps working even if a data attribute is missing
            const visibleText = ($(this).text() || '').toUpperCase();

            // All searchable content for this repo
            const searchable = name + ' ' + dist + ' ' + section + ' ' + releasever + ' ' + type + ' ' + packageType + ' ' + description + ' ' + tags + ' ' + visibleText;

            // Check that ALL terms match somewhere in the searchable content
            // A 'TAG:xxx' term must match one of this repo's tags exactly, instead of
            // matching anywhere in the searchable content (name, description, etc.)
            const allMatch = terms.every(function (term) {
                if (term.indexOf('TAG:') === 0) {
                    return tagsList.indexOf(term.substring(4)) > -1;
                }

                return searchable.indexOf(term) > -1;
            });

            if (allMatch) {
                $(this).show();
                $(this).closest('.repo-item-wrapper').show();
                $(this).closest('.repos-row').show();
                $(this).closest('.repos-row').prev('.group-repo-name').show();
                // Reveal ALL ancestor groups (they can be nested), not just the closest one
                $(this).parents('.repos-list-group').show();
            }
        });

        mylayout.hideLoading();
    }

    /**
     *  Add a tag to the search input and filter repositories by it
     * @param {string} tag
     */
    searchByTag(tag)
    {
        const input = $('#repo-search-input');

        // Retrieve the terms already present in the search input
        const current = input.val().trim();
        const terms = current.length > 0 ? current.split(/\s+/) : [];

        // Use a 'tag:' prefix so the search only matches this exact tag,
        // instead of matching the tag name anywhere (e.g. in a repo name)
        const searchTerm = 'tag:' + tag;

        // Add the tag only if it is not already present (case-insensitive)
        if (!terms.some(function (term) {
            return term.toUpperCase() === searchTerm.toUpperCase();
        })) {
            terms.push(searchTerm);
        }

        input.val(terms.join(' '));

        // Filter the repositories with the updated search terms
        this.search();
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
     * Switch a repository description <p> into an editable input
     * @param {*} p jQuery element of the description <p>
     */
    startEditDescription(p)
    {
        if (!myrepopermission.allowedAction('edit')) {
            myalert.print('You do not have permission to edit repositories', 'error');
            return;
        }

        // If already in edit mode, do nothing
        if (p.find('input').length > 0) {
            return;
        }

        const currentDescription = p.text().trim();
        const repoId = p.attr('repo-id');
        const envId = p.attr('env-id');

        // Remove empty class (hide placeholder)
        p.removeClass('repo-description-empty');

        // Create input field
        const input = $('<input type="text" class="repo-description-input-edit">')
            .attr('repo-id', repoId)
            .attr('env-id', envId)
            .attr('data-original', currentDescription)
            .val(currentDescription);

        // Replace <p> content with input
        p.html(input);
        input.focus();
    }

    /**
     * Switch a repository tags display into an editable select2
     * @param {*} display jQuery element containing the tags display
     */
    startEditTags(display)
    {
        // Ignore if the user does not have permission to edit repositories
        if (!myrepopermission.allowedAction('edit')) {
            return;
        }

        // If already in edit mode, do nothing
        if (display.hasClass('editing')) {
            return;
        }

        const repoId = display.attr('repo-id');

        // Current tags of this repo
        const currentTags = (display.attr('data-tags') || '').split(',').map(function (tag) {
            return tag.trim();
        }).filter(function (tag) {
            return tag.length > 0;
        });

        // Collect all existing tags across all repos to suggest them in the dropdown
        const allTags = new Set(currentTags);

        $('.repo-tags-display').each(function () {
            ($(this).attr('data-tags') || '').split(',').forEach(function (tag) {
                tag = tag.trim();
                if (tag.length > 0) {
                    allTags.add(tag);
                }
            });
        });

        // Build the multiple select element
        const select = $('<select multiple class="repo-tags-input-edit"></select>')
            .attr('repo-id', repoId)
            .attr('data-original', currentTags.join(','));

        // Add each existing tag as an option, pre-selecting the ones currently applied to this repo
        allTags.forEach(function (tag) {
            const selected = currentTags.includes(tag);
            select.append(new Option(tag, tag, selected, selected));
        });

        // Switch to edit mode and replace the content with the select
        display.addClass('editing').removeClass('repo-tags-empty').empty().append(select);

        // Convert to a select2 "multiple", allowing the user to add new tags
        myselect2.convert(select, 'Add tags...', true, false);

        // Open the dropdown right away
        select.select2('open');
    }

    /**
     * Save tags being edited and revert select2 to plain text display
     * @param {*} display jQuery element containing the tags display
     */
    saveRepoTags(display)
    {
        const select = display.find('select.repo-tags-input-edit');

        if (select.length === 0) {
            return;
        }

        const repoId = display.attr('repo-id');

        // Retrieve selected tags, clean and deduplicate them
        let tags = select.val() || [];
        tags = tags.map(function (tag) {
            return tag.trim();
        }).filter(function (tag) {
            return tag.length > 0;
        });

        // Deduplicate and sort tags case-insensitively
        tags = [...new Set(tags)].sort(function (a, b) {
            return a.toLowerCase().localeCompare(b.toLowerCase());
        });

        // Destroy the select2 instance
        select.select2('destroy');

        // Save the new tags
        this.updateTags(repoId, tags.join(','));

        // Update data-tags on the display and on the parent repo item (used by the search)
        display.attr('data-tags', tags.join(','));
        display.closest('.repo-item').attr('data-tags', tags.join(','));

        // Revert to the text display
        display.removeClass('editing').empty();

        if (tags.length > 0) {
            tags.forEach(function (tag) {
                const tagItem = $('<div class="flex align-item-center column-gap-5 mediumopacity repo-tag-item"></div>')
                    .attr('title', tag + ' tag');

                $('<img src="/assets/icons/tag.svg" class="icon-np icon-small" />').appendTo(tagItem);
                $('<p class="font-size-13"></p>').text(tag).appendTo(tagItem);

                tagItem.appendTo(display);
            });
        } else {
            display.addClass('repo-tags-empty');
            // Hide the tags container and show the header "Add tags" button again
            const repoItem = display.closest('.repo-item');
            repoItem.find('.repo-tags-container').hide();
            repoItem.find('.repo-add-tags-btn').show();
        }
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
            false,
            // Print error alert:
            true
        );
    }

    /**
     * Update repository tags
     * @param {*} id
     * @param {*} tags Comma-separated list of tags
     */
    updateTags(id, tags)
    {
        ajaxRequest(
            // Controller:
            'repo/edit',
            // Action:
            'tags',
            // Data:
            {
                repoId: id,
                tags: tags
            },
            // Print success alert:
            false,
            // Print error alert:
            true
        );
    }
}
