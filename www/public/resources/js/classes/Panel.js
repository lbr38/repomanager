class Panel {
    /**
     * Get panel by name
     * @param {*} name
     * @param {*} params
     * @param {*} append
     * @returns
     */
    get(name, params = [''], append = true)
    {
        // Print a loading icon on the bottom of the page
        printLoading();

        if ($('.slide-panel-container[slide-panel="' + name + '"]').length == 0) {
            // Create an empty panel container, append it to the body and show it
            var html  = '<div class="slide-panel-container" slide-panel="' + name + '">';
            html += '<div class="slide-panel">';
            html += '<div class="flex justify-end">';
            html += '<img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="' + name + '" title="Close" />';
            html += '</div>';
            html += '<div class="flex justify-center align-item-center height-100">';
            html += '<img src="/assets/icons/loading.svg" class="icon-np" />';
            html += '</div>';
            html += '</div>'
            html += '</div>';
            $('body').append(html);
        }

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
        });

        return new Promise((resolve, reject) => {
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
                true
            ).then(function () {
                // Append panel to footer
                if (append === true) {
                    var html = $(jsonValue.message).find('.slide-panel').html();

                    // If panel content was not found in the response, reject the promise
                    if (html === undefined) {
                        reject('Panel content was not found');
                    }

                    // Replace current panel content with the content from the response
                    $('.slide-panel-container[slide-panel="' + name + '"]').find('.slide-panel').html(html);
                }

                resolve('Panel retrieved successfully');
            }).catch(function (e) {
                reject('Failed to get panel: ' + e);
            }).finally(function () {
                // Hide loading icon
                hideLoading();
            });
        });
    }

    /**
     * Relaod panel content, by name
     * @param {*} name
     * @param {*} params
     * @returns
     */
    reload(name, params = [''])
    {
        // Print a loading icon on the bottom of the page
        printLoading();

        // Check if panel has children with class .veil-on-reload, if so print a veil on them
        printLoadingVeilByParentClass('slide-panel-reloadable-div[slide-panel="' + name + '"]');

        return new Promise((resolve, reject) => {
            /**
             *  Get panel
             */
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
                true
            ).then(function () {
                // Get panel content
                var html = $(jsonValue.message).find('.slide-panel').html();

                // If panel content was not found in the response, reject the promise
                if (html === undefined) {
                    reject('Panel content was not found');
                }

                // Replace slide-panel-reloadable-div with new content
                $('.slide-panel-container[slide-panel="' + name + '"]').find('.slide-panel').html(html);

                // Reload opened or closed elements that where opened/closed before reloading
                reloadOpenedClosedElements();

                resolve('Panel reloaded successfully');
            }).catch(function (e) {
                reject('Failed to reload panel ' + name + ': ' + e);
            }).finally(function () {
                // Hide loading icon
                hideLoading();
            });
        });
    }

    /**
     * Close panel
     * @param {*} name
     */
    close(name = null)
    {
        if (name != null) {
            $('.slide-panel-container[slide-panel="' + name + '"]').find('.slide-panel').animate({
                right: '-1000px',
            }).promise().done(function () {
                $('.slide-panel-container[slide-panel="' + name + '"]').remove();
            })
        } else {
            $('.slide-panel').animate({
                right: '-1000px',
            }).promise().done(function () {
                $('.slide-panel-container').remove();
            })
        }
    }
}
