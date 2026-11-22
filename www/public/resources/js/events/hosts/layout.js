/**
 *  Event: switch to compact view
 */
$(document).on('click','#compact-view-btn',function () {
    // Get current view mode from cookie
    var viewMode = mycookie.get('hosts/compact-view');

    // Default to compact view ("1") if no cookie is set - same as PHP logic
    if (viewMode == "") {
        viewMode = "1";
    }

    // Toggle the view mode and set the new value
    var newViewMode;
    
    // If compact view is active, switch to full view
    if (viewMode == "1") {
        newViewMode = "0";
        mycookie.set('hosts/compact-view', 0, 365);
    }
    // If full view is active, switch to compact view
    else {
        newViewMode = "1";
        mycookie.set('hosts/compact-view', 1, 365);
    }

    // Update button text, title and icon using the NEW view mode
    $(this).find('span').text(newViewMode == "1" ? 'More details' : 'Less details');
    $(this).attr('title', newViewMode == "1" ? 'Show more details' : 'Show less details');
    $(this).find('img').attr('src', newViewMode == "1" ? '/assets/icons/plus.svg' : '/assets/icons/minus.svg');

    // Reload the container
    mycontainer.reload('hosts/list', '#hosts');
});

/**
 *  Search input events, restore the search from the page URL (?search=...) on page load
 */
HostSearch.init();

/**
 *  Event: print help tooltip for search input
 */
$(document).on('mouseenter', '.search-host-tooltip', function () {
    content  = '<p>Syntax</p>';
    content += '<p><code>search</code> <code>filter=value</code></p>';

    content += '<p class="margin-top-10">Host filters <span class="note">(click to add)</span></p>';
    content += HostSearch.filtersList([
        'hostname=HOSTNAME',
        'os=OS',
        'os-version=VERSION',
        'os-family=FAMILY',
        'type=TYPE',
        'kernel=KERNEL',
        'arch=ARCHITECTURE',
        'profile=PROFILE',
        'env=ENVIRONMENT',
        'agent-status=STATUS',
        'agent-version=VERSION',
        'reboot-required=true/false',
        'compliant=true/false',
        'tag=TAG'
    ]);

    content += '<p class="margin-top-10">Package filters <span class="note">(click to add)</span></p>';
    content += HostSearch.filtersList([
        'package=NAME',
        'version=VERSION',
        'strict',
        'strict-name',
        'strict-version',
        'absent'
    ]);

    content += '<p class="note margin-top-10">Example: Ubuntu hosts with an IP in 192.168.x</p>';
    content += '<p><code>os=ubuntu 192.168</code></p>';
    content += '<p class="note margin-top-5">Example: prod hosts that are not Debian based</p>';
    content += '<p><code>env=prod os-family!=debian</code></p>';
    content += '<p class="note margin-top-5">Example: prod hosts with apache2 2.4.x installed (exact package name)</p>';
    content += '<p><code>env=prod package=apache2 version=2.4 strict-name</code></p>';
    content += '<p class="note margin-top-5">Example: hosts where nginx is not installed</p>';
    content += '<p><code>package!=nginx</code></p>';

    content += '<div class="flex flex-direction-column row-gap-5 margin-top-10">';
    content += '<p>• Free text is searched in all fields, each word must match</p>';
    content += '<p>• Filters can be combined, hosts must match all of them</p>';
    content += '<p>• Use <code>!=</code> to exclude, e.g. <code>env!=prod</code> or <code>package!=nginx</code> (same as <code>absent</code>)</p>';
    content += '<p>• <code>strict</code> = exact package name and version</p>';
    content += '<p>• Type a filter name or <code>filter=</code> to get suggestions, pick one with Tab, Enter or a click</p>';
    content += '<p>• The search is kept in the page URL, it can be bookmarked or shared</p>';
    content += '<p>• Use quotes when a value contains spaces, e.g os="Linux Mint"</p>';
    content += '<p>• Search is case-insensitive, press Escape to clear it</p>';
    content += '</div>';

    // Print tooltip
    mytooltip.show(this, content);
});

/**
 *  Event: show / hide all hosts groups
 */
$(document).on('click','#hide-all-host-groups',function () {
    var hide = $(this).attr('state') == 'visible';
    var newState = hide ? 'hidden' : 'visible';

    $(this).attr('state', newState);
    $(this).attr('title', hide ? 'Show all host groups' : 'Hide all host groups');
    $(this).find('img').attr('src', hide ? '/assets/icons/view-off.svg' : '/assets/icons/view.svg');
    $(this).find('span').text(hide ? 'Expand all' : 'Collapse all');

    $('.hosts-group-container').each(function () {
        var content = $(this).children('.hosts-group-content');
        var name = $(this).find('.hide-host-group');

        if (hide) {
            content.slideUp(200);
        } else {
            content.slideDown(200);
        }

        name.attr('state', newState);
        name.attr('title', (hide ? 'Expand group ' : 'Collapse group ') + name.text());
    });
});

/**
 *  Event: show / hide a hosts group content
 */
$(document).on('click','.hide-host-group',function () {
    var hide = $(this).attr('state') == 'visible';

    $(this).attr('state', hide ? 'hidden' : 'visible');
    $(this).attr('title', (hide ? 'Expand group ' : 'Collapse group ') + $(this).text());

    $(this).closest('.hosts-group-container').children('.hosts-group-content').slideToggle(200);
});
