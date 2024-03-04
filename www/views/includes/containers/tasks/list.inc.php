<section class="section-right reloadable-container" container="tasks/list">
    <h3>TASKS</h3>

        <?php
        /**
         *  Print scheduled tasks table
         */
        \Controllers\Layout\Table\Render::render('tasks/list-scheduled');

        /**
         *  Print running tasks table
         */
        \Controllers\Layout\Table\Render::render('tasks/list-running');

        /**
         *  Print done tasks table
         */
        \Controllers\Layout\Table\Render::render('tasks/list-done'); ?>
</section>