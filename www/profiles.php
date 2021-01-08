<html>
<?php include('common-head.inc.php'); ?>

<?php
  // Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
  require 'common-vars.php';
  require 'common-functions.php';
  require 'common.php';
  require 'display.php';
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
    $profileName = $_GET['profileName'];
    exec("rm -fr ${PROFILS_MAIN_DIR}/${profileName}/");
  }

  // Cas où on souhaite renommer un profil
  if (isset($_POST['profileName']) AND isset($_POST['actualProfileName'])) {
    $profileName = $_POST['profileName'];
    $actualProfileName = $_POST['actualProfileName'];
    exec("mv ${PROFILS_MAIN_DIR}/${actualProfileName} ${PROFILS_MAIN_DIR}/${profileName}"); // renommage du nom de profil (renomme le répertoire $actualProfileName par $profileName)
  }

  // Cas où on ajoute un repo à un profil
  if (isset($_POST['profileName']) AND !empty($_POST['addProfileRepo'])) {
    $profileName = $_POST['profileName'];
    $addProfileRepo = $_POST['addProfileRepo'];
    if ($OS_TYPE == "deb") {
      $addProfileRepoDist = $_POST['addProfileRepoDist'];
      $addProfileRepoSection = $_POST['addProfileRepoSection'];
      exec("cd ${PROFILS_MAIN_DIR}/${profileName}/ && ln -s ${REPOS_CONF_FILES_DIR}/${REPO_FILES_PREFIX}${addProfileRepo}_${addProfileRepoDist}_${addProfileRepoSection}.list");
    } elseif ($OS_TYPE == "rpm") {
      exec("cd ${PROFILS_MAIN_DIR}/${profileName}/ && ln -s ${REPOS_CONF_FILES_DIR}/${REPO_FILES_PREFIX}${addProfileRepo}.repo");
    }
  }

  // Cas où on souhaite supprimer un repo d'un profil
  if (isset($_GET['action']) AND ($_GET['action'] == "deleteProfileRepo") AND isset($_GET['profileName']) AND isset($_GET['repoName'])) {
    $profileName = $_GET['profileName'];
    $repoName = $_GET['repoName'];
    if ($OS_TYPE == "deb") {
      $repoDist =  $_GET['repoDist'];
      $repoSection =  $_GET['repoSection'];
      exec("unlink ${PROFILS_MAIN_DIR}/${profileName}/${REPO_FILES_PREFIX}${repoName}_${repoDist}_${repoSection}.list");
    } elseif ($OS_TYPE == "rpm") {
      exec("unlink ${PROFILS_MAIN_DIR}/${profileName}/${REPO_FILES_PREFIX}${repoName}.repo");
    }
  }

  // Modif de la configuration d'un profil
  if (isset($_POST['profileName']) AND isset($_POST['profileConf_excludeMajor']) AND isset($_POST['profileConf_exclude']) AND isset($_POST['profileConf_needRestart']) AND isset($_POST['profileConf_keepCron']) AND isset($_POST['profileConf_allowOverwrite']) AND isset($_POST['profileConf_allowReposFilesOverwrite'])) {
    $profileConf_excludeMajor = $_POST['profileConf_excludeMajor'];
    $profileConf_exclude = $_POST['profileConf_exclude'];
    $profileConf_needRestart = $_POST['profileConf_needRestart'];
    $profileConf_keepCron = $_POST['profileConf_keepCron'];
    $profileConf_allowOverwrite = $_POST['profileConf_allowOverwrite'];
    $profileConf_allowReposFilesOverwrite = $_POST['profileConf_allowReposFilesOverwrite'];
    // On écrit dans le fichier de conf ce qui a été envoyé en POST :
    exec("sed -i 's/^EXCLUDE_MAJEURE=.*/EXCLUDE_MAJEURE=\"" . $profileConf_excludeMajor . "\"/g' ${PROFILS_MAIN_DIR}/${profileName}/config");
    exec("sed -i 's/^EXCLUDE=.*/EXCLUDE=\"" . $profileConf_exclude . "\"/g' ${PROFILS_MAIN_DIR}/${profileName}/config");
    exec("sed -i 's/^NEED_RESTART=.*/NEED_RESTART=\"" . $profileConf_needRestart . "\"/g' ${PROFILS_MAIN_DIR}/${profileName}/config");
    exec("sed -i 's/^KEEP_CRON=.*/KEEP_CRON=\"" . $profileConf_keepCron . "\"/g' ${PROFILS_MAIN_DIR}/${profileName}/config");
    exec("sed -i 's/^ALLOW_OVERWRITE=.*/ALLOW_OVERWRITE=\"" . $profileConf_allowOverwrite . "\"/g' ${PROFILS_MAIN_DIR}/${profileName}/config");
    exec("sed -i 's/^ALLOW_REPOSFILES_OVERWRITE=.*/ALLOW_REPOSFILES_OVERWRITE=\"" . $profileConf_allowReposFilesOverwrite . "\"/g' ${PROFILS_MAIN_DIR}/${profileName}/config");
  }

  // Création d'un nouveau profil
  // trouver un moyen d'afficher une alerte si le profil existe déjà
  if (isset($_POST['newProfile'])) {
    $newProfile = $_POST['newProfile'];
    // Créer le répertoire du profil :
    exec("mkdir -p ${PROFILS_MAIN_DIR}/${newProfile}");
    // Créer le fichier de config :
    exec("touch ${PROFILS_MAIN_DIR}/${newProfile}/config");
    // Créer le fichier de config du profil avec des valeurs vides ou par défaut :
    exec("echo '[$newProfile]\nEXCLUDE_MAJEURE=\"\"\nEXCLUDE=\"\"\nNEED_RESTART=\"\"\nKEEP_CRON=\"\"\nALLOW_OVERWRITE=\"yes\"\nALLOW_REPOSFILES_OVERWRITE=\"yes\"' > ${PROFILS_MAIN_DIR}/${newProfile}/config");
  }
?>

<body>
<?php include('common-header.inc.php'); ?>

  <article class='main'>
    <!-- REPOS ACTIFS -->
    <article class="left">
        <?php include('common-repos-list.inc.php'); ?>
    </article>

    <article class="right">
      <h5>PROFILS</h5>
      <?php
        $i = 0;
        $profilesNames = scandir($PROFILS_MAIN_DIR);
        foreach($profilesNames as $profileName) {
          if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "00_repo-conf-files") AND ($profileName != "main")) { // fix temporaire pour ne pas afficher les répertoires ../ et ./ (trouver une autre solution plus propre)
            echo '
              <form action="profiles.php" method="post" class="profileForm">
              <table class="table-large">
                <tbody>';
                // On veut pouvoir renommer les profils, donc il faut transmettre le nom de profil actuel (actualProfileName),
                echo '
                <input type="hidden" name="actualProfileName" value="'.$profileName.'" />';
                  // ainsi qu'afficher ce même profil actuel dans un input type=text qui permettra d'en renseigner un nouveau (profileName) :
                  echo '
                  <tr>
                    <td>
                      <input type="text" value="'.$profileName.'" name="profileName" class="invisible_input" />
                      <a href="?action=deleteprofile&profileName='.$profileName.'" title="Supprimer le profil '.$profileName.'"><img src="images/trash.png" /></a>
                    </td>
                  </tr>';

                $profileName_dir = "$PROFILS_MAIN_DIR/$profileName";
                $repoConfFiles = scandir($profileName_dir);
                
                foreach($repoConfFiles as $repoFile) { // Pour chaque répertoire de profil sur le serveur, on récupère les noms de fichier de conf (.repo ou .list selon l'OS)
                  if (($repoFile != "..") AND ($repoFile != ".") AND ($repoFile != "config")){ // fix temporaire pour ne pas afficher les répertoires ../ et ./ (trouver une autre solution plus propre)
                    if ($OS_TYPE == "rpm") {
                      $repoFile = str_replace(".repo", "","$repoFile"); // remplace ".repo" par rien dans le nom du fichier, afin d'afficher seulement le nom du repo (ce qui nous interesse) et pas le nom complet du fichier
                      $repoFile = str_replace("${REPO_FILES_PREFIX}", "","$repoFile"); // retire le prefix configuré dans l'onglet paramètres afin de n'obtenir que le nom du repo, sa distribution et sa section
                      $repoName = $repoFile;
                    }
                    if ($OS_TYPE == "deb") {
                      $repoFile = str_replace(".list", "","$repoFile"); // retire le suffixe ".list" afin de n'obtenir que le nom du repo, sa distribution et sa section
                      $repoFile = str_replace("${REPO_FILES_PREFIX}", "","$repoFile"); // retire le prefix configuré dans l'onglet paramètres afin de n'obtenir que le nom du repo, sa distribution et sa section
                      $repoFile = preg_split("/_/", "$repoFile");
                      $repoName = $repoFile[0];
                      $repoDist = $repoFile[1];
                      $repoSection = $repoFile[2];
                    }
                    echo "<tr>";
                    echo "<td>$repoName</td>";
                    if ($OS_TYPE == "rpm") {
                      echo "<td><a href=\"?action=deleteProfileRepo&profileName=${profileName}&repoName=${repoName}\" title=\"Supprimer le repo ${repoName}\"><img src=\"images/trash.png\" /></a></td>";
                    }
                    if ($OS_TYPE == "deb") {
                      echo "<td>$repoDist</td>";
                      echo "<td>$repoSection</td>";
                      echo "<td><a href=\"?action=deleteProfileRepo&profileName=${profileName}&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}\" title=\"Supprimer le repo ${repoName}\"><img src=\"images/trash.png\" /></a></td>";
                    }

                    echo "</tr>";;
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
                echo '
                <tr>
                  <td>Ajouter un repo à ce profil :</td>
                </tr>
                <tr>';
                if ($OS_TYPE == "rpm") { echo '
                  <td><input type="text" autocomplete="off" name="addProfileRepo" class="input-small" placeholder="Nom du repo" /></td>
                  <td><button type="submit" class="button-submit-xsmall-blue">Ajouter</button></td>';
                }

                if ($OS_TYPE == "deb") { echo '
                  <td><input type="text" autocomplete="off" name="addProfileRepo" class="input-small" placeholder="Nom du repo" /></td>
                  <td><input type="text" autocomplete="off" name="addProfileRepoDist" class="input-small" placeholder="Distribution" /></td>
                  <td><input type="text" autocomplete="off" name="addProfileRepoSection" class="input-small" placeholder="Section" /></td>
                  <td><button type="submit" class="button-submit-xsmall-blue">Ajouter</button></td>';
                }

                echo '
                </tr>
                <tr><td colspan="100%"><hr></td></tr>
                </tbody>
                </table>
                <table class="table-large">
                <thead>
                  <tr>
                    <td>Configuration :</td>
                  </tr>
                </thead>
                  <div class="profilConfig-div">
                    <tbody>
                      <tr>
                        <td title="Paquets à exclure uniquement si sa nouvelle version est majeure">EXCLUDE_MAJEURE</td>
                        <td><input type="text" autocomplete="off" class="profileConf_input" name="profileConf_excludeMajor" value="'.$profileConf_excludeMajor.'" /></td>
                      </tr>
                      <tr>
                        <td title="Paquets à exclure quelque soit la version proposée">EXCLUDE</td>
                        <td><input type="text" autocomplete="off" class="profileConf_input" name="profileConf_exclude" value="'.$profileConf_exclude.'" /></td>
                      </tr>
                      <tr>
                        <td title="Services nécessitant un redémarrage après mise à jour">NEED_RESTART</td>
                        <td><input type="text" autocomplete="off" class="profileConf_input" name="profileConf_needRestart" value="'.$profileConf_needRestart.'" /></td>
                      </tr>
                      <tr>
                        <td title="Conserver ou non la tâche cron après exécution de la mise à jour">KEEP_CRON</td>
                        <td><input type="text" autocomplete="off" class="profileConf_input" name="profileConf_keepCron" value="'.$profileConf_keepCron.'" /></td>
                      </tr>
                      <tr>
                      <td title="Autoriser linux-autoupdate à récupérer et écraser sa conf à chaque exécution">ALLOW_OVERWRITE</td>
                        <td><input type="text" autocomplete="off" class="profileConf_input" name="profileConf_allowOverwrite" value="'.$profileConf_allowOverwrite.'" /></td>
                      </tr>
                      <tr>
                        <td title="Autoriser linux-autoupdate à récupérer automatiquement les fichiers .list ou .repo de son profil">ALLOW_REPOSFILES_OVERWRITE</td>
                        <td><input type="text" autocomplete="off" class="profileConf_input" name="profileConf_allowReposFilesOverwrite" value="'.$profileConf_allowReposFilesOverwrite.'" /></td>
                      </tr>
                    </tbody>
                  </div>
                  <tr>
                    
                    <td colspan="100%"><button type="submit" class="button-submit-large-green">Enregistrer</button></td>
                  </tr>
                
                <tr><td colspan="100%"><hr></td></tr>
              </tbody>
              </table>
              </form>';
            $i++;
          }
        }?>
    </table>

<br>

      <p>Ajouter un nouveau profil :</p>
      <form action="profiles.php" method="post" class="actionform">
        <input type="text" name="newProfile" autocomplete="off">
        <button type="submit" class="button-submit-large-green">Enregistrer</button>
      </form>
      </article>
  </article>
  <!-- divs cachées de base -->
  <!-- div des groupes de repos -->
  <?php include('common-groupslist.inc.php'); ?>

  <!-- div des hotes et fichers de repos -->
  <?php include('common-repos-sources.inc.php'); ?>
  
  <?php include('common-footer.inc.php'); ?>
</body>
</html>