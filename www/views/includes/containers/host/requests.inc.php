<section class="section-main reloadable-container" container="host/requests">
    <div class="div-generic-blue">
        <h5>REQUESTS</h5>

        <p class="lowopacity-cst">Requests sent to the host</p>
        <br>

        <?php
        /**
         *  Print requests
         */
        \Controllers\Layout\Table\Render::render('host/requests'); ?>
    </div>
</section>