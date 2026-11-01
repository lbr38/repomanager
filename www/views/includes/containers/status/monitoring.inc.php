<section id="system-monitoring" class="section-main reloadable-container" container="status/monitoring">
    <div class="flexrc d-align-item-center row-gap-15 justify-space-between margin-top-10 margin-bottom-15">
        <div>
            <h3 class="margin-top-0 margin-bottom-5">SYSTEM MONITORING</h3>
            <p class="note">Overview of system resource usage.</p>
        </div>

        <div>
            <h6 class="margin-0">SELECT PERIOD</h6>
            <div class="pointer input-daterangepicker input-large echart-range" chart-id="system-cpu-usage-chart system-memory-usage-chart system-disk-usage-chart" range="Last 24 Hours">
                <p>Last 24 Hours</p>
            </div>
        </div>
    </div>

    <div id="monitoring-stats-container">
        <div class="echart-container div-generic-blue">
            <div id="system-cpu-usage-chart-loading" class="echart-loading">
                <img src="/assets/icons/loading.svg" class="icon-np" />
            </div>

            <div id="system-cpu-usage-chart" class="echart" type="line" generate></div>
        </div>

        <div class="echart-container div-generic-blue">
            <div id="system-memory-usage-chart-loading" class="echart-loading">
                <img src="/assets/icons/loading.svg" class="icon-np" />
            </div>

            <div id="system-memory-usage-chart" class="echart" type="line" generate></div>
        </div>

        <div class="echart-container div-generic-blue">
            <div id="system-disk-usage-chart-loading" class="echart-loading">
                <img src="/assets/icons/loading.svg" class="icon-np" />
            </div>

            <div id="system-disk-usage-chart" class="echart" type="line" generate></div>
        </div>
    </div>
</section>

<script>
    $(document).ready(function(){
        mydaterangepicker.convert('div.echart-range[chart-id~="system-cpu-usage-chart"]', 'Last 24 Hours');
    });
</script>