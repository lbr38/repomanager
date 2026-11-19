/**
 *  Description and tags of a host, edited in place on the host page and in the hosts list
 *  Each host description and tags are contained in a '.host-meta-scope' element (host line or host page header)
 */

// Timer of a click on a tag in the hosts list, delayed to make sure it is not a double-click (edit the tags)
let hostTagClickTimer = null;

/**
 *  Event: click on "Add a description" button
 */
$(document).on('click', '.host-add-description-btn', function () {
    myhost.startEditDescription(myhost.getMetaScope(this).find('p.host-description-input'));
});

/**
 *  Event: single-click on an empty description to add one
 */
$(document).on('click', 'p.host-description-input.host-description-empty', function () {
    myhost.startEditDescription($(this));
});

/**
 *  Event: double-click on an existing description to edit it
 */
$(document).on('dblclick', 'p.host-description-input', function () {
    myhost.startEditDescription($(this));
});

/**
 *  Event: save host description when pressing 'Enter' key
 */
$(document).on('keypress', '.host-description-input-edit', function (e) {
    e.stopPropagation();

    const keycode = (e.keyCode ? e.keyCode : e.which);

    if (keycode != '13') {
        return;
    }

    const input = $(this);
    const p = input.closest('p.host-description-input');
    const originalDescription = input.attr('data-original');
    const newDescription = input.val().trim();

    // Mark as saved to prevent blur from reverting (native property, jQuery data is wiped when the input is removed)
    this._descSaved = true;

    if (newDescription !== originalDescription) {
        myhost.updateDescription(input.attr('host-id'), newDescription).catch(function () {
            // Restore the original description if it could not be saved
            myhost.endEditDescription(p, originalDescription);
        });
    }

    myhost.endEditDescription(p, newDescription);
});

/**
 *  Event: cancel description edition when pressing 'Escape' key
 */
$(document).on('keydown', '.host-description-input-edit', function (e) {
    if (e.key === 'Escape') {
        $(this).trigger('blur');
    }
});

/**
 *  Event: revert description input on blur (click outside)
 */
$(document).on('blur', '.host-description-input-edit', function () {
    // If already saved via Enter, do nothing
    if (this._descSaved) {
        return;
    }

    const input = $(this);

    myhost.endEditDescription(input.closest('p.host-description-input'), input.attr('data-original'));
});

/**
 *  Event: click on "Add tags" button
 */
$(document).on('click', '.host-add-tags-btn', function () {
    myhost.startEditTags(myhost.getMetaScope(this).find('.host-tags-display'));
});

/**
 *  Event: single-click on an empty tags area to add tags
 */
$(document).on('click', '.host-tags-display.host-tags-empty', function () {
    myhost.startEditTags($(this));
});

/**
 *  Event: double-click on existing tags to edit them
 */
$(document).on('dblclick', '.host-tags-display', function () {
    clearTimeout(hostTagClickTimer);
    myhost.startEditTags($(this));
});

/**
 *  Event: save tags when clicking outside the tags editor
 */
$(document).on('mousedown', function (e) {
    $('.host-tags-display.editing').each(function () {
        const display = $(this);

        // Ignore clicks inside the editor itself or inside the select2 dropdown
        if ($(e.target).closest('.host-tags-display').is(display) || $(e.target).closest('.select2-container, .select2-dropdown').length > 0) {
            return;
        }

        myhost.saveTags(display);
    });
});

/**
 *  Event: click on a tag in the hosts list to filter the hosts by this tag
 *  When the tags can be edited, the filter waits for a possible double-click (edit the tags)
 */
$(document).on('click', '.host-line .host-tag-item', function (e) {
    e.stopPropagation();

    const tag = $(this).text().trim();

    clearTimeout(hostTagClickTimer);

    if (!$(this).closest('.host-tags-display').hasClass('host-tags-editable')) {
        HostSearch.addFilterValue('tag', tag);
        return;
    }

    hostTagClickTimer = setTimeout(function () {
        HostSearch.addFilterValue('tag', tag);
    }, 250);
});
