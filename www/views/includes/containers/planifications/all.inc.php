<section class="section-right reloadable-container" container="planifications/all">
    <h3>PLANIFICATIONS</h3>

    <?php
    \Controllers\Layout\Container\Render::render('planifications/queued-running', 0);
    \Controllers\Layout\Container\Render::render('planifications/form');
    \Controllers\Layout\Container\Render::render('planifications/history', 0); ?>
</section>