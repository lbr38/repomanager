<h3>DUPLIQUER UN SNAPSHOT DE REPO</h3>

<?php
echo '<table class="op-table">';
    echo '<tr>
        <th>REPO SOURCE :</th>
        <td>';
if ($this->packageType == "rpm") {
    echo '<span class="label-white">' . $this->name . '</span>⟶<span class="label-black">' . $this->dateFormatted . '</span>';
}
if ($this->packageType == "deb") {
    echo '<span class="label-white">' . $this->name . ' ❯ ' . $this->dist . ' ❯ ' . $this->section . '</span>⟶<span class="label-black">' . $this->dateFormatted . '</span>';
}
        echo '</td>
        </tr>';

    echo '<tr>
        <th>NOM DU NOUVEAU REPO :</th>
        <td><span class="label-white">' . $this->targetName . '</span></td>
    </tr>';

if (!empty($this->targetDescription)) {
    echo '<tr>
            <th>DESCRIPTION :</th>
            <td>' . $this->targetDescription . '</td>
        </tr>';
}
if (!empty($this->targetGroup)) {
    echo '<tr>
            <th>AJOUT À UN GROUPE :</th>
            <td><span class="label-white">' . $this->targetGroup . '</span></td>
        </tr>';
}
echo '</table>';
?>
