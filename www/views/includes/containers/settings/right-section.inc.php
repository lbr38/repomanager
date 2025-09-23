<section class="section-right reloadable-container" container="settings/right-section">
    <?php
    \Controllers\Layout\Container\Render::render('settings/users');
    \Controllers\Layout\Container\Render::render('settings/debug-mode');
    \Controllers\Layout\Container\Render::render('settings/status'); ?>
</section>