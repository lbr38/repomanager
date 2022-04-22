<?php
echo '<table class="op-table">';
if (!empty($source)) {
    echo '<tr><th>REPO SOURCE :</th><td>' . $source . '</td></tr>';
}
if (!empty($name)) {
    echo '<tr><th>NOM DU REPO :</th><td>' . $name . '</td></tr>';
}
if (!empty($dist)) {
    echo '<tr><th>DISTRIBUTION :</th><td>' . $dist . '</td></tr>';
}
if (!empty($section)) {
    echo '<tr><th>SECTION :</th><td>' . $section . '</td></tr>';
}
if (!empty($targetDescription)) {
    echo '<tr><th>DESCRIPTION :</th><td>' . $targetDescription . '</td></tr>';
}
if (!empty($targetGpgCheck)) {
    echo "<tr><th>VERIFICATION DES SIGNATURES GPG :</th><td>";

    if ($targetGpgCheck == 'yes') {
        echo '<span><img src="ressources/icons/greencircle.png" class="icon-small" /> Activé</span>';
    }
    if ($targetGpgCheck == 'no') {
        echo '<span><img src="ressources/icons/redcircle.png" class="icon-small" /> Désactivé</span>';
    }
    echo "</td></tr>";
}
if (!empty($targetGpgResign)) {
    echo '<tr><th>SIGNATURE DU REPO AVEC GPG :</th><td>';

    if ($targetGpgResign == "yes") {
        echo '<span><img src="ressources/icons/greencircle.png" class="icon-small" /> Activé</span>';
    }
    if ($targetGpgResign == "no") {
        echo '<span><img src="ressources/icons/redcircle.png" class="icon-small" /> Désactivé</span>';
    }
    echo '</tr>';
}
if (!empty($targetGroup)) echo '<tr><th>AJOUT AU GROUPE :</th><td><img src="ressources/icons/folder.png" class="icon" />' . $targetGroup . '</td></tr>';
echo "</table>";
?>