<div id="task-details" class="div-generic-blue margin-bottom-15">
    <div class="grid grid-2 row-gap-10 column-gap-20">
        <div>
            <h6 class="margin-top-0">REPOSITORY</h6>
            <p>
                <span class="label-white">
                    <?php
                    if ($repoController->getPackageType() == 'rpm') {
                        echo $rawParams['old-name'] . ' ❯ ' . $repoController->getReleasever();
                    }
                    if ($repoController->getPackageType() == 'deb') {
                        echo $rawParams['old-name'] . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
                    } ?>
                </span>
            </p>
        </div>

        <div>
            <h6 class="margin-top-0">RENAME TO</h6>
            <p>
                <span class="label-white">
                    <?php
                    if ($repoController->getPackageType() == 'rpm') {
                        echo $rawParams['name'] . ' ❯ ' . $repoController->getReleasever();
                    }
                    if ($repoController->getPackageType() == 'deb') {
                        echo $rawParams['name'] . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
                    } ?>
                </span>
            </p>
        </div>
    </div>
</div>