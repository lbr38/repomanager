<section class="flex-div-50 reloadable-container" container="host/history">
    <div class="echart-container div-generic-blue">
        <h6 class="margin-top-0">PACKAGES EVENTS</h6>
        <p class="note">Latest packages events over a 15 days period.</p>
        <div id="host-packages-status-chart-loading" class="echart-loading">
            <img src="/assets/icons/loading.svg" class="icon-np" />
        </div>

        <div id="host-packages-status-chart" class="echart"></div>
    </div>

    <div class="div-generic-blue">
        <h6 class="margin-top-0">PACKAGES EVENTS DETAILS</h6>
        <p class="note">Packages events details history.</p>

        <?php
        // Print packages events history
        \Controllers\Layout\Table\Render::render('host/history'); ?>
    </div>

    <script>
        $(document).ready(function(){
            new EChart('line', 'host-packages-status-chart');
        });
    </script>
</section>