/**
 *  Signing keys panel: GPG keys of the trusted keyring, RSA keys of the alpine source repositories and signify keys of the openbsd ones
 *  One section per type of key, displayed one at a time as in the settings page. Each section has its own search (and filter for
 *  the GPG keys), as in the tasks list. The active section, searches and filter are kept in session storage, to be restored when
 *  the panel is reloaded
 */

/**
 *  Return the signing keys panel
 */
function signingKeysPanel()
{
    return $('.slide-panel-container[slide-panel="repos/sources/keys"]');
}

/**
 *  Restore the active section, searches and GPG filter of the panel, then filter the keys
 *  Called by the panel itself, each time it is loaded or reloaded
 */
function signingKeysInit()
{
    const panel = signingKeysPanel();
    const filter = sessionStorage.getItem('signing-keys/filter');

    // The filter may not exist anymore (no more key matching it)
    if (filter && panel.find('select.signing-keys-gpg-filter option[value="' + filter + '"]').length) {
        panel.find('select.signing-keys-gpg-filter').val(filter);
    }

    panel.find('input.signing-keys-search').each(function () {
        $(this).val(sessionStorage.getItem('signing-keys/search/' + $(this).attr('section')) || '');
    });

    signingKeysApply();
}

/**
 *  Return the active section (gpg, alpine or openbsd)
 */
function signingKeysActiveSection()
{
    const section = sessionStorage.getItem('signing-keys/section');

    if (section && signingKeysPanel().find('.signing-keys-section[section="' + section + '"]').length) {
        return section;
    }

    return 'gpg';
}

/**
 *  Show the active section and, in each section, the keys matching its search (all the words) and the GPG filter
 *  The selection of the hidden keys is cleared, so that only the keys shown can be deleted
 */
function signingKeysApply()
{
    const panel = signingKeysPanel();
    const active = signingKeysActiveSection();
    const filter = panel.find('select.signing-keys-gpg-filter').val() || 'all';

    panel.find('.signing-keys-section').each(function () {
        const section = $(this).attr('section');
        const items = $(this).find('.signing-keys-item');
        const terms = ($(this).find('input.signing-keys-search').val() || '').toLowerCase().split(/\s+/).filter(Boolean);
        const compact = terms.join('');
        const searching = terms.length > 0;
        const filtering = section === 'gpg' && filter !== 'all';
        let matches = 0;

        items.each(function () {
            const text = $(this).attr('search') || '';
            const fingerprints = $(this).attr('search-fpr') || '';
            const filters = ($(this).attr('filters') || '').split(' ');

            // A word matches the text (names, user Ids, links, source repositories...) or, from 8 characters, a fingerprint
            // A fingerprint can also be searched with spaces (e.g. 'B8B8 0B5B 623E ...')
            const match = (terms.every(term => text.includes(term) || (term.length >= 8 && fingerprints.includes(term))) || (/^[0-9a-f]{8,}$/.test(compact) && fingerprints.includes(compact)))
                && (section !== 'gpg' || filter === 'all' || filters.includes(filter));

            $(this).toggle(match);

            if (match) {
                matches++;
            }
        });

        // Hide the source repositories groups without matching key, unless there is no search (show the groups without key)
        $(this).find('.signing-keys-group').each(function () {
            $(this).toggle(!searching || $(this).find('.signing-keys-item').filter(function () {
                return $(this).css('display') !== 'none';
            }).length > 0);
        });

        $(this).find('.signing-keys-no-result').toggleClass('hide', items.length === 0 || matches > 0);

        // Only the active section is shown, the navigation buttons of the other sections are faded and their count is not blue
        $(this).toggleClass('hide', section !== active);
        panel.find('.signing-keys-nav-btn[section="' + section + '"]').toggleClass('mediumopacity panel-tab-inactive', section !== active);

        // Number of keys of the section, in the navigation: matching / total when searching or filtering
        const count = panel.find('.signing-keys-nav-count[section="' + section + '"]');
        count.attr('class', (section === active ? 'label-count' : 'label-tr') + ' label-medium signing-keys-nav-count');
        count.text(searching || filtering ? matches + '/' + count.attr('total') : count.attr('total'));
    });

    // A hidden key cannot stay selected
    panel.find('.signing-keys-item').each(function () {
        if ($(this).css('display') === 'none' || $(this).closest('.signing-keys-section').hasClass('hide')) {
            $(this).find('input.child-checkbox').prop('checked', false);
        }
    });

    signingKeysRefreshSelection();
}

/**
 *  Return the checkboxes of the keys shown in a section (matching the search and the filter), or in all the sections
 */
function signingKeysVisibleCheckboxes(section = null)
{
    const sections = signingKeysPanel().find('.signing-keys-section' + (section ? '[section="' + section + '"]' : '')).not('.hide');

    return sections.find('.signing-keys-item').filter(function () {
        return $(this).css('display') !== 'none';
    }).find('input.child-checkbox');
}

/**
 *  Update the 'select all' checkboxes and the confirm box (actions on the selected keys), from the selected keys
 *  The keys of a single section can be selected at a time (one confirm box per type of key)
 */
function signingKeysRefreshSelection()
{
    const panel = signingKeysPanel();

    panel.find('input.signing-keys-select-all').each(function () {
        const checkboxes = signingKeysVisibleCheckboxes($(this).attr('section'));

        $(this).prop('checked', checkboxes.length > 0 && checkboxes.filter(':checked').length === checkboxes.length);
    });

    // Print the confirm box of the selected keys, or close it if no key is selected anymore
    const checked = panel.find('input.child-checkbox:checked');

    if (checked.length > 0) {
        mycheckbox.select(checked.first());
    } else if ($('#confirm-box').length && $('#confirm-box').attr('confirm-box-id') === 'signing-keys-confirm-box') {
        myconfirmbox.close();
    }
}

/**
 *  Unselect the keys of the other sections than the specified one
 */
function signingKeysClearOtherSections(section)
{
    signingKeysPanel().find('.signing-keys-section').not('[section="' + section + '"]').find('input.child-checkbox').prop('checked', false);
}

/**
 *  Remove the selected keys of alpine or openbsd source repositories ('<source id>/<key name>'), one after the other
 */
async function signingKeysRemoveSourceKeys(refs)
{
    let removed = 0;

    try {
        for (const ref of refs) {
            const separator = ref.indexOf('/');

            await ajaxRequest('repo/source/keys', 'remove-key', { id: ref.substring(0, separator), name: ref.substring(separator + 1) }, false, true);
            removed++;
        }

        myalert.print(removed + ' key' + (removed > 1 ? 's' : '') + ' removed', 'success');
    } catch (error) {
        // The error is printed by ajaxRequest
    } finally {
        reloadSigningKeysPanels();
    }
}

/**
 *  Selected GPG keys: delete them from the trusted keyring
 */
checkboxesCallback['signing-keys-gpg'] = function (id, count) {
    const referenced = count.filter(function () {
        return parseInt($(this).attr('usage-count')) > 0;
    }).length;

    let message = count.length + ' GPG key' + (count.length > 1 ? 's' : '') + ' selected';

    if (referenced > 0) {
        message += ', ' + referenced + ' referenced by source repositories: their signature can no longer be verified (the sync fails if the signature check is enabled)';
    }

    myconfirmbox.print(
        {
            'title': 'GPG keys',
            'message': message,
            'id': 'signing-keys-confirm-box',
            'buttons': [
            {
                'text': 'Delete',
                'color': 'red',
                'callback': function () {
                    ajaxRequest(
                        // Controller:
                        'repo/source/source',
                        // Action:
                        'delete-gpgkey',
                        // Data:
                        {
                            id: id
                        },
                        // Print success alert:
                        true,
                        // Print error alert:
                        true
                    ).finally(function () {
                        reloadSigningKeysPanels();
                    });
                }
            }]
        }
    );
};

/**
 *  Selected RSA or signify keys: remove them from their source repository
 */
checkboxesCallback['signing-keys-alpine'] = checkboxesCallback['signing-keys-openbsd'] = function (id, count) {
    myconfirmbox.print(
        {
            'title': count.first().attr('checkbox-id') === 'signing-keys-alpine' ? 'RSA keys' : 'Signify keys',
            'message': count.length + ' key' + (count.length > 1 ? 's' : '') + ' selected: the signatures made with ' + (count.length > 1 ? 'them' : 'it') + ' can no longer be verified (the sync fails if the signature check is enabled)',
            'id': 'signing-keys-confirm-box',
            'buttons': [
            {
                'text': 'Remove',
                'color': 'red',
                'callback': function () {
                    signingKeysRemoveSourceKeys(id);
                }
            }]
        }
    );
};

/**
 *  Reload the signing keys panel and the source repositories panel (its source repositories forms print the name of the arch GPG
 *  keys and the alpine and openbsd keys)
 */
function reloadSigningKeysPanels()
{
    mypanel.reload('repos/sources/keys');
    mypanel.reload('repos/sources/list');
}


/**
 *  Event: open a section
 */
$(document).on('click','.signing-keys-nav-btn',function () {
    sessionStorage.setItem('signing-keys/section', $(this).attr('section'));
    signingKeysApply();
});

/**
 *  Event: filter the GPG keys by status
 */
$(document).on('change','select.signing-keys-gpg-filter',function () {
    sessionStorage.setItem('signing-keys/filter', $(this).val());
    signingKeysApply();
});

/**
 *  Event: search the keys of a section
 */
$(document).on('input','input.signing-keys-search',function () {
    sessionStorage.setItem('signing-keys/search/' + $(this).attr('section'), $(this).val());
    signingKeysApply();
});

/**
 *  Event: clear the search with Escape, the panel is only closed (see general.js) when the search is already empty
 *  Same event as general.js (keyup), so that stopping the propagation prevents the panel from being closed
 */
$(document).on('keyup','input.signing-keys-search',function (e) {
    if (e.key === 'Escape' && $(this).val() !== '') {
        e.stopPropagation();
        $(this).val('').trigger('input');
    }
});

/**
 *  Event: show or hide the details of a key, only one key details are shown at a time
 */
$(document).on('click','.signing-keys-row',function () {
    const item = $(this).closest('.signing-keys-item');

    // Hide the details of the other keys
    signingKeysPanel().find('.signing-keys-item').not(item).find('.signing-keys-details').slideUp(100);

    item.find('.signing-keys-details').slideToggle(100);
});

/**
 *  Event: select or unselect all the keys shown in a section (matching the search and the filter)
 */
$(document).on('click','input.signing-keys-select-all',function (e) {
    e.stopPropagation();

    const section = $(this).attr('section');

    signingKeysClearOtherSections(section);
    signingKeysVisibleCheckboxes(section).prop('checked', $(this).is(':checked'));
    signingKeysRefreshSelection();
});

/**
 *  Event: select or unselect a key, the keys of the other sections are unselected
 *  The confirm box is printed by the checkboxes callbacks (see mycheckbox.select())
 */
$(document).on('click','.signing-keys-item input.child-checkbox',function () {
    signingKeysClearOtherSections($(this).closest('.signing-keys-section').attr('section'));
    signingKeysRefreshSelection();
});
