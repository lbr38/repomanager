<section class="flex-div-50 reloadable-container" container="host/history">
    <div class="echart-container div-generic-blue">
        <div class="flex flex-wrap column-gap-15 row-gap-15 justify-space-between">
            <div>
                <h6 class="margin-top-0">PACKAGES EVENTS</h6>
                <p class="note">Packages events over the selected period.</p>
            </div>

            <div class="pointer input-daterangepicker input-large echart-range" chart-id="host-packages-status-chart" range="Last 30 Days">
                <p>Last 30 Days</p>
            </div>
        </div>

        <div id="host-packages-status-chart-loading" class="echart-loading">
            <img src="/assets/icons/loading.svg" class="icon-np" />
        </div>

        <div id="host-packages-status-chart" class="echart min-height-300"></div>
    </div>

    <div class="div-generic-blue">
        <h6 class="margin-top-0">PACKAGE EVENT HISTORY</h6>
        <p class="note">Detailed history of package events.</p>

        <?php
        // Print packages events history
        \Controllers\Layout\Table\Render::render('host/history'); ?>
    </div>

    <script>
        $(document).ready(function(){
            mydaterangepicker.convert('div.echart-range[chart-id="host-packages-status-chart"]', 'Last 30 Days', false);
            new EChart('line', 'host-packages-status-chart');
        });
    </script>
</section>
