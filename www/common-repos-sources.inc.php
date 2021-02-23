<div class="divManageReposSources">
<img id="ReposSourcesCloseButton" title="Fermer" class="icon-lowopacity" src="icons/close.png" />
  <?php 
    if ($OS_FAMILY == "Redhat") { echo "<h5>REPOS SOURCES</h5>"; }
    if ($OS_FAMILY == "Debian") { echo "<h5>HOTES SOURCES</h5>"; }
  ?>
  
  <?php
  $i=0;
  // Cas Redhat
  if ($OS_FAMILY == "Redhat") {
    echo '<p>Pour créer un miroir, repomanager doit connaitre l\'URL du repo source.</p>';
    echo '<hr>';
    echo '<div class="div-68 is-inline-block">';
    // Formulaire d'ajout d'un nouveau repo source rpm
    echo "<form action=\"${actual_uri}\" method=\"post\" autocomplete=\"off\">";
    echo '<p><b>Ajouter un nouveau repo source :</b></p>';
    echo '<table class="table-large">';
    echo '<tr>';
    echo '<td><b>1.</b></td>';
    echo '<td colspan="100%">Nom du repo :</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td></td>';
    echo '<td><input type="text" class="input-medium" name="newRepoName" id="newRepoNameInput" required></td>';
    echo '<td class="td-hide" id="newRepoNameHiddenTd"></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td><b>2.</b></td>';
    echo '<td colspan="100%">Type d\'URL :</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td></td>';
    echo '<td colspan="100%">';
    echo '<select name="newRepoUrlType" class="select-medium">';
    echo '<option value="baseurl">baseurl</option>';
    echo '<option value="mirrorlist">mirrorlist</option>';
    echo '<option value="metalink">metalink</option>';
    echo '</select> ';
    echo '<input type="text" name="newRepoUrl" class="input-large">';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td><b>3.</b></td>';
    echo '<td colspan="100%">Ce repo distant dispose d\'une clé GPG ';
    echo '<select id="newRepoSourceSelect" class="select-small">';
    echo '<option id="newRepoSourceSelect_yes">Oui</option>';
    echo '<option id="newRepoSourceSelect_no">Non</option>';
    echo '</select>';
    echo '</td>';
    echo '</tr>';
    echo '<tr class="tr-hide">';
    echo '<td></td>';
    echo '<td colspan="100%">Vous pouvez utilisez une clé déjà présente dans le trousseau de repomanager ou renseignez l\'URL vers la clé GPG ou bien importer une nouvelle clé GPG au format texte dans le trousseau de repomanager.</td>';
    echo '</tr>';
    echo '<tr class="tr-hide">';
    echo '<td></td>';
    echo '<td class="td-fit">Clé GPG du trousseau de repomanager :</td>';
    echo '<td>';
    echo '<select name="existingRepoGpgKey">';
    echo '<option value="">Choisir une clé GPG...</option>';
    $gpgFiles = scandir($RPM_GPG_DIR);
    foreach($gpgFiles as $gpgFile) {
      if (($gpgFile != "..") AND ($gpgFile != ".")) {
        echo "<option value=\"${gpgFile}\">${gpgFile}</option>";
      }
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';
    echo '<tr class="tr-hide">';
    echo '<td></td>';
    echo '<td class="td-fit">URL vers une clé GPG :</td>';
    echo '<td><input type="text" name="newRepoGpgKeyURL" placeholder="https://"></td>';
    echo '</tr>';
    echo '<tr class="tr-hide">';
    echo '<td></td>';
    echo '<td class="td-fit">Importer une nouvelle clé GPG (format ASCII) :</td>';
    echo '<td><textarea name="newRepoGpgKeyText"></textarea></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td></td>';
    echo '<td colspan="100%"><button type="submit" class="button-submit-medium-blue">Ajouter</button></td>';
    echo '</tr>';
    echo '</table>';
    echo '</form>';
    echo '<br>';

    // Affichage des repos sources actuels
    $reposFiles = scandir($REPOMANAGER_YUM_DIR);
    echo '<p><b>Repos sources actuels :</b></p>';
    echo '<div class="reposSourcesDivContainer">';
    foreach($reposFiles as $repoFileName) {
      if (($repoFileName != "..") AND ($repoFileName != ".") AND ($repoFileName != "repomanager.conf")) { // on ignore le fichier principal repomanager.conf (qui est dans /etc/yum.repos.d/repomanager/)
        // on retire le suffixe .repo du nom du fichier afin que ça soit plus propre dans la liste
        $repoFileNameFormated = str_replace(".repo", "", $repoFileName);
        // on récupère le contenu du fichier
        $content = file_get_contents("${REPOMANAGER_YUM_DIR}/${repoFileName}", true);
        echo '<div class="reposSourcesDiv">';
          echo "<span>$repoFileNameFormated</span>";
          // bouton pour supprimer le repo source
          echo "<img src=\"icons/bin.png\" class=\"repoSourceDeleteToggle${i} float-right icon-lowopacity\" title=\"Supprimer le repo $repoFileNameFormated\" />";
          deleteConfirm("Êtes-vous sûr de vouloir supprimer le repo source $repoFileNameFormated", "?action=deleteRepoFile&repoFileName=${repoFileName}", "repoSourceDeleteDiv${i}", "repoSourceDeleteToggle${i}");
          // bouton pour afficher la conf du repo source
          echo "<img id=\"reposSourcesToggle${i}\" class=\"float-right icon-lowopacity\" src=\"icons/cog.png\" />";
          // conf dans un div caché
          echo "<div id=\"reposSourcesConfDiv${i}\" class=\"hide\">";
          echo "<form action=\"${actual_uri}\" method=\"post\" autocomplete=\"off\">";
          echo '<input type="hidden" name="action" value="editRepoSourceConf" />';
          echo "<input type=\"hidden\" name=\"repoFileName\" value=\"${repoFileName}\" />";
          echo '<textarea class="reposSourcesTextarea">';
          echo "${content}";
          echo '</textarea>';
          echo '<td colspan="100%"><button type="submit" class="button-submit-large-green">Enregistrer</button></td>';
          echo '</form>';
          echo '</div>'; // clôture de reposSourcesConfDiv${i}
        echo '</div>'; // clôture de reposSourcesDiv
        // Afficher ou masquer la div qui affiche la conf de chaque repo source :
        echo '<script>';
        echo '$(document).ready(function(){';
        echo "$(\"#reposSourcesToggle${i}\").click(function(){";
        echo "$(\"div#reposSourcesConfDiv${i}\").slideToggle(250);";
        echo '$(this).toggleClass("open");';
        echo '});';
        echo '});';
        echo '</script>';
        ++$i;
      }
    }
    echo '</div>'; // clôture de reposSourcesDivContainer
    echo '</div>'; // clôture de <div class="div-68 is-inline-block">
  }


  // Cas Debian
  if ($OS_FAMILY == "Debian") {
    echo '<p>Pour créer un miroir, debmirror a besoin de connaitre l\'URL de l\'hôte source.</p>';
    echo '<hr>';
    echo '<div class="div-68 is-inline-block">';
    echo '<p>Renseignez l\'URL de l\'hôte source en lui donnant un nom unique.</p>';
    echo '<p><b>Ajouter une nouvelle url hôte :</b></p>';
    echo "<form action=\"${actual_uri}\" method=\"post\" autocomplete=\"off\">";
    echo '<table class="table-large">';
    echo '<tr>';
    echo '<td class="td-medium"><input type="text" name="newHostName" placeholder="Donner un nom à l\'hôte"/></td>';
    echo '<td class="td-medium"><input type="text" name="newHostUrl" placeholder="Adresse URL de l\'hôte" /></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td colspan="2"><textarea name="newHostGpgKey" placeholder="Clé GPG (optionnel)" /></textarea></td>';
    echo '<td class="td-fit"><button type="submit" class="button-submit-xxsmall-blue" title="Ajouter">+</button></td>';
    echo '<tr>';
    echo '</table>';
    echo '</form>';

    // récupération de tous les hotes renseignés dans $HOSTS_CONF
    //$rows = explode("\n", file_get_contents($HOSTS_CONF));
    $rows = preg_split('/\s+/', trim(file_get_contents($HOSTS_CONF)));
    if (!empty($rows)) {
      echo '<p><b>Hôtes actuels :</b></p>';
      echo '<div class="reposSourcesDivContainer">';
      foreach($rows as $data) {
        $rowData = explode(',', $data);
        $hostName = str_replace(['Name=', '"'], '', $rowData[0]);
        $repoHost = str_replace(['Url=', '"'], '', $rowData[1]);
        echo '<div class="reposSourcesDiv">';
        echo "<span>${hostName} (${repoHost})</span>";
        echo "<img src=\"icons/bin.png\" class=\"repoSourceDeleteToggle${i} float-right icon-lowopacity\" title=\"Supprimer l'hôte ${hostName}\" />";
        deleteConfirm("Êtes-vous sûr de vouloir supprimer l'hôte source $hostName", "?action=deleteHost&hostName=${hostName}", "repoSourceDeleteDiv${i}", "repoSourceDeleteToggle${i}");
        echo '</div>';
        ++$i;
      }
      echo '</div>'; // cloture de <div class="reposSourcesDivContainer">
    }
    echo '</div>'; // cloture de <div class="div-45...>
  } ?>

  <div class="div-28 is-inline-block align-top float-right">
    <p>Liste des clés GPG du trousseau de repomanager</p>
    <table class="table-large">
   
    <?php
    $j=0;
    if ($OS_FAMILY == "Redhat") { // dans le cas de rpm, les clés gpg sont importées dans $RPM_GPG_DIR (en principe par défaut /etc/pki/rpm-gpg/repomanager)
      $gpgFiles = scandir($RPM_GPG_DIR);
      foreach($gpgFiles as $gpgFile) {
        if (($gpgFile != "..") AND ($gpgFile != ".")) {
          echo '<tr>';
          echo '<td>';
          echo "<img class=\"gpgKeyDeleteToggle${j} icon-lowopacity\" title=\"Supprimer la clé GPG ${gpgFile}\" src=\"icons/bin.png\" />";
          deleteConfirm("Êtes-vous sûr de vouloir supprimer la clé ${gpgFile}", "?action=deleteGpgKey&gpgKeyFile=${gpgFile}", "gpgKeyDeleteDiv${j}", "gpgKeyDeleteToggle${j}");
          echo '</td>';
          echo '<td>';
          echo "${gpgFile}";
          echo '</td>';
          echo '</tr>';
          ++$j;
        }
      }
    }

    if ($OS_FAMILY == "Debian") {
      $gpgKeysList = shell_exec("gpg --no-default-keyring --keyring ${GPGHOME}/trustedkeys.gpg --list-key --fixed-list-mode --with-colons | sed 's/^pub/\\npub/g'");
      $gpgKeysList = explode(PHP_EOL.PHP_EOL, $gpgKeysList);
      foreach ($gpgKeysList as $gpgKey) {
        $gpgKeyID = shell_exec("echo \"$gpgKey\" | sed -n -e '/pub/,/uid/p' | grep '^fpr:' | awk -F':' '{print $10}'"); // on récup uniquement l'ID de la clé GPG
        $gpgKeyID = preg_replace('/\s+/', '', $gpgKeyID); // retire tous les espaces blancs
        $gpgKeyName = shell_exec("echo \"$gpgKey\" | sed -n -e '/pub/,/uid/p' | grep '^uid:' | awk -F':' '{print $10}'");
        if (!empty($gpgKeyID) AND !empty($gpgKeyName)) {
          echo '<tr>';
          echo '<td>';
          echo "<img src=\"icons/bin.png\" class=\"gpgKeyDeleteToggle${j} icon-lowopacity\" title=\"Supprimer la clé GPG ${gpgKeyID}\" />";
          deleteConfirm("Êtes-vous sûr de vouloir supprimer la clé ${gpgKeyName}", "?action=deleteGpgKey&gpgKeyID=${gpgKeyID}", "gpgKeyDeleteDiv${j}", "gpgKeyDeleteToggle${j}");
          echo '</td>';
          echo '<td>';
          echo "$gpgKeyName ($gpgKeyID)";
          echo '</td>';
          echo '</tr>';
          ++$j;
        }
      }
    } ?>
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
    $('#ReposSourcesCloseButton').click(function() {
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