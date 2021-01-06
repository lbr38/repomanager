<!-- retirer le input hidden qui transmet ostype (ne sert plus à rien on utilise $OS_TYPE directement) -->
<h5>OPERATIONS</h5>
<?php
if ($OS_TYPE == "rpm") {
echo '
<a href="#" id="operationToggle1" class="button-operations-large"><img src="icons/plus.png" class="icon"/>Créer un nouveau repo</a>
<br>
<div id="divOperation1" class="hide">
  <form action="/traitement.php" method="post" class="actionform">
    <input name="actionId" type="hidden" value="newRepo">
    <table class="actiontable">
      <tr>
        <td>Nom du repo</td>
        <td><input type="text" name="repoRealname" autocomplete="off" placeholder="Nom entre [crochets] dans le fichier de conf" required></td>
      </tr>
      <tr>
        <td>Nom personnalisé</td>
        <td><input type="text" name="repoAlias" autocomplete="off" placeholder="Ne pas utiliser d\'underscore \'_\'"></td>
      </tr>
      <tr>
        <td>Description</td>
        <td><input type="text" name="repoDescription" autocomplete="off"></td>
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
          } echo '
        </td>
      </tr>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
</div>       

<a href="#" id="operationToggle2" class="button-operations-large"><img src="icons/refresh.png" class="icon"/>Mettre à jour un repo existant</a>
<br>
<div id="divOperation2" class="hide">
  <form action="/traitement.php" method="post" class="actionform">
    <input name="actionId" type="hidden" value="updateRepo">
    <table class="actiontable">
      <tr>
        <td>Nom du repo</td>
        <td><input type="text" name="repoName" autocomplete="off" placeholder="" required></td>
      </tr>
      <tr>
        <td>GPG check</td>
        <td colspan="2">
          <input type="radio" id="updateRepoGpgCheck_yes" name="updateRepoGpgCheck" value="yes" checked="yes">
          <label for="updateRepoGpgCheck_yes">Yes</label>
          <input type="radio" id="updateRepoGpgCheck_no" name="updateRepoGpgCheck" value="no">
          <label for="updateRepoGpgCheck_no">No</label>
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
          } echo '
        </td>
      </tr>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
  </form>
</div>

<a href="#" id="operationToggle3" class="button-operations-large"><img src="icons/link.png" class="icon"/>Changer l\'env d\'un repo</a>
<br>
<div id="divOperation3" class="hide">
  <form action="/traitement.php" method="post" class="actionform">
    <input name="actionId" type="hidden" value="changeEnv">
    <table class="actiontable">
      <tr>
        <td>Nom du repo</td>
        <td><input type="text" name="repoName" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Env actuel</td>
        <td><input type="text" name="repoEnv" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Nouvel env</td>
        <td><input type="text" name="repoNewEnv" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Description</td>
        <td><input type="text" name="repoDescription" autocomplete="off"></td>
      </tr>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
</div>

<a href="#" id="operationToggle4" class="button-operations-large"><img src="icons/duplicate.png" class="icon"/>Dupliquer un repo</a>
<br>
<div id="divOperation4" class="hide">
  <form action="/traitement.php" method="post" class="actionform">
    <input name="actionId" type="hidden" value="duplicateRepo">
    <table class="actiontable">
      <tr>
        <td>Nom du repo à dupliquer</td>
        <td><input type="text" name="repoName" autocomplete="off" required></td>
      </tr>
      <tr>
          <td>Env du repo</td>
          <td><input type="text" name="repoEnv" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Nouveau nom du repo</td>
        <td><input type="text" name="repoNewName" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Description</td>
        <td><input type="text" name="repoDescription" autocomplete="off"></td>
      </tr>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
</div>

<a href="#" id="operationToggle5" class="button-operations-large"><img src="icons/bin.png" class="icon"/>Supprimer un repo</a>
<br>
<div id="divOperation5" class="hide">
  <form action="/traitement.php" method="get" class="actionform" autocomplete="off">
    <input name="actionId" type="hidden" value="deleteRepo">
    <table class="actiontable">
      <tr>
        <td>Nom du repo</td>
        <td><input type="text" name="repoName" required></td>
      </tr>
      <tr>
        <td>Env</td>
        <td><input type="text" name="repoEnv" required></td>
      </tr>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
</div>';
}
?>


<?php
if ($OS_TYPE == "deb") {
echo '
<a href="#" id="operationToggle1" class="button-operations-large"><img src="icons/plus.png" class="icon"/>Créer une nouvelle section</a>
<br>
<div id="divOperation1" class="hide">
  <form action="/traitement.php" method="post" class="actionform">
    <input name="actionId" type="hidden" value="newRepo">
    <table class="actiontable">
      <tr>
        <td>Nom de l\'hôte</td>
        <td><input type="text" name="repoHostName" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Nom personnalisé (fac.)</td>
        <td><input type="text" name="repoAlias" autocomplete="off" placeholder="Ne pas utiliser d\'underscore \'_\'"></td>
      </tr>
      <tr>
        <td>Distribution</td>
        <td><input type="text" name="repoDist" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Section</td>
        <td><input type="text" name="repoSection" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Description</td>
        <td><input type="text" name="repoDescription" autocomplete="off"></td>
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
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
</div>

<a href="#" id="operationToggle2" class="button-operations-large"><img src="icons/refresh.png" class="icon"/>Mettre à jour une section existante</a>
<br>
<div id="divOperation2" class="hide">
  <form action="/traitement.php" method="post" class="actionform">
    <input name="actionId" type="hidden" value="updateRepo">
    <table class="actiontable">
      <tr>
        <td>Nom du repo</td>
        <td><input type="text" name="repoName" autocomplete="off" placeholder="" required></td>
      </tr>
      <tr>
        <td>Distribution</td>
        <td><input type="text" name="repoDist" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Section</td>
        <td><input type="text" name="repoSection" autocomplete="off" required></td>
      </tr>
      <td>GPG check</td>
        <td colspan="2">
          <input type="radio" id="updateRepoGpgCheck_yes" name="updateRepoGpgCheck" value="yes" checked="yes">
          <label for="updateRepoGpgCheck_yes">Yes</label>
          <input type="radio" id="updateRepoGpgCheck_no" name="updateRepoGpgCheck" value="no">
          <label for="updateRepoGpgCheck_no">No</label>
        </td>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
</div>

<a href="#" id="operationToggle3" class="button-operations-large"><img src="icons/link.png" class="icon"/>Changer l\'env d\'une section</a>
<br>
<div id="divOperation3" class="hide">
  <form action="/traitement.php" method="post" class="actionform">
    <input name="actionId" type="hidden" value="changeEnv">
    <table class="actiontable">
      <tr>
        <td>Nom du repo</td>
        <td><input type="text" name="repoName" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Distribution</td>
        <td><input type="text" name="repoDist" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Section</td>
        <td><input type="text" name="repoSection" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Env actuel</td>
        <td><input type="text" name="repoEnv" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Nouvel env</td>
        <td><input type="text" name="repoNewEnv" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Description</td>
        <td><input type="text" name="repoDescription" autocomplete="off"></td>
      </tr>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
</div>

<a href="#" id="operationToggle4" class="button-operations-large"><img src="icons/duplicate.png" class="icon"/>Dupliquer une section</a>
<br>
<div id="divOperation4" class="hide">
  <form action="/traitement.php" method="post" class="actionform">
    <input name="actionId" type="hidden" value="duplicateRepo">
    <table class="actiontable">
      <tr>
        <td>Nom du repo</td>
        <td><input type="text" name="repoName" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Nom de la distribution</td>
        <td><input type="text" name="repoDist" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Nom de la section</td>
        <td><input type="text" name="repoSection" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Env de la section</td>
        <td><input type="text" name="repoEnv" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Nouveau nom du repo</td>
        <td><input type="text" name="repoNewName" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Description</td>
        <td><input type="text" name="repoDescription" autocomplete="off"></td>
      </tr>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
</div>

<a href="#" id="operationToggle5" class="button-operations-large"><img src="icons/bin.png" class="icon"/>Supprimer une section</a>
<br>
<div id="divOperation5" class="hide">
  <form action="/traitement.php" method="post" class="actionform">
    <input name="actionId" type="hidden" value="deleteSection">
    <table class="actiontable">
      <tr>
        <td>Nom du repo</td>
        <td><input type="text" name="repoName" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Nom de la distribution</td>
        <td><input type="text" name="repoDist" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Nom de la section</td>
        <td><input type="text" name="repoSection" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Env du repo</td>
        <td><input type="text" name="repoEnv" autocomplete="off" required></td>
      </tr>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
</div>

<a href="#" id="operationToggle6" class="button-operations-large"><img src="icons/bin.png" class="icon"/>Supprimer une distribution</a>
<br>
<div id="divOperation6" class="hide">
  <form action="/traitement.php" method="post" class="actionform">
    <input name="actionId" type="hidden" value="deleteDist">
    <table class="actiontable">
      <tr>
        <td>Nom du repo</td>
        <td><input type="text" name="repoName" autocomplete="off" required></td>
      </tr>
      <tr>
        <td>Nom de la distribution</td>
        <td><input type="text" name="repoDist" autocomplete="off" required></td>
      </tr>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
</div>

<a href="#" id="operationToggle7" class="button-operations-large"><img src="icons/bin.png" class="icon"/>Supprimer un repo</a>
<br>
<div id="divOperation7" class="hide">
  <form action="/traitement.php" method="post" class="actionform">
    <input name="actionId" type="hidden" value="deleteRepo">
    <table class="actiontable">
      <tr>
        <td>Nom du repo</td>
        <td><input type="text" name="repoName" autocomplete="off" required></td>
      </tr>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
</div>'; }
?>