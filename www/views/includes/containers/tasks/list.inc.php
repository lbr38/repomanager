<section class="section-right reloadable-container" container="tasks/list">
    <h3>HISTORY</h3>

        <?php
        /**
         *  Print running tasks table
         */
        \Controllers\Layout\Table\Render::render('tasks/list-running'); ?>

        <?php
        /**
         *  Print done tasks table
         */
        \Controllers\Layout\Table\Render::render('tasks/list-done'); ?>
</section>