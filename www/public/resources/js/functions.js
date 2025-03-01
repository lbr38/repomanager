/**
 *  Open websocket connection with server
 */
function websocket_client()
{
    const server = window.location.hostname;

    // If the target server uses https, then use wss://
    if (window.location.protocol == 'https:') {
        var path = 'wss://' + server + '/ws';
    } else {
        var path = 'ws://' + server + '/ws';
    }

    const socket = new WebSocket(path);

    // Handle connection open
    socket.onopen = function (event) {
        console.log('Websocket connection opened at ' + path);

        // Send connection type to server
        message = JSON.stringify({
            'connection-type': 'browser-client'
        });

        sendMessage(message);
    };

    // Handle received message
    socket.onmessage = function (event) {
        // Parse message
        message = JSON.parse(event.data);

        // If message type is reload-container, then reload container
        if (message.type == 'reload-container') {
            reloadContainer(message.container);
        }
    };

    // Handle connection close
    socket.onclose = function (event) {
        console.log('Websocket connection closed with ' + server);

        // If the connection was closed cleanly
        if (event.wasClean) {
            console.log('Websocket connection with ' + server + ' closed (code=' + event.code + ' reason=' + event.reason + ')');

        // If the connection was closed unexpectedly
        // For example, the server process was killed or network problems occurred
        } else {
            console.log('Websocket connection with ' + server + ' closed unexpectedly');
        }
    };

    function sendMessage(message)
    {
        socket.send(message);
    }
}

/**
 * Get panel by name
 * @param {*} name
 */
function getPanel(name, params = [''])
{
    ajaxRequest(
        // Controller:
        'general',
        // Action:
        'get-panel',
        // Data:
        {
            name: name,
            params: params
        },
        // Print success alert:
        false,
        // Print error alert:
        true,
        // Reload containers:
        [],
        // Execute function on success:
        [
            "$('footer').append(jsonValue.message);",
            "openPanel('" + name + "');"
        ]
    );
}

/**
 * Open a panel by name
 * @param {*} name
 */
function openPanel(name)
{
    // If there is another panel opened, the background of the new panel should be transparent to avoid overlay
    if ($('.slide-panel-container').length > 1) {
        var background = '#00000000';
    } else {
        var background = '#0000001f';
    }

    $('.slide-panel-container[slide-panel="' + name + '"]').css({
        visibility: 'visible',
        background: background
    }).promise().done(function () {
        $('.slide-panel-container[slide-panel="' + name + '"]').find('.slide-panel').animate({
            right: '0'
        })
    })
}

/**
 * Close a panel by name
 * @param {*} name
 */
function closePanel(name = null)
{
    if (name != null) {
        $('.slide-panel-container[slide-panel="' + name + '"]').find('.slide-panel').animate({
            right: '-1000px',
        }).promise().done(function () {
            // $('.slide-panel-container[slide-panel="' + name + '"]').css({
            //     visibility: 'hidden'
            // })
            $('.slide-panel-container[slide-panel="' + name + '"]').remove();
        })
    } else {
        $('.slide-panel').animate({
            right: '-1000px',
        }).promise().done(function () {
            // $('.slide-panel-container').css({
            //     visibility: 'hidden'
            // })
            $('.slide-panel-container').remove();
        })
    }
}

/**
 * Print an alert
 * @param {*} message
 * @param {*} type
 * @param {*} timeout
 */
function printAlert(message, type = null, timeout = 3000)
{
    random = Math.floor(Math.random() * (100000 - 100 + 1) + 100)

    if (type == null) {
        var classes = 'alert ' + random;
        var selector = '.alert.' + random;
        var icon = 'info';
    }

    if (type == 'success') {
        var classes = 'alert-success ' + random;
        var selector = '.alert-success.' + random;
        var icon = 'check';
    }

    if (type == 'error') {
        var classes = 'alert-error ' + random;
        var selector = '.alert-error.' + random;
        var icon = 'error';
        timeout = 4000;
    }

    // Remove any existing alert
    $('.alert').remove();

    $('footer').append(' \
    <div class="' + classes + '"> \
        <div class="flex align-item-center column-gap-8 padding-left-15 padding-right-15"> \
            <img src="/assets/icons/' + icon + '.svg" class="icon-np" /> \
            <div> \
                <p>' + message + '</p> \
            </div> \
        </div> \
    </div>');

    $(selector).css({
        visibility: 'visible'
    }).promise().done(function () {
        $(selector).animate({
            right: '0'
        }, 150)
    })

    if (timeout != null) {
        window.setTimeout(function () {
            closeAlert(selector);
        }, timeout);
    }
}

/**
 * Print a confirm box
 * @param {*} title
 * @param {*} confirmBoxFunction1
 * @param {*} confirmBtn1
 * @param {*} confirmBoxFunction2
 * @param {*} confirmBtn2
 */
function confirmBox(data)
{
    // Confirm box html
    var confirmBoxHtml = '<div id="confirm-box" class="confirm-box">'

    // Confirm box inner content
    var innerHtml = '<div class="flex flex-direction-column row-gap-10 padding-left-15 padding-right-15">'

    // Container for title and message
    innerHtml += '<div>';

    // If there is a title
    if (data.title != "") {
        innerHtml += '<div class="flex justify-space-between">';
        innerHtml += '<h6 class="margin-top-0 margin-bottom-0 wordbreakall">' + data.title.toUpperCase() + '</h6>';
        innerHtml += '<img src="/assets/icons/close.svg" class="icon-large lowopacity confirm-box-cancel-btn" title="Close" />';
        innerHtml += '</div>';
    }

    // If there is a message
    if (!empty(data.message)) {
        innerHtml += '<p class="note">' + data.message + '</p>';
    }

    // Close container for title and message
    innerHtml += '</div>';

    // Container for buttons
    innerHtml += '<div class="grid grid-2 column-gap-15 row-gap-15">';

    // Loop through data to print each button
    if (!empty(data.buttons)) {
        var id = 0;
        for (const [key, value] of Object.entries(data.buttons)) {
            innerHtml += '<div class="confirm-box-btn btn-auto-' + value.color + '" confirm-btn-id="' + id + '" pointer">' + value.text + '</div>';
            id++;
        }
    }

    // Close container for buttons
    innerHtml += '</div>'

    // Close base html
    innerHtml += '</div>'

    // Append inner html to confirm box container
    confirmBoxHtml += innerHtml;

    // Close confirm box container
    confirmBoxHtml += '</div>'

    /**
     *  If there is already a confirm box with the same id, do not remove it to avoid blinking
     *  but replace its content
     */
    if (!empty(data.id) && $('#confirm-box').length > 0 && $('#confirm-box').attr('confirm-box-id') == data.id) {
        // Replace confirm box inner content
        $('#confirm-box[confirm-box-id="' + data.id + '"]').html(innerHtml);
    } else {
        // Remove any existing confirm box
        $("#confirm-box").remove();

        // Append html to footer
        $('footer').append(confirmBoxHtml);

        // Set confirm box id if specified
        if (!empty(data.id)) {
            $('#confirm-box').attr('confirm-box-id', data.id);
        }

        // Show confirm box
        $('#confirm-box').css({
            visibility: 'visible'
        }).promise().done(function () {
            $('#confirm-box').animate({
                right: '0'
            }, 150)
        });
    }

    // If a button is clicked
    $('.confirm-box-btn').click(function () {
        // Get button id
        var id = $(this).attr('confirm-btn-id');

        // Get function from data
        if (empty(data.buttons[id].callback)) {
            printAlert('Error: no function specified for this button', 'error');
            return;
        }

        // Execute function
        data.buttons[id].callback();

        // Close confirm box unless closeBox is set to false
        if (empty(data.buttons[id].closeBox) || (!empty(data.buttons[id].closeBox) && data.buttons[id].closeBox == true)) {
            closeConfirmBox();
        }
    });

    // If 'cancel' choice is clicked
    $('.confirm-box-cancel-btn').click(function () {
        closeConfirmBox();
    });
}

/**
 *  Close alert and confirm box modal
 */
function closeAlert(selector = '.alert')
{
    $(selector).animate({
        right: '-1000px'
    }, 150).promise().done(function () {
        $(selector).remove();
    });
}

/**
 *  Close confirm box
 */
function closeConfirmBox()
{
    $('#confirm-box').animate({
        right: '-1000px'
    }, 150).promise().done(function () {
        $('#confirm-box').remove();
    });
}

function printLoading()
{
    $('#loading').remove();

    $('footer').append('<div id="loading"><p class="lowopacity">Loading</p><img src="/assets/icons/loading.svg"></div>');
}

function hideLoading()
{
    setTimeout(function () {
        $('#loading').remove();
    },1500);
}

/**
 * Print a veil on specified element by class name, parent element must be relative
 * @param {*} name
 */
function printLoadingVeilByClass(name)
{
    $('.' + name).append('<div class="loading-veil"><img src="/assets/icons/loading.svg" class="icon" /><span class="lowopacity-cst">Loading</span></div>');
}

/**
 * Find all child elements with class .veil-on-reload and print a veil on them, each element must be relative
 * @param {*} name
 */
function printLoadingVeilByParentClass(name)
{
    $('.' + name).find('.veil-on-reload').append('<div class="loading-veil"><img src="/assets/icons/loading.svg" class="icon" /><span class="lowopacity-cst">Loading</span></div>');
}

/**
 * Print a modal window with specified content
 * @param {*} content
 * @param {*} title
 * @param {*} inPre
 */
function printModalWindow(content, title, inPre = true)
{
    /**
     *  If a modal window is already opened, remove it
     */
    $('.modal-window-container').remove();

    html = '<div class="modal-window-container">'
        + '<div class="modal-window">'
        + '<div class="flex justify-space-between">'
        + '<h4>' + title + '</h4>'
        + '<span class="modal-window-close-btn"><img title="Close" class="close-btn lowopacity" src="/assets/icons/close.svg" /></span>'
        + '</div>'
        + '<div>';
    if (inPre) {
        html += '<pre>' + content + '</pre>';
    } else {
        html += content;
    }

    html += '</div>'
        + '</div>'
        + '</div>';

    $('footer').append(html);
}

/**
 *  Slide div by class name or Id and save state in sessionStorage
 *  @param {*} name
 */
function slide(name)
{
    /**
     *  Get element display state (display: none or display: block/grid etc...)
     */
    var state = $(name).css('display');

    /**
     *  Open or close element
     */
    $(name).slideToggle('fast');

    /**
     *  If element was hidden (display: none) then it is now opened
     */
    if (state == 'none') {
        sessionStorage.setItem(name + '/opened', 'true');
    } else {
        /**
         *  Else it was opened and is now closed
         */
        sessionStorage.setItem(name + '/opened', 'false');
    }
}

/**
 * Rechargement du contenu d'un élément, par son Id
 * @param {string} id
 */
function reloadContentById(id)
{
    $('#' + id).load(location.href + ' #' + id + ' > *');
}

/**
 *  Copie du contenu d'un élement dans le presse-papier
 *  @param {*} containerid
 */
function copyToClipboard(containerid)
{
    var range = document.createRange();
    range.selectNode(containerid);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);
    document.execCommand("copy");
    window.getSelection().removeAllRanges();

    printAlert('Copied to clipboard', 'success');
}

/**
 *  Print OS icon image
 */
function printOsIcon(os = '', os_family = '')
{
    if (os != '') {
        if (os.toLowerCase() == 'centos') {
            return '<img src="/assets/icons/products/centos.png" class="icon-np" title="' + os + '">';
        } else if (os.toLowerCase().match(/rocky/i)) {
            return '<img src="/assets/icons/products/rockylinux.png" class="icon-np" title="' + os + '">';
        } else if (os.toLowerCase().match(/alma/i)) {
            return '<img src="/assets/icons/products/almalinux.png" class="icon-np" title="' + os + '">';
        } else if (os.toLowerCase().match(/oracle/i)) {
            return '<img src="/assets/icons/products/oracle.png" class="icon-np" title="' + os + '">';
        } else if (os.toLowerCase().match(/fedora/i)) {
            return '<img src="/assets/icons/products/fedora.png" class="icon-np" title="' + os + '">';
        } else if (os.toLowerCase().match(/redhat/i)) {
            return '<img src="/assets/icons/products/redhat.png" class="icon-np" title="' + os + '">';
        } else if (os.toLowerCase().match(/debian|armbian/i)) {
            return '<img src="/assets/icons/products/debian.png" class="icon-np" title="' + os + '">';
        } else if (os.toLowerCase().match(/ubuntu|kubuntu|xubuntu|mint/i)) {
            return '<img src="/assets/icons/products/ubuntu.png" class="icon-np" title="' + os + '">';
        }
    }

    /**
     *  If OS could not be found and OS family is specified
     */
    if (os_family != '') {
        if (os_family.toLowerCase().match(/debian|ubuntu|kubuntu|xubuntu|armbian|mint/i)) {
            return '<img src="/assets/icons/products/debian.png" class="icon-np" title="' + os + '">';
        } else if (os_family.toLowerCase().match(/rhel|centos|fedora/i)) {
            return '<img src="/assets/icons/products/redhat.png" class="icon-np" title="' + os + '">';
        }
    }

    /**
     *  Else return generic icon
     */
    return '<img src="/assets/icons/products/tux.png" class="icon-np" title="' + os + '">';
}

/**
 * Get cookie value by name
 * @param {*} cname
 * @returns
 */
function getCookie(cname)
{
    let name = cname + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');

    for (let i = 0; i <ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }

    return "";
}

/**
 * Set cookie value
 * @param {*} cname
 * @param {*} cvalue
 * @param {*} exdays
 */
function setCookie(cname, cvalue, exdays)
{
    const d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    let expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/;Secure";
}

/**
 *  Return GET parameters as object (array)
 */
function getGetParams()
{
    /**
     *  Get current URL and GET parameters
     */
    let url = new URL(window.location.href)
    let params = new URLSearchParams(url.search);
    let entries = params.entries();

    /**
     *  Parse and convert to object
     *  For each GET param, add key and value to the object
     */
    let array = {}
    for (let entry of entries) { // each 'entry' is a [key, value]
        let [key, val] = entry;

        /**
         *  If key ends with '[]' then it's an array
         */
        if (key.endsWith('[]')) {
            // clean up the key
            key = key.slice(0,-2);
            (array[key] || (array[key] = [])).push(val)
        /**
         *  Else it's a normal parameter
         */
        } else {
            array[key] = val;
        }
    }

    return array;
}

/**
 * Return true if the value is empty
 */
function empty(value)
{
    // Check if the value is null or undefined
    if (value == null) {
        return true;
    }

    // Check if the value is a string and is empty
    if (typeof value === 'string' && value.trim() === '') {
        return true;
    }

    // Check if the value is an empty array
    if (Array.isArray(value) && value.length === 0) {
        return true;
    }

    // Check if the value is an empty object
    if (typeof value === 'object' && Object.keys(value).length === 0) {
        return true;
    }

    // Check if the value is a number and is NaN
    if (typeof value === 'number' && isNaN(value)) {
        return true;
    }

    // If none of the above conditions are met, the value is not empty
    return false;
}

/**
 * Print environment tag with color, in task form
 * Environment colors must be set in localStorage
 * @param {*} env
 * @param {*} selector
 */
function printEnv(env, selector)
{
    // Default colors
    var background = '#ffffff';
    var color = '#000000';

    // Check if the environment color is set in localStorage
    if (localStorage.getItem('env/' + env) !== null) {
        definition = JSON.parse(localStorage.getItem('env/' + env));
        color = definition.color;
        background = definition.background;
    }

    // Generate html
    var html = '⸺<span class="env" style="background-color: ' + background + '; color: ' + color + ';">' + env + '</span>';

    // Print environment
    $(selector).html(html);
}
