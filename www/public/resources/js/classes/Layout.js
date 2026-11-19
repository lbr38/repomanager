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
     * Add a red dot on the browser tab favicon when at least one task is running
     * (the menu prints a badge on the tasks tab when tasks are running)
     */
    updateFavicon()
    {
        const running = $('#menu .menu-task-badge').length > 0;
        let link = document.querySelector('link[rel="icon"]');

        if (link === null) {
            link = document.createElement('link');
            link.rel = 'icon';
            document.head.appendChild(link);
        }

        if (!running) {
            link.href = '/favicon.ico';
            return;
        }

        // The favicon with the dot is generated only once
        if (this.runningFavicon) {
            link.href = this.runningFavicon;
            return;
        }

        const image = new Image();

        image.onload = () => {
            const size = 64;
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');

            canvas.width = size;
            canvas.height = size;
            context.drawImage(image, 0, 0, size, size);

            // Cut out a ring around the dot so it stays visible on any tab bar color
            context.globalCompositeOperation = 'destination-out';
            context.beginPath();
            context.arc(size - 16, size - 16, 16, 0, 2 * Math.PI);
            context.fill();

            // Red dot, bottom right
            context.globalCompositeOperation = 'source-over';
            context.fillStyle = '#F32F63';
            context.beginPath();
            context.arc(size - 16, size - 16, 12, 0, 2 * Math.PI);
            context.fill();

            this.runningFavicon = canvas.toDataURL('image/png');

            // Tasks may have ended while the image was loading
            if ($('#menu .menu-task-badge').length > 0) {
                link.href = this.runningFavicon;
            }
        };

        image.src = '/favicon.ico';
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
     *  Print a veil on the whole page
     */
    veilBody()
    {
        $('body').append('<div class="body-veil"><img src="/assets/official-logo/logo.svg" /><img class="mediumopacity-cst" src="/assets/icons/loading.svg" /></div>');
    }

    toggleElementById(id)
    {
        $('#' + id).toggle();
    }

    /**
     * Return the state (id, value, cursor position) of the text field the user is typing in, if it is inside the specified element
     * Used to restore it once the element has been replaced by a reload, null if the user is not typing in it
     * @param {*} element
     * @returns {Object|null}
     */
    getFocusedField(element)
    {
        const field = document.activeElement;

        if (!field || !field.id || !$(field).is('input[type="text"], input[type="search"], textarea') || !$(element).has(field).length) {
            return null;
        }

        return {
            id: field.id,
            value: field.value,
            start: field.selectionStart,
            end: field.selectionEnd
        };
    }

    /**
     * Restore the text field the user was typing in before it has been replaced by a reload (see getFocusedField())
     * The value typed by the user is kept, as it may not have been taken into account by the reload yet
     * @param {Object|null} state
     */
    restoreFocusedField(state)
    {
        const field = state ? document.getElementById(state.id) : null;

        if (!field) {
            return;
        }

        field.value = state.value;
        field.focus();
        field.setSelectionRange(state.start, state.end);
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
