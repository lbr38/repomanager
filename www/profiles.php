<html>
<?php include('common-head.inc.php'); ?>

<?php
  // Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
  require 'vars/common.vars';
  require 'common-functions.php';
  require 'common.php';
  require 'vars/display.vars';
  if ($debugMode == "enabled") { echo "Mode debug activé : "; print_r($_POST); }

  // Créer le répertoire principal des profils si n'existe pas
  // boolean true = créee récursivement tous les sous-répertoires si n'existent pas
  if (!file_exists($PROFILS_MAIN_DIR)) {
    mkdir($PROFILS_MAIN_DIR, 0775, true);
  }

  // Créer le répertoire qui accueille les fichiers de conf .list ou .repo si n'existe pas
  if (!file_exists($REPOS_CONF_FILES_DIR)) {
    mkdir($REPOS_CONF_FILES_DIR, 0775, true);
  }

  // Cas où on souhaite supprimer un profil
  // trouver moyen d'afficher une confirmation (boite de dialogue en javascript)
  if (isset($_GET['action']) AND ($_GET['action'] == "deleteprofile") AND isset($_GET['profileName'])) {
    $profileName = validateData($_GET['profileName']);
    exec("rm -fr ${PROFILS_MAIN_DIR}/${profileName}/");
  }

  // Cas où on souhaite renommer un profil
  if (isset($_POST['profileName']) AND isset($_POST['actualProfileName'])) {
    $profileName = validateData($_POST['profileName']);
    $actualProfileName = validateData($_POST['actualProfileName']);
    exec("mv ${PROFILS_MAIN_DIR}/${actualProfileName} ${PROFILS_MAIN_DIR}/${profileName}"); // renommage du nom de profil (renomme le répertoire $actualProfileName par $profileName)
  }

  // Cas où on ajoute un repo à un profil
  if (isset($_POST['profileName']) AND !empty($_POST['addProfileRepo'])) {
    $profileName = validateData($_POST['profileName']);
    $addProfileRepo = validateData($_POST['addProfileRepo']);
    if ($OS_FAMILY == "Debian") {
      $addProfileRepoDist = validateData($_POST['addProfileRepoDist']);
      $addProfileRepoSection = validateData($_POST['addProfileRepoSection']);
      exec("cd ${PROFILS_MAIN_DIR}/${profileName}/ && ln -s ${REPOS_CONF_FILES_DIR}/${REPO_CONF_FILES_PREFIX}${addProfileRepo}_${addProfileRepoDist}_${addProfileRepoSection}.list");
    } elseif ($OS_FAMILY == "Redhat") {
      exec("cd ${PROFILS_MAIN_DIR}/${profileName}/ && ln -s ${REPOS_CONF_FILES_DIR}/${REPO_CONF_FILES_PREFIX}${addProfileRepo}.repo");
    }
  }

  // Cas où on souhaite supprimer un repo d'un profil
  if (isset($_GET['action']) AND ($_GET['action'] == "deleteProfileRepo") AND isset($_GET['profileName']) AND isset($_GET['repoName'])) {
    $profileName = validateData($_GET['profileName']);
    $repoName = validateData($_GET['repoName']);
    if ($OS_FAMILY == "Debian") {
      $repoDist = validateData($_GET['repoDist']);
      $repoSection =  validateData($_GET['repoSection']);
      exec("unlink ${PROFILS_MAIN_DIR}/${profileName}/${REPO_CONF_FILES_PREFIX}${repoName}_${repoDist}_${repoSection}.list");
    } elseif ($OS_FAMILY == "Redhat") {
      exec("unlink ${PROFILS_MAIN_DIR}/${profileName}/${REPO_CONF_FILES_PREFIX}${repoName}.repo");
    }
  }

  // Modif de la configuration d'un profil
  if (isset($_POST['profileName']) AND isset($_POST['profileConf_excludeMajor']) AND isset($_POST['profileConf_exclude']) AND isset($_POST['profileConf_needRestart']) AND isset($_POST['profileConf_keepCron']) AND isset($_POST['profileConf_allowOverwrite']) AND isset($_POST['profileConf_allowReposFilesOverwrite'])) {
    $profileConf_excludeMajor = validateData($_POST['profileConf_excludeMajor']);
    $profileConf_exclude = validateData($_POST['profileConf_exclude']);
    $profileConf_needRestart = validateData($_POST['profileConf_needRestart']);
    $profileConf_keepCron = validateData($_POST['profileConf_keepCron']);
    $profileConf_allowOverwrite = validateData($_POST['profileConf_allowOverwrite']);
    $profileConf_allowReposFilesOverwrite = validateData($_POST['profileConf_allowReposFilesOverwrite']);
    // On écrit dans le fichier de conf ce qui a été envoyé en POST :
    exec("sed -i 's/^EXCLUDE_MAJEURE=.*/EXCLUDE_MAJEURE=\"${profileConf_excludeMajor}\"/g' ${PROFILS_MAIN_DIR}/${profileName}/config");
    exec("sed -i 's/^EXCLUDE=.*/EXCLUDE=\"${profileConf_exclude}\"/g' ${PROFILS_MAIN_DIR}/${profileName}/config");
    exec("sed -i 's/^NEED_RESTART=.*/NEED_RESTART=\"${profileConf_needRestart}\"/g' ${PROFILS_MAIN_DIR}/${profileName}/config");
    exec("sed -i 's/^KEEP_CRON=.*/KEEP_CRON=\"${profileConf_keepCron}\"/g' ${PROFILS_MAIN_DIR}/${profileName}/config");
    exec("sed -i 's/^ALLOW_OVERWRITE=.*/ALLOW_OVERWRITE=\"${profileConf_allowOverwrite}\"/g' ${PROFILS_MAIN_DIR}/${profileName}/config");
    exec("sed -i 's/^ALLOW_REPOSFILES_OVERWRITE=.*/ALLOW_REPOSFILES_OVERWRITE=\"${profileConf_allowReposFilesOverwrite}\"/g' ${PROFILS_MAIN_DIR}/${profileName}/config");
  }

  // Création d'un nouveau profil
  // trouver un moyen d'afficher une alerte si le profil existe déjà
  if (isset($_POST['newProfile'])) {
    $newProfile = validateData($_POST['newProfile']);
    // Créer le répertoire du profil :
    exec("mkdir -p ${PROFILS_MAIN_DIR}/${newProfile}");
    // Créer le fichier de config :
    exec("touch ${PROFILS_MAIN_DIR}/${newProfile}/config");
    // Créer le fichier de config du profil avec des valeurs vides ou par défaut :
    exec("echo '[${newProfile}]\nEXCLUDE_MAJEURE=\"\"\nEXCLUDE=\"\"\nNEED_RESTART=\"\"\nKEEP_CRON=\"\"\nALLOW_OVERWRITE=\"yes\"\nALLOW_REPOSFILES_OVERWRITE=\"yes\"' > ${PROFILS_MAIN_DIR}/${newProfile}/config");
  }
?>

<body>
<?php include('common-header.inc.php'); ?>

<section class="mainSectionLeft">
  <!-- REPOS ACTIFS -->
  <section class="left">
      <?php include('common-repos-list.inc.php'); ?>
  </section>
</section>

<section class="mainSectionRight">
    <section class="right">
      <h5>PROFILS</h5>
      <?php
        $i = 0;
        $profilesNames = scandir($PROFILS_MAIN_DIR);
        foreach($profilesNames as $profileName) {
          if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "_configurations") AND ($profileName != "main")) { // fix temporaire pour ne pas afficher les répertoires ../ et ./ (trouver une autre solution plus propre)
            echo '<form action="profiles.php" method="post" class="profileForm" autocomplete="off">';
            echo '<table class="table-large">';
            echo '<tbody>';
            // On veut pouvoir renommer les profils, donc il faut transmettre le nom de profil actuel (actualProfileName),
            echo "<input type=\"hidden\" name=\"actualProfileName\" value=\"${profileName}\" />";
            // ainsi qu'afficher ce même profil actuel dans un input type=text qui permettra d'en renseigner un nouveau (profileName) :
            echo '<tr>';
            echo '<td class="td-fit">';
            echo "<a href=\"?action=deleteprofile&profileName=${profileName}\" title=\"Supprimer le profil ${profileName}\"><img class=\"icon-lowopacity\" src=\"icons/bin.png\" /></a>";
            echo '</td>';
            echo '<td>';
            echo "<input type=\"text\" value=\"${profileName}\" name=\"profileName\" class=\"invisibleInput\" />";
            echo '</td>';
            echo '</tr>';

            $profileName_dir = "$PROFILS_MAIN_DIR/$profileName";
            $repoConfFiles = scandir($profileName_dir);
                
            foreach($repoConfFiles as $repoFile) { // Pour chaque répertoire de profil sur le serveur, on récupère les noms de fichier de conf (.repo ou .list selon l'OS)
              if (($repoFile != "..") AND ($repoFile != ".") AND ($repoFile != "config")){ // fix temporaire pour ne pas afficher les répertoires ../ et ./ (trouver une autre solution plus propre)
                if ($OS_FAMILY == "Redhat") {
                  $repoFile = str_replace(".repo", "","$repoFile"); // remplace ".repo" par rien dans le nom du fichier, afin d'afficher seulement le nom du repo (ce qui nous interesse) et pas le nom complet du fichier
                  $repoFile = str_replace("${REPO_CONF_FILES_PREFIX}", "","$repoFile"); // retire le prefix configuré dans l'onglet paramètres afin de n'obtenir que le nom du repo, sa distribution et sa section
                  $repoName = $repoFile;
                }
                if ($OS_FAMILY == "Debian") {
                  $repoFile = str_replace(".list", "","$repoFile"); // retire le suffixe ".list" afin de n'obtenir que le nom du repo, sa distribution et sa section
                  $repoFile = str_replace("${REPO_CONF_FILES_PREFIX}", "","$repoFile"); // retire le prefix configuré dans l'onglet paramètres afin de n'obtenir que le nom du repo, sa distribution et sa section
                  $repoFile = preg_split("/_/", "$repoFile");
                  $repoName = $repoFile[0];
                  $repoDist = $repoFile[1];
                  $repoSection = $repoFile[2];
                }
                echo '<tr>';
                
                if ($OS_FAMILY == "Redhat") {
                  echo "<td class=\"td-fit\"><a href=\"?action=deleteProfileRepo&profileName=${profileName}&repoName=${repoName}\" title=\"Retirer le repo ${repoName}\"><img class=\"icon-lowopacity\" src=\"icons/bin.png\" /></a></td>";
                  echo "<td>${repoName}</td>";
                }
                if ($OS_FAMILY == "Debian") {
                  echo "<td class=\"td-fit\"><a href=\"?action=deleteProfileRepo&profileName=${profileName}&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}\" title=\"Retirer la section ${repoSection}\"><img class=\"icon-lowopacity\" src=\"icons/bin.png\" /></a></td>";
                  echo "<td>${repoName}</td>";
                  echo "<td>${repoDist}</td>";
                  echo "<td>${repoSection}</td>";
                }
                echo "</tr>";
              }                      
              // on en profite pour récupérer la conf du profil contenue dans le fichier "config", ce sera utile pour la suite
              // voir si on peut faire autrement qu'avec une commande exec (voir du côté de php fopen)
              $profileConf_excludeMajor = exec("grep '^EXCLUDE_MAJEURE=' ${PROFILS_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
              $profileConf_exclude = exec("grep '^EXCLUDE=' ${PROFILS_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
              $profileConf_needRestart = exec("grep '^NEED_RESTART=' ${PROFILS_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
              $profileConf_keepCron = exec("grep '^KEEP_CRON=' ${PROFILS_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
              $profileConf_allowOverwrite = exec("grep '^ALLOW_OVERWRITE=' ${PROFILS_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
              $profileConf_allowReposFilesOverwrite = exec("grep '^ALLOW_REPOSFILES_OVERWRITE=' ${PROFILS_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
            }

            echo '<tr>';
            echo '<td></td>';
            if ($OS_FAMILY == "Redhat") { 
              echo '<td><input type="text" name="addProfileRepo" class="input-small" placeholder="Nom du repo" /></td>';
              echo '<td><button type="submit" class="button-submit-xsmall-blue">Ajouter</button></td>';
            }

            if ($OS_FAMILY == "Debian") { 
              echo '<td><input type="text" name="addProfileRepo" class="input-small" placeholder="Nom du repo" /></td>';
              echo '<td><input type="text" name="addProfileRepoDist" class="input-small" placeholder="Distribution" /></td>';
              echo '<td><input type="text" name="addProfileRepoSection" class="input-small" placeholder="Section" /></td>';
              echo '<td><button type="submit" class="button-submit-xsmall-blue">Ajouter</button></td>';
            }

            echo '</tr>';
            echo '<tr><td colspan="100%"><hr></td></tr>';
            echo '</tbody>';
            echo '</table>';
            echo '<table class="table-large">';
            echo '<thead>';
            echo '<tr>';
            echo "<td><a href=\"#\" id=\"profilConfigurationToggleButton${i}\">Configuration</a></td>";
            echo '</tr>';
            echo '</thead>';
            echo "<tbody id=\"profilConfigurationTbody${i}\" class=\"hide\">";
            echo '<tr>';
            echo '<td class="td-fit" title="Paquets à exclure uniquement si sa nouvelle version est majeure">Paquets à exclure en cas de version majeure</td>';
            echo "<td><input type=\"text\" class=\"profileConf_input\" name=\"profileConf_excludeMajor\" value=\"${profileConf_excludeMajor}\" /></td>";
            echo '</tr>';
            echo '<tr>';
            echo '<td class="td-fit" title="Paquets à exclure quelque soit la version proposée">Paquets à exclure (toute version)</td>';
            echo "<td><input type=\"text\" class=\"profileConf_input\" name=\"profileConf_exclude\" value=\"${profileConf_exclude}\" /></td>";
            echo '</tr>';
            echo '<tr>';
            echo '<td class="td-fit" title="Services nécessitant un redémarrage après mise à jour">Services à redémarrer</td>';
            echo "<td><input type=\"text\" class=\"profileConf_input\" name=\"profileConf_needRestart\" value=\"${profileConf_needRestart}\" /></td>";
            echo '</tr>';
            echo '<tr>';
            echo '<td class="td-fit" title="Conserver ou non la tâche cron après exécution de la mise à jour">Conserver la tâche cron</td>';
            echo "<td><input type=\"text\" autocomplete=\"off\" class=\"profileConf_input\" name=\"profileConf_keepCron\" value=\"${profileConf_keepCron}\" /></td>";
            echo '</tr>';
            echo '<tr>';
            echo '<td class="td-fit" title="Autoriser linux-autoupdate à récupérer et écraser sa conf à chaque exécution">Autoriser la mise à jour auto. de la configuration</td>';
            echo "<td><input type=\"text\" autocomplete=\"off\" class=\"profileConf_input\" name=\"profileConf_allowOverwrite\" value=\"${profileConf_allowOverwrite}\" /></td>";
            echo '</tr>';
            echo '<tr>';
            echo '<td class="td-fit" title="Autoriser linux-autoupdate à récupérer automatiquement les fichiers .list ou .repo de son profil">Autoriser la mise à jour auto. des fichiers de repo</td>';
            echo "<td><input type=\"text\" autocomplete=\"off\" class=\"profileConf_input\" name=\"profileConf_allowReposFilesOverwrite\" value=\"${profileConf_allowReposFilesOverwrite}\" /></td>";
            echo '</tr>';
            echo '<tr>';        
            echo '<td colspan="100%"><button type="submit" class="button-submit-large-green">Enregistrer</button></td>';
            echo '</tr>';
            echo '</tbody>';
                
            echo '<tr><td colspan="100%"><hr></td></tr>';
            echo '</tbody>';
            echo '</table>';
            echo '</form>';

            // Afficher ou masquer la div 'Configuration' :
            echo "<script>";
            echo "$(document).ready(function(){";
              echo "$(\"a#profilConfigurationToggleButton${i}\").click(function(){";
                echo "$(\"tbody#profilConfigurationTbody${i}\").slideToggle(150);";
                echo '$(this).toggleClass("open");';
              echo "});";
            echo "});";
            echo "</script>";
            $i++;
          }
        }?>
    </table>
    <br>
    <p>Ajouter un nouveau profil :</p>
    <form action="profiles.php" method="post" autocomplete="off">
      <input type="text" name="newProfile">
      <button type="submit" class="button-submit-medium-blue">Ajouter</button>
    </form>
    </section>
</section>
<!-- divs cachées de base -->
<!-- GERER LES GROUPES -->
<?php include('common-groupslist.inc.php'); ?>

<!-- REPOS/HOTES SOURCES -->
<?php include('common-repos-sources.inc.php'); ?>

<?php include('common-footer.inc.php'); ?>
</body>
</html>