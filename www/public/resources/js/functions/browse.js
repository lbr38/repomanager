/**
 *  Print packages tree
 */
function printTree()
{
    $('#loading-tree').remove();
    $('#explorer').show();

    // hide all the sub-menus
    $("div.explorer-toggle").next().hide();

    // set the cursor of the toggling span elements
    $("div.explorer-toggle").css("cursor", "pointer");

    // prepend a plus sign to signify that the sub-menus aren't expanded
    $("div.explorer-toggle").prepend("+ ");

    // add a click function that toggles the sub-menu when the corresponding
    // span element is clicked
    $("div.explorer-toggle").click(function () {
        $(this).next().toggle(100);

        // switch the plus to a minus sign or vice-versa
        var v = $(this).html().substring(0, 1);
        if ( v == "+" ) {
            $(this).html("-" + $(this).html().substring(1));
        } else if ( v == "-" ) {
            $(this).html("+" + $(this).html().substring(1));
        }
    });
}

/**
 *  Download package
 */
function downloadPackage()
{
    packagesToDownload = [];

    /**
     *  Get all selected checkboxes and their file-id (media) attribute
     */
    $('#packages-list').find('input[name=packageName\\[\\]]:checked').each(function () {
        packagesToDownload.push({ filename: $(this).attr('filename'), path: $(this).attr('path') });
    });

    /**
     *  Append a temporary <a> element to download files
     */
    var temporaryDownloadLink = document.createElement("a");
    temporaryDownloadLink.style.display = 'none';

    document.body.appendChild(temporaryDownloadLink);

    for (var n = 0; n < packagesToDownload.length; n++) {
        var download = packagesToDownload[n];
        temporaryDownloadLink.setAttribute('href', '/repo/' + download.path);
        temporaryDownloadLink.setAttribute('download', download.filename);

        /**
         *  Click on the <a> element to start download
         */
        temporaryDownloadLink.click();
    }

    /**
     *  Remove temporary <a> element
     */
    document.body.removeChild(temporaryDownloadLink);
}

/**
 * Delete packages
 * @param {*} snapId
 */
function deletePackages(snapId)
{
    var packages = [];

    // Get the path of the selected packages
    $('body').find('input[name=packageName\\[\\]]:checked').each(function () {
        packages.push($(this).attr('path'));
    });

    ajaxRequest(
        // Controller:
        'browse',
        // Action:
        'deletePackage',
        {
            snapId: snapId,
            packages: packages
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload containers:
        ['browse/list', 'browse/actions']
    );
}