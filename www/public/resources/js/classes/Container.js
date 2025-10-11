class Container {
    /**
     * Reload container content
     * @param {*} container
     * @param {string|null} identifier
     */
    reload(container, identifier = null)
    {
        var useMorphdom = false;

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
                    // Check if container must use Morphdom
                    if (typeof containersUsingMorphdom !== 'undefined' && containersUsingMorphdom.includes(container)) {
                        useMorphdom = true;
                    }

                    // If morphdom must be used
                    if (useMorphdom) {
                        // Replace with new content using morphdom
                        morphdom($('.reloadable-container[container="' + container + '"]')[0], jsonValue.message, {
                            // Avoid some elements to be updated
                            onBeforeElUpdated: function (fromEl, toEl) {
                                // Case the element is a video and it is currently playing, do not update it
                                if (fromEl.tagName === 'VIDEO' && !fromEl.paused) {
                                    return false;
                                }

                                // Case the element is a checkbox and it is currently checked, do not update it
                                if (fromEl.tagName === 'INPUT' && fromEl.type === 'checkbox' && fromEl.checked) {
                                    return false;
                                }

                                // Case the element is a canvas (e.g. ChartJS), do not update it
                                if (fromEl.tagName === 'CANVAS') {
                                    return false;
                                }

                                return true;
                            }
                        });
                    } else {
                        // If an identifier is provided, reload only that specific container
                        if (identifier) {
                            // Find the specific identifier (e.g #identifier) in jsonValue.message
                            const content = $(jsonValue.message).find(identifier);

                            // If the content is found, replace the container with the new content
                            if (content.length) {
                                $('.reloadable-container[container="' + container + '"]').find(identifier).replaceWith(content);
                            }
                        // Otherwise, replace the entire container with the new content
                        } else {
                            $('.reloadable-container[container="' + container + '"]').replaceWith(jsonValue.message);
                        }
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
