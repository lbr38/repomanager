<section id="system-monitoring" class="section-main reloadable-container" container="status/monitoring">
    <div class="flexrc d-align-item-center row-gap-15 justify-space-between margin-top-10 margin-bottom-15">
        <div>
            <h3 class="margin-top-0 margin-bottom-5">SYSTEM MONITORING</h3>
            <p class="note">Overview of system resource usage.</p>
        </div>

        <div>
            <h6 class="margin-0">SELECT PERIOD</h6>
            <select id="monitoring-days-select" class="select-medium">
                <option value="1">1 day</option>
                <option value="3" selected>3 days</option>
                <option value="7">7 days</option>
                <option value="15">15 days</option>
                <option value="30">30 days</option>
            </select>
        </div>
    </div>

    <div id="monitoring-stats-container">
        <div class="echart-container div-generic-blue">
            <div id="system-cpu-usage-chart-loading" class="echart-loading">
                <img src="/assets/icons/loading.svg" class="icon-np" />
            </div>

            <div id="system-cpu-usage-chart" class="echart"></div>
        </div>

        <div class="echart-container div-generic-blue">
            <div id="system-memory-usage-chart-loading" class="echart-loading">
                <img src="/assets/icons/loading.svg" class="icon-np" />
            </div>

            <div id="system-memory-usage-chart" class="echart"></div>
        </div>

        <div class="echart-container div-generic-blue">
            <div id="system-disk-usage-chart-loading" class="echart-loading">
                <img src="/assets/icons/loading.svg" class="icon-np" />
            </div>

            <div id="system-disk-usage-chart" class="echart"></div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            new EChart('line', 'system-cpu-usage-chart');
            new EChart('line', 'system-memory-usage-chart');
            new EChart('line', 'system-disk-usage-chart');
        });
    </script>
</section>