<div id="task-details" class="div-generic-blue margin-bottom-15">
    <div class="grid grid-2 row-gap-10 column-gap-20">
        <div>
            <h6 class="margin-top-0">REPOSITORY</h6>
            <p>
                <span class="label-white">
                    <?php
                    if ($repoController->getPackageType() == 'rpm') {
                        echo $repoController->getName() . ' ❯ ' . $repoController->getReleasever();
                    }
                    if ($repoController->getPackageType() == 'deb') {
                        echo $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
                    } ?>
                </span>
            </p>
        </div>

        <div>
            <h6 class="margin-top-0">SNAPSHOT</h6>
            <p>
                <span class="label-black"><?= $repoController->getDateFormatted() ?></span>
            </p>
        </div>
    </div>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <div>
            <h6>ENVIRONMENT</h6>
            <div class="flex column-gap-5 row-gap-5 flex-wrap">
                <?php
                foreach ($rawParams['env'] as $env) {
                    echo \Controllers\Utils\Generate\Html\Label::envtag($env);
                } ?>
            </div>
        </div>
    </div>
</div>
