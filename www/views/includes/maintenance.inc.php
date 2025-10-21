<?php
if (\Controllers\Update::running() or \Controllers\App\Maintenance::running()) :
    if (\Controllers\Update::running()) {
        $title = 'UPDATE RUNNING';
        $message = 'Reposerver is currently being updated. Please try again later.';
    }

    if (\Controllers\App\Maintenance::running()) {
        $title = 'MAINTENANCE';
        $message = 'Reposerver is under maintenance. Please try again later.';
    }

    // Do not display maintenance page on /status page
    if (__ACTUAL_URI__[1] != 'status') : ?>
        <div id="maintenance-container">
            <div id="maintenance">
                <h3 class="margin-top-0"><?= $title ?></h3>
                <p><?= $message ?></p>
                <br>
                <button class="btn-medium-green" onClick="window.location.reload();">Refresh</button>
            </div>
        </div>
        <?php
    endif;
    unset($title, $message);
endif;
