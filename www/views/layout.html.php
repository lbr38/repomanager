<!DOCTYPE html>
<html>
    <?php include_once(ROOT . '/views/includes/head.inc.php'); ?>

    <body>
        <div class="flex column-gap-40">
            <!-- Left side menu -->
            <?php \Controllers\Layout\Container\Render::render('header/menu'); ?>

            <!-- Main content -->
            <article>
                <div id="menu-burger">
                    <img src="/assets/icons/menu.svg" class="icon-large get-panel-btn" panel="header/menu-burger" title="Open menu" />
                </div>

                <?php
                    include_once(ROOT . '/views/includes/containers/header/general-error-messages.inc.php');
                    include_once(ROOT . '/views/includes/containers/header/general-log-messages.inc.php');
                    include_once(ROOT . '/views/includes/containers/header/service-status.inc.php');
                    include_once(ROOT . '/views/includes/containers/header/debug-mode.inc.php');
                    include_once(ROOT . '/views/includes/maintenance.inc.php');
                ?>

                <?= $content ?>

                <?php include_once(ROOT . '/views/includes/footer.inc.php'); ?>
            </article>

            <!-- Right side virtual space -->
            <div id="virtual-space"></div>
        </div>

        

    

        
    </body>
</html>