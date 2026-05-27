<section class="section-main reloadable-container" container="snapshot/kpi">
    <h3>SNAPSHOT</h3>

    <?php
    if ($repoController->getPackageType() == 'rpm') {
        $repository = $repoController->getName() . ' ❯ ' . $repoController->getReleasever();
        $accent = 'blue';
    }
    if ($repoController->getPackageType() == 'deb') {
        $repository = $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
        $accent = 'red';
    }

    $class = 'grid-rfr-1-4';

    if (in_array($rebuild, ['needed', 'running'])) {
        $class = 'grid-rfr-1-5';

        if ($rebuild == 'needed') {
            $title = 'Repository snapshot content has been modified, metadata rebuild is required';
            $icon = 'warning';
            $text = 'Rebuild required';
            $kpiAccent = 'yellow';
        }

        if ($rebuild == 'running') {
            $title = 'A task is running on this repository snapshot';
            $icon = 'loading';
            $text = 'Rebuild running';
            $kpiAccent = 'cyan';
        }
    } ?>

    <div class="grid <?= $class ?> column-gap-20 row-gap-20 margin-bottom-15">
        <div class="kpi-card accent-<?= $accent ?>">
            <img src="/assets/icons/package.svg" class="icon-np icon-medium" />
            <div>
                <span class="label-white"><?= $repository ?></span>
                <p class="mediumopacity-cst">Repository</p>
            </div>
        </div>

        <div class="kpi-card">
            <img src="/assets/icons/calendar.svg" class="icon-np icon-medium" />
            <div>
                <p class="kpi-value"><?= $repoController->getDateFormatted() ?></p>
                <p class="mediumopacity-cst">Date</p>
            </div>
        </div>

        <div class="kpi-card">
            <img src="/assets/icons/save.svg" class="icon-np icon-medium" />
            <div>
                <p class="kpi-value"><?= $repoSize ?></p>
                <p class="mediumopacity-cst">Size</p>
            </div>
        </div>

        <div class="kpi-card">
            <img src="/assets/icons/package.svg" class="icon-np icon-medium" />
            <div>
                <p class="kpi-value"><?= $packagesCount ?></p>
                <p class="mediumopacity-cst">Packages</p>
            </div>
        </div>

        <?php
        if (in_array($rebuild, ['needed', 'running'])) : ?>
            <div class="kpi-card kpi-accent-<?= $kpiAccent ?>">
                <img src="/assets/icons/<?= $icon ?>.svg" class="icon-np icon-medium" />
                <div>
                    <p class="kpi-value" title="<?= $title ?>"><?= $text ?></p>
                    <p class="mediumopacity-cst">Metadata</p>
                </div>
            </div>
            <?php
        endif ?>
    </div>
</section>
