<div class="div-generic-blue">
    <div class="flex align-item-center justify-space-between">
        <h3>LOCAL <?= strtoupper($this->repo->getPackageType()) ?> REPOSITORY</h3>

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
                    if (!empty($this->repo->getDist()) and !empty($this->repo->getSection())) {
                        echo $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection();
                    } else {
                        echo $this->repo->getName();
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