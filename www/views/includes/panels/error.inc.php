<section class="section-main slide-panel">
    <div class="div-generic-blue margin-15">
        <h6 class="margin-top-0">PANEL LOADING ERROR</h6>

        <p class="note">An error occurred while trying to load <code><?= $panel ?></code> panel: <?= strtolower($e->getMessage()) ?></p>
        <?php
        if (!empty($e->getTraceAsString())) {
            echo '<pre class="codeblock copy margin-top-10">' . $e->getTraceAsString() . '</pre>';
        } ?>
    </div>
</section>
