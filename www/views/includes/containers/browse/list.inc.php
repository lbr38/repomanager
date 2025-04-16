<section class="section-left reloadable-container" container="browse/list">
    <h3>BROWSE</h3>

    <?php
    if ($myrepo->getPackageType() == 'rpm') {
        $repo = $myrepo->getName();
    }
    if ($myrepo->getPackageType() == 'deb') {
        $repo = $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection();
    } ?>

    <div class="div-generic-blue grid grid-4">
        <div>
            <h6 class="margin-top-0">REPOSITORY</h6>
            <p><span class="label-white"><?= $repo ?></span></p>
        </div>

        <div>
            <h6 class="margin-top-0">SNAPSHOT</h6>
            <p><span class="label-black"><?= $myrepo->getDateFormatted() ?></span></p>
        </div>

        <div>
            <h6 class="margin-top-0">SIZE</h6>
            <p><?= $repoSize ?></p>
        </div>

        <div>
            <h6 class="margin-top-0">PACKAGES</h6>
            <p><?= $packagesCount ?></p>
        </div>
    </div>

    <div>
        <?php
        if ($myrepo->getRebuild() == 'needed') {
            echo '<p class="yellowtext">Repository snapshot content has been modified. You have to rebuild metadata.<br><br></p>';
        } ?>

        <div id="loading-tree" class="flex align-item-center column-gap-5"><p>Generating tree structure</p><img src="/assets/icons/loading.svg" class="icon" /></div>

        <div id="explorer" class="hide">
            <form id="packages-list" snap-id="<?= $snapId ?>">
                <?php
                /**
                 *  Print packages list
                 */
                \Controllers\Browse::tree($repoPath); ?>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            printTree();
        });
    </script>
</section>