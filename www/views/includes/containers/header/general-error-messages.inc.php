<article class="reloadable-container" container="header/general-error-messages">
    <?php
    /**
     *  Print missing parameters alert if any
     */
    if (__LOAD_GENERAL_ERROR > 0) : ?>
        <section class="section-main">
            <div class="div-generic-blue">
                <?php
                if (__LOAD_SETTINGS_ERROR > 0) : ?>
                    <h6 class="margin-top-0">FINALIZE THE CONFIGURATION</h6>
                
                    <div class="flex column-gap-5">
                        <img src="/assets/icons/warning.svg" class="icon" />
                        <p class="note">Please, go to the <a href="/settings"><b>settings tab</b></a> to finalize the configuration before running any task.</p>
                    </div>
                    <?php
                endif;

                if (!empty(__LOAD_ERROR_MESSAGES)) : ?>
                    <div class="flex flex-direction-column row-gap-5 margin-top-10">
                        <?php
                        foreach (__LOAD_ERROR_MESSAGES as $message) {
                            echo '<p>' . $message . '</p>';
                        } ?>
                    </div>
                    <?php
                endif ?>
            </div>
        </section>
        <?php
    endif ?>
</article>
