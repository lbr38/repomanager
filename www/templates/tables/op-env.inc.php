<div id="log-op-title" class="div-generic-blue">
    <h3>NEW REPO ENVIRONMENT</h3>
</div>

<div class="div-generic-blue">
    <table class="op-table">
        <tr>
            <th>REPO</th>
            <td>
                <span class="label-white">
                    <?php
                    if ($this->repo->getPackageType() == 'rpm') {
                        echo $this->repo->getName();
                    }
                    if ($this->repo->getPackageType() == 'deb') {
                        echo $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection();
                    } ?>
                </span>
            </td>
        </tr>

        <tr>
            <th>ENVIRONMENT</th>
            <td>
                <span><?= \Controllers\Common::envtag($this->repo->getTargetEnv()) ?></span>⟶<span class="label-black"><?= $this->repo->getDateFormatted() ?></span>
            </td>
        </tr>
        <?php
        if (!empty($this->repo->getTargetDescription())) : ?>
            <tr>
                <th>DESCRIPTION</th>
                <td><?= $this->repo->getTargetDescription() ?></td>
            </tr>
            <?php
        endif ?>
    </table>
</div>