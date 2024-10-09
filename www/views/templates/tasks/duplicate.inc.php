<div class="div-generic-blue">
    <div class="flex align-item-center justify-space-between">
        <h3>DUPLICATE REPOSITORY SNAPSHOT</h3>

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
                    if ($this->sourceRepo->getPackageType() == 'rpm') {
                        echo $this->sourceRepo->getName();
                    }
                    if ($this->sourceRepo->getPackageType() == 'deb') {
                        echo $this->sourceRepo->getName() . ' ❯ ' . $this->sourceRepo->getDist() . ' ❯ ' . $this->sourceRepo->getSection();
                    } ?>
                </span>
            </p>
        </div>

        <div>
            <h6 class="margin-top-0">SNAPSHOT</h6>
            <p>
                <span class="label-black"><?= $this->sourceRepo->getDateFormatted() ?></span>
            </p>
        </div>
    </div>

    <div class="grid grid-2">
        <div>
            <h6>DUPLICATE TO</h6>
            <p>
                <span class="label-white">
                    <?php
                    if ($this->sourceRepo->getPackageType() == 'rpm') {
                        echo $this->repo->getName();
                    }
                    if ($this->sourceRepo->getPackageType() == 'deb') {
                        echo $this->repo->getName() . ' ❯ ' . $this->sourceRepo->getDist() . ' ❯ ' . $this->sourceRepo->getSection();
                    } ?>
                </span>
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
        endif;

        if (!empty($this->repo->getGroup())) : ?>
            <div>
                <h6>ADD TO GROUP</h6>
                <p><?= $this->repo->getGroup() ?></p>
            </div>
            <?php
        endif ?>
    </div>
</div>