class Layout {
    /**
     * Reload content of an element by its ID
     * @param {*} id
     */
    reloadContentById(id)
    {
        $('#' + id).load(location.href + ' #' + id + ' > *');
    }

    /**
     * Print a loading icon
     */
    printLoading()
    {
        // Remove existing loading icon if any
        $('#loading').remove();

        // Append a new loading icon to the body
        $('body').append('<div id="loading"><img src="/assets/icons/loading.svg"></div>');
    }

    /**
     * Remove the loading icon
     */
    hideLoading()
    {
        setTimeout(function () {
            $('#loading').remove();
        }, 1000);
    }

    /**
     * Print a veil on specified element by class name, parent element must be relative
     * @param {*} name
     */
    printLoadingVeilByClass(name)
    {
        $('.' + name).append('<div class="loading-veil"><img src="/assets/icons/loading.svg" class="icon" /><span class="lowopacity-cst">Loading</span></div>');
    }

    /**
     * Find all child elements with class .veil-on-reload and print a veil on them, each element must be relative
     * @param {*} name
     */
    printLoadingVeilByParentClass(name)
    {
        $('.' + name).find('.veil-on-reload').append('<div class="loading-veil"><img src="/assets/icons/loading.svg" class="icon" /><span class="lowopacity-cst">Loading</span></div>');
    }

    /**
     *  Reload opened or closed elements that where opened/closed before reloading
     */
    reloadOpenedClosedElements()
    {
        /**
         *  Retrieve sessionStorage with key finishing by /opened (<element>/opened)
         */
        const openedElements = Object.keys(sessionStorage).filter(function (key) {
            return key.endsWith('/opened');
        });

        /**
         *  Retrieve all checkboxes state (starting with checkbox/)
         */
        const checkboxElements = Object.keys(sessionStorage).filter(function (key) {
            return key.startsWith('checkbox/');
        });

        /**
         *  If there are /opened elements set to true, open them
         */
        openedElements.forEach(function (element) {
            if (sessionStorage.getItem(element) == 'true') {
                var element = element.replace('/opened', '');
                $(element).show();
            }
            if (sessionStorage.getItem(element) == 'false') {
                var element = element.replace('/opened', '');
                $(element).hide();
            }
        });

        /**
         *  If there are checkboxes checked, check them
         *  e.g of an item in sessionStorage: checkbox/<unique-id>/checked
         */
        checkboxElements.forEach(function (element) {
            // Get checkbox id
            var checkboxId = element.replace('checkbox/', '').replace('/checked', '');
            // Get checkbox state
            var checkboxState = sessionStorage.getItem(element);
            // If checkbox state is true, check the checkbox
            if (checkboxState == 'true') {
                // Check the checkbox
                $('input[type="checkbox"][cid="' + checkboxId +'"]').prop('checked', true);
                // Set the checkbox as visible
                $('input[type="checkbox"][cid="' + checkboxId +'"]').css('visibility', 'visible');
                $('input[type="checkbox"][cid="' + checkboxId +'"]').css('opacity', '1');
            }
        });
    }
}
