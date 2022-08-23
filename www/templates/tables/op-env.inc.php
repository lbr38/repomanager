<h3>NEW REPO ENVIRONMENT</h3>
<table class="op-table">
    <tr>
        <th>REPO:</th>
        <td>
            <span class="label-white">
                <?php
                if ($this->packageType == 'rpm') {
                    echo $this->name;
                }
                if ($this->packageType == 'deb') {
                    echo $this->name . ' ❯ ' . $this->dist . ' ❯ ' . $this->section;
                } ?>
            </span>
        </td>
    </tr>

    <tr>
        <th>ENVIRONMENT:</th>
        <td>
            <span><?= \Controllers\Common::envtag($this->targetEnv)?></span>⟶<span class="label-black"><?=$this->dateFormatted?></span>
        </td>
    </tr>
    <?php
    if (!empty($this->targetDescription)) : ?>
        <tr>
            <th>DESCRIPTION:</th>
            <td><?=$this->targetDescription?></td>
        </tr>
    <?php endif ?>
</table>