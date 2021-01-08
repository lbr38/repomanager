<h5>OPERATIONS</h5>
<?php
if ($OS_TYPE == "rpm") {
echo '
<a href="#" id="operationToggle6" class="button-operations-large"><img src="icons/bin.png" class="icon"/>Supprimer une version de repo archivée</a>
<br>
<div id="divOperation6" class="hide">
  <form action="/traitement.php" method="get" class="actionform" autocomplete="off">
    <input id="actionId" name="actionId" type="hidden" value="deleteOldRepo">
    <table class="actiontable">
      <tr>
        <td>Nom du repo</td>
        <td><input type="text" name="repoName" required></td>
      </tr>
      <tr>
        <td>Date</td>
        <td><input type="text" name="repoDate" required></td>
      </tr>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
</div> 
  
<a href="#" id="operationToggle7" class="button-operations-large"><img src="icons/arrow-up.png" class="icon"/>Remettre en production une version de repo archivée</a>
<br>
<div id="divOperation7" class="hide">
  <form action="/traitement.php" method="get" class="actionform" autocomplete="off">
    <input id="actionId" name="actionId" type="hidden" value="restoreOldRepo">
    <table class="actiontable">
      <tr>
        <td>Nom du repo</td>
        <td><input type="text" name="repoName" required></td>
      </tr>
      <tr>
        <td>Date</td>
        <td><input type="text" name="repoDate" required></td>
      </tr>
      <tr>
        <td>Description</td>
        <td><input type="text" name="repoDescription"></td>
      </tr>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
</div>'; }

if ($OS_TYPE == "deb") {
echo '
<a href="#" id="operationToggle8" class="button-operations-large"><img src="icons/bin.png" class="icon"/>Supprimer une version de section archivée</a>
<br>
<div id="divOperation8" class="hide">
  <form action="/traitement.php" method="get" class="actionform" autocomplete="off">
    <input id="actionId" name="actionId" type="hidden" value="deleteOldRepo">
    <table class="actiontable">
      <tr>
        <td>Nom du repo</td>
        <td><input type="text" name="repoName" required></td>
      </tr>
      <tr>
        <td>Distribution</td>
        <td><input type="text" name="repoDist" required></td>
      </tr>
      <tr>
        <td>Section</td>
        <td><input type="text" name="repoSection" required></td>
      </tr>
      <tr>
        <td>Date</td>
        <td><input type="text" name="repoDate" required></td>
      </tr>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
</div> 

<a href="#" id="operationToggle9" class="button-operations-large"><img src="icons/arrow-up.png" class="icon"/>Remettre en production une version de section archivée</a>
<br>
<div id="divOperation9" class="hide">
  <form action="/traitement.php" method="get" class="actionform" autocomplete="off">
    <input id="actionId" name="actionId" type="hidden" value="restoreOldRepo">
    <table class="actiontable">
      <tr>
        <td>Nom du repo</td>
        <td><input type="text" name="repoName" required></td>
      </tr>
      <tr>
        <td>Distribution</td>
        <td><input type="text" name="repoDist" required></td>
      </tr>
      <tr>
        <td>Section</td>
        <td><input type="text" name="repoSection" required></td>
      </tr>
      <tr>
        <td>Date</td>
        <td><input type="text" name="repoDate" required></td>
      </tr>
      <tr>
        <td>Description</td>
        <td><input type="text" name="repoDescription"></td>
      </tr>
      <tr>
        <td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>
      </tr>
    </table>
  </form>
</div>';
}

// scripts jQuery pour faire dérouler le formulaire de chaque opération
echo "<script>";
echo "$(document).ready(function(){";
echo "$(\"a#operationToggle1\").click(function(){";
echo "$(\"div#divOperation1\").slideToggle(250);";
echo "$(this).toggleClass(\"open\");";
echo "});";
echo "});";

echo "$(document).ready(function(){";
echo "$(\"a#operationToggle2\").click(function(){";
echo "$(\"div#divOperation2\").slideToggle(250);";
echo "$(this).toggleClass(\"open\");";
echo "});";
echo "});";

echo "$(document).ready(function(){";
echo "$(\"a#operationToggle3\").click(function(){";
echo "$(\"div#divOperation3\").slideToggle(250);";
echo "$(this).toggleClass(\"open\");";
echo "});";
echo "});";

echo "$(document).ready(function(){";
echo "$(\"a#operationToggle4\").click(function(){";
echo "$(\"div#divOperation4\").slideToggle(250);";
echo "$(this).toggleClass(\"open\");";
echo "});";
echo "});";

echo "$(document).ready(function(){";
echo "$(\"a#operationToggle5\").click(function(){";
echo "$(\"div#divOperation5\").slideToggle(250);";
echo "$(this).toggleClass(\"open\");";
echo "});";
echo "});";

echo "$(document).ready(function(){";
echo "$(\"a#operationToggle6\").click(function(){";
echo "$(\"div#divOperation6\").slideToggle(250);";
echo "$(this).toggleClass(\"open\");";
echo "});";
echo "});";

echo "$(document).ready(function(){";
echo "$(\"a#operationToggle7\").click(function(){";
echo "$(\"div#divOperation7\").slideToggle(250);";
echo "$(this).toggleClass(\"open\");";
echo "});";
echo "});";

if ($OS_TYPE == "deb") { // scripts supplémentaires pour debian uniquement
  echo "$(document).ready(function(){";
  echo "$(\"a#operationToggle8\").click(function(){";
  echo "$(\"div#divOperation8\").slideToggle(250);";
  echo "$(this).toggleClass(\"open\");";
  echo "});";
  echo "});";

  echo "$(document).ready(function(){";
  echo "$(\"a#operationToggle9\").click(function(){";
  echo "$(\"div#divOperation9\").slideToggle(250);";
  echo "$(this).toggleClass(\"open\");";
  echo "});";
  echo "});";
}
echo "</script>";

?>