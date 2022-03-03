<?php
if (OS_FAMILY == "Redhat") echo '<h3>CREATION D\'UN NOUVEAU REPO LOCAL</h3>';
if (OS_FAMILY == "Debian") echo '<h3>CREATION D\'UNE NOUVELLE SECTION DE REPO LOCAL</h3>';
?>

<table class="op-table">
    <tr>
        <th>NOM DU REPO :</th>
        <td><?=$name?></td>
    </tr>
    <?php
    if (OS_FAMILY == "Debian") { ?>
        <tr>
            <th>DISTRIBUTION :</th>
            <td><?=$dist?></td>
        </tr>
        <tr>
            <th>SECTION :</th>
            <td><?=$section?></td>
        </tr>
<?php }
    if (!empty($targetDescription)) { ?>
        <tr>
            <th>DESCRIPTION :</th>
            <td><?=$targetDescription?></td>
        </tr>
<?php }
    if (!empty($targetGroup)) { ?>
        <tr>
            <th>AJOUT À UN GROUPE :</th>
            <td><?=$targetGroup?></td>
        </tr>
<?php } ?>
</table>