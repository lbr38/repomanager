<article class="reloadable-container" container="header/service-status">
    <?php
    // Do not display the message if an update is running because it is normal that the service is not running during an update
    if (!SERVICE_RUNNING and !UPDATE_RUNNING) : ?>
        <section class="section-main">
            <div class="div-generic-blue flex column-gap-5">
                <img src="/assets/icons/warning.svg" class="icon" />
                <p class="yellowtext">Repomanager service is not running. Please restart the container.</p>
            </div>
        </section>
        <?php
    endif ?> 
</article>