<div class="div-generic-blue margin-bottom-15">
    <div class="flex align-item-center justify-space-between">
        <h3>UPDATE REPOSITORY</h3>

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
                    if (!empty($repoController->getDist()) and !empty($repoController->getSection())) {
                        echo $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
                    } else {
                        echo $repoController->getName();
                    } ?>
                </span>
            </p>
        </div>

        <div>
            <h6 class="margin-top-0">SOURCE REPOSITORY</h6>
            <p class="copy"><span class="label-white"><?= $repoController->getSource() ?></span></p>
        </div>
    </div>

    <?php
    if ($repoController->getPackageType() == 'rpm' and !empty($repoController->getReleasever())) : ?>
        <div class="grid grid-2 row-gap-10 column-gap-20">
            <div>
                <h6>RELEASE VERSION</h6>
                <p><?= $repoController->getReleasever() ?></p>
            </div>
        </div>
        <?php
    endif ?>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <?php
        if (!empty($rawParams['arch'])) : ?>
            <div>
                <h6>ARCHITECTURE</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ($rawParams['arch'] as $arch) {
                        echo '<span class="label-black">' . $arch . '</span>';
                    } ?>
                </div>
            </div>
            <?php
        endif;

        if (!empty($rawParams['env'])) : ?>
            <div>
                <h6>ENVIRONMENT</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ($rawParams['env'] as $env) {
                        echo \Controllers\Utils\Generate\Html\Label::envtag($env);
                    } ?>
                </div>
            </div>
            <?php
        endif ?>
    </div>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <?php
        if (!empty($rawParams['package-include'])) : ?>
            <div>
                <h6>PACKAGES TO INCLUDE</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ($rawParams['package-include'] as $package) {
                        echo '<span class="label-black">' . $package . '</span>';
                    } ?>
                </div>
            </div>
            <?php
        endif;

        if (!empty($rawParams['package-exclude'])) : ?>
            <div>
                <h6>PACKAGES TO EXCLUDE</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ($rawParams['package-exclude'] as $package) {
                        echo '<span class="label-black">' . $package . '</span>';
                    } ?>
                </div>
            </div>
            <?php
        endif ?>
    </div>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <?php
        if (!empty($rawParams['gpg-check'])) : ?>
            <div>
                <h6>CHECK GPG SIGNATURES</h6>
                <?php
                if ($rawParams['gpg-check'] == 'true') : ?>
                    <div class="flex column-gap-5">
                        <img src="/assets/icons/check.svg" class="icon" />
                        <span>Enabled</span>
                    </div>
                    <?php
                endif;
                if ($rawParams['gpg-check'] == 'false') : ?>
                    <div class="flex column-gap-5">
                        <img src="/assets/icons/error.svg" class="icon" />
                        <span>Disabled</span>
                    </div>
                    <?php
                endif ?>
            </div>
            <?php
        endif;

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
