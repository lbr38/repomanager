class Tooltip {
    /**
     * Print loading tooltip
     * @param {MouseEvent} e
     * @return {void}
     */
    loading(e)
    {
        // Remove existing tooltip if any
        $('#tooltip').remove();

        // Append a new tooltip with loading content
        $('body').append('<div id="tooltip"><div class="flex align-item-center column-gap-5"><p>Loading</p><img src="/assets/icons/loading.svg" class="icon"/></div></div>');

        // Set tooltip position (next to the mouse cursor)
        this.position(e);
    }

    /**
     * Print tooltip with content
     * @param {string} content
     * @param {MouseEvent} e
     * @return {void}
     */
    print(content, e)
    {
        // If there is no tooltip element, create one
        if ($('#tooltip').length == 0) {
            $('body').append('<div id="tooltip"></div>');
        }

        // Add content to the tooltip
        $('#tooltip').html(content);

        // Set tooltip position (next to the mouse cursor)
        this.position(e);

        // Remove tooltip on mouse leave
        $(document).on('mouseleave', '#tooltip', function () {
            $('#tooltip').remove();
        });

        // Also remove tooltip if mouse is moved away from the tooltip (like 100px away from all sides)
        $(document).on('mousemove', function (event) {
            const tooltip = $('#tooltip');
            if (tooltip.length > 0) {
                const tooltipOffset = tooltip.offset();
                const tooltipWidth = tooltip.outerWidth();
                const tooltipHeight = tooltip.outerHeight();
                if (event.pageX < tooltipOffset.left - 100 ||
                    event.pageX > tooltipOffset.left + tooltipWidth + 100 ||
                    event.pageY < tooltipOffset.top - 100 ||
                    event.pageY > tooltipOffset.top + tooltipHeight + 100) {
                    $('#tooltip').remove();
                }
            }
        });
    }

    /**
     * Set tooltip position based on mouse cursor
     * @param {MouseEvent} e
     * @return {void}
     */
    position(e)
    {
        const screenWidth = $(window).scrollLeft() + $(window).width() - 20;
        const screenHeight = $(window).scrollTop() + $(window).height() - 20;
        const tooltip = $('#tooltip');
        const width = tooltip.outerWidth();
        const height = tooltip.outerHeight();
        const mouseX = e.pageX;
        const mouseY = e.pageY;
        let left = mouseX;
        let top = mouseY - 20;

        // Adjust position if tooltip goes out of bounds
        if (left + width > screenWidth) {
            left = screenWidth - width - 10;
        }

        if (top + height > screenHeight) {
            top = screenHeight - height - 10;
        }

        // Set the tooltip position
        tooltip.css({
            left: left + 'px',
            top: top + 'px'
        });
    }
}