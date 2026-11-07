class DateRangePicker
{
    /**
     * Return the predefined ranges, computed from the current date
     * Called again each time a relative range needs to be recomputed (e.g. charts auto-update)
     * @returns {object} - Predefined ranges, label => [start, end]
     */
    presets()
    {
        return {
            'Last 24 Hours': [moment().subtract(24, 'hours'), moment()],
            'Today': [moment().startOf('day'), moment().endOf('day')],
            'Yesterday': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
            'Last 7 Days': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
            'Last 30 Days': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
            'Last 3 Months': [moment().subtract(3, 'month').startOf('month'), moment().endOf('month')],
            'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().endOf('month')],
            'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
        };
    }

    /**
     * Convert an input element into a date range picker
     * @param {string} element - The selector of the input element to convert
     * @param {string} preset - The label of the predefined range to select by default (default: none, start and end dates are set to now)
     * @param {boolean} timePicker - Enable the time picker (default: true)
     * @param {boolean} predefinedRanges - Enable the predefined ranges (default: true)
     */
    convert(element, preset = null, timePicker = true, predefinedRanges = true)
    {
        const presets = this.presets();
        var start = moment();
        var end = moment();

        if (preset && presets[preset]) {
            start = presets[preset][0];
            end = presets[preset][1];
        }

        function cb(start, end, label) {
            var text = '';

            // Add a "value" attribute to the element with the selected date range in "YYYY-MM-DD - YYYY-MM-DD" format
            if (timePicker) {
                $(element).attr('value', start.format('YYYY-MM-DD HH:mm') + ' - ' + end.format('YYYY-MM-DD HH:mm'));
            } else {
                $(element).attr('value', start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
            }

            // Add "start" and "end" attributes with the selected dates as timestamps, and a "range" attribute with the selected range label
            // (label of a predefined range, or "Custom Range")
            $(element).attr('start', start.unix());
            $(element).attr('end', end.unix());
            $(element).attr('range', label);

            // Format <p> inner text to a more human readable format "DD-MM-YYYY - DD-MM-YYYY"

            // If a predefined range is selected, display its label
            if (presets[label]) {
                text = label;
            // If the start and the end date are the same, only display one date with times if enabled, otherwise display the range
            } else if (start.isSame(end, 'day')) {
                if (timePicker) {
                    if (start.isSame(end, 'second')) {
                        text = start.format('DD-MM-YYYY HH:mm');
                    } else {
                        text = 'From ' + start.format('DD-MM-YYYY HH:mm') + ' to ' + end.format('HH:mm');
                    }
                } else {
                    text = start.format('DD-MM-YYYY');
                }
            } else {
                if (timePicker) {
                    text = 'From ' + start.format('DD-MM-YYYY HH:mm') + ' to ' + end.format('DD-MM-YYYY HH:mm');
                } else {
                    text = 'From ' + start.format('DD-MM-YYYY') + ' to ' + end.format('DD-MM-YYYY');
                }
            }

            $(element).find('p').html(text);
            $(element).find('p').attr('title', text);

            // Trigger change event on the element to allow other scripts to react to the date range change
            $(element).trigger('change');
        }

        // Default configuration
        var config = {
            startDate: start,
            endDate: end,
            locale: {
                format: 'DD-MM-YYYY'
            }
        };

        // Add time picker if enabled
        if (timePicker) {
            config.timePicker = true;
            config.timePicker24Hour = true;
            // config.timePickerSeconds = true;
            config.locale.format = 'DD-MM-YYYY HH:mm';
        }

        // Add predefined ranges if enabled
        if (predefinedRanges) {
            config.ranges = presets;
        }

        // Convert the input element into a date range picker
        $(element).daterangepicker(config, cb);
    }
}
