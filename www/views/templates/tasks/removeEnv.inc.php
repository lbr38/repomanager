<div class="div-generic-blue">
    <div class="flex align-item-center justify-space-between">
        <h3>REMOVE ENVIRONMENT</h3>

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
                    if (!empty($repoController->getDist()) and !empty($repoController->getSection())) {
                        echo $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
                    } else {
                        echo $repoController->getName();
                    } ?>
                </span>
            </p>
        </div>

        <div>
            <h6 class="margin-top-0">ENVIRONNEMENT</h6>
            <p><?= \Controllers\Common::envtag($rawParams['env']) ?></p>
        </div>
    </div>
</div>
