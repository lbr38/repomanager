<div id="log-op-title" class="div-generic-blue">
    <h3>REMOVE REPO ENVIRONMENT</h3>
</div>

<div class="div-generic-blue">
    <table class="op-table">
        <tr>
            <th>REPO:</th>
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
            <th>ENVIRONNEMENT:</th>
            <td><?=\Controllers\Common::envtag($this->env)?></td>
        </tr>
    </table>
</div>