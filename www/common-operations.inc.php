<!--<h5>OPERATIONS</h5>-->
<?php
if ($OS_FAMILY == "Redhat") {
  echo '<h5>CRÉER UN NOUVEAU REPO</h5>';
  echo '<form action="/traitement.php" method="get" class="actionform" autocomplete="off">';
  echo '<input name="actionId" type="hidden" value="newRepo">';
  echo '<table class="actiontable">';
  echo '<tr>';
  echo '<td>Nom du repo source</td>';
  echo '<td>';
  echo '<select name="repoRealname" required>';
  $reposFiles = scandir($REPOMANAGER_YUM_DIR);
  foreach($reposFiles as $repoFileName) {
    if (($repoFileName != "..") AND ($repoFileName != ".") AND ($repoFileName != "repomanager.conf")) {
      // on retire le suffixe .repo du nom du fichier afin que ça soit plus propre dans la liste
      $repoFileNameFormated = str_replace(".repo", "", $repoFileName);
      echo "<option value=\"${repoFileNameFormated}\">${repoFileNameFormated}</option>";
    }
  }
  echo '</select>';
  echo '</td>';
  echo '</tr>';
  echo '<tr>';
  echo '<td>Nom personnalisé (fac.)</td>';
  echo '<td><input type="text" name="repoAlias" placeholder="Ne pas utiliser d\'underscore \'_\'"></td>';
  echo '</tr>';
  echo '<tr>';
  echo '<td>Description</td>';
  echo '<td><input type="text" name="repoDescription"></td>';
  echo '</tr>';
  echo '<tr>';
  echo '<td>GPG check</td>';
  echo '<td colspan="2">';
  echo '<input type="radio" id="newRepoGpgCheck_yes" name="newRepoGpgCheck" value="yes" checked="yes">';
  echo '<label for="newRepoGpgCheck_yes">Yes</label>';
  echo '<input type="radio" id="newRepoGpgCheck_no" name="newRepoGpgCheck" value="no">';
  echo '<label for="newRepoGpgCheck_no">No</label>';
  echo '</td>';
  echo '</tr>';
  echo '<tr>';
  echo '<td>Re-signer avec GPG</td>';
  echo '<td colspan="2">';
  if ($GPG_SIGN_PACKAGES == "yes") {
    echo '<input type="radio" id="repoGpgResign_yes" name="repoGpgResign" value="yes" checked="yes">';
    echo '<label for="repoGpgResign_yes">Yes</label>';
    echo '<input type="radio" id="repoGpgResign_no" name="repoGpgResign" value="no">';
    echo '<label for="repoGpgResign_no">No</label>';
  } else {
    echo '<input type="radio" id="repoGpgResign_yes" name="repoGpgResign" value="yes">';
    echo '<label for="repoGpgResign_yes">Yes</label>';
    echo '<input type="radio" id="repoGpgResign_no" name="repoGpgResign" value="no" checked="yes">';
    echo '<label for="repoGpgResign_no">No</label>';
  } 
  echo '</td>';
  echo '</tr>';
  echo '<tr>';
  echo '<td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>';
  echo '</tr>';
  echo '</table>';
  echo '</form>';
}
?>

<?php
if ($OS_FAMILY == "Debian") {
  echo '<h5>CRÉER UNE NOUVELLE SECTION</h5>';
  echo '<form action="/traitement.php" method="get" class="actionform" autocomplete="off">';
  echo '<input name="actionId" type="hidden" value="newRepo">';
  echo '<table class="actiontable">';
  echo '<tr>';
  echo '<td>Nom de l\'hôte source</td>';
  echo '<td>';
  echo '<select name="repoHostName" required>';
  $file_content = file_get_contents($HOSTS_CONF);
  $rows = explode("\n", $file_content);
  $j=0;
  foreach($rows as $data) {
    if(!empty($data) AND $data !== "[HOTES]") {
      $rowData = explode(',', $data);
      $hostName = str_replace(['Name=', '"'], '', $rowData[0]);
      $repoHost = str_replace(['Url=', '"'], '', $rowData[1]);
      echo "<option value=\"${hostName}\">${hostName}</option>";
    }
  };
  echo '</select>';
  echo '</td>';
  echo '</tr>';
  echo '<tr>';
  echo '<td>Nom personnalisé (fac.)</td>';
  echo '<td><input type="text" name="repoAlias" placeholder="Ne pas utiliser d\'underscore \'_\'"></td>';
  echo '</tr>';
  echo '<tr>';
  echo '<td>Distribution</td>';
  echo '<td><input type="text" name="repoDist" required></td>';
  echo '</tr>';
  echo '<tr>';
  echo '<td>Section</td>';
  echo '<td><input type="text" name="repoSection" required></td>';
  echo '</tr>';
  echo '<tr>';
  echo '<td>Description</td>';
  echo '<td><input type="text" name="repoDescription"></td>';
  echo '</tr>';
  echo '<tr>';
  echo '<td>GPG check</td>';
  echo '<td colspan="2">';
  echo '<input type="radio" id="newRepoGpgCheck_yes" name="newRepoGpgCheck" value="yes" checked="yes">';
  echo '<label for="newRepoGpgCheck_yes">Yes</label>';
  echo '<input type="radio" id="newRepoGpgCheck_no" name="newRepoGpgCheck" value="no">';
  echo '<label for="newRepoGpgCheck_no">No</label>';
  echo '</td>';
  echo '</tr>';
  echo '<tr>';
  echo '<td>Re-signer avec GPG</td>';
  echo '<td colspan="2">';
  if ($GPG_SIGN_PACKAGES == "yes") {
    echo '<input type="radio" id="repoGpgResign_yes" name="repoGpgResign" value="yes" checked="yes">';
    echo '<label for="repoGpgResign_yes">Yes</label>';
    echo '<input type="radio" id="repoGpgResign_no" name="repoGpgResign" value="no">';
    echo '<label for="repoGpgResign_no">No</label>';
  } else {
    echo '<input type="radio" id="repoGpgResign_yes" name="repoGpgResign" value="yes">';
    echo '<label for="repoGpgResign_yes">Yes</label>';
    echo '<input type="radio" id="repoGpgResign_no" name="repoGpgResign" value="no" checked="yes">';
    echo '<label for="repoGpgResign_no">No</label>';
  }
  echo '</td>';
  echo '</tr>';
  echo '<tr>';
  echo '<td colspan="2"><button type="submit" class="button-submit-large-red">Valider</button></td>';
  echo '</tr>';
  echo '</table>';
  echo '</form>';
}
?>