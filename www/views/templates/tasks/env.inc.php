<div class="div-generic-blue">
    <div class="flex align-item-center justify-space-between">
        <h3>POINT AN ENVIRONMENT</h3>

        <div class="text-right">
            <p title="Task execution date"><?= DateTime::createFromFormat('Y-m-d', $taskInfo['Date'])->format('d-m-Y') . ' ' . $taskInfo['Time'] ?></p>
            <p title="Task Id">Task #<?= $taskId ?></p>
        </div>
    </div>
</div>

<div class="div-generic-blue">
    <div class="grid grid-2 row-gap-10 column-gap-20">
        <div>
            <h6 class="margin-top-0">REPOSITORY</h6>
            <p>
                <span class="label-white">
                    <?php
                    if ($repoController->getPackageType() == 'rpm') {
                        echo $repoController->getName();
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
            <p>
                <?= \Controllers\Common::envtag($rawParams['env']) ?>
            </p>
        </div>
    </div>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <?php
        if (!empty($repoController->getDescription())) : ?>
            <div>
                <h6>DESCRIPTION</h6>
                <p><?= $repoController->getDescription() ?></p>
            </div>
            <?php
        endif ?>
    </div>
</div>
