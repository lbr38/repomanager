<h3>CREATION D'UN NOUVEAU REPO LOCAL</h3>

<table class="op-table">
    <tr>
        <th>REPO :</th>
        <td><?=$this->name?></td>
    </tr>
    <?php
    if (!empty($this->dist) and !empty($this->section)) { ?>
        <tr>
            <th>DISTRIBUTION :</th>
            <td><?=$this->dist?></td>
        </tr>
        <tr>
            <th>SECTION :</th>
            <td><?=$this->section?></td>
        </tr>
    <?php }
    if (!empty($this->targetDescription)) { ?>
        <tr>
            <th>DESCRIPTION :</th>
            <td><?=$this->targetDescription?></td>
        </tr>
    <?php }
    if (!empty($this->targetGroup)) { ?>
        <tr>
            <th>AJOUT À UN GROUPE :</th>
            <td><?=$this->targetGroup?></td>
        </tr>
    <?php } ?>
</table>