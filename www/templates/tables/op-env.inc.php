<?php
echo "<h3>NOUVEL ENVIRONNEMENT</h3>";
echo "<table class=\"op-table\">
<tr>
    <th>NOM DU REPO :</th>
    <td>${name}</td>
</tr>";
if (OS_FAMILY == "Debian") {
    echo "<tr>
        <th>DISTRIBUTION :</th>
        <td>${dist}</td>
    </tr>
    <tr>
        <th>SECTION :</th>
        <td>${section}</td>
    </tr>";
}
echo '<tr>
    <th>ENVIRONNEMENT SOURCE :</th>
    <td><span>'.Common::envtag($env).'</span></td>
</tr>';
echo '<tr>
    <th>NOUVEL ENVIRONNEMENT :</th>
    <td><span>'.Common::envtag($targetEnv).'</span></td>
</tr>';
if (!empty($targetDescription)) {
    echo "<tr>
        <th>DESCRIPTION :</th>
        <td>${targetDescription}</td>
    </tr>";
}
echo '</table>';
?>