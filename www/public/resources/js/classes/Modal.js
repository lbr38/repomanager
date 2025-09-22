class Modal
{
    /**
     * Printing an empty modal with loading indicator.
     */
    loading()
    {
        mylayout.printLoading();

        // Remove existing modal window if any
        $('.modal-window-container').remove();

        var html = '<div class="modal-window-container">'
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

        // Remove existing modal window if any
        $('.modal-window-container').remove();

        // Generate content
        var html = '<div class="modal-window-container">'
            + '<div class="modal-window">'
            + '<div class="modal-window-title">'
            + '<h4 class="margin-0">' + title + '</h4>'
            + '<span class="modal-window-close-btn"><img title="Close" class="close-btn lowopacity" src="/assets/icons/close.svg" /></span>'
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