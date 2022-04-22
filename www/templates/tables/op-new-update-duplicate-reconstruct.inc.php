<?php

echo '<table class="op-table">';
if ($this->op->getAction() != 'reconstruct') {
    if (!empty($this->source)) {
        echo '<tr><th>REPO SOURCE :</th><td>' . $this->source . '</td></tr>';
    }
}

echo '<tr>
    <th>REPO :</th>
    <td>
        <span class="label-white">';
if (!empty($this->dist) and !empty($this->section)) {
    echo $this->name . ' ❯ ' . $this->dist . ' ❯ ' . $this->section;
} else {
    echo $this->name;
}
        echo '</span>
    </td>
</tr>';

if (!empty($this->targetDescription)) {
    echo '<tr><th>DESCRIPTION :</th><td>' . $this->targetDescription . '</td></tr>';
}
if (!empty($this->targetGpgCheck)) {
    echo "<tr><th>VERIFICATION DES SIGNATURES GPG :</th><td>";

    if ($this->targetGpgCheck == 'yes') {
        echo '<span><img src="ressources/icons/greencircle.png" class="icon-small" /> Activé</span>';
    }
    if ($this->targetGpgCheck == 'no') {
        echo '<span><img src="ressources/icons/redcircle.png" class="icon-small" /> Désactivé</span>';
    }
    echo "</td></tr>";
}
if (!empty($this->targetGpgResign)) {
    echo '<tr><th>SIGNATURE DU REPO AVEC GPG :</th><td>';

    if ($this->targetGpgResign == "yes") {
        echo '<span><img src="ressources/icons/greencircle.png" class="icon-small" /> Activé</span>';
    }
    if ($this->targetGpgResign == "no") {
        echo '<span><img src="ressources/icons/redcircle.png" class="icon-small" /> Désactivé</span>';
    }
    echo '</tr>';
}
if (!empty($this->targetGroup)) {
    echo '<tr><th>AJOUT AU GROUPE :</th><td><img src="ressources/icons/folder.png" class="icon" />' . $this->targetGroup . '</td></tr>';
}
echo "</table>";
