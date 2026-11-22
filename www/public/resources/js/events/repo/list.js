/**
 *  Event: show / hide all repos groups
 */
$(document).on('click','#hide-all-repo-groups',function () {
    var hide = $(this).attr('state') == 'visible';

    myrepo.setHideAllGroupsBtn(hide);

    $('.repos-list-group').each(function () {
        myrepo.toggleGroup($(this), hide);
    });
});

/**
 *  Event: show / hide repos group content
 */
$(document).on('click','.hide-repo-group',function () {
    myrepo.toggleGroup($(this).closest('.repos-list-group'), $(this).attr('state') == 'visible');
});

/**
 *  Collapse the groups that were collapsed during the session, on page load
 */
myrepo.restoreGroups();

/**
 *  Search input events, restore the search from the page URL (?search=...) on page load
 */
RepoSearch.init();

/**
 *  Event: print help tooltip for search input
 */
$(document).on('mouseenter', '.search-repo-tooltip', function () {
    let content = '<p>Syntax</p>';
    content += '<p><code>search</code> <code>filter=value</code></p>';

    content += '<p class="margin-top-10">Filters <span class="note">(click to add)</span></p>';
    content += RepoSearch.filtersList([
        'name=NAME',
        'dist=DISTRIBUTION',
        'component=COMPONENT',
        'releasever=VERSION',
        'type=TYPE',
        'package-type=TYPE',
        'description=DESCRIPTION',
        'env=ENVIRONMENT',
        'tag=TAG'
    ]);

    content += '<p class="note margin-top-10">Example: deb repositories of the bookworm distribution</p>';
    content += '<p><code>package-type=deb dist=bookworm</code></p>';
    content += '<p class="note margin-top-5">Example: repositories with a snapshot in prod, except mirrors</p>';
    content += '<p><code>env=prod type!=mirror</code></p>';

    content += '<div class="flex flex-direction-column row-gap-5 margin-top-10">';
    content += '<p>• Free text is searched in all fields, each word must match</p>';
    content += '<p>• Filters can be combined, repositories must match all of them</p>';
    content += '<p>• Use <code>!=</code> to exclude, e.g. <code>env!=prod</code></p>';
    content += '<p>• <code>tag=</code> and <code>env=</code> must match exactly</p>';
    content += '<p>• Type a filter name or <code>filter=</code> to get suggestions, pick one with Tab, Enter or a click</p>';
    content += '<p>• The search is kept in the page URL, it can be bookmarked or shared</p>';
    content += '<p>• Use quotes when a value contains spaces, e.g description="Debian main"</p>';
    content += '<p>• Search is case-insensitive, press Escape to clear it</p>';
    content += '</div>';

    // Print tooltip
    mytooltip.show(this, content);
});
