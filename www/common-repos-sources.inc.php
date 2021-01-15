<!--<div id="divManageReposSources" class="divManageReposSources">-->
<div class="divManageReposSources">
<a href="#" id="ReposSourcesSlideDownButton" title="Fermer"><img class="icon-lowopacity" src="icons/close.png" /></a>

  <?php 
    if ($OS_TYPE == "rpm") { echo "<h5>REPOS SOURCES</h5>"; }
    if ($OS_TYPE == "deb") { echo "<h5>HOTES D'ORIGINES</h5>"; }
  ?>
  <div class="div-half-left">
  <?php
  if ($OS_TYPE == "rpm") {
    echo "<p>Pour créer un miroir, repomanager doit connaitre l'URL de l'hôte à aspirer.<br>Renseigner ici l'URL en lui donnant un nom unique. Ce nom correspondra au \"Nom du repo\" dans les opérations.</p>";
    $reposFiles = scandir($REPOMANAGER_YUM_DIR);
    $i=0;
    foreach($reposFiles as $repoFileName) {
      if (($repoFileName != "..") AND ($repoFileName != ".") AND ($repoFileName != "repomanager.conf")) { // on ignore le fichier principal repomanager.conf (qui est dans /etc/yum.repos.d/00_repomanager/)
        // on retire le suffixe .repo du nom du fichier afin que ça soit plus propre dans la liste
        $repoFileNameFormated = str_replace(".repo", "", $repoFileName);
        // on récupère le contenu du fichier
        $content = file_get_contents("${REPOMANAGER_YUM_DIR}/${repoFileName}", true);
        echo "<p>";
        echo "<a href=\"?action=deleteRepoFile&repoFileName=${repoFileName}\"><img src=\"icons/bin.png\" class=\"icon-lowopacity\"/></a>";
        echo "<b><a href=\"#\" id=\"reposSourcesToggle${i}\">${repoFileNameFormated}</a></b>";
        echo "</p>";
        echo "<div id=\"divReposSources${i}\" class=\"divReposSources\">";
        echo "<textarea>";
        echo "${content}";
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
    echo "<br>";
    echo "<form action=\"\" method=\"post\" autocomplete=\"off\">";
    echo "<p><b>Ajouter un nouveau fichier de conf :</b></p>";
    echo "<table class=\"table-auto\">";
    echo "<tr>";
    echo "<td><b>1.</b></td>";
    echo "<td colspan=\"2\">Nom du repo :</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td></td>";
    echo "<td colspan=\"2\"><input type=\"text\" name=\"newRepoName\" id=\"newRepoNameInput\" required></td>";
    echo "<td class=\"td-hide\" id=\"newRepoNameHiddenTd\"></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td><b>2.</b></td>";
    echo "<td>Baseurl :</td>";
    echo "<td>ou Mirrorlist :</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td></td>";
    echo "<td><input type=\"text\" name=\"newRepoBaseUrl\"></td>";
    echo "<td><input type=\"text\" name=\"newRepoMirrorList\"></td>";
    echo "</tr>";
    echo "<tr><td><br></td></tr>";
    echo "<tr>";
    echo "<td><b>3.</b></td>";
    echo "<td>Ce repo distant dispose d'une clé GPG</td>";
    echo "<td>";
    echo "<select id=\"newRepoSourceSelect\">";
    echo "<option id=\"newRepoSourceSelect_yes\">Oui</option>";
    echo "<option id=\"newRepoSourceSelect_no\">Non</option>";
    echo "</select>";
    echo "</td>";
    echo "</tr>";

    echo "<tr class=\"tr-hide\">";
    echo "<td colspan=\"100%\">Renseignez l'URL vers la clé GPG, ou bien la clé GPG au format texte (elle sera importée dans le trousseau de repomanager)</td>";
    echo "</tr>";
    echo "<tr class=\"tr-hide\">";
    echo "<td></td>";
    echo "<td>GPG URL</td>";
    echo "<td>GPG texte</td>";
    echo "</tr>";
    echo "<tr class=\"tr-hide\">";
    echo "<td></td>";
    echo "<td><input type=\"text\" name=\"newRepoGpgKeyURL\"></td>";
    echo "<td><textarea name=\"newRepoGpgKeyText\"></textarea></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-medium-blue\">Ajouter</button></td>";
    echo "</tr>";
    echo "</table>";
    echo "</form>";
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
        echo "<td class=\"td-auto\"><a href=\"?action=deleteHost&repoName=${repoName}\"><img src=\"icons/bin.png\" class=\"icon-lowopacity\"/></a></td>";
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
    <table class="table-auto">
    <?php
      if ($OS_TYPE == "rpm") { // dans le cas de rpm, les clés gpg sont importées dans $RPM_GPG_DIR (en principe par défaut /etc/pki/rpm-gpg/repomanager)
        $gpgFiles = scandir($RPM_GPG_DIR);
        foreach($gpgFiles as $gpgFile) {
          if (($gpgFile != "..") AND ($gpgFile != ".")) {
            echo "<tr>";
            echo "<td>";
            echo "<a href=\"?action=deleteGpgKey&gpgKeyFile=${gpgFile}\">";
            echo "<img src=\"icons/bin.png\" class=\"icon-lowopacity\"/>";
            echo "</a>";
            echo "</td>";
            echo "<td>";
            echo "${gpgFile}";
            echo "</td>";
            echo "</tr>";
          }
        }
      }

      if ($OS_TYPE == "deb") {
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
            echo "<img src=\"icons/bin.png\" class=\"icon-lowopacity\"/>";
            echo "</a>";
            echo "</td>";
            echo "<td>";
            echo "$gpgKeyName ($gpgKeyID)";
            echo "</td>";
            echo "</tr>";
          }
        }
      }
    ?>
    </table>
  </div>
</div>

<script> 
// Afficher ou masquer la div permettant de gérer les repos/hôtes sources (div s'affichant en bas de la page)
$(document).ready(function(){
    // Le bouton up permet d'afficher la div et également de la fermer si on reclique dessus
    $('#ReposSourcesSlideUpButton').click(function() {
        $('div.divManageReposSources').slideToggle(150);
    });

    // Le bouton down (petite croix) permet la même chose, il sera surtout utilisé pour fermer la div
    $('#ReposSourcesSlideDownButton').click(function() {
      $('div.divManageReposSources').slideToggle(150);
    });
});


// rpm : afficher ou masquer les inputs permettant de renseigner une clé gpg à importer, en fonction de la valeur du select
$(function() {
  $("#newRepoSourceSelect").change(function() {
    if ($("#newRepoSourceSelect_yes").is(":selected")) {
      $(".tr-hide").show();
    } else {
      $(".tr-hide").hide();
    }
  }).trigger('change');
});

// rpm : affiche une td avec le nom final du repo entre crochets [] tel qu'il sera inséré dans son fichier
$("#newRepoNameInput").on("input", function(){
  $(".td-hide").show(); // D'abord on affiche la td cachée
  var content = $('#newRepoNameInput').val(); // on récupère le contenu du input #newRepoNameInput
  $("#newRepoNameHiddenTd").text(content + ".repo"); // on affiche le contenu à l'intérieur de la td, concaténé de '.repo' afin d'afficher le nom du fichier complet
});
</script>