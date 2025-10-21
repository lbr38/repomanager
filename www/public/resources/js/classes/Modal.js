class Modal
{
    /**
     * Printing an empty modal with loading indicator.
     */
    loading()
    {
        mylayout.printLoading();

        var html = '<div class="modal-window-container modal-loading">'
            + '<div class="modal-window">'
            + '<div class="modal-window-title">'
            + '<h4 class="margin-0">Loading...</h4>'
            + '</div>'
            + '<div class="modal-window-content flex justify-center align-item-center height-100">'
            + '<img src="/assets/icons/loading.svg" class="icon-np icon-large" />'
            + '</div>'
            + '</div>'
            + '</div>';

        // Append the modal window to the body
        $('body').append(html);
    }

    /**
     * Print a modal window with the given content and title.
     * @param {*} content
     * @param {*} title
     * @param {*} inPre
     */
    print(content, title, inPre = true)
    {
        mylayout.printLoading();

        // Generate random number id for the modal
        const id = Math.random().toString(36).substring(2, 15);

        // Remove existing modal loading window if any
        $('.modal-window-container.modal-loading').remove();

        // Parse content and if a line contains [ERR] or [WRN], color it accordingly
        content = content.split('\n').map(function(line) {
            if (line.includes('[ERR]')) {
                return '<span class="redtext font-size-12">' + line + '</span>';
            }
            if (line.includes('[WRN]')) {
                return '<span class="yellowtext font-size-12">' + line + '</span>';
            }

            return line;
        }).join('\n');

        // Generate content
        var html = '<div class="modal-window-container" modal="' + id + '">'
            + '<div class="modal-window">'
            + '<div class="modal-window-title">'
            + '<h4 class="margin-0">' + title + '</h4>'
            + '<span class="modal-window-close-btn" modal="' + id + '"><img title="Close" class="close-btn lowopacity" src="/assets/icons/close.svg" /></span>'
            + '</div>'
            + '<div class="modal-window-content">';

        // If content must be wrapped in a <pre> tag
        if (inPre) {
            html += '<pre class="codeblock copy">' + content + '</pre>';
        } else {
            html += content;
        }

        html += '</div>'
            + '</div>'
            + '</div>';

        // Append the modal window to the body
        $('body').append(html);

        mylayout.hideLoading();
    }
}