<section id="system-monitoring" class="section-main">
    <h3 class="margin-bottom-5">SYSTEM MONITORING</h3>
    <p class="note margin-bottom-15">Overview of system resource usage (last hour).</p>

    <div id="monitoring-stats-container">
        <div class="div-generic-blue">
            <div id="system-cpu-usage-chart-loading" class="flex justify-center align-item-center">
                <img src="/assets/icons/loading.svg" class="icon-np" />
            </div>

            <canvas id="system-cpu-usage-chart"></canvas>
        </div>

        <div class="div-generic-blue">
            <div id="system-memory-usage-chart-loading" class="flex justify-center align-item-center">
                <img src="/assets/icons/loading.svg" class="icon-np" />
            </div>

            <canvas id="system-memory-usage-chart"></canvas>
        </div>

        <div class="div-generic-blue">
            <div id="system-disk-usage-chart-loading" class="flex justify-center align-item-center">
                <img src="/assets/icons/loading.svg" class="icon-np" />
            </div>

            <canvas id="system-disk-usage-chart"></canvas>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            new AsyncChart('line', 'system-cpu-usage-chart');
            new AsyncChart('line', 'system-memory-usage-chart');
            new AsyncChart('line', 'system-disk-usage-chart');
        });    
    </script>
</section>