<?php
if (OS_FAMILY == "Redhat") echo "<h3>DUPLIQUER UN REPO</h3>";
if (OS_FAMILY == "Debian") echo "<h3>DUPLIQUER UNE SECTION DE REPO</h3>";

echo '<table class="op-table">';
if (OS_FAMILY == "Redhat") {
    echo "<tr>
        <th>NOM DU REPO SOURCE :</th>
        <td>$name ".Common::envtag($env)."</td>
    </tr>";
}
if (OS_FAMILY == "Debian") {
    echo "<tr>
        <th>NOM DU REPO SOURCE :</th>
        <td>$name</td>
    </tr>
    <tr>
        <th>DISTRIBUTION :</th>
        <td>$dist</td>
    </tr>
    <tr>
        <th>SECTION :</th>
        <td>$section ".Common::envtag($env)."</td>
    </tr>";
}
if (!empty($targetName)) {
    echo "<tr>
        <th>NOM DU NOUVEAU REPO :</th>
        <td>$targetName</td>
    </tr>";
}
if (!empty($targetDescription)) {
    echo "<tr>
        <th>DESCRIPTION :</th>
        <td>$targetDescription</td>
    </tr>";
}
if (!empty($targetGroup)) {
    echo "<tr>
        <th>AJOUT À UN GROUPE :</th>
        <td>$targetGroup</td>
    </tr>";
}
echo '</table>';
?>