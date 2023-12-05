<section class="section-right reloadable-container" container="operations/list">
    <h3>HISTORY</h3>

        <?php
        /**
         *  Print running operations table
         */
        \Controllers\Layout\Table\Render::render('operations/list-running', 0); ?>

        <?php
        /**
         *  Print done operations table
         */
        \Controllers\Layout\Table\Render::render('operations/list-done', 0); ?>
</section>