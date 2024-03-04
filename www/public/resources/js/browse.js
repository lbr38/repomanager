/**
 *  Print packages tree
 */
function printTree()
{
    $('#loading').remove();
    $('#explorer').show();

    // hide all the sub-menus
    $("div.explorer-toggle").next().hide();

    // add a link nudging animation effect to each link
    $("#explorer a, #explorer div.explorer-toggle").hover(
        function () {
            $(this).stop().animate({
                paddingLeft: '10px',
            }, 200);
        },
        function () {
            $(this).stop().animate({
                paddingLeft: '0',
            }, 200);
        }
    );

    // set the cursor of the toggling span elements
    $("div.explorer-toggle").css("cursor", "pointer");

    // prepend a plus sign to signify that the sub-menus aren't expanded
    $("div.explorer-toggle").prepend("+ ");

    // add a click function that toggles the sub-menu when the corresponding
    // span element is clicked
    $("div.explorer-toggle").click(function () {
        $(this).next().toggle(200);
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
 *  Event: when we click on a checkbox, we show the 'Delete' button
 */
$(document).on('click',".packageName-checkbox",function () {
    // Count the number of checked checkbox
    var checked = $('body').find('input[name=packageName\\[\\]]:checked').length;

    // If there is at least 1 checkbox selected then we show the 'Delete' button
    if (checked >= 1) {
        var snapId = $('#packages-list').attr('snap-id');
        var packages = [];

        // Get the path of the selected packages
        $('body').find('input[name=packageName\\[\\]]:checked').each(function () {
            packages.push($(this).attr('path'));
        });

        confirmBox(
            '',
            function () {
                ajaxRequest('browse', 'deletePackage', {snapId: snapId, packages: packages}, true, true, ['browse/list', 'browse/actions']); },
            'Delete',
            function () {
                downloadPackage(); },
            'Download'
        );
    }

    // If no checkbox is selected then we hide the 'Delete' button
    if (checked == 0) {
        closeConfirmBox();
    }
});

/**
 *  Event: rebuild metadata
 */
$(document).on('click',"#rebuildBtn",function () {
    var snapId = $(this).attr('snap-id');
    var gpgSign = 'false';

    if ($('input[type=checkbox][name=gpgSign]').is(':checked')) {
        var gpgSign = 'true';
    }

    ajaxRequest(
        // Controller:
        'browse',
        // Action:
        'rebuild',
        // Data:
        {
            snapId: snapId,
            gpgSign: gpgSign
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload containers:
        ['browse/list', 'browse/actions']
    );
});
