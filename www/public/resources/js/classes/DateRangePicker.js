class DateRangePicker
{
    /**
     * Convert an input element into a date range picker
     * @param {string} element - The selector of the input element to convert
     * @param {number} days - The number of days to subtract from the current date to set the start date (default: 0)
     */
    convert(element, days = 0, timePicker = true, predefinedRanges = true)
    {
        var start = moment();
        var end = moment();

        if (days > 0) {
            var start = moment().subtract(days, 'days');
        }

        function cb(start, end) {
            var text = '';

            // Add a "value" attribute to the element with the selected date range in "YYYY-MM-DD - YYYY-MM-DD" format
            if (timePicker) {
                $(element).attr('value', start.format('YYYY-MM-DD HH:mm') + ' - ' + end.format('YYYY-MM-DD HH:mm'));
            } else {
                $(element).attr('value', start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
            }

            // Format <p> inner text to a more human readable format "DD-MM-YYYY - DD-MM-YYYY"

            // If the start and the end date are the same, only display one date with times if enabled, otherwise display the range
            if (start.isSame(end, 'day')) {
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
            config.ranges = {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'Last 3 Months': [moment().subtract(3, 'month').startOf('month'), moment().endOf('month')],
                'Last 6 Months': [moment().subtract(6, 'month').startOf('month'), moment().endOf('month')],
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
            };
        }

        // Convert the input element into a date range picker
        $(element).daterangepicker(config, cb);        
    }
}
