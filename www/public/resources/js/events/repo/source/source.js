/**
 *  Events listeners
 */

/**
 *  Event: Add source repo
 */
$(document).on('submit','#add-source-repo-form',function () {
    event.preventDefault();

    var params = {};

    /**
     *  Retrieve source repo type
     */
    params['type'] = $('input[name=addSourceRepoType]:checked').val();

    /**
     *  Retrieve source repo name
     */
    params['name'] = $('input[name=addSourceName]').val();

    /**
     *  Retrieve source repo url
     */
    params['url'] = $('input[name=addSourceUrl]').val();

    ajaxRequest(
        // Controller:
        'repo/source/source',
        // Action:
        'new',
        // Data:
        {
            params: params
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mypanel.reload('repos/sources/list');
    });

    return false;
});

/**
 *  Event: import source repositories from list
 */
$(document).on('submit','#import-source-repos',function () {
    event.preventDefault();

    list = $(this).find('select[name="source-repos-list"]').val();

    myalert.print('Importing source repositories...', null, null);

    ajaxRequest(
        // Controller:
        'repo/source/source',
        // Action:
        'import',
        // Data:
        {
            list: list
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mypanel.reload('repos/sources/list');
    });

    return false;
});

/**
 *  Event: add a distribution, a component, a release version or an arch, alpine or openbsd item (repository, architecture, branch, version) when pressing 'Enter' in its input
 *  Triggers the '+' button next to the input, the default action is prevented so that the parent form is not submitted
 *  The keys of the arch, alpine and openbsd source repositories are handled by events/repo/source/keys.js
 */
$(document).on('keydown', 'input.source-repo-add-distribution-input, input.source-repo-edit-distribution-add-component-input, input.source-repo-add-releasever-input, input.source-repo-arch-add-input', function (e) {
    if (e.key !== 'Enter' || e.originalEvent.isComposing) {
        return;
    }

    e.preventDefault();
    e.stopPropagation();

    // Ignore key auto-repeat to avoid sending the same value multiple times
    if (e.originalEvent.repeat) {
        return;
    }

    $(this).next('button').trigger('click');
});

/**
 *  Event: Edit source repo
 */
$(document).on('click','.source-repo-form-submit-btn',function () {
    event.preventDefault();

    var id = $(this).attr('source-id');
    var params = {};

    /**
     *  Retrieve the parameters entered by the user and push them into the object
     */
    $('form.source-repo-form[source-id="' + id + '"]').find('.source-param').each(function () {
        var name = $(this).attr('param-name');

        // If input is a checkbox and it is checked then its value is 'true', else its value is 'false'
        if ($(this).attr('type') == 'checkbox') {
            if ($(this).is(":checked")) {
                var value = 'true';
            } else {
                var value = 'false';
            }
        } else {
            var value = $(this).val();
        }

        params[name] = value;
    });

    ajaxRequest(
        // Controller:
        'repo/source/source',
        // Action:
        'edit',
        // Data:
        {
            id: id,
            params: params
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        mypanel.reload('repos/sources/list');
    });

    return false;
});

/**
 *  Event: Show/hide source repo params
 */
$(document).on('click','.source-repo-edit-param-btn',function () {
    var sourceId = $(this).attr('source-id');

    slide('.source-repo-param-div[source-id="' + sourceId + '"]');
});

/**
 *  Set a source repositories list filter (stored in a cookie for PHP to load the right data), go back to the first page and reload the table
 *  @param {string} name
 *  @param {string} value
 */
function setSourceRepoFilter(name, value)
{
    mycookie.set('tables/repos/sources/list/' + name, encodeURIComponent(value), 1);
    mycookie.delete('tables/repos/sources/list/offset');

    mytable.reload('repos/sources/list');
}

/**
 *  Event: open a source repositories type tab
 */
$(document).on('click', '.source-repo-type-btn', function () {
    setSourceRepoFilter('type', $(this).attr('source-type'));
});

/**
 *  Event: search source repositories, the table is reloaded when the user stops typing
 */
var sourceRepoSearchTimeout = null;

$(document).on('input', '#source-repo-search', function () {
    var value = $(this).val().trim();

    clearTimeout(sourceRepoSearchTimeout);

    sourceRepoSearchTimeout = setTimeout(function () {
        setSourceRepoFilter('search', value);
    }, 400);
});

// Escape clears the search, the panel is only closed (see general.js) when the search is already empty
$(document).on('keyup', '#source-repo-search', function (e) {
    if (e.key === 'Escape' && $(this).val() !== '') {
        e.stopPropagation();

        clearTimeout(sourceRepoSearchTimeout);
        $(this).val('');
        setSourceRepoFilter('search', '');
    }
});
