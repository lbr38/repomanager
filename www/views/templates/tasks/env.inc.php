<div class="div-generic-blue">
    <div class="flex align-item-center justify-space-between">
        <h3>POINT AN ENVIRONMENT</h3>

        <div class="text-right">
            <p title="Task execution date"><?= DateTime::createFromFormat('Y-m-d', $this->task->getDate())->format('d-m-Y') . ' ' . $this->task->getTime() ?></p>
            <p title="Task Id">Task #<?= $this->task->getId() ?></p>
        </div>
    </div>
</div>

<div class="div-generic-blue">
    <div class="grid grid-2">
        <div>
            <h6 class="margin-top-0">REPOSITORY</h6>
            <p>
                <span class="label-white">
                    <?php
                    if ($this->repo->getPackageType() == 'rpm') {
                        echo $this->repo->getName();
                    }
                    if ($this->repo->getPackageType() == 'deb') {
                        echo $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection();
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

    <div class="grid grid-2">
        <div>
            <h6>ENVIRONMENT</h6>
            <p>
                <?= \Controllers\Common::envtag($this->repo->getEnv()) ?>
            </p>
        </div>
    </div>

    <div class="grid grid-2">
        <?php
        if (!empty($this->repo->getDescription())) : ?>
            <div>
                <h6>DESCRIPTION</h6>
                <p><?= $this->repo->getDescription() ?></p>
            </div>
            <?php
        endif ?>
    </div>
</div>
