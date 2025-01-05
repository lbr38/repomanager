<section class="flex-div-50 div-generic-blue reloadable-container" container="host/requests">
    <div class="flex justify-space-between">
        <h6 class="margin-top-0">REQUESTS</h6>

        <div id="host-request-btn" class="slide-btn" host-id="<?= $id ?>" title="Request">
            <img src="/assets/icons/rocket.svg">
            <span>New request</span>
        </div>
    </div>

    <?php
    /**
     *  Print requests
     */
    \Controllers\Layout\Table\Render::render('host/requests'); ?>
</section>