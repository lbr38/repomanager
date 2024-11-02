<div class="div-generic-blue">
    <div class="flex align-item-center justify-space-between">
        <h3>REBUILD REPOSITORY METADATA</h3>

        <div class="text-right">
            <p title="Task execution date"><?= DateTime::createFromFormat('Y-m-d', $this->task->getDate())->format('d-m-Y') . ' ' . $this->task->getTime() ?></p>
            <p title="Task Id">Task #<?= $this->task->getId() ?></p>
        </div>
    </div>
</div>

<div class="div-generic-blue">
    <div class="grid grid-2 row-gap-10">
        <div>
            <h6 class="margin-top-0">REPOSITORY</h6>
            <p>
                <span class="label-white">
                    <?php
                    if (!empty($this->repo->getDist()) and !empty($this->repo->getSection())) {
                        echo $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection();
                    } else {
                        echo $this->repo->getName();
                    } ?>
                </span>
            </p>
        </div>

        <div>
            <h6 class="margin-top-0">SNAPSHOT</h6>
            <p>
                <span class="label-black"><?= $this->repo->getDateFormatted() ?></span>
            </p>
        </div>
    </div>

    <?php
    if ($this->repo->getPackageType() == 'rpm' and !empty($this->repo->getReleasever())) : ?>
        <div class="grid grid-2 row-gap-10">
            <div>
                <h6>RELEASE VERSION</h6>
                <p><?= $this->repo->getReleasever() ?></p>
            </div>
        </div>
        <?php
    endif ?>

    <div class="grid grid-2 row-gap-10">
        <?php
        if (!empty($this->repo->getArch())) : ?>
            <div>
                <h6>ARCHITECTURE</h6>
                <div class="flex column-gap-5 row-gap-5">
                    <?php
                    foreach ($this->repo->getArch() as $arch) {
                        echo '<span class="label-black">' . $arch . '</span>';
                    } ?>
                </div>
            </div>
            <?php
        endif ?>
    </div>

    <div class="grid grid-2 row-gap-10">
        <?php
        if (!empty($this->repo->getGpgSign())) : ?>
            <div>
                <h6>SIGN WITH GPG</h6>
                <?php
                if ($this->repo->getGpgSign() == 'true') : ?>
                    <div class="flex column-gap-5">
                        <img src="/assets/icons/check.svg" class="icon" />
                        <span>Enabled</span>
                    </div>
                    <?php
                endif;
                if ($this->repo->getGpgSign() == 'false') : ?>
                    <div class="flex column-gap-5">
                        <img src="/assets/icons/warning-red.svg" class="icon" />
                        <span>Disabled</span>
                    </div>
                    <?php
                endif ?>
            </div>
            <?php
        endif ?>
    </div>
</div>
