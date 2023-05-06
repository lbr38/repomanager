<article class="reloadable-container" container="header/general-error-messages">
    <?php
    /**
     *  Print missing parameters alert if any
     */
    if (__LOAD_GENERAL_ERROR > 0) : ?>
        <section class="section-main">
            <div class="div-generic-blue">
                <span class="yellowtext">Some settings from the <a href="/settings"><b>settings tab</b></a> contain missing or bad value that could generate errors on Repomanager. Please finalize the configuration before running any operation.</span>
                <br><br>
                <?php
                foreach (__LOAD_ERROR_MESSAGES as $message) {
                    echo '<span>' . $message . '</span><br>';
                } ?>
            </div>
        </section>
        <?php
    endif ?>
</article>
