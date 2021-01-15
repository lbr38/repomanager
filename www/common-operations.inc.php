<!--<h5>OPERATIONS</h5>-->
<?php
if ($OS_TYPE == "rpm") {
echo '<h5>CRÉER UN NOUVEAU REPO</h5>';
echo '
<form action="/traitement.php" method="get" class="actionform" autocomplete="off">
    <input name="actionId" type="hidden" value="newRepo">
    <table class="actiontable">
      <tr>
        <td>Nom du repo</td>
        <td><input type="text" name="repoRealname" placeholder="Nom entre [crochets] dans le fichier de conf" required></td>
      </tr>
      <tr>
        <td>Nom personnalisé</td>
        <td><input type="text" name="repoAlias" placeholder="Ne pas utiliser d\'underscore \'_\'"></td>
      </tr>
      <tr>
        <td>Description</td>
        <td><input type="text" name="repoDescription"></td>
      </tr>
      <tr>
        <td>GPG check</td>
        <td colspan="2">
          <input type="radio" id="newRepoGpgCheck_yes" name="newRepoGpgCheck" value="yes" checked="yes">
          <label for="newRepoGpgCheck_yes">Yes</label>
          <input type="radio" id="newRepoGpgCheck_no" name="newRepoGpgCheck" value="no">
          <label for="newRepoGpgCheck_no">No</label>
        </td>
      </tr>
      <tr>
        <td>Re-signer avec GPG</td>
        <td colspan="2">';
          if ( $GPG_SIGN_PACKAGES == "yes" ) {
            echo "<input type=\"radio\" id=\"repoGpgResign_yes\" name=\"repoGpgResign\" value=\"yes\" checked=\"yes\">";
            echo "<label for=\"repoGpgResign_yes\">Yes</label>";
            echo "<input type=\"radio\" id=\"repoGpgResign_no\" name=\"repoGpgResign\" value=\"no\">";
            echo "<label for=\"repoGpgResign_no\">No</label>";
          } else {
            echo "<input type=\"radio\" id=\"repoGpgResign_yes\" name=\"repoGpgResign\" value=\"yes\">";
            echo "<label for=\"repoGpgResign_yes\">Yes</label>";
            echo "<input type=\"radio\" id=\"repoGpgResign_no\" name=\"repoGpgResign\" value=\"no\" checked=\"yes\">";
            echo "<label for=\"repoGpgResign_no\">No</label>";
          } 
echo '</td>
      </tr>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>';
}
?>

<?php
if ($OS_TYPE == "deb") {
echo "<h5>CRÉER UNE NOUVELLE SECTION</h5>";
echo "<form action=\"/traitement.php\" method=\"get\" class=\"actionform\" autocomplete=\"off\">";
echo "<input name=\"actionId\" type=\"hidden\" value=\"newRepo\">";
echo "<table class=\"actiontable\">";
echo "<tr>";
echo "<td>Nom de l'hôte</td>";
echo "<td><input type=\"text\" name=\"repoHostName\" required></td>";
echo "</tr>";
echo "<tr>";
echo "<td>Nom personnalisé (fac.)</td>";
echo "<td><input type=\"text\" name=\"repoAlias\" placeholder=\"Ne pas utiliser d'underscore '_'\"></td>";
echo "</tr>";
echo "<tr>";
echo "<td>Distribution</td>";
echo "<td><input type=\"text\" name=\"repoDist\" required></td>";
echo "</tr>";
echo "<tr>";
echo "<td>Section</td>";
echo "<td><input type=\"text\" name=\"repoSection\" required></td>";
echo "</tr>";
echo "<tr>";
echo "<td>Description</td>";
echo "<td><input type=\"text\" name=\"repoDescription\"></td>";
echo "</tr>";
echo "<tr>";
echo "<td>GPG check</td>";
echo "<td colspan=\"2\">";
echo "<input type=\"radio\" id=\"newRepoGpgCheck_yes\" name=\"newRepoGpgCheck\" value=\"yes\" checked=\"yes\">";
echo "<label for=\"newRepoGpgCheck_yes\">Yes</label>";
echo "<input type=\"radio\" id=\"newRepoGpgCheck_no\" name=\"newRepoGpgCheck\" value=\"no\">";
echo "<label for=\"newRepoGpgCheck_no\">No</label>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan=\"2\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
echo "</tr>";
echo "</table>";
echo "</form>"; }
?>