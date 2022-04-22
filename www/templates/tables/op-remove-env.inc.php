<h3>SUPPRESSION D'UN ENVIRONNEMENT</h3>

<table class="op-table">
    <tr>
        <th>REPO :</th>
        <td>
            <span class="label-white">
                <?php
                if (!empty($this->dist) and !empty($this->section)) {
                    echo $this->name . ' ❯ ' . $this->dist . ' ❯ ' . $this->section;
                } else {
                    echo $this->name;
                } ?>
            </span>
        </td>
    </tr>
    <tr>
        <th>ENVIRONNEMENT :</th>
        <td><?=\Models\Common::envtag($this->env)?></td>
    </tr>
</table>