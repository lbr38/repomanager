<div id="log-op-title" class="div-generic-blue flex justify-space-between align-item-center">
    <h3>DUPLICATE REPOSITORY SNAPSHOT</h3>

    <div class="text-right">
        <p title="Task Id">
            <b>#<?= $this->task->getId() ?></b>
        </p>
        <p title="Task execution date">
            <b><?= DateTime::createFromFormat('Y-m-d', $this->task->getDate())->format('d-m-Y') . ' ' . $this->task->getTime() ?></b>
        </p>
    </div>
</div>

<div class="div-generic-blue">
    <table class="op-table">
        <tr>
            <th>SOURCE REPOSITORY</th>
            <td>
                <?php
                if ($this->sourceRepo->getPackageType() == 'rpm') {
                    echo '<span class="label-white">' . $this->sourceRepo->getName() . '</span>⟶<span class="label-black">' . $this->sourceRepo->getDateFormatted() . '</span>';
                }
                if ($this->sourceRepo->getPackageType() == 'deb') {
                    echo '<span class="label-white">' . $this->sourceRepo->getName() . ' ❯ ' . $this->sourceRepo->getDist() . ' ❯ ' . $this->sourceRepo->getSection() . '</span>⟶<span class="label-black">' . $this->sourceRepo->getDateFormatted() . '</span>';
                } ?>
            </td>
        </tr>

        <tr>
            <th>NEW REPOSITORY NAME</th>
            <td>
                <?php
                if ($this->sourceRepo->getPackageType() == 'rpm') {
                    echo '<span class="label-white">' . $this->repo->getName() . '</span>';
                }
                if ($this->sourceRepo->getPackageType() == 'deb') {
                    echo '<span class="label-white">' . $this->repo->getName() . ' ❯ ' . $this->sourceRepo->getDist() . ' ❯ ' . $this->sourceRepo->getSection() . '</span>';
                } ?>
            </td>
        </tr>

        <?php
        if (!empty($this->repo->getDescription())) : ?>
            <tr>
                <th>DESCRIPTION</th>
                <td><?= $this->repo->getDescription() ?></td>
            </tr>
            <?php
        endif;

        if (!empty($this->repo->getGroup())) : ?>
            <tr>
                <th>ADD TO GROUP</th>
                <td><span class="label-white"><?= $this->repo->getGroup() ?></span></td>
            </tr>
            <?php
        endif ?>
    </table>
</div>