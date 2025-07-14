/**
 *  Open websocket connection with server
 */
websocket_client();

/**
 *  Event: get panel
 */
$(document).on('click','.get-panel-btn',function () {
    mypanel.get($(this).attr('panel'));
});

/**
 *  Slide panel closing
 */
$(document).on('click','.slide-panel-close-btn',function () {
    mypanel.close($(this).attr('slide-panel'));
});

/**
 *  Event: show general log details
 */
$(document).on('click','.general-log-show-info-btn',function () {
    var id = $(this).attr('log-id');

    $('pre.general-log-details[log-id="' + id + '"]').toggle();
});

/**
 *  Event: mark general log as read
 */
$(document).on('click','.general-log-acquit-btn',function () {
    var id = $(this).attr('log-id');

    ajaxRequest(
        // Controller:
        'general',
        // Action:
        'acquitLog',
        // Data:
        {
            id: id
        },
        // Print success alert:
        false,
        // Print error alert:
        true,
        // Reload containers:
        ['header/general-log-messages']
    );
});

/**
 *  Event: close request log details
 */
$(document).on('click','.modal-window-close-btn',function () {
    $(".modal-window-container").remove();
});

/**
 *  Event: hide slided window and modal window on escape button press
 */
$(document).keyup(function (e) {
    if (e.key === "Escape") {
        mypanel.close();
        closeAlert();
        closeConfirmBox();
        $(".modal-window-container").remove();
    }
});

/**
 *  Event: print a copy icon on element with .copy class
 */
$(document).on('mouseenter','.copy',function () {
    // If the element is a <pre> tag, the copy icon is in the top right corner
    if ($(this).is('pre')) {
        $(this).append('<img src="/assets/icons/duplicate.svg" class="icon-lowopacity icon-copy-top-right margin-left-5" title="Copy to clipboard">');
    } else {
        $(this).append('<img src="/assets/icons/duplicate.svg" class="icon-lowopacity icon-copy margin-left-5" title="Copy to clipboard">');
    }
});

/**
 *  Event: remove copy icon on element with .copy class
 */
$(document).on('mouseleave','.copy',function () {
    $(this).find('.icon-copy').remove();
    $(this).find('.icon-copy-top-right').remove();
});

/**
 *  Event: copy parent text on click on element with .icon-copy class
 */
$(document).on('click','.icon-copy, .icon-copy-top-right',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    var text = $(this).parent().text().trim();

    navigator.clipboard.writeText(text).then(() => {
        printAlert('Copied to clipboard', 'success');
    },() => {
        printAlert('Failed to copy', 'error');
    });
});

/**
 *  Event: copy on click on element with .copy-input-onclick class
 */
$(document).on('click','.copy-input-onclick',function (e) {
    var text = $(this).val().trim();

    navigator.clipboard.writeText(text).then(() => {
        printAlert('Copied to clipboard', 'success');
    },() => {
        printAlert('Failed to copy', 'error');
    });
});

/**
 *  Event: click on a reloadable table page number
 */
$(document).on('click','.reloadable-table-page-btn',function () {
    /**
     *  Get table name and offset from parent
     */
    var table = $(this).parents('.reloadable-table').attr('table');
    var page = $(this).attr('page');

    /**
     *  Calculate offset (page * 10 - 10)
     */
    offset = parseInt(page) * 10 - 10;

    /**
     *  If offset is negative, set it to 0
     */
    if (offset < 0) {
        offset = 0;
    }

    /**
     *  Set cookie for PHP to load the right content
     *  e.g tables/tasks/list-done/offset
     */
    mycookie.set('tables/' + table + '/offset', offset, 1);

    reloadTable(table, offset);
});

/**
 *  Reload opened or closed elements that where opened/closed before reloading
 */
function reloadOpenedClosedElements()
{
    /**
     *  Retrieve sessionStorage with key finishing by /opened (<element>/opened)
     */
    var openedElements = Object.keys(sessionStorage).filter(function (key) {
        return key.endsWith('/opened');
    });

    /**
     *  If there are /opened elements set to true, open them
     */
    openedElements.forEach(function (element) {
        if (sessionStorage.getItem(element) == 'true') {
            var element = element.replace('/opened', '');
            $(element).show();
        }
        if (sessionStorage.getItem(element) == 'false') {
            var element = element.replace('/opened', '');
            $(element).hide();
        }
    });
}

/**
 * Ajax: Get and reload container
 * @param {*} container
 */
function reloadContainer(container)
{
    return new Promise((resolve, reject) => {
        try {
            /**
             *  If the container to reload does not exist, return
             */
            if (!$('.reloadable-container[container="' + container + '"]').length) {
                return;
            }

            /**
             *  Print a loading icon on the bottom of the page
             */
            printLoading();

            /**
             *  Check if container has children with class .veil-on-reload
             *  If so print a veil on them
             */
            printLoadingVeilByParentClass('reloadable-container[container="' + container + '"]');

            ajaxRequest(
                // Controller:
                'general',
                // Action:
                'getContainer',
                // Data:
                {
                    sourceUrl: window.location.href,
                    sourceUri: window.location.pathname,
                    container: container
                },
                // Print success alert:
                false,
                // Print error alert:
                true,
                // Reload container:
                [],
                // Execute functions on success:
                [
                    // Replace container with itself, with new content
                    "$('.reloadable-container[container=\"" + container + "\"]').replaceWith(jsonValue.message)",
                    // Reload opened or closed elements that were opened/closed before reloading
                    "reloadOpenedClosedElements()"
                ]
            ).then(() => {
                // Hide loading icon
                hideLoading();

                // Resolve promise
                resolve('Container reloaded');
            });
        } catch (error) {
            // Reject promise
            reject('Failed to reload container');
        }
    });
}

/**
 * Ajax: Get and reload table
 * @param {*} table
 * @param {*} offset
 */
function reloadTable(table, offset = 0)
{
    printLoading();

    ajaxRequest(
        // Controller:
        'general',
        // Action:
        'getTable',
        // Data:
        {
            table: table,
            offset: offset,
            sourceUrl: window.location.href,
            sourceUri: window.location.pathname,
            sourceGetParameters: getGetParams()
        },
        // Print success alert:
        false,
        // Print error alert:
        true,
        // Reload container:
        [],
        // Execute functions on success:
        [
            // Replace table with itself, with new content
            "$('.reloadable-table[table=\"" + table + "\"]').replaceWith(jsonValue.message)"
        ]
    );

    /**
     *  Hide loading icon
     */
    hideLoading();
}
