<!DOCTYPE html>
<html>
    <?php include_once(ROOT . '/views/includes/head.inc.php'); ?>

    <body>
        <div id="top"></div> <!-- to go to the top of the page -->

        <?php
            \Controllers\Layout\Container\Render::render('header/menu');
            include_once(ROOT . '/views/includes/containers/header/general-error-messages.inc.php');
            include_once(ROOT . '/views/includes/containers/header/general-log-messages.inc.php');
            include_once(ROOT . '/views/includes/containers/header/service-status.inc.php');
            include_once(ROOT . '/views/includes/containers/header/debug-mode.inc.php');
            include_once(ROOT . '/views/includes/maintenance.inc.php');
        ?>

        <article>
            <?= $content ?>
        </article>

        <?php
            /**
             *  Footer
             */
            include_once(ROOT . '/views/includes/footer.inc.php');
        ?>

        <div id="bottom"></div> <!-- to go to the bottom of the page -->
    </body>
</html>