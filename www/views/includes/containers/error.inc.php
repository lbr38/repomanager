<section class="section-main">
    <h3>ERROR</h3>

    <div class="div-generic-blue">
        <p>An error occurred while trying to load <code><?= $container ?></code> container: <?= $e->getMessage() ?></p>
        <?php
        if (!empty($e->getTraceAsString())) {
            echo '<pre class="codeblock margin-top-10">' . $e->getTraceAsString() . '</pre>';
        } ?>
    </div>
</section>
