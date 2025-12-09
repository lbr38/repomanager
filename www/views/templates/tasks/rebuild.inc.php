<div class="div-generic-blue margin-bottom-15">
    <div class="flex align-item-center justify-space-between">
        <h3>REBUILD REPOSITORY METADATA</h3>

        <div class="text-right">
            <p title="Task execution date"><?= DateTime::createFromFormat('Y-m-d', $taskInfo['Date'])->format('d-m-Y') . ' ' . $taskInfo['Time'] ?></p>
            <div class="flex align-item-center column-gap-5 justify-end">
                <p title="Task Id">Task #<?= $taskId ?></p>
                <?php
                if ((DEVEL or \Controllers\App\DebugMode::enabled()) and file_exists(MAIN_LOGS_DIR . '/repomanager-task-' . $taskId . '-log.process')) {
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
                    if (!empty($repoController->getDist()) and !empty($repoController->getSection())) {
                        echo $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
                    } else {
                        echo $repoController->getName() . ' ❯ ' . $repoController->getReleasever();
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
        <?php
        if (!empty($repoController->getArch())) : ?>
            <div>
                <h6>ARCHITECTURE</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ($repoController->getArch() as $arch) {
                        echo '<span class="label-black">' . $arch . '</span>';
                    } ?>
                </div>
            </div>
            <?php
        endif ?>

        <?php
        if (!empty($rawParams['gpg-sign'])) : ?>
            <div>
                <h6>SIGN WITH GPG</h6>
                <?php
                if ($rawParams['gpg-sign'] == 'true') : ?>
                    <div class="flex column-gap-5">
                        <img src="/assets/icons/check.svg" class="icon" />
                        <span>Enabled</span>
                    </div>
                    <?php
                endif;
                if ($rawParams['gpg-sign'] == 'false') : ?>
                    <div class="flex column-gap-5">
                        <img src="/assets/icons/error.svg" class="icon" />
                        <span>Disabled</span>
                    </div>
                    <?php
                endif ?>
            </div>
            <?php
        endif ?>
    </div>
</div>
