class Container {
    /**
     * Reload container content
     * @param {*} container
     */
    reload(container, identifier = null)
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
                    // If an identifier is provided, reload only that specific container
                    if (identifier) {
                        // Find the specific identifier (e.g #hostDiv) in jsonValue.message
                        const content = $(jsonValue.message).find(identifier);

                        // If the content is found, replace the container with the new content
                        if (content.length) {
                            $('.reloadable-container[container="' + container + '"] > ' + identifier).replaceWith(content);
                        }
                    // Otherwise, replace the entire container with the new content
                    } else {
                        $('.reloadable-container[container="' + container + '"]').replaceWith(jsonValue.message);
                    }

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
