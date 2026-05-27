<section class="section-main reloadable-container" container="repo/kpi">
    <h3>REPOSITORY</h3>

    <?php
    if ($repoController->getPackageType() == 'rpm') {
        $repository = $repoController->getName() . ' ❯ ' . $repoController->getReleasever();
        $accent = 'blue';
    }
    if ($repoController->getPackageType() == 'deb') {
        $repository = $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
        $accent = 'red';
    } ?>

    <div class="grid grid-rfr-1-3 column-gap-20 row-gap-20 margin-bottom-15">
        <div class="kpi-card accent-<?= $accent ?>">
            <img src="/assets/icons/package.svg" class="icon-np icon-medium" />
            <div>
                <span class="label-white"><?= $repository ?></span>
                <p class="mediumopacity-cst">Repository</p>
            </div>
        </div>

        <div class="kpi-card">
            <img src="/assets/icons/stack.svg" class="icon-np icon-medium" />
            <div>
                <p class="kpi-value"><?= count($snapshots) ?></p>
                <p class="mediumopacity-cst">Snapshots</p>
            </div>
        </div>

        <div class="kpi-card">
            <img src="/assets/icons/cloud-download.svg" class="icon-np icon-medium" />
            <div>
                <p class="kpi-value"><?= $accessCount ?></p>
                <p class="mediumopacity-cst">Total accesses today</p>
            </div>
        </div>
    </div>
</section>
