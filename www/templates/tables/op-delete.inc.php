<div id="log-op-title" class="div-generic-blue">
    <h3>DELETE REPO SNAPSHOT</h3>
</div>

<div class="div-generic-blue">
    <table class="op-table">
        <tr>
            <th>REPO</th>
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
            <th>DATE</th>
            <td><span class="label-black"><?=$this->dateFormatted?></span></td>
        </tr>
    </table>
</div>