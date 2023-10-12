setInterval(function () {
    getContainerState();
}, 2000);

/**
 *  Slide panel opening
 */
$(document).on('click','.slide-panel-btn',function () {
    var name = $(this).attr('slide-panel');
    openPanel(name);
});

/**
 *  Slide panel closing
 */
$(document).on('click','.slide-panel-close-btn',function () {
    closePanel();
});

/**
 *  Event: mark log as read
 */
$(document).on('click','.acquit-log-btn',function () {
    var id = $(this).attr('log-id');

    acquitLog(id);
});

/**
 *  Event: hide slided window on escape button press
 */
$(document).keyup(function (e) {
    if (e.key === "Escape") {
        closePanel();
    }
});

/**
 *  Event: stop operation
 */
$(document).on('click','.kill-btn',function () {
    var pid = $(this).attr('pid');
    stopOperation(pid);
});

/**
 *  Event: print a copy icon on element with .copy class
 */
$(document).on('mouseenter','.copy',function () {
    $(this).append('<img src="/assets/icons/duplicate.svg" class="icon-lowopacity icon-copy" title="Copy to clipboard">');
});

/**
 *  Event: remove copy icon on element with .copy class
 */
$(document).on('mouseleave','.copy',function () {
    $(this).find('.icon-copy').remove();
});

/**
 *  Event: copy parent text on click on element with .icon-copy class
 */
$(document).on('click','.icon-copy',function () {
    var text = $(this).parent().text().trim();

    navigator.clipboard.writeText(text).then(() => {
        printAlert('Copied to clipboard', 'success');
    },() => {
        printAlert('Failed to copy', 'error');
    });
});

/**
 * Ajax: Mark log as read
 * @param {string} id
 */
function acquitLog(id)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "general",
            action: "acquitLog",
            id: id
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            reloadContainer('header/general-log-messages');
        },
        error: function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Get and reload container
 * @param {*} container
 */
function reloadContainer(container)
{
    printLoading();

    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            sourceUrl: window.location.href,
            sourceUri: window.location.pathname,
            controller: "general",
            action: "getContainer",
            container: container
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            /**
             *  Replace container with itself, with new content
             */
            $('.reloadable-container[container="' + container + '"]').replaceWith(jsonValue.message);
        },
        error: function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });

    hideLoading();
}

/**
 *  Ajax: Get all containers state and reload them if needed
 */
function getContainerState()
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "general",
            action: "getContainerState"
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            /**
             *  Parse results and compare with current state
             */
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            containersArray = jQuery.parseJSON(jsonValue.message);
            containersArray.forEach(obj => {
                Object.entries(obj).forEach(([key, value]) => {
                    if (key == 'Container') {
                        containerName = value;
                    }
                    if (key == 'Id') {
                        containerStateId = value;
                    }
                });

                /**
                 *  If current container does not appear in cookies yet, add it
                 */
            if (getCookie(containerName) == "") {
                setCookie(containerName, containerStateId, 365);
                /**
                 *  Else compare current state with cookie state
                 */
            } else {
                var cookieState = getCookie(containerName);

                /**
                 *  If state has changed, reload container and update cookie
                 */
                if (cookieState != containerStateId) {
                    setCookie(containerName, containerStateId, 365);
                    reloadContainer(containerName);
                }
            }
            });
        },
        error: function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 * Ajax: Get and print alert box
 * @param {*} name
 */
function getConfirmBox(name)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "general",
            action: "getConfirmBox",
            name: name
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            closeConfirmBox();
            $('#newalert').remove();
            $('footer').append(jsonValue.message);
        },
        error: function (jqXHR, textStatus, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}

/**
 *  Ajax: Stop operation
 *  @param {string} pid
 */
function stopOperation(pid)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "operation",
            action: "stopOperation",
            pid: pid
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
        },
        error: function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}