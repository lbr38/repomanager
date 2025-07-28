/**
 *  Open websocket connection with server
 */
function websocket_client()
{
    const server = window.location.host;

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
            mycontainer.reload(message.container);
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
