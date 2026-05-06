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
        'repo/browse',
        // Action:
        'delete-package',
        {
            snapId: snapId,
            packages: packages
        },
        // Print success alert:
        false,
        // Print error alert:
        true
    ).then(function () {
        // Reload packages list and actions
        mycontainer.reload('browse/list');
        mycontainer.reload('browse/actions');

        // Print packages that have been deleted
        var deletedNames = jsonValue.message.map(function (pkg) { return pkg.name; });
        var maxDisplay = 10;
        var message = 'Packages deleted: <br>' + deletedNames.slice(0, maxDisplay).join('<br>');
        if (deletedNames.length > maxDisplay) {
            message += '<br>+' + (deletedNames.length - maxDisplay) + ' more...';
        }
        myalert.print(message, 'success');
    });
}