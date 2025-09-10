class System {
    /**
     *  Get the CPU usage and update the display
     */
    getCpuUsage() {
        // Use a Web Worker for CPU usage to avoid blocking the main thread and so the browser
        this.cpuWorker = new Worker('/resources/js/workers/cpu-usage.js');
        this.cpuUsage          = $('#cpu-usage');
        this.cpuLoadingIcon    = $('#cpu-usage-loading');
        this.cpuUsageContainer = $('#cpu-usage-container');

        // When the worker sends data
        this.cpuWorker.onmessage = (event) => {
            const cpuUsage = event.data.cpuUsage;

            this.cpuLoadingIcon.remove();
            
            if ($('#cpu-usage-icon').length === 0) {
                this.cpuUsageContainer.append('<span id="cpu-usage-icon" class="round-item"></span>');
            }

            this.cpuUsageIcon = $('#cpu-usage-icon');

            // If the CPU usage is null, display an error
            if (cpuUsage === null) {
                this.cpuUsage.text('Error');
                this.cpuUsageIcon
                    .removeClass('bkg-green bkg-yellow bkg-red')
                    .addClass('bkg-red');
            } else {
                let color = 'green';

                if (cpuUsage > 30 && cpuUsage <= 70) {
                    color = 'yellow';
                } else if (cpuUsage > 70) {
                    color = 'red';
                }

                // Update the CPU usage display
                this.cpuUsage.text(cpuUsage + '%');
                this.cpuUsageIcon
                    .removeClass('bkg-green bkg-yellow bkg-red')
                    .addClass('bkg-' + color);
            }
        };
    }
}
