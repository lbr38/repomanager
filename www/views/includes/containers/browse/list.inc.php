<section class="section-left reloadable-container" container="browse/list">
    <h3>BROWSE</h3>

    <?php
    if (!empty($myrepo->getName()) and !empty($myrepo->getDist()) and !empty($myrepo->getSection())) {
        echo '<p>Explore <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span></p>';
    } else {
        echo '<p>Explore <span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span></p>';
    } ?>

    <br>

    <div class="div-generic-blue grid grid-2">
        <div>
            <div class="circle-div-container">
                <div class="circle-div-container-count-green">
                    <span>
                        <?= $repoSize ?>
                    </span>
                </div>
                <div>
                    <span>Repo size</span>
                </div>
            </div>
        </div>
        <div>
            <div class="circle-div-container">
                <div class="circle-div-container-count-green">
                    <span>
                        <?= $packagesCount ?>
                    </span>
                </div>
                <div>
                    <span>Total packages</span>
                </div>
            </div>
        </div>
    </div>

    <div class="div-generic-blue">
        <?php
        if ($myrepo->getRebuild() == 'needed' or (is_dir($repoPath . '/my_uploaded_packages') and !\Controllers\Filesystem\Directory::isEmpty($repoPath . "/my_uploaded_packages"))) {
            echo '<p class="yellowtext">Repository snapshot content has been modified. You have to rebuild metadata.<br><br></p>';
        } ?>
        <span id="loading">Generating tree structure<img src="/assets/images/loading.gif" class="icon" /></span>

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