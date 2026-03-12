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
                                // Check container-specific rules first
                                if (typeof morphdomSkipRules !== 'undefined' && morphdomSkipRules[container]) {
                                    if (this._shouldSkipElement(fromEl, toEl, morphdomSkipRules[container])) {
                                        return false;
                                    }
                                }
                                
                                // Check default rules
                                if (typeof defaultMorphdomSkipRules !== 'undefined') {
                                    if (this._shouldSkipElement(fromEl, toEl, defaultMorphdomSkipRules)) {
                                        return false;
                                    }
                                }
                                
                                return true;
                            }.bind(this)
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

                    // Execute post reload function if exists
                    if (typeof postReloadFunctions !== 'undefined' && typeof postReloadFunctions[container] === 'function') {
                        postReloadFunctions[container]();
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

    /**
     * Check if an element should be skipped based on declarative rules
     * @param {Element} fromEl
     * @param {Element} toEl
     * @param {Array} rules
     * @returns {boolean}
     */
    _shouldSkipElement(fromEl, toEl, rules) {
        for (const rule of rules) {
            // Check if element matches the rule selector
            if (this._elementMatches(fromEl, rule.element)) {
                switch (rule.skipIf) {
                    case 'playing':
                        if (!fromEl.paused) {
                            return true;
                        }
                        break;
                    case 'sameAttribute':
                        if (rule.attribute && fromEl.getAttribute(rule.attribute) === toEl.getAttribute(rule.attribute)) {
                            return true;
                        }
                        break;
                    case 'checked':
                        if (fromEl.checked) {
                            return true;
                        }
                        break;
                    case 'always':
                        return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if element matches a selector (simple implementation)
     * @param {Element} element
     * @param {string} selector
     * @returns {boolean}
     */
    _elementMatches(element, selector) {
        // Handle simple cases like 'VIDEO', 'CANVAS', 'INPUT[type="checkbox"]'
        if (selector === element.tagName) {
            return true;
        }
        
        // Handle attribute selectors like 'INPUT[type="checkbox"]'
        const match = selector.match(/^(\w+)\[([^=]+)="([^"]+)"\]$/);
        if (match) {
            const [, tagName, attr, value] = match;
            return element.tagName === tagName && element.getAttribute(attr) === value;
        }
        
        return false;
    }
}
