class System {
    /**
     *  Get the CPU usage and update the display
     *  Value can be retrieved from: /resources/monitoring/cpu-usage
     */
    getCpuUsage() {
        $.ajax({
            url: '/resources/monitoring/cpu-usage',
            method: 'GET',
            dataType: 'text',
        }).fail(() => {
            console.error('Failed to fetch CPU usage.');
            this.value = null;
        }).done((data) => {
            this.value = data.trim();
        }).always(() => {
            const cpuUsage          = $('#cpu-usage');
            const cpuUsageContainer = $('#cpu-usage-container');

            $('#cpu-usage-loading').remove();
            
            if ($('#cpu-usage-icon').length === 0) {
                cpuUsageContainer.append('<span id="cpu-usage-icon" class="round-item"></span>');
            }

            const cpuUsageIcon = $('#cpu-usage-icon');

            // If the CPU usage is null, display an error
            if (this.value === null) {
                cpuUsage.text('Error');
                cpuUsageIcon
                    .removeClass('bkg-green bkg-yellow bkg-red')
                    .addClass('bkg-red');
            } else {
                let color = 'green';

                if (this.value > 30 && this.value <= 70) {
                    color = 'yellow';
                } else if (this.value > 70) {
                    color = 'red';
                }

                // Update the CPU usage display
                cpuUsage.text(this.value + '%');
                cpuUsageIcon
                    .removeClass('bkg-green bkg-yellow bkg-red')
                    .addClass('bkg-' + color);
            }
        });
    }
}
