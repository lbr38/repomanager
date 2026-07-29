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
        mylayout.printLoading();

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
            }, window.innerWidth >= 1025 ? 200 : 100);
        });

        return new Promise((resolve, reject) => {
            try {
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
                    // Do not print error alert (it will be logged in the console and in the panel)
                    false 
                ).then(() => {
                    // Append panel to footer
                    if (append === true) {
                        const content = $(jsonValue.message);

                        // If the root element is a slide-panel, get its content, otherwise get the content of the slide-panel child
                        const html = (content.is('.slide-panel') ? content : content.find('.slide-panel')).html();

                        // If panel content was not found in the response, reject the promise
                        if (html === undefined) {
                            console.error('Panel content was not found in the response');
                            reject('Panel content was not found');
                        }

                        // Replace current panel content with the content from the response
                        $('.slide-panel-container[slide-panel="' + name + '"]').find('.slide-panel').html(html);
                    }

                    resolve('Panel retrieved successfully');
                }).catch(e => {
                    // Log error to console
                    console.error('Failed to get panel: ' + e);

                    // Print error to the user
                    myalert.print(e, 'error');

                    // Reject promise
                    reject('Failed to get panel: ' + e);
                }).finally(() => {
                    // Hide loading icon
                    mylayout.hideLoading();
                });
            } catch (error) {
                // Catch synchronous errors (before the ajax request is sent)

                // Reject promise
                reject('Failed to get panel, synchronous error: ' + error.message);
            }
        });
    }

    /**
     * Reload panel content, by name
     * @param {*} name
     * @param {*} params
     * @returns
     */
    reload(name, params = [''])
    {
        // Print a loading icon on the bottom of the page
        mylayout.printLoading();

        // Check if panel has children with class .veil-on-reload, if so print a veil on them
        mylayout.printLoadingVeilByParentClass('slide-panel-reloadable-div[slide-panel="' + name + '"]');

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
            ).then(() => {
                // Get panel content
                const $parsed = $(jsonValue.message);
                var html = ($parsed.is('.slide-panel') ? $parsed : $parsed.find('.slide-panel')).html();

                // If panel content was not found in the response, reject the promise
                if (html === undefined) {
                    reject('Panel content was not found');
                }

                // Replace slide-panel-reloadable-div with new content
                $('.slide-panel-container[slide-panel="' + name + '"]').find('.slide-panel').html(html);

                // Reload opened or closed elements that where opened/closed before reloading
                mylayout.reloadOpenedClosedElements();

                resolve('Panel reloaded successfully');
            }).catch((e) => {
                reject('Failed to reload panel ' + name + ': ' + e);
            }).finally(() => {
                // Hide loading icon
                mylayout.hideLoading();
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
            }, window.innerWidth >= 1025 ? 200 : 100).promise().done(function () {
                $('.slide-panel-container[slide-panel="' + name + '"]').remove();
            })
        } else {
            $('.slide-panel').animate({
                right: '-1000px',
            }, window.innerWidth >= 1025 ? 200 : 100).promise().done(function () {
                $('.slide-panel-container').remove();
            })
        }
    }
}
