<div id="divManageReposSources" class="divManageReposSources">
  <?php 
    if ($OS_TYPE == "rpm") { echo "<h5>REPOS SOURCES</h5>"; }
    if ($OS_TYPE == "deb") { echo "<h5>HOTES D'ORIGINES</h5>"; }
  ?>
  <div class="div-half-left">
  <?php
  if ($OS_TYPE == "rpm") {
    echo "<p>Pour créer un miroir, reposync a besoin de connaitre l'URL de l'hôte à aspirer. Renseigner l'URL ici en lui donnant un nom unique. Ce nom correspondra au \"Nom du repo\" dans les opérations.</p>";
    $reposFiles = scandir($REPOMANAGER_YUM_DIR);
    $i=0;
    foreach($reposFiles as $repoFileName) {
      if (($repoFileName != "..") AND ($repoFileName != ".") AND ($repoFileName != "repomanager.conf")) { // on ignore le fichier principal repomanager.conf (qui est dans /etc/yum.repos.d/00_repomanager/)
        echo "<p>";
        echo "<a href=\"?action=deleteRepoFile&repoFileName=${repoFileName}\"><img src=\"images/trash.png\"/></a>";
        echo "<b><a href=\"#\" id=\"reposSourcesToggle${i}\">${repoFileName}</a></b>";
        echo "</p>";
        echo "<div id=\"divReposSources${i}\" class=\"divReposSources\">";
        $contenu = file_get_contents("${REPOMANAGER_YUM_DIR}/${repoFileName}", true);
        echo "<textarea>";
        echo "${contenu}";
        echo "</textarea>";
        echo "</div>";

        // Afficher ou masquer la div qui affiche la conf de chaque repo source :
        echo "<script>";
        echo "$(document).ready(function(){";
        echo "$(\"a#reposSourcesToggle${i}\").click(function(){";
        echo "$(\"div#divReposSources${i}\").slideToggle(250);";
        echo "$(this).toggleClass(\"open\");";
        echo "});";
        echo "});";
        echo "</script>";
        $i++;
      }
    }

    # Formulaire d'ajout d'un nouveau repo source rpm
    echo "<form action=\"\" method=\"post\">";
    echo "<p><b>Ajouter un nouveau fichier de conf :</b></p>";
    echo "<p>Nom du repo :</p>";
    echo "<input type=\"text\" name=\"newRepoName\" autocomplete=\"off\">";
    echo "<p>   </p>";
    echo "<p>Contenu :</p>";
    echo "<textarea name=\"newRepoFileConf\" placeholder=\"Insérez tout le contenu du fichier de conf ici\"></textarea>";
    echo "<p>Clé GPG (optionnel) :</p>";
    echo "<input type=\"text\" name=\"newRepoFileGpgKey\" placeholder=\"Clé de signature GPG du repo source\" autocomplete=\"off\">";
    echo "<button type=\"submit\" class=\"button-submit-medium-blue\">Ajouter</button>";
    echo "</form>";

    /* ancien 
    echo "<form action=\"\" method=\"post\">";
    echo "<p><b>Ajouter un nouveau fichier de conf :</b></p>";
    echo "<p>Nom du fichier :</p>";
    echo "<input type=\"text\" name=\"newRepoFile\" placeholder=\"xxxx.repo\" autocomplete=\"off\">";
    echo "<p>Contenu :</p>";
    echo "<textarea name=\"newRepoFileConf\" placeholder=\"Insérez tout le contenu du fichier de conf ici\"></textarea>";
    echo "<p>Clé GPG (optionnel) :</p>";
    echo "<input type=\"text\" name=\"newRepoFileGpgKey\" placeholder=\"Clé de signature GPG du repo source\" autocomplete=\"off\">";
    echo "<button type=\"submit\" class=\"button-submit-medium-blue\">Ajouter</button>";
    echo "</form>"; */
  }

  if ($OS_TYPE == "deb") {
    echo "<p>Pour créer un miroir, debmirror a besoin de connaitre l'URL de l'hôte à aspirer. Renseignez l'URL ici en lui donnant un nom unique. <br>Ce nom correspondra au \"Nom du repo\" dans les opérations.</p>";
    echo "<table class=\"table-auto\">";
    echo "<thead>";
    echo "<tr>";
    echo "<td></td>";
    echo "<td class=\"td-auto\">Nom</td>";
    echo "<td class=\"td-auto\">URL hôte</td>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    $file_content = file_get_contents($REPO_ORIGIN_FILE);
    $rows = explode("\n", $file_content);

    foreach($rows as $data) {
      if(!empty($data) AND $data !== "[HOTES]") {
        //get row data
        $rowData = explode(',', $data);
        $repoName = str_replace(['Name=', '"'], '', $rowData[0]);
        $repoHost = str_replace(['Url=', '"'], '', $rowData[1]);
        echo "<tr>";
        echo "<td class=\"td-auto\"><a href=\"?action=deleteHost&repoName=${repoName}\"><img src=\"images/trash.png\" /></a></td>";
        echo "<td class=\"td-auto\">${repoName}</td>";
        echo "<td class=\"td-auto\">${repoHost}</td>";
        echo "</tr>";
      }
    };
    echo "</table>";
    echo "<br><br>";

    echo "<table class=\"table-auto\">";
    echo "<tr>";
    echo "<td class=\"td-auto\"><b>Ajouter une nouvelle url hôte :</b></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class=\"td-auto\">Donner un nom à l'hôte :</td>";
    echo "<td class=\"td-auto\">Adresse URL de l'hôte :</td>";
    echo "<td class=\"td-auto\">Clé GPG (optionnel) :</td>";
    echo "</tr>";
    echo "<form action=\"\" method=\"post\" class=\"actionform\">";
    echo "<tr>";
    echo "<td class=\"td-auto\"><input type=\"text\" name=\"newHostName\" autocomplete=\"off\"></td>";
    echo "<td class=\"td-auto\"><input type=\"text\" name=\"newHostUrl\" autocomplete=\"off\"></td>";
    echo "<td class=\"td-auto\"><textarea name=\"newHostGpgKey\" autocomplete=\"off\"></textarea></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class=\"td-auto\"><button type=\"submit\" class=\"button-submit-medium-blue\">Ajouter</button></td>";
    echo "</tr>";
    echo "</form>";
    echo "</tbody>";
    echo "</table>";
  }?>
  </div>
  <div class="div-half-right">
    <p>Liste des clés GPG du trousseau de repomanager</p>
    <table class="table-large">
    <?php 
        $gpgKeysList = shell_exec("gpg --no-default-keyring --keyring ${GPGHOME}/trustedkeys.gpg --list-key --fixed-list-mode --with-colons | sed 's/^pub/\\npub/g'");
        $gpgKeysList = explode(PHP_EOL.PHP_EOL, $gpgKeysList);
        foreach ($gpgKeysList as $gpgKey) {
          $gpgKeyID = shell_exec("echo \"$gpgKey\" | sed -n -e '/pub/,/uid/p' | grep '^fpr:' | awk -F':' '{print $10}'"); // on récup uniquement l'ID de la clé GPG
          $gpgKeyID = preg_replace('/\s+/', '', $gpgKeyID); // retire tous les espaces blancs
          $gpgKeyName = shell_exec("echo \"$gpgKey\" | sed -n -e '/pub/,/uid/p' | grep '^uid:' | awk -F':' '{print $10}'");
          if (!empty($gpgKeyID) AND !empty($gpgKeyName)) {
            echo "<tr>";
            echo "<td>";
            echo "<a href=\"?action=deleteGpgKey&gpgKeyID=${gpgKeyID}\">";
            echo "<img src=\"images/trash.png\"/>";
            echo "</a>";
            echo "</td>";
            echo "<td>";
            echo "$gpgKeyName ($gpgKeyID)";
            echo "</td>";
            echo "</tr>";
          }
        }      
    ?>
    </table>
  </div>
</div>

<script> // Afficher ou masquer la div permettant de gérer les repos/hôtes sources (div s'affichant en bas de la page)
$(document).ready(function(){
  $("a#reposSourcesToggle").click(function(){
    $("div#divManageReposSources").slideToggle(150);
    $(this).toggleClass("open");
  });
});
</script>