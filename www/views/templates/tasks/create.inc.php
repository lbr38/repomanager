<div class="div-generic-blue">
    <div class="flex align-item-center justify-space-between">
        <h3>
            <?php
            if ($repoController->getType() == 'mirror') {
                echo strtoupper($repoController->getPackageType()) . ' MIRROR REPOSITORY';
            }
            if ($repoController->getType() == 'local') {
                echo 'LOCAL ' . strtoupper($repoController->getPackageType()) . ' REPOSITORY';
            } ?>
        </h3>

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

        <?php
        if ($repoController->getType() == 'mirror') : ?>
            <div>
                <h6 class="margin-top-0">SOURCE REPOSITORY</h6>
                <p class="copy"><span class="label-white"><?= $repoController->getSource() ?></span></p>
            </div>
            <?php
        endif ?>
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
        if (!empty($repoController->getArch())) : ?>
            <div>
                <h6>ARCHITECTURE</h6>
                <div class="flex column-gap-5 row-gap-5">
                    <?php
                    foreach ($repoController->getArch() as $arch) {
                        echo '<span class="label-black">' . $arch . '</span>';
                    } ?>
                </div>
            </div>

            <?php
        endif ?>
    </div>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <?php
        if ($repoController->getType() == 'mirror') :
            if (!empty($repoController->getPackagesToInclude())) : ?>
                <div>
                    <h6>PACKAGES TO INCLUDE</h6>
                    <div class="flex column-gap-5 row-gap-5">
                        <?php
                        foreach ($repoController->getPackagesToInclude() as $package) {
                            echo '<span class="label-black">' . $package . '</span>';
                        } ?>
                    </div>
                </div>
                <?php
            endif;

            if (!empty($repoController->getPackagesToExclude())) : ?>
                <div>
                    <h6>PACKAGES TO EXCLUDE</h6>
                    <div class="flex column-gap-5 row-gap-5">
                        <?php
                        foreach ($repoController->getPackagesToExclude() as $package) {
                            echo '<span class="label-black">' . $package . '</span>';
                        } ?>
                    </div>
                </div>
                <?php
            endif;
        endif ?>
    </div>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <?php
        if ($repoController->getType() == 'mirror') :
            if (!empty($repoController->getGpgCheck())) : ?>
                <div>
                    <h6>CHECK GPG SIGNATURES</h6>
                    <?php
                    if ($repoController->getGpgCheck() == 'true') : ?>
                        <div class="flex column-gap-5">
                            <img src="/assets/icons/check.svg" class="icon" />
                            <span>Enabled</span>
                        </div>
                        <?php
                    endif;
                    if ($repoController->getGpgCheck() == 'false') : ?>
                        <div class="flex column-gap-5">
                            <img src="/assets/icons/error.svg" class="icon" />
                            <span>Disabled</span>
                        </div>
                        <?php
                    endif ?>
                </div>
                <?php
            endif;

            if (!empty($repoController->getGpgSign())) : ?>
                <div>
                    <h6>SIGN WITH GPG</h6>
                    <?php
                    if ($repoController->getGpgSign() == 'true') : ?>
                        <div class="flex column-gap-5">
                            <img src="/assets/icons/check.svg" class="icon" />
                            <span>Enabled</span>
                        </div>
                        <?php
                    endif;
                    if ($repoController->getGpgSign() == 'false') : ?>
                        <div class="flex column-gap-5">
                            <img src="/assets/icons/error.svg" class="icon" />
                            <span>Disabled</span>
                        </div>
                        <?php
                    endif ?>
                </div>
                <?php
            endif;
        endif ?>
    </div>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <?php
        if (!empty($repoController->getDescription())) : ?>
            <div>
                <h6>DESCRIPTION</h6>
                <p><?= $repoController->getDescription() ?></p>
            </div>
            <?php
        endif;

        if (!empty($repoController->getGroup())) : ?>
            <div>
                <h6>ADD TO GROUP</h6>
                <p><?= $repoController->getGroup() ?></p>
            </div>
            <?php
        endif ?>
    </div>
</div>
