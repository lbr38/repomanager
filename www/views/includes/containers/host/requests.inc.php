<?php
use \Controllers\User\Permission\Host as HostPermission; ?>

<section class="flex-div-50 div-generic-blue reloadable-container" container="host/requests">
    <div class="flex justify-space-between">
        <h6 class="margin-top-0">REQUESTS</h6>

        <?php
        if (IS_ADMIN or (HostPermission::allowedAction('request-general-infos') or HostPermission::allowedAction('request-packages-infos') or HostPermission::allowedAction('update-packages'))) : ?>
            <div id="host-request-btn" class="slide-btn" host-id="<?= $id ?>" title="Request">
                <img src="/assets/icons/rocket.svg">
                <span>New request</span>
            </div>
            <?php
        endif ?>
    </div>

    <?php
    /**
     *  Print requests
     */
    \Controllers\Layout\Table\Render::render('host/requests'); ?>
</section>