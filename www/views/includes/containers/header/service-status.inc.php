<article class="reloadable-container" container="header/service-status">
    <?php
    if (!SERVICE_RUNNING) : ?>
        <section class="section-main">
            <div class="div-generic-blue flex column-gap-5">
                <img src="/assets/icons/warning.svg" class="icon" />
                <p class="yellowtext">Repomanager service is not running. Please restart the container.</p>
            </div>
        </section>
        <?php
    endif ?> 
</article>