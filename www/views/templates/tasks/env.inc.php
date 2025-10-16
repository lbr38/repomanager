<div class="div-generic-blue margin-bottom-15">
    <div class="flex align-item-center justify-space-between">
        <h3>POINT AN ENVIRONMENT</h3>

        <div class="text-right">
            <p title="Task execution date"><?= DateTime::createFromFormat('Y-m-d', $taskInfo['Date'])->format('d-m-Y') . ' ' . $taskInfo['Time'] ?></p>
            <div class="flex align-item-center column-gap-5 justify-end">
                <p title="Task Id">Task #<?= $taskId ?></p>
                <?php
                if (DEVEL and file_exists(MAIN_LOGS_DIR . '/repomanager-task-' . $taskId . '-log.process')) {
                    echo '<img src="/assets/icons/file.svg" class="icon view-task-process-log" task-id="' . $taskId . '" title="Debug log" />';
                } ?>
            </div>
        </div>
    </div>
</div>

<div class="div-generic-blue margin-bottom-15">
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
                    echo \Controllers\Common::envtag($env);
                } ?>
            </div>
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
