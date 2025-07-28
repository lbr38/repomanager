class Container {
    /**
     * Reload container content
     * @param {*} container
     */
    reload(container)
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
                mylayout.printLoading();

                /**
                 *  Check if container has children with class .veil-on-reload
                 *  If so print a veil on them
                 */
                mylayout.printLoadingVeilByParentClass('reloadable-container[container="' + container + '"]');

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
                    true
                ).then(() => {
                    // Replace container with itself, with new content
                    $('.reloadable-container[container="' + container + '"]').replaceWith(jsonValue.message);

                    // Reload opened or closed elements that were opened/closed before reloading
                    mylayout.reloadOpenedClosedElements();

                    // Hide loading icon
                    mylayout.hideLoading();

                    // Resolve promise
                    resolve('Container reloaded');
                });
            } catch (error) {
                // Reject promise
                reject('Failed to reload container');
            }
        });
    }
}
