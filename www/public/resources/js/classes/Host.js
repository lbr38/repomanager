class Host
{
    /**
     * Get the action box for a list of hosts
     * @param {*} hosts
     */
    getActionBox()
    {
        var hosts = [];

        // The buttons that will be displayed in the confirm box
        var buttons = [];

        /**
         *  The list of allowed actions the user can execute on the selected hosts
         *  By default: all, unless the user has specific permissions
         *  Those permissions are later verified by the server so even if the user tries to execute an action he is not allowed to, it will not work
         */
        var allowedActions = ['request-general-infos', 'request-packages-infos', 'update-packages', 'reset', 'delete'];

        // Get all checked checkbox
        $('input[type="checkbox"][name="checkbox-host[]"]').each(function () {
            // If checkbox is checked then add host id to hosts
            if (this.checked) {
                hosts.push($(this).val());
            }
        });

        // If no hosts are selected, close the confirmation box and exit
        if (hosts.length == 0) {
            myconfirmbox.close();
            return;
        }

        // Get permissions from cookie
        if (mycookie.exists('user_permissions')) {
            var userPermissions = JSON.parse(mycookie.get('user_permissions'));

            // Reset allowed actions array
            var allowedActions = [];

            // Loop through all permissions and check if the user has the permission to execute the action
            if (userPermissions.hosts && userPermissions.hosts['allowed-actions'] && userPermissions.hosts['allowed-actions']) {
                var allowedActions = userPermissions.hosts['allowed-actions'];
            }
        }

        // Define confirm box buttons depending on the allowed actions
        if (allowedActions.includes('request-general-infos')) {
            buttons.push(
                {
                    'text': 'Request general information',
                    'callback': function () {
                        executeAction('request-general-infos', hosts);
                    }
                }
            );
        }

        if (allowedActions.includes('request-packages-infos')) {
            buttons.push(
                {
                    'text': 'Request package information',
                    'callback': function () {
                        executeAction('request-packages-infos', hosts);
                    }
                }
            );
        }

        if (allowedActions.includes('update-packages')) {
            buttons.push(
                {
                    'text': 'Update packages',
                    'callback': function () {
                        mypanel.get('hosts/requests/update-packages', {
                            hostsId: hosts
                        });
                    }
                }
            );
        }

        // Export to CSV is always allowed by default
        buttons.push(
            {
                'text': 'Export to CSV',
                'callback': function () {
                    myhost.export(hosts);
                }
            }
        );

        if (allowedActions.includes('reset')) {
            buttons.push(
                {
                    'text': 'Reset',
                    'color': 'red',
                    'callback': function () {
                        executeAction('reset', hosts);
                    }
                }
            );
        }

        if (allowedActions.includes('delete')) {
            buttons.push(
                {
                    'text': 'Delete',
                    'color': 'red',
                    'callback': function () {
                        executeAction('delete', hosts);
                    }
                }
            );
        }

        myconfirmbox.print(
            {
                'title': 'Execute an action',
                'message': hosts.length + ' host' + (hosts.length > 1 ? 's' : '') + ' selected',
                'id': 'hosts-actions-confirm-box',
                'buttons': buttons
            }
        );
    }

    /**
     * Export a list of hosts to a CSV format
     * @param {*} hosts
     */
    export(hosts)
    {
        myalert.print('Exporting hosts...');

        ajaxRequest(
            // Controller:
            'host/export',
            // Action:
            'export',
            // Data:
            {
                hosts: hosts,
            },
            // Print success alert:
            false,
            // Print error alert:
            true
        ).then(function () {
            // Convert JSON response to CSV lines without wrapping fields in double quotes.
            // Replace CSV-breaking characters to keep a stable comma-separated format.
            const sanitizeCsvField = function (field) {
                return (field ?? '')
                    .toString()
                    .replace(/\r\n|\r|\n/g, ' ')
                    .replace(/,/g, ' ')
                    .replace(/"/g, "'")
                    .trim();
            };

            const lines = JSON.parse(jsonValue.message).map(row => row.map(sanitizeCsvField).join(','));

            // Join the lines with newline characters
            const csv = lines.join('\n');
            const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);

            // Create a link to download the CSV file
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', 'hosts.csv');
            document.body.appendChild(link);

            // Trigger the download
            link.click();

            // Clean up
            document.body.removeChild(link);
        });
    }

    /**
     * Return the element containing the description and tags of a host: its line in the hosts list or the host page header
     * @param {*} element jQuery element inside it
     * @returns {*}
     */
    getMetaScope(element)
    {
        return $(element).closest('.host-meta-scope');
    }

    /**
     * Show the description and tags of a host only when it has some (or while they are being edited)
     * The "Add a description" / "Add tags" buttons are only available when the host has none
     * @param {*} scope jQuery element containing the description and tags of the host
     */
    refreshMeta(scope)
    {
        const description = scope.find('p.host-description-input');
        const tags = scope.find('.host-tags-display');
        const hasDescription = description.find('input').length > 0 || description.text().trim() !== '';
        const hasTags = tags.hasClass('editing') || (tags.attr('data-tags') || '') !== '';

        description.toggleClass('host-description-empty', !hasDescription && description.hasClass('host-description-editable'));
        tags.toggleClass('host-tags-empty', !hasTags && tags.hasClass('host-tags-editable'));

        scope.find('.host-description-container').toggleClass('hide', !hasDescription);
        scope.find('.host-tags-container').toggleClass('hide', !hasTags);
        scope.find('.host-add-description-btn').toggleClass('hide', hasDescription);
        scope.find('.host-add-tags-btn').toggleClass('hide', hasTags);
        scope.find('.host-line-meta').toggleClass('hide', !hasDescription && !hasTags);
    }

    /**
     * Switch a host description <p> into an editable input
     * @param {*} p jQuery element of the description <p>
     */
    startEditDescription(p)
    {
        // Ignore if the user is not allowed to edit the host, or if already in edit mode
        if (!p.hasClass('host-description-editable') || p.find('input').length > 0) {
            return;
        }

        const currentDescription = p.text().trim();

        const input = $('<input type="text" class="host-description-input-edit">')
            .attr('host-id', p.attr('host-id'))
            .attr('data-original', currentDescription)
            .val(currentDescription);

        p.html(input);
        this.refreshMeta(this.getMetaScope(p));
        input.focus();
    }

    /**
     * Revert a host description input to a <p> with the specified value
     * @param {*} p jQuery element of the description <p>
     * @param {string} description
     */
    endEditDescription(p, description)
    {
        p.text(description);

        // In the hosts list the description is cut to one line, its full text is printed in the tooltip
        if (p.closest('.host-line').length > 0) {
            p.attr('title', description);
        }

        this.refreshMeta(this.getMetaScope(p));
    }

    /**
     * Switch a host tags display into an editable select2
     * @param {*} display jQuery element containing the tags display
     */
    startEditTags(display)
    {
        // Ignore if the user is not allowed to edit the host, or if already in edit mode
        if (!display.hasClass('host-tags-editable') || display.hasClass('editing')) {
            return;
        }

        const currentTags = (display.attr('data-tags') || '').split(',').map(function (tag) {
            return tag.trim();
        }).filter(function (tag) {
            return tag.length > 0;
        });

        const select = $('<select multiple class="host-tags-input-edit"></select>')
            .attr('host-id', display.attr('host-id'))
            .attr('data-original', currentTags.join(','));

        // Suggest the tags already used by the other hosts of the page (hosts list), the current ones are selected
        const allTags = new Set(currentTags);

        $('.host-tags-display').each(function () {
            ($(this).attr('data-tags') || '').split(',').forEach(function (tag) {
                if (tag.trim() !== '') {
                    allTags.add(tag.trim());
                }
            });
        });

        allTags.forEach(function (tag) {
            const selected = currentTags.includes(tag);
            select.append(new Option(tag, tag, selected, selected));
        });

        // Switch to edit mode and replace the content with a select2 "multiple", allowing the user to add new tags
        display.addClass('editing').empty().append(select);
        this.refreshMeta(this.getMetaScope(display));
        myselect2.convert(select, 'Add tags...', true, false);

        // Clear the typed text once a tag has been added, the dropdown stays open to add other tags
        select.on('select2:select', function () {
            select.data('select2').$container.find('.select2-search__field').val('').trigger('input');
        });

        select.select2('open');
    }

    /**
     * Save the tags being edited (if changed) and revert the select2 to the tags display
     * @param {*} display jQuery element containing the tags display
     */
    saveTags(display)
    {
        const select = display.find('select.host-tags-input-edit');

        if (select.length === 0) {
            return;
        }

        // Retrieve selected tags, trimmed, deduplicated and sorted case-insensitively
        let tags = (select.val() || []).map(function (tag) {
            return tag.trim();
        }).filter(function (tag) {
            return tag.length > 0;
        });

        tags = [...new Set(tags)].sort(function (a, b) {
            return a.toLowerCase().localeCompare(b.toLowerCase());
        });

        const original = select.attr('data-original');

        select.select2('destroy');

        // Save the new tags only if they have changed
        if (tags.join(',') !== original) {
            ajaxRequest(
                // Controller:
                'host/edit',
                // Action:
                'tags',
                // Data:
                {
                    hostId: display.attr('host-id'),
                    tags: tags.join(',')
                },
                // Print success alert:
                false,
                // Print error alert:
                true
            ).catch(function () {
                // Restore the original tags if they could not be saved
                myhost.renderTags(display, original ? original.split(',') : []);
            });
        }

        this.renderTags(display, tags);
    }

    /**
     * Print the tags of a host in its tags display
     * @param {*} display jQuery element containing the tags display
     * @param {array} tags
     */
    renderTags(display, tags)
    {
        const hostLine = display.closest('.host-line');

        display.attr('data-tags', tags.join(',')).removeClass('editing').empty();

        tags.forEach(function (tag) {
            const tagItem = $('<div class="flex align-item-center column-gap-5 host-tag-item"></div>');

            // In the hosts list a click on a tag filters the hosts by this tag
            if (hostLine.length > 0) {
                tagItem.attr('title', 'Click to filter by ' + tag + ' tag');
            }

            $('<img src="/assets/icons/tag.svg" class="icon-np icon-small" />').appendTo(tagItem);
            $('<span></span>').text(tag).appendTo(tagItem);

            tagItem.appendTo(display);
        });

        // The tags of a host line are used by the hosts search (tag= filter)
        hostLine.attr('tags', tags.join(','));

        this.refreshMeta(this.getMetaScope(display));
    }

    /**
     * Save the description of a host
     * @param {string} id
     * @param {string} description
     * @returns {Promise}
     */
    updateDescription(id, description)
    {
        return ajaxRequest(
            // Controller:
            'host/edit',
            // Action:
            'description',
            // Data:
            {
                hostId: id,
                description: description
            },
            // Print success alert:
            false,
            // Print error alert:
            true
        );
    }
}
