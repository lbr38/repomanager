<section class="section-main reloadable-container" container="tasks/log">
    <?php
    // If task exists and has a log, show the log, otherwise show a message that the log is not found
    if ($taskFound) {
        include_once(ROOT . '/views/includes/containers/tasks/log/log-found.inc.php');
    } else {
        include_once(ROOT . '/views/includes/containers/tasks/log/log-not-found.inc.php');
    } ?>
</section>