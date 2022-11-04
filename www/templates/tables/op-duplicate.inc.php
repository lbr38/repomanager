<div id="log-op-title" class="div-generic-blue">
    <h3>DUPLICATE REPO SNAPSHOT</h3>
</div>

<div class="div-generic-blue">
    <table class="op-table">
        <tr>
            <th>SOURCE REPO</th>
            <td>
                <?php
                if ($this->packageType == "rpm") {
                    echo '<span class="label-white">' . $this->name . '</span>⟶<span class="label-black">' . $this->dateFormatted . '</span>';
                }
                if ($this->packageType == "deb") {
                    echo '<span class="label-white">' . $this->name . ' ❯ ' . $this->dist . ' ❯ ' . $this->section . '</span>⟶<span class="label-black">' . $this->dateFormatted . '</span>';
                } ?>
            </td>
        </tr>

        <tr>
            <th>NEW REPO NAME</th>
            <td><span class="label-white"><?= $this->targetName ?></span></td>
        </tr>

        <?php
        if (!empty($this->targetDescription)) : ?>
            <tr>
                <th>DESCRIPTION</th>
                <td><?= $this->targetDescription ?></td>
            </tr>
            <?php
        endif;

        if (!empty($this->targetGroup)) : ?>
            <tr>
                <th>ADD TO GROUP</th>
                <td><span class="label-white"><?= $this->targetGroup ?></span></td>
            </tr>
            <?php
        endif ?>
    </table>
</div>