class Tooltip {
    constructor()
    {
        // Element the tooltip is currently attached to
        this.anchor = null;

        // Delays (ms) before showing / hiding the tooltip
        this.showDelay = 150;
        this.hideDelay = 150;

        this.showTimeout = null;
        this.hideTimeout = null;

        // Keep the tooltip open while the mouse is over it (allows to click on links, copy text, etc.)
        $(document).on('mouseenter', '#tooltip', () => {
            clearTimeout(this.hideTimeout);
        });

        $(document).on('mouseleave', '#tooltip', () => {
            this.scheduleHide();
        });

        // Hide tooltip on scroll (position would be wrong) and on Escape key
        window.addEventListener('scroll', (e) => {
            // Ignore scroll inside the tooltip itself
            if (e.target instanceof Element && e.target.closest('#tooltip')) {
                return;
            }
            this.hide();
        }, true);

        $(document).on('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hide();
            }
        });
    }

    /**
     * Show a tooltip attached to an element
     * @param {HTMLElement} element
     * @param {string} content HTML content
     * @param {object} options
     *   - position: 'top' (default), 'bottom', 'left' or 'right'. Flipped automatically if there is not enough space
     *   - delay: delay (ms) before showing the tooltip (default: this.showDelay)
     * @return {void}
     */
    show(element, content, options = {})
    {
        const position = options.position ?? 'top';
        const delay = options.delay ?? this.showDelay;

        clearTimeout(this.showTimeout);
        clearTimeout(this.hideTimeout);

        // Detach from the previous element
        if (this.anchor !== null && this.anchor !== element) {
            $(this.anchor).off('mouseleave.tooltip');
        }

        this.anchor = element;

        // Hide tooltip when the mouse leaves the element
        $(element).off('mouseleave.tooltip').one('mouseleave.tooltip', () => {
            clearTimeout(this.showTimeout);
            this.scheduleHide();
        });

        this.showTimeout = setTimeout(() => {
            // Mouse has left the element in the meantime
            if (this.anchor !== element) {
                return;
            }

            let tooltip = document.getElementById('tooltip');

            if (tooltip === null) {
                tooltip = document.createElement('div');
                tooltip.id = 'tooltip';
                tooltip.setAttribute('role', 'tooltip');
                tooltip.innerHTML = '<div class="tooltip-content"></div>';
                document.body.appendChild(tooltip);
            }

            tooltip.querySelector('.tooltip-content').innerHTML = content;

            this.position(tooltip, element, position);

            // Trigger the fade-in transition
            requestAnimationFrame(() => tooltip.classList.add('tooltip-visible'));
        }, delay);
    }

    /**
     * Show a text tooltip (content is escaped)
     * @param {HTMLElement} element
     * @param {string} text
     * @param {object} options see show()
     * @return {void}
     */
    showText(element, text, options = {})
    {
        const p = document.createElement('p');
        p.className = 'tooltip-text';
        p.textContent = text;

        this.show(element, p.outerHTML, options);
    }

    /**
     * Hide the tooltip
     * @return {void}
     */
    hide()
    {
        clearTimeout(this.showTimeout);
        clearTimeout(this.hideTimeout);

        if (this.anchor !== null) {
            $(this.anchor).off('mouseleave.tooltip');
            this.anchor = null;
        }

        $('#tooltip').remove();
    }

    /**
     * Hide the tooltip after a short delay, leaving time for the mouse to enter the tooltip
     * @return {void}
     */
    scheduleHide()
    {
        clearTimeout(this.hideTimeout);

        this.hideTimeout = setTimeout(() => {
            this.hide();
        }, this.hideDelay);
    }

    /**
     * Print loading tooltip
     * Kept for compatibility, prefer show()
     * @param {Event} e
     * @return {void}
     */
    loading(e)
    {
        this.show(e.currentTarget, '<div class="flex align-item-center column-gap-5"><p>Loading</p><img src="/assets/icons/loading.svg" class="icon"/></div>', { delay: 0 });
    }

    /**
     * Print tooltip with content
     * Kept for compatibility, prefer show()
     * @param {string} content
     * @param {Event} e
     * @return {void}
     */
    print(content, e)
    {
        const element = e.currentTarget;

        // Content may arrive after an async request: do not print it if the mouse has left the element
        if (!element.matches(':hover')) {
            return;
        }

        this.show(element, content, { delay: 0 });
    }

    /**
     * Position the tooltip around the element, flip it if it overflows the viewport
     * @param {HTMLElement} tooltip
     * @param {HTMLElement} element
     * @param {string} position
     * @return {void}
     */
    position(tooltip, element, position)
    {
        const margin = 10;
        const gap = 10;
        const rect = element.getBoundingClientRect();
        const width = tooltip.offsetWidth;
        const height = tooltip.offsetHeight;
        const viewportWidth = document.documentElement.clientWidth;
        const viewportHeight = document.documentElement.clientHeight;

        const opposite = { top: 'bottom', bottom: 'top', left: 'right', right: 'left' };

        if (!(position in opposite)) {
            position = 'top';
        }

        // Space available on each side of the element
        const space = {
            top: rect.top - margin,
            bottom: viewportHeight - rect.bottom - margin,
            left: rect.left - margin,
            right: viewportWidth - rect.right - margin
        };

        const needed = {
            top: height + gap,
            bottom: height + gap,
            left: width + gap,
            right: width + gap
        };

        // Try the requested position, then the opposite side, then the sides of the other axis
        const vertical = ['top', 'bottom'];
        const horizontal = ['right', 'left'];
        const candidates = [position, opposite[position]].concat(vertical.includes(position) ? horizontal : vertical);
        const fitting = candidates.find(side => space[side] >= needed[side]);

        if (fitting !== undefined) {
            position = fitting;
        } else {
            // Nothing fits, use the side with the most space
            position = candidates.reduce((best, side) => (space[side] - needed[side] > space[best] - needed[best]) ? side : best);
        }

        let top;
        let left;

        if (position === 'top' || position === 'bottom') {
            top = position === 'top' ? rect.top - height - gap : rect.bottom + gap;
            left = rect.left + rect.width / 2 - width / 2;
        } else {
            top = rect.top + rect.height / 2 - height / 2;
            left = position === 'left' ? rect.left - width - gap : rect.right + gap;
        }

        // Keep the tooltip inside the viewport
        left = Math.min(Math.max(left, margin), viewportWidth - width - margin);
        top = Math.min(Math.max(top, margin), viewportHeight - height - margin);

        // Arrow points to the center of the element, even if the tooltip has been shifted
        const arrowX = Math.min(Math.max(rect.left + rect.width / 2 - left, 16), width - 16);
        const arrowY = Math.min(Math.max(rect.top + rect.height / 2 - top, 16), height - 16);

        tooltip.setAttribute('data-position', position);
        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
        tooltip.style.setProperty('--tooltip-arrow-x', arrowX + 'px');
        tooltip.style.setProperty('--tooltip-arrow-y', arrowY + 'px');
    }
}
