<div id="task-details" class="div-generic-blue margin-bottom-15">
    <div class="grid grid-2 row-gap-10 column-gap-20">
        <div>
            <h6 class="margin-top-0">REPOSITORY</h6>
            <p>
                <span class="label-white">
                    <?php
                    if ($repoController->getPackageType() == 'deb') {
                        echo $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
                    }
                    if ($repoController->getPackageType() == 'rpm') {
                        echo $repoController->getName() . ' ❯ ' . $repoController->getReleasever();
                    } ?>
                </span>
            </p>
        </div>

        <div>
            <h6 class="margin-top-0">DATE</h6>
            <p class="label-black"><?= $repoController->getDateFormatted() ?></p>
        </div>
    </div>
</div>
