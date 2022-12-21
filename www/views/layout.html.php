<!DOCTYPE html>
<html>
    <?php
    include_once(ROOT . '/includes/head.inc.php'); ?>

    <body>
        <div id="top"></div> <!-- to go to the top of the page -->

        <?php include_once(ROOT . '/includes/header.inc.php'); ?>

        <article>
            <?= $content ?>
        </article>

        <?php include_once(ROOT . '/includes/footer.inc.php'); ?>

        <div id="bottom"></div> <!-- to go to the bottom of the page -->
    </body>
</html>