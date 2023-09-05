<div id="log-op-title" class="div-generic-blue">
    <h3>DUPLICATE REPO SNAPSHOT</h3>
</div>

<div class="div-generic-blue">
    <table class="op-table">
        <tr>
            <th>SOURCE REPO</th>
            <td>
                <?php
                if ($this->repo->getPackageType() == "rpm") {
                    echo '<span class="label-white">' . $this->repo->getName() . '</span>⟶<span class="label-black">' . $this->repo->getDateFormatted() . '</span>';
                }
                if ($this->repo->getPackageType() == "deb") {
                    echo '<span class="label-white">' . $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection() . '</span>⟶<span class="label-black">' . $this->repo->getDateFormatted() . '</span>';
                } ?>
            </td>
        </tr>

        <tr>
            <th>NEW REPO NAME</th>
            <td><span class="label-white"><?= $this->repo->getTargetName() ?></span></td>
        </tr>

        <?php
        if (!empty($this->repo->getTargetDescription())) : ?>
            <tr>
                <th>DESCRIPTION</th>
                <td><?= $this->repo->getTargetDescription() ?></td>
            </tr>
            <?php
        endif;

        if (!empty($this->repo->getTargetGroup())) : ?>
            <tr>
                <th>ADD TO GROUP</th>
                <td><span class="label-white"><?= $this->repo->getTargetGroup() ?></span></td>
            </tr>
            <?php
        endif ?>
    </table>
</div>