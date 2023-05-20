var display = getCookie('display-log');
showHideLog(display);

function showHideLog(display)
{
    if (display == 'true') {
        $('.getPackagesDiv').css('display', 'block');
        $('.signRepoDiv').css('display', 'block');
        $('.createRepoDiv').css('display', 'block');
        $('#display-log-btn').attr('display', 'false');

        document.cookie = "display-log=true; Secure";
    }

    if (display == 'false') {
        $('.getPackagesDiv').css('display', 'none');
        $('.signRepoDiv').css('display', 'none');
        $('.createRepoDiv').css('display', 'none');
        $('#display-log-btn').attr('display', 'true');

        document.cookie = "display-log=false; Secure";
    }
}

/**
 *  Event: print full log
 */
$(document).on('click','#display-log-btn',function () {
    var display = $(this).attr('display');
    showHideLog(display);
});

/**
 *  Event: show logfile content
 */
$(document).on('click','.show-logfile-btn',function () {
    var logfile = $(this).attr('logfile');
    setCookie('view-logfile', logfile, 1);
    reloadContainer('operations/log');
});

/**
 *  Event: print all done operations
 */
$(document).on('click','#print-all-op',function () {
    $(".hidden-op").show();        // On affiche les opérations masquées
    $("#print-all-op").hide();    // On masque le bouton "Afficher tout"
    // Création d'un cookie (expiration 15min)
    document.cookie = "printAllOp=yes;max-age=900; Secure";
});

/**
 *  Event: print all done regular operations
 */
$(document).on('click','#print-all-regular-op',function () {
    $(".hidden-regular-op").show();        // On affiche les opérations masquées
    $("#print-all-regular-op").hide();    // On masque le bouton "Afficher tout"
    // Création d'un cookie (expiration 15min)
    document.cookie = "printAllRegularOp=yes;max-age=900; Secure";
});

/**
 *  Event: relaunch operation
 */
$(document).on('click','.relaunch-operation-btn',function () {
    var poolId = $(this).attr('pool-id');

    relaunchOperation(poolId);
});

/**
 *  Ajax : Relaunch operation
 *  @param {string} poolId
 */
function relaunchOperation(poolId)
{
    $.ajax({
        type: "POST",
        url: "ajax/controller.php",
        data: {
            controller: "operation",
            action: "relaunchOperation",
            poolId: poolId
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
        },
        error : function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}