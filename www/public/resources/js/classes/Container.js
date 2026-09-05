class Container {
    /**
     * Reload container content
     * @param {*} container
     * @param {string|null} identifier
     */
    reload(container, identifier = null)
    {
        var useMorphdom = false;
        var partialConfig = (typeof containersPartialReload !== 'undefined') ? containersPartialReload[container] : null;
        var partial = false;

        return new Promise((resolve, reject) => {
            try {
                // If the container to reload does not exist, return
                if (!$('.reloadable-container[container="' + container + '"]').length) {
                    return;
                }

                /**
                 *  If the user is actively interacting with the container, only the items that
                 *  are not busy will be refreshed, to avoid losing selections and inputs
                 */
                if (partialConfig && this._isBusy($('.reloadable-container[container="' + container + '"]'))) {
                    partial = true;
                }

                // Print a loading icon on the bottom of the page
                mylayout.printLoading();

                /**
                 *  Check if container has children with class .veil-on-reload
                 *  If so print a veil on them
                 *  The veil is not printed on a partial reload, as the user is working on the container
                 */
                if (!partial) {
                    mylayout.printLoadingVeilByParentClass('reloadable-container[container="' + container + '"]');
                }

                ajaxRequest(
                    // Controller:
                    'general',
                    // Action:
                    'get-container',
                    // Data:
                    {
                        sourceUrl: window.location.href,
                        sourceUri: window.location.pathname,
                        container: container
                    },
                    // Print success alert:
                    false,
                    // Do not print error alert (it will be logged in the console)
                    false
                ).then(() => {
                    // Check if container must use Morphdom
                    if (typeof containersUsingMorphdom !== 'undefined' && containersUsingMorphdom.includes(container)) {
                        useMorphdom = true;
                    }

                    // If only the non-busy items must be refreshed
                    if (partial) {
                        this._partialReplace(container, partialConfig, jsonValue.message);

                    // If morphdom must be used
                    } else if (useMorphdom) {
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

                    // Resolve promise
                    resolve('Container reloaded');
                }).catch(e => {
                    // Log error to console
                    console.error('Failed to reload container ' + container + ': ' + e);

                    // Print error to the user
                    myalert.print(e, 'error');

                    // Reject promise
                    reject('Failed to reload container ' + container + ': ' + e);
                }).finally(() => {
                    // Hide loading icon
                    mylayout.hideLoading();
                });
            } catch (error) {
                // Catch synchronous errors (before the ajax request is sent)

                // Reject promise
                reject('Failed to reload container, synchronous error: ' + error.message);
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

    /**
     * Replace only the items of a container that are not busy, keeping the ones the user is working on
     * @param {string} container
     * @param {Object} config
     * @param {string} html
     */
    _partialReplace(container, config, html) {
        const current = $('.reloadable-container[container="' + container + '"]');
        const received = $('<div>').html(html);

        received.find(config.item).each((index, newItem) => {
            const key = $(newItem).attr(config.key);

            // Item cannot be matched without a key
            if (!key) {
                return;
            }

            // Find the matching item currently displayed
            const existing = current.find(config.item).filter((i, el) => $(el).attr(config.key) === key);

            // The item does not exist yet on the page, it will show up on the next complete reload
            if (!existing.length) {
                return;
            }

            // Leave the items the user is currently working on untouched
            if (this._isBusy(existing)) {
                return;
            }

            existing.replaceWith(newItem);
        });
    }

    /**
     * Check if the user is actively interacting with an element (focus, checked checkbox, filled input)
     * @param {jQuery} element
     * @returns {boolean}
     */
    _isBusy(element) {
        // An element inside has the focus
        if (element.find(':focus').length) {
            return true;
        }

        // A checkbox inside is checked
        if (element.find('input[type="checkbox"]:checked').length) {
            return true;
        }

        // A text input or a textarea inside is filled
        if (element.find('input[type="text"], input[type="search"], input:not([type]), textarea').filter(function () {
            return $(this).val().trim() !== '';
        }).length) {
            return true;
        }

        return false;
    }
}
