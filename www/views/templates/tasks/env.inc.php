<div id="log-op-title" class="div-generic-blue">
    <h3>POINT AN ENVIRONMENT</h3>
</div>

<div class="div-generic-blue">
    <table class="op-table">
        <tr>
            <th>REPOSITORY</th>
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
            <th>SNAPSHOT</th>
            <td>
                <span class="label-black"><?= $this->repo->getDateFormatted() ?></span>
            </td>
        </tr>

        <tr>
            <th>ENVIRONMENT</th>
            <td>
                <span><?= \Controllers\Common::envtag($this->repo->getEnv()) ?></span>
            </td>
        </tr>

        <?php
        if (!empty($this->repo->getDescription())) : ?>
            <tr>
                <th>DESCRIPTION</th>
                <td><?= $this->repo->getDescription() ?></td>
            </tr>
            <?php
        endif ?>
    </table>
</div>