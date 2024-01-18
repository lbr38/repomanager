<section class="flex-div-50 div-generic-blue reloadable-container" container="host/history">
    <h5>HISTORY</h5>

    <p class="lowopacity-cst">Packages events history (installation, update, uninstallation...)</p>
    <br>

    <?php
    /**
     *  Print packages events history
     */
    \Controllers\Layout\Table\Render::render('host/history'); ?>
</section>