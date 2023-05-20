<article class="reloadable-container" container="header/debug-mode">
    <?php
    /**
     *  Debug mode
     */
    if (DEBUG_MODE === true) : ?>
        <section class="section-main">
            <div class="div-generic-blue">
                <p>Debug mode enabled</p>
                <?php
                if (!empty($_POST)) {
                    echo '<br>POST : <pre>';
                    print_r($_POST);
                    echo '</pre>';
                }
                if (!empty($_GET)) {
                    echo '<br>GET : <pre>';
                    print_r($_GET);
                    echo '</pre>';
                } ?>
            </div>
        </section>
        <?php
    endif ?>
</article>