/**
 *  Event: click on rename repository button
 */
$(document).on('click','.repo-rename-btn',function (e) {
    e.preventDefault(e);

    // The buttons that will be displayed in the confirm box
    var buttons = [];

    if (!myrepopermission.allowedAction('rename')) {
        myalert.print('You do not have permission to rename repositories', 'error');
        return;
    }

    // Get panel
    mypanel.get('repos/rename', {
        repos: JSON.stringify([{
            'repo-id': $(this).attr('repo-id')
        }])
    });
});

/**
 *  Event: click on edit repository button
 */
$(document).on('click', '.repo-edit-btn', function () {
    mypanel.get('repos/edit', {
        'repo-id': $(this).attr('repo-id')
    });
});

/**
 *  Event: submit repository edit form
 */
$(document).on('submit', '#repo-edit-form', function (e) {
    e.preventDefault();

    const params = {
        'repo-id': $(this).attr('repo-id')
    };

    // Checkboxes value is 'true' or 'false', other inputs value is sent as is
    $(this).find('.edit-param').each(function () {
        if ($(this).attr('type') == 'checkbox') {
            params[$(this).attr('param-name')] = $(this).is(':checked') ? 'true' : 'false';
        } else {
            params[$(this).attr('param-name')] = $(this).val();
        }
    });

    ajaxRequest(
        // Controller:
        'repo/edit',
        // Action:
        'validate-execute',
        // Data:
        {
            params: JSON.stringify(params)
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    );
});

/**
 *  Event: hide the snapshots retention input when keeping all snapshots (repositories and mirrors forms)
 */
$(document).on('change', '.retention-params input[param-name="retention-keep-all"]', function () {
    $(this).closest('.retention-params').find('.retention-input-container').toggleClass('hide', $(this).is(':checked'));
});

/**
 *  Event: click on "Add a description" button (header)
 */
$(document).on('click', '.repo-add-description-btn', function () {
    const btn = $(this);
    const repoItem = btn.closest('.repo-item');
    const container = repoItem.find('.repo-description-container');
    const p = container.find('p.repo-description-input');

    // Hide the button, reveal the description container and enter edit mode
    btn.hide();
    container.show();
    myrepo.startEditDescription(p);
});

/**
 *  Event: single-click on an empty description to add one
 */
$(document).on('click', 'p.repo-description-input.repo-description-empty', function () {
    myrepo.startEditDescription($(this));
});

/**
 *  Event: double-click on an existing description to edit it
 */
$(document).on('dblclick', 'p.repo-description-input', function () {
    myrepo.startEditDescription($(this));
});

/**
 *  Event: edit repository description when pressing 'Enter' key
 */
$(document).on('keypress','.repo-description-input-edit',function (e) {
    e.stopPropagation();

    const keycode = (e.keyCode ? e.keyCode : e.which);

    if (keycode == '13') {
        const input = $(this);
        const p = input.closest('p.repo-description-input');
        const repoItem = p.closest('.repo-item');
        const newDescription = input.val().trim();

        // Mark as saved to prevent blur from reverting.
        // Use a native property (not jQuery .data) because p.text() below calls
        // .empty()/cleanData on the input, which would wipe jQuery data before the
        // synchronous blur (fired when the focused input is removed) can read it.
        this._descSaved = true;

        // Save description
        myrepo.updateDescription(input.attr('repo-id'), newDescription);

        // Revert to <p> with new value
        p.text(newDescription);

        // If description is now empty, hide the container and show the header button again
        if (!newDescription) {
            p.addClass('repo-description-empty');
            repoItem.find('.repo-description-container').hide();
            repoItem.find('.repo-add-description-btn').show();
        } else {
            p.removeClass('repo-description-empty');
        }
    }
});

/**
 *  Event: revert description input on blur (click outside)
 */
$(document).on('blur','.repo-description-input-edit',function () {
    const input = $(this);

    // If already saved via Enter, do nothing (native flag survives cleanData)
    if (this._descSaved) {
        return;
    }

    const p = input.closest('p.repo-description-input');
    const repoItem = p.closest('.repo-item');
    const originalDescription = input.attr('data-original');

    // Revert to <p> with original value
    p.text(originalDescription);

    // If description is empty, hide the container and show the header button again
    if (!originalDescription) {
        p.addClass('repo-description-empty');
        repoItem.find('.repo-description-container').hide();
        repoItem.find('.repo-add-description-btn').show();
    } else {
        p.removeClass('repo-description-empty');
    }
});

/**
 *  Event: click on "Add tags" button (header)
 */
$(document).on('click', '.repo-add-tags-btn', function () {
    const btn = $(this);
    const repoItem = btn.closest('.repo-item');
    const container = repoItem.find('.repo-tags-container');
    const display = container.find('div.repo-tags-display');

    // Hide the button, reveal the tags container and enter edit mode
    btn.hide();
    container.show();
    myrepo.startEditTags(display);
});

/**
 *  Event: single-click on an empty tags area to add tags
 */
$(document).on('click', '.repo-tags-display.repo-tags-empty', function () {
    myrepo.startEditTags($(this));
});

/**
 *  Event: single-click on a tag to filter the repositories search by it
 */
$(document).on('click', '.repo-tag-item', function (e) {
    e.stopPropagation();

    const tag = $(this).find('p').text().trim();

    if (tag) {
        myrepo.searchByTag(tag);
    }
});

/**
 *  Event: double-click on existing tags to edit them
 */
$(document).on('dblclick', '.repo-tags-display', function () {
    myrepo.startEditTags($(this));
});

/**
 *  Event: save tags when clicking outside the tags editor
 */
$(document).on('mousedown', function (e) {
    const editing = $('.repo-tags-display.editing');

    if (editing.length === 0) {
        return;
    }

    editing.each(function () {
        const display = $(this);

        // Ignore clicks inside the editor itself or inside the select2 dropdown/container
        if ($(e.target).closest('.repo-tags-display').is(display)) {
            return;
        }

        if ($(e.target).closest('.select2-container, .select2-dropdown').length > 0) {
            return;
        }

        myrepo.saveRepoTags(display);
    });
});
