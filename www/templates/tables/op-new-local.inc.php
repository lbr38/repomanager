<div id="log-op-title" class="div-generic-blue">
    <h3>CREATE NEW LOCAL REPO</h3>
</div>

<div class="div-generic-blue">
    <table class="op-table">
        <tr>
            <th>REPO:</th>
            <td><?=$this->name?></td>
        </tr>
        <?php
        if (!empty($this->dist) and !empty($this->section)) : ?>
            <tr>
                <th>DISTRIBUTION:</th>
                <td><?=$this->dist?></td>
            </tr>
            <tr>
                <th>SECTION:</th>
                <td><?=$this->section?></td>
            </tr>
            <?php
        endif;
        if (!empty($this->targetDescription)) : ?>
            <tr>
                <th>DESCRIPTION:</th>
                <td><?=$this->targetDescription?></td>
            </tr>
            <?php
        endif;
        if (!empty($this->targetGroup)) : ?>
            <tr>
                <th>ADD TO GROUP:</th>
                <td><?=$this->targetGroup?></td>
            </tr>
            <?php
        endif ?>
    </table>
</div>