<article class="reloadable-container" container="header/debug-mode">
    <?php
    /**
     *  Debug mode
     */
    if (DEBUG_MODE) : ?>
        <section class="section-main">
            <div class="div-generic-blue">
                <div class="flex align-item-center column-gap-5">
                    <img src="/assets/icons/build.svg" class="icon-mediumopacity" />
                    <p class="note">Debug mode enabled.</p>
                </div>

                <?php
                if (!empty($_POST)) {
                    echo '<br>POST data:</h6>';
                    echo '<pre class="codeblock copy">';
                    print_r($_POST);
                    echo '</pre>';
                }
                if (!empty($_GET)) {
                    echo '<h6>GET data</h6>';
                    echo '<pre class="codeblock copy">';
                    print_r($_GET);
                    echo '</pre>';
                } ?>
            </div>
        </section>
        <?php
    endif ?>
</article>
