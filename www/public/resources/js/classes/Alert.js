class Alert
{
    /**
     * Print an alert message
     * @param {*} message
     * @param {*} type
     * @param {*} timeout
     */
    print(message, type = null, timeout = 3000)
    {
        const random = Math.floor(Math.random() * (100000 - 100 + 1) + 100)

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
            window.setTimeout(() => {
                this.close(selector);
            }, timeout);
        }
    }

    /**
     * Close an alert message
     * @param {*} selector
     */
    close(selector = '.alert')
    {
        $(selector).animate({
            right: '-1000px'
        }, 150).promise().done(function () {
            $(selector).remove();
        });
    }
}