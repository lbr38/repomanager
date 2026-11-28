/**
 *  Event: send a test email
 */
$(document).on('click','#send-test-email-btn',function () {
    ajaxRequest(
        // Controller:
        'settings',
        // Action:
        'sendTestEmail',
        // Data:
        {},
        // Print success alert:
        true,
        // Print error alert:
        true
    );
});

/**
 *  Event: apply settings
 */
$(document).on('submit','.settings-form',function () {
    event.preventDefault();

    var settings_params_obj = {};

    /**
     *  Loop through each input in the settings form
     */
    $('.reloadable-container[container="settings/settings"]').find('.settings-param').each(function () {
        /**
         *  Getting param name in the 'param-name' attribute of each input
         */
        var param_name = $(this).attr('param-name');

        /**
         *  If input is a checkbox and it is checked then its value is 'true'
         *  Else its value is 'false'
         */
        if ($(this).attr('type') == 'checkbox') {
            if ($(this).is(":checked")) {
                var param_value = 'true';
            } else {
                var param_value = 'false';
            }

        /**
         *  If input is a radio then get its value only if it is checked, else process the next param
         */
        } else if ($(this).attr('type') == 'radio') {
            if ($(this).is(":checked")) {
                var param_value = $(this).val();
            } else {
                return; // In jquery '.each()' loops, return is like 'continue'
            }
        } else {
            /**
             *  If input is not a checkbox nor a radio then get its value
             */
            var param_value = $(this).val();
        }

        /**
         *  Add param name and value to the global object array
         */
        settings_params_obj[param_name] = param_value;
    });

    /**
     *  Convert object array to JSON before sending
     */
    var settings_params_json = JSON.stringify(settings_params_obj);

    ajaxRequest(
        // Controller:
        'settings',
        // Action:
        'applySettings',
        // Data:
        {
            settings_params: settings_params_json,
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        [
            'header/menu',
            'header/general-error-messages',
            'settings/settings',
        ]
    );

    return false;
});

/**
 *  Settings page layout: sections navigation, search and unsaved changes
 *  Sections are only hidden, never removed from the DOM: the save handler above must keep sending every .settings-param
 */
const settingsLayout = {
    // Settings values when the page (or the settings container) was loaded, used to detect unsaved changes
    baseline: null,

    /**
     *  Return the settings values, collected the same way as the save handler
     *  Values are stringified so that multiple selects (arrays) can be compared
     */
    values: function () {
        var values = {};

        $('.reloadable-container[container="settings/settings"]').find('.settings-param').each(function () {
            var name = $(this).attr('param-name');

            if ($(this).attr('type') == 'checkbox') {
                values[name] = $(this).is(':checked') ? 'true' : 'false';
            } else if ($(this).attr('type') == 'radio') {
                if ($(this).is(':checked')) {
                    values[name] = $(this).val();
                }
            } else {
                values[name] = JSON.stringify($(this).val());
            }
        });

        return values;
    },

    /**
     *  Return the active section, from the URL hash (e.g. /settings#sso), else the first section
     */
    activeSection: function () {
        var hash = window.location.hash.replace('#', '');

        if (/^[a-z-]+$/.test(hash) && $('.settings-section[section="' + hash + '"]').length) {
            return hash;
        }

        return $('.settings-section').first().attr('section');
    },

    /**
     *  Return the text a settings field can be found with: section and card titles, field title, descriptions, choices and param name
     */
    searchText: function (field) {
        if (field.dataset.search === undefined) {
            var section = $(field).closest('.settings-section').find('.group-header-name').text();
            var card = $(field).closest('.div-generic-blue').find('h5').first().text();
            var params = $(field).find('.settings-param').map(function () {
                return $(this).attr('param-name');
            }).get().join(' ');
            var text = $(field).clone().find('.settings-modified-label, .user-generated-passwd').remove().end().text();

            field.dataset.search = (section + ' ' + card + ' ' + text + ' ' + params).toLowerCase().replace(/\s+/g, ' ');
        }

        return field.dataset.search;
    },

    /**
     *  Called when the settings container is (re)loaded: the current values become the saved values
     */
    refresh: function () {
        this.baseline = this.values();
        this.updateUnsaved();
        this.apply();
    },

    /**
     *  Display the active section, or every field matching the search
     */
    apply: function () {
        var words = ($('#settings-search').val() || '').trim().toLowerCase().split(/\s+/).filter(function (word) {
            return word !== '';
        });
        var searching = words.length > 0;
        var active = this.activeSection();
        var total = 0;

        $('.settings-section').each(function () {
            var section = $(this).attr('section');
            var matches = 0;

            $(this).find('.settings-field').each(function () {
                var field = this;
                var match = !searching || words.every(function (word) {
                    return settingsLayout.searchText(field).includes(word);
                });

                $(this).toggleClass('hide', !match);

                if (match) {
                    matches++;
                }
            });

            // Hide the cards whose fields are all hidden
            $(this).find('.div-generic-blue').not('.settings-field').each(function () {
                var fields = $(this).find('.settings-field');
                $(this).toggleClass('hide', fields.length > 0 && fields.not('.hide').length === 0);
            });

            // Hide the empty columns, so that the remaining cards are displayed from the left
            $(this).find('.settings-column').each(function () {
                $(this).toggleClass('hide', $(this).find('.div-generic-blue').not('.hide').length === 0);
            });

            $(this).toggleClass('hide', searching ? matches === 0 : section !== active);
            $(this).find('.settings-search-count').text(matches).toggleClass('hide', !searching);

            // Section title: same as the active navigation button, only printed when searching (several sections displayed)
            $(this).find('.group-header').toggleClass('hide', !searching);

            // Navigation button and warning icon of this section: the active one is highlighted, the other sections (or the sections without search results) are faded
            $('.settings-nav-btn[section="' + section + '"]')
                .toggleClass('active', !searching && section === active)
                .toggleClass('mediumopacity', searching || section !== active)
                .toggleClass('opacity-30', searching && matches === 0)
                .toggleClass('settings-nav-inactive', !searching && section !== active);
            $('.settings-nav-warning[section="' + section + '"]').toggleClass('hide', $(this).find('.note-warning').length === 0);

            if (searching) {
                total += matches;
            }
        });

        $('#settings-search-empty').toggleClass('hide', !searching || total > 0);
    },

    /**
     *  Print the number of unsaved changes, on the save buttons, on the sections navigation and on each modified field
     */
    updateUnsaved: function () {
        if (this.baseline === null) {
            return;
        }

        var baseline = this.baseline;
        var current = this.values();
        var counts = {};
        var changed = Object.keys(current).filter(function (name) {
            return current[name] !== baseline[name];
        });

        $('.settings-modified-label').remove();

        changed.forEach(function (name) {
            var param = $('.reloadable-container[container="settings/settings"]').find('.settings-param[param-name="' + name + '"]').first();
            var section = param.closest('.settings-section').attr('section');

            param.closest('.settings-field').find('h6').first().append('<span class="label-yellow label-medium settings-modified-label">modified</span>');
            counts[section] = (counts[section] || 0) + 1;
        });

        $('.settings-nav-unsaved').each(function () {
            var count = counts[$(this).attr('section')] || 0;
            $(this).text(count).toggleClass('hide', count === 0);
        });

        $('.settings-unsaved-label').text(changed.length + ' unsaved change' + (changed.length > 1 ? 's' : '')).toggleClass('hide', changed.length === 0);
    }
};

/**
 *  Event: open a settings section (or scroll to it when searching)
 */
$(document).on('click', '.settings-nav-btn', function () {
    var section = $(this).attr('section');

    if (($('#settings-search').val() || '').trim() !== '') {
        var target = $('.settings-section[section="' + section + '"]');

        if (target.length && !target.hasClass('hide')) {
            target[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        return;
    }

    history.replaceState(null, '', '#' + section);
    settingsLayout.apply();
});

/**
 *  Event: search settings
 */
$(document).on('input', '#settings-search', function () {
    settingsLayout.apply();
});

$(document).on('keydown', '#settings-search', function (e) {
    if (e.key === 'Escape') {
        $(this).val('');
        settingsLayout.apply();
    }
});

/**
 *  Event: section changed from the URL
 */
$(window).on('hashchange', function () {
    settingsLayout.apply();
});

/**
 *  Event: a setting value changed
 */
$(document).on('input change', '.reloadable-container[container="settings/settings"] .settings-param', function () {
    settingsLayout.updateUnsaved();
});

/**
 *  Event: when some content is reloaded (environments, users), forget the search index and filter the content again while searching
 */
$(document).ajaxStop(function () {
    $('.settings-field').each(function () {
        delete this.dataset.search;
    });

    if (($('#settings-search').val() || '').trim() !== '') {
        settingsLayout.apply();
    }
});
