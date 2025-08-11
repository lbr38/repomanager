/**
 *  Event: switch to compact view
 */
$(document).on('click','#compact-view-btn',function () {
    // Get current view mode from cookie
    var viewMode = mycookie.get('hosts/compact-view');

    // If view mode is set to true, then set it to false
    if (viewMode == 1) {
        mycookie.set('hosts/compact-view', 0, 365);
    }

    // If there was no cookie or if view mode is set to false, then set it to true
    if (viewMode == "" || viewMode == 0) {
        mycookie.set('hosts/compact-view', 1, 365);
    }

    // Reload the container
    mycontainer.reload('hosts/list', '#hosts');
});

/**
 *  Event: print help tooltip for search host input
 */
$(document).on('mouseenter', '.search-host-tooltip', function (e) {
    mytooltip.loading(e);

    content  = '<p>Syntax</p>';
    content += '<p><code>search</code></p>';

    content += '<p class="margin-top-10">Filters</p>';
    content += '<div class="flex align-item-center flex-wrap column-gap-5 row-gap-8 max-width-500">';
    content += '<code>hostname=HOSTNAME</code>';
    content += '<code>os=OS</code>';
    content += '<code>os-version=VERSION</code>';
    content += '<code>os-family=FAMILY</code>';
    content += '<code>type=TYPE</code>';
    content += '<code>kernel=KERNEL</code>';
    content += '<code>arch=ARCHITECTURE</code>';
    content += '<code>profile=PROFILE</code>';
    content += '<code>env=ENVIRONMENT</code>';
    content += '<code>agent-version=VERSION</code>';
    content += '<code>reboot-required=true/false</code>';
    content += '</div>';
    content += '<p class="note margin-top-5">Example: get the list of hosts with Ubuntu OS and search for IP 192.168.x</p>';
    content += '<p><code>os=ubuntu 192.168</code></p>';

    content += '<div class="flex flex-direction-column row-gap-5 margin-top-10">';
    content += '<p>● Main search is performed on all fields, use filters to narrow down results</p>';
    content += '<p>● You can combine multiple filters</p>';
    content += '<p>● Use quotes when filter contains spaces, e.g os="Linux Mint"</p>';
    content += '<p>● Search and filters are case-insensitive</p>';
    content += '</div>';

    // Print tooltip
    mytooltip.print(content, e);
});

/**
 *  Event: print help tooltip for search package input
 */
$(document).on('mouseenter', '.search-package-tooltip', function (e) {
    mytooltip.loading(e);

    content  = '<p>Syntax</p>';
    content += '<p><code>name=PACKAGE_NAME</code></p>';

    content += '<p class="margin-top-10">Optional filters</p>';
    content += '<div class="flex flex-direction-column row-gap-5 max-width-500">';
    content += '<p><code>version=PACKAGE_VERSION</code></p>';
    content += '<p><code>strict-name=true/false</code></p>';
    content += '<p><code>strict-version=true/false</code></p>';
    content += '</div>';
    content += '<p class="note margin-top-5">Example: strict search for apache2 package version 2.4.x</p>';
    content += '<p><code>name=apache2 version=2.4 strict-name=true</code></p>';

    content += '<div class="flex flex-direction-column row-gap-5 margin-top-10">';
    content += '<p>● Use scrict filters to search for exact package name and/or version</p>';
    content += '<p>● Search and filters are case-insensitive</p>';
    content += '</div>';

    // Print tooltip
    mytooltip.print(content, e);
});
