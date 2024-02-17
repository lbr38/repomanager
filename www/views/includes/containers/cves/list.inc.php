<section class="section-main reloadable-container" container="cves/list">
    <h3>CVE</h3>

    <div class='margin-bottom-15'>
        <p>Search in CVEs:</p>
        <form action="/cves" method="get">
            <input class="input-medium" type="text" name="search" autocomplete="off">
        </form>
    </div>

    <?php
    /**
     *  Print CVEs list
     */
    \Controllers\Layout\Table\Render::render('cves/list'); ?>

</section>
