<?php
if (OS_FAMILY == "Redhat") echo '<h3>RESTAURER UN REPO ARCHIVÉ</h3>';
if (OS_FAMILY == "Debian") echo '<h3>RESTAURER UNE SECTION ARCHIVÉE</h3>';
echo "<table class=\"op-table\">
<tr>
    <th>NOM DU REPO :</th>
    <td>$name</td>
</tr>";
if (OS_FAMILY == "Debian") {
    echo "<tr>
        <th>DISTRIBUTION :</th>
        <td>$dist</td>
    </tr>
    <tr>
        <th>SECTION :</th>
        <td>$section</td>
    </tr>";
}
echo "<tr>
    <th>DATE :</th>
    <td>$dateFormatted</td>
</tr>
<tr>
    <th>ENVIRONNEMENT CIBLE :</th>
    <td>".Common::envtag($targetEnv)."</td>
</tr>";
if (!empty($description)) {
    echo "<tr>
        <th>DESCRIPTION :</th>
        <td>$description</td>
    </tr>";
}
echo "</table>";
?>