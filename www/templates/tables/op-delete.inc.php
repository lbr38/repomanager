<?php
if ($status == 'active') {
    if (OS_FAMILY == 'Redhat') echo "<h3>SUPPRESSION D'UN REPO</h3>";
    if (OS_FAMILY == 'Debian') echo "<h3>SUPPRESSION D'UNE SECTION</h3>";
}
if ($status == 'archived') {
    if (OS_FAMILY == 'Redhat') echo "<h3>SUPPRESSION D'UN REPO ARCHIVÉ</h3>";
    if (OS_FAMILY == 'Debian') echo "<h3>SUPPRESSION D'UNE SECTION ARCHIVÉE</h3>";
} ?>
<table class="op-table">
    <tr>
        <th>NOM DU REPO :</th>
        <td><?=$name?></td>
    </tr>

<?php
if (OS_FAMILY == 'Debian') { ?>
    <tr>
        <th>DISTRIBUTION :</th>
        <td><?=$dist?></td>
    </tr>
    <tr>
        <th>SECTION :</th>
        <td><?=$section?></td>
    </tr>
<?php } ?>
    <tr>
        <th>DATE :</th>
        <td><span class="label-black"><?=$dateFormatted?></span></td>
    </tr>
<?php
if ($status == 'active') { ?>
    <tr>
        <th>ENVIRONNEMENT :</th>
        <td><?=Common::envtag($env)?></td>
    </tr>
<?php } ?>
</table>