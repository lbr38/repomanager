class Tooltip {
    constructor()
    {
        this.screenWidth = $(window).width();
        this.screenHeight = $(window).height();
    }

    /**
     * Print loading tooltip
     * @return {void}
     */
    loading()
    {
        // Remove existing tooltip if any
        $('#tooltip').remove();

        // Append a new tooltip with loading content
        $('body').append('<div id="tooltip"><div class="flex align-item-center column-gap-5"><p>Loading</p><img src="/assets/icons/loading.svg" class="icon"/></div></div>');

        // Set tooltip position (next to the mouse cursor)
        this.position();
    }

    /**
     * Print tooltip with content
     * @param {string} content
     * @return {void}
     */
    print(content)
    {
        // If there is no tooltip element, create one
        if ($('#tooltip').length == 0) {
            $('body').append('<div id="tooltip"></div>');
        }

        // Add content to the tooltip
        $('#tooltip').html(content);

        // Set tooltip position (next to the mouse cursor)
        this.position();

        // Remove tooltip on mouse leave
        $(document).on('mouseleave', '#tooltip', function() {
            $('#tooltip').remove();
        });

        // Also remove tooltip if mouse is moved away from the tooltip (like 100px away from all sides)
        $(document).on('mousemove', function(event) {
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
     * @return {void}
     */
    position()
    {
        // Set tooltip position (next to the mouse cursor)
        const tooltip = $('#tooltip');
        const width = tooltip.outerWidth();
        const height = tooltip.outerHeight();
        const mouseX = event.pageX;
        const mouseY = event.pageY;
        let left = mouseX + 0;
        let top = mouseY - 20;

        // Adjust position if tooltip goes out of bounds
        if (left + width > this.screenWidth) {
            left = this.screenWidth - width - 10;
        }
        if (top + height > this.screenHeight) {
            top = this.screenHeight - height - 10;
        }

        // Set the tooltip position
        tooltip.css({
            left: left + 'px',
            top: top + 'px'
        });
    }
}