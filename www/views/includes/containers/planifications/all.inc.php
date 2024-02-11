<section class="section-right reloadable-container" container="planifications/all">
    <h3>SCHEDULE TASKS</h3>

    <?php
    \Controllers\Layout\Container\Render::render('planifications/queued-running');
    \Controllers\Layout\Container\Render::render('planifications/form');
    \Controllers\Layout\Container\Render::render('planifications/history'); ?>
</section>