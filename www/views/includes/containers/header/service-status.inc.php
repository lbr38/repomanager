<article class="reloadable-container" container="header/service-status">
    <?php
    if (!SERVICE_RUNNING) : ?>
        <section class="section-main">
            <div class="div-generic-blue flex">
                <img src="assets/icons/warning.png" class="icon" /><span class="yellowtext">Repomanager service is not running. Please restart the container.</span>
            </div>
        </section>
        <?php
    endif ?> 
</article>