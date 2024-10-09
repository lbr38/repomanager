<div class="div-generic-blue">
    <div class="flex align-item-center justify-space-between">
        <h3>REMOVE ENVIRONMENT</h3>

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

        <div>
            <h6 class="margin-top-0">ENVIRONNEMENT</h6>
            <p><?= \Controllers\Common::envtag($this->repo->getEnv()) ?></p>
        </div>
    </div>
</div>
