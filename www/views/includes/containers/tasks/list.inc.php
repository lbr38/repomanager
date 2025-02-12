<section class="section-right reloadable-container" container="tasks/list">
    <h3>TASKS</h3>

        <?php
        /**
         *  Print running tasks table
         */
        \Controllers\Layout\Table\Render::render('tasks/list-running');

        /**
         *  Print queued tasks table
         */
        \Controllers\Layout\Table\Render::render('tasks/list-queued');

        /**
         *  Print scheduled tasks table
         */
        \Controllers\Layout\Table\Render::render('tasks/list-scheduled');

        /**
         *  Print done tasks table
         */
        \Controllers\Layout\Table\Render::render('tasks/list-done'); ?>
</section>