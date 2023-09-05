<div id="log-op-title" class="div-generic-blue">
    <h3>CREATE NEW LOCAL REPO</h3>
</div>

<div class="div-generic-blue">
    <table class="op-table">
        <tr>
            <th>REPO</th>
            <td><?= $this->repo->getName() ?></td>
        </tr>
        <?php
        if (!empty($this->repo->getDist()) and !empty($this->repo->getSection())) : ?>
            <tr>
                <th>DISTRIBUTION</th>
                <td><?= $this->repo->getDist() ?></td>
            </tr>
            <tr>
                <th>SECTION</th>
                <td><?= $this->repo->getSection() ?></td>
            </tr>
            <?php
        endif;
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
                <td><?= $this->repo->getTargetGroup() ?></td>
            </tr>
            <?php
        endif ?>
    </table>
</div>