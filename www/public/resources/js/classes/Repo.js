class Repo {
    /**
     *  Collapse or expand a repositories group, the state is kept in sessionStorage so that it survives a container reload
     *  @param {object} group .repos-list-group element
     *  @param {boolean} hide
     *  @param {boolean} animate
     */
    toggleGroup(group, hide, animate = true)
    {
        const content = group.children('.group-content');
        const name = group.children('.group-header').find('.hide-repo-group');

        if (hide) {
            animate ? content.slideUp(200) : content.hide();
        } else {
            animate ? content.slideDown(200) : content.show();
        }

        name.attr('state', hide ? 'hidden' : 'visible');
        name.attr('title', (hide ? 'Expand group ' : 'Collapse group ') + name.text());

        sessionStorage.setItem('repos/list/group/' + group.attr('group-id') + '/collapsed', hide);
    }

    /**
     *  Collapse again the repositories groups that were collapsed before the container reload
     */
    restoreGroups()
    {
        $('.repos-list-group').each(function () {
            if (sessionStorage.getItem('repos/list/group/' + $(this).attr('group-id') + '/collapsed') == 'true') {
                myrepo.toggleGroup($(this), true, false);
            }
        });

        // Keep the "Collapse all" button consistent with the groups state
        const allHidden = $('.repos-list-group').length > 0 && $('.repos-list-group .group-header .hide-repo-group[state="visible"]').length == 0;
        myrepo.setHideAllGroupsBtn(allHidden);
    }

    /**
     *  Set the "Collapse all" / "Expand all" button state
     *  @param {boolean} hidden
     */
    setHideAllGroupsBtn(hidden)
    {
        const btn = $('#hide-all-repo-groups');

        btn.attr('state', hidden ? 'hidden' : 'visible');
        btn.attr('title', hidden ? 'Show all repository groups' : 'Hide all repository groups');
        btn.find('img').attr('src', hidden ? '/assets/icons/view-off.svg' : '/assets/icons/view.svg');
        btn.find('span').text(hidden ? 'Expand all' : 'Collapse all');
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
     * Check if the user is allowed to edit a repository or a mirror (mirrors Id are prefixed with 'mirror-')
     * @param {string} repoId
     * @returns {boolean}
     */
    allowedToEdit(repoId)
    {
        if (String(repoId).startsWith('mirror-')) {
            return mymirrorpermission.allowedAction('edit');
        }

        return myrepopermission.allowedAction('edit');
    }

    /**
     * Switch a repository description <p> into an editable input
     * @param {*} p jQuery element of the description <p>
     */
    startEditDescription(p)
    {
        const repoId = p.attr('repo-id');

        if (!this.allowedToEdit(repoId)) {
            myalert.print('You do not have permission to edit this item', 'error');
            return;
        }

        // If already in edit mode, do nothing
        if (p.find('input').length > 0) {
            return;
        }

        const currentDescription = p.text().trim();
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
        const repoId = display.attr('repo-id');

        // Ignore if the user does not have permission to edit this repository or mirror
        if (!this.allowedToEdit(repoId)) {
            return;
        }

        // If already in edit mode, do nothing
        if (display.hasClass('editing')) {
            return;
        }

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
