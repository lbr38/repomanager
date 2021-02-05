<!DOCTYPE html>
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
  if (!file_exists($PROFILES_MAIN_DIR)) {
    mkdir($PROFILES_MAIN_DIR, 0775, true);
  }

  // Créer le répertoire qui accueille les fichiers de conf .list ou .repo si n'existe pas
  if (!file_exists($REPOS_PROFILES_CONF_DIR)) {
    mkdir($REPOS_PROFILES_CONF_DIR, 0775, true);
  }

  // Créer le répertoire qui accueille le fichier de conf du serveur de repo
  if (!file_exists($REPOSERVER_PROFILES_CONF_DIR)) {
    mkdir($REPOSERVER_PROFILES_CONF_DIR, 0775, true);
  }

  // Créer le fichier de conf du serveur n'existe pas on le crée
  if (!file_exists("${PROFILE_SERVER_CONF}")) {
    touch("${PROFILE_SERVER_CONF}");
  }

  // Cas où on souhaite modifier la conf serveur
  if (!empty($_POST['serverConf_manageClientsConf']) AND !empty($_POST['serverConf_manageClients_reposConf'])) {
    $serverConf_manageClientsConf = validateData($_POST['serverConf_manageClientsConf']);
    $serverConf_manageClients_reposConf = validateData($_POST['serverConf_manageClients_reposConf']);

    // On forge le bloc de conf qu'on va écrire dans le fichier
    $conf = "[REPOSERVER]\n";
    $conf = "${conf}URL=\"https://${WWW_HOSTNAME}\"\n";
    $conf = "${conf}PROFILES_URL=\"${WWW_PROFILES_DIR_URL}\"\n";
    $conf = "${conf}OS_FAMILY=\"${OS_FAMILY}\"\n";
    $conf = "${conf}OS_NAME=\"${OS_NAME}\"\n";
    $conf = "${conf}OS_VERSION=\"${OS_VERSION}\"\n";
    // Sur les systèmes CentOS il est possible de modifier la variable releasever, permettant de faire des miroirs de version de paquets différent de l'OS
    // Si c'est le cas, ($RELEASEVER différent de la version d'OS_VERSION alors il faut indiquer aux serveurs clients que ce serveur gère des paquets de version $RELEASEVER)
    if (!empty($RELEASEVER) AND $RELEASEVER !== $OS_VERSION) {
      $conf = "${conf}PACKAGES_OS_VERSION=\"${RELEASEVER}\"\n";
    }
    $conf = "${conf}MANAGE_CLIENTS_CONF=\"${serverConf_manageClientsConf}\"\n";
    $conf = "${conf}MANAGE_CLIENTS_REPOSCONF=\"${serverConf_manageClients_reposConf}\"\n";

    // Ajout de la conf au fichier de conf serveur
    file_put_contents("${PROFILE_SERVER_CONF}", $conf);

    // Affichage d'un message
    printAlert("La configuration du serveur a été enregistrée");
  }

  // Cas où on souhaite supprimer un profil
  // trouver moyen d'afficher une confirmation (boite de dialogue en javascript)
  if (!empty($_GET['action']) AND ($_GET['action'] == "deleteprofile") AND !empty($_GET['profileName'])) {
    $profileName = validateData($_GET['profileName']);
    exec("rm -fr ${PROFILES_MAIN_DIR}/${profileName}/");
    // Affichage d'un message
    printAlert("Le profil $profileName a été supprimé");
  }

  // Cas où on souhaite renommer un profil
  if (!empty($_POST['profileName']) AND !empty($_POST['actualProfileName'])) {
    $profileName = validateData($_POST['profileName']);
    $actualProfileName = validateData($_POST['actualProfileName']);
    $error = 0;
    // On vérifie qu'un profil du même nom n'existe pas déjà. Si c'est le cas on affiche un message d'erreur
    if (file_exists("${PROFILES_MAIN_DIR}/${profileName}")) {
      printAlert("Erreur : un profil du même nom ($profileName) existe déjà");
      $error++;
    }
    // Si pas d'erreur alors on peut renommer le répertoire de profil
    if ($error === 0) {
      exec("mv ${PROFILES_MAIN_DIR}/${actualProfileName} ${PROFILES_MAIN_DIR}/${profileName}");
      // Affichage d'un message
      printAlert("Le profil $actualProfileName a été renommé en $profileName");
    }
  }

  // Cas où on ajoute un repo/section à un profil toto
  if (!empty($_POST['profileName']) AND !empty($_POST['addProfileRepo'])) {
    $profileName = validateData($_POST['profileName']);
    $addProfileRepo = validateData($_POST['addProfileRepo']);
    $error = 0;
    if ($OS_FAMILY == "Redhat") {
      // On vérifie que le repo existe :
      $checkIfRepoExists = exec("grep '^Name=\"${addProfileRepo}\"' $REPOS_LIST");
      if (empty($checkIfRepoExists)) {
        printAlert("Le repo $addProfileRepo n'existe pas");
        $error++;
      }
      // Si pas d'erreur alors on ajoute le repo
      if ($error == 0) {
        exec("cd ${PROFILES_MAIN_DIR}/${profileName}/ && ln -s ${REPOS_PROFILES_CONF_DIR}/${REPO_CONF_FILES_PREFIX}${addProfileRepo}.repo");
        // Affichage d'un message
        printAlert("Le repo $addProfileRepo a été ajouté au profil $profileName");
      }
    }
    if ($OS_FAMILY == "Debian" AND !empty($_POST['addProfileRepoDist']) AND !empty($_POST['addProfileRepoSection'])) {
      $addProfileRepoDist = validateData($_POST['addProfileRepoDist']);
      $addProfileRepoSection = validateData($_POST['addProfileRepoSection']);
      // On vérifie que la section repo existe :
      $checkIfRepoExists = exec("grep '^Name=\"${addProfileRepo}\",Host=\".*\",Dist=\"${addProfileRepoDist}\",Section=\"${addProfileRepoSection}\"' $REPOS_LIST");
      if (empty($checkIfRepoExists)) {
        printAlert("La section $addProfileRepoSection du repo $addProfileRepo n'existe pas");
        $error++;
      }
      // Si pas d'erreur alors on ajoute le repo
      if ($error == 0) {
        exec("cd ${PROFILES_MAIN_DIR}/${profileName}/ && ln -s ${REPOS_PROFILES_CONF_DIR}/${REPO_CONF_FILES_PREFIX}${addProfileRepo}_${addProfileRepoDist}_${addProfileRepoSection}.list");
        // Affichage d'un message
        printAlert("La section $addProfileRepoSection du repo $addProfileRepo a été ajouté au profil $profileName");
      }
    }
  }

  // Cas où on souhaite supprimer un repo d'un profil
  if (!empty($_GET['action']) AND ($_GET['action'] == "deleteProfileRepo") AND !empty($_GET['profileName']) AND !empty($_GET['repoName'])) {
    $profileName = validateData($_GET['profileName']);
    $repoName = validateData($_GET['repoName']);
    if ($OS_FAMILY == "Redhat") {
      exec("unlink ${PROFILES_MAIN_DIR}/${profileName}/${REPO_CONF_FILES_PREFIX}${repoName}.repo");
      // Affichage d'un message
      printAlert("Le repo $repoName a été retiré du profil $profileName");
    }
    if ($OS_FAMILY == "Debian" AND !empty($_GET['repoDist']) AND !empty($_GET['repoSection'])) {
      $repoDist = validateData($_GET['repoDist']);
      $repoSection =  validateData($_GET['repoSection']);
      exec("unlink ${PROFILES_MAIN_DIR}/${profileName}/${REPO_CONF_FILES_PREFIX}${repoName}_${repoDist}_${repoSection}.list");
      // Affichage d'un message
      printAlert("La section $repoSection du repo $repoName a été retiré du profil $profileName");
    }
  }

  // Modif de la configuration d'un profil
  // Utilisation de isset car certaines valeurs peuvent être vides
  if (!empty($_POST['profileName']) AND isset($_POST['profileConf_excludeMajor']) AND isset($_POST['profileConf_exclude']) AND isset($_POST['profileConf_needRestart']) AND !empty($_POST['profileConf_keepCron']) AND !empty($_POST['profileConf_allowOverwrite']) AND !empty($_POST['profileConf_allowReposFilesOverwrite'])) {
    $profileName = validateData($_POST['profileName']);
    $profileConf_excludeMajor = validateData($_POST['profileConf_excludeMajor']);
    $profileConf_exclude = validateData($_POST['profileConf_exclude']);
    $profileConf_needRestart = validateData($_POST['profileConf_needRestart']);
    $profileConf_keepCron = validateData($_POST['profileConf_keepCron']);
    $profileConf_allowOverwrite = validateData($_POST['profileConf_allowOverwrite']);
    $profileConf_allowReposFilesOverwrite = validateData($_POST['profileConf_allowReposFilesOverwrite']);
    // On écrit dans le fichier de conf ce qui a été envoyé en POST :
    exec("sed -i 's/^EXCLUDE_MAJOR=.*/EXCLUDE_MAJOR=\"${profileConf_excludeMajor}\"/g' ${PROFILES_MAIN_DIR}/${profileName}/config");
    exec("sed -i 's/^EXCLUDE=.*/EXCLUDE=\"${profileConf_exclude}\"/g' ${PROFILES_MAIN_DIR}/${profileName}/config");
    exec("sed -i 's/^NEED_RESTART=.*/NEED_RESTART=\"${profileConf_needRestart}\"/g' ${PROFILES_MAIN_DIR}/${profileName}/config");
    exec("sed -i 's/^KEEP_CRON=.*/KEEP_CRON=\"${profileConf_keepCron}\"/g' ${PROFILES_MAIN_DIR}/${profileName}/config");
    exec("sed -i 's/^ALLOW_OVERWRITE=.*/ALLOW_OVERWRITE=\"${profileConf_allowOverwrite}\"/g' ${PROFILES_MAIN_DIR}/${profileName}/config");
    exec("sed -i 's/^ALLOW_REPOSFILES_OVERWRITE=.*/ALLOW_REPOSFILES_OVERWRITE=\"${profileConf_allowReposFilesOverwrite}\"/g' ${PROFILES_MAIN_DIR}/${profileName}/config");
    // Affichage d'un message
    printAlert("La configuration du profil $profileName a été modifée");
  }

  // Création d'un nouveau profil
  // trouver un moyen d'afficher une alerte si le profil existe déjà
  if (!empty($_POST['newProfile'])) {
    $newProfile = validateData($_POST['newProfile']);
    $error = 0;
    // On vérifie qu'un profil du même nom n'existe pas déjà
    if (file_exists("${PROFILES_MAIN_DIR}/${newProfile}")) {
      printAlert("Erreur : un profil du même nom ($newProfile) existe déjà");
      $error++;
    }
    // Si pas d'erreur alors on peut renommer le répertoire de profil
    if ($error === 0) {
      // Créer le répertoire du profil :
      exec("mkdir -p ${PROFILES_MAIN_DIR}/${newProfile}");
      // Créer le fichier de config :
      exec("touch ${PROFILES_MAIN_DIR}/${newProfile}/config");
      // Créer le fichier de config du profil avec des valeurs vides ou par défaut :
      exec("echo 'EXCLUDE_MAJOR=\"\"\nEXCLUDE=\"\"\nNEED_RESTART=\"\"\nKEEP_CRON=\"no\"\nALLOW_OVERWRITE=\"yes\"\nALLOW_REPOSFILES_OVERWRITE=\"yes\"' > ${PROFILES_MAIN_DIR}/${newProfile}/config");
      // Affichage d'un message
      printAlert("Le profil $newProfile a été créé");
    }
  }
?>

<body>
<?php include('common-header.inc.php'); ?>

<section class="mainSectionLeft">
  <!-- REPOS ACTIFS -->
  <section class="left">
      <?php include('common-repos-list.inc.php'); ?>
  </section>
  <section class="left">
      <!-- REPOS ARCHIVÉS-->
      <?php include('common-repos-archive-list.inc.php'); ?>
  </section>
</section>

<section class="mainSectionRight">
    <section class="right">
      <h5>GESTION DES PROFILS</h5>
      <p>Vous pouvez créer des profils de configuration pour vos serveurs clients utilisant <?php if ($OS_FAMILY == "Redhat") { echo "yum-update-auto"; } if ($OS_FAMILY == "Debian") { echo "apt-update-auto"; } ?>.<br>A chaque exécution d'une mise à jour, les clients récupèreront automatiquement leur configuration et leurs fichiers de repo depuis ce serveur de repo.</p>
      <p><b>Configuration de ce serveur :</b></p>
      <?php       
        // Récupération de la conf dans le fichier de conf serveur
        $serverConf_manageClientsConf = exec("grep '^MANAGE_CLIENTS_CONF=' ${PROFILE_SERVER_CONF} | cut -d'=' -f2 | sed 's/\"//g'");
        $serverConf_manageClients_reposConf = exec("grep '^MANAGE_CLIENTS_REPOSCONF=' ${PROFILE_SERVER_CONF} | cut -d'=' -f2 | sed 's/\"//g'");
      ?>
        
      <form action="profiles.php" method="post" autocomplete="off" class="background-gray">
        <table class="table-large">
          <tr>
            <td>URL d'accès aux profils</td>
            <td><input type="text" class="td-medium" value="<?php echo "$WWW_PROFILES_DIR_URL";?>" readonly /></td>
          </tr>
          <tr>
            <td>Famille d'OS</td>
            <td><input type="text" class="td-medium" value="<?php echo "$OS_FAMILY";?>" readonly /></td>
          </tr>
          <tr>
            <td>Nom de l'OS</td>
            <td><input type="text" class="td-medium" value="<?php echo "$OS_NAME";?>" readonly /></td>
          </tr>
          <tr>
            <td>Version d'OS</td>
            <td><input type="text" class="td-medium" value="<?php echo "$OS_VERSION";?>" readonly /></td>
          </tr>
          <?php
          if (!empty($RELEASEVER) AND $RELEASEVER !== $OS_VERSION) {
          echo '<tr>';
          echo '<td>Version de paquets gérée</td>';
          echo "<td><input type=\"text\" class=\"td-medium\" value=\"$RELEASEVER\" readonly /></td>";
          echo '</tr>';
          }
          ?>
          <tr>
            <td>Gérer la configuration des clients</td>
            <td class="td-medium">
              <input type="radio" id="serverConf_manageClientsConf_yes" name="serverConf_manageClientsConf" value="yes" <?php if ($serverConf_manageClientsConf == "yes") { echo "checked"; } ?> />
              <label for="serverConf_manageClientsConf_yes">Yes</label>
              <input type="radio" id="serverConf_manageClientsConf_no" name="serverConf_manageClientsConf" value="no" <?php if ($serverConf_manageClientsConf == "no") { echo "checked"; } ?>/>
              <label for="serverConf_manageClientsConf_no">No</label>
            </td>
          </tr>
          <tr>
            <td>Gérer la configuration des repos clients</td>
            <td class="td-medium">
              <input type="radio" id="serverConf_manageClients_reposConf_yes" name="serverConf_manageClients_reposConf" value="yes" <?php if ($serverConf_manageClients_reposConf == "yes") { echo "checked"; } ?>/>
              <label for="serverConf_manageClients_reposConf_yes">Yes</label>
              <input type="radio" id="serverConf_manageClients_reposConf_no" name="serverConf_manageClients_reposConf" value="no" <?php if ($serverConf_manageClients_reposConf == "no") { echo "checked"; } ?>/>
              <label for="serverConf_manageClients_reposConf_no">No</label>
            </td>
          </tr>
          <tr>
            <td colspan="100%"><button type="submit" class="button-submit-large-green">Enregistrer</button></td>
          </tr>
        </table>
      </form>

    <p><b>PROFILS</b></p>
    <?php
        // Affichage des profils et leur configuration
        $i = 0;
        $profilesNames = scandir($PROFILES_MAIN_DIR); // Récupération de tous les noms de profils
        sort($profilesNames); // Tri des profils afin de les afficher dans l'ordre alpha
        foreach($profilesNames as $profileName) {
          if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "_configurations") AND ($profileName != "_reposerver") AND ($profileName != "${PROFILE_SERVER_CONF}")) { // fix temporaire pour ne pas afficher les répertoires ../ et ./ (trouver une autre solution plus propre)
            echo '<form action="profiles.php" method="post" autocomplete="off">';
            echo '<table class="table-large">';
            // On veut pouvoir renommer les profils, donc il faut transmettre le nom de profil actuel (actualProfileName),
            echo "<input type=\"hidden\" name=\"actualProfileName\" value=\"${profileName}\" />";
            // ainsi qu'afficher ce même profil actuel dans un input type=text qui permettra d'en renseigner un nouveau (profileName) :
            echo '<tr>';
            echo '<td>';
            echo "<img src=\"icons/idcard.png\" class=\"icon\" /><input type=\"text\" value=\"${profileName}\" name=\"profileName\" class=\"input-medium invisibleInput\" />";
            echo '</td>';
            echo '<td class="td-fit">';
            echo "<a href=\"?action=deleteprofile&profileName=${profileName}\" title=\"Supprimer le profil ${profileName}\"><img class=\"icon-lowopacity\" src=\"icons/bin.png\" /></a>";
            echo "<a href=\"?action=duplicateprofile&profileName=${profileName}\" title=\"Créer un nouveau profil en dupliquant la configuration de $profileName\"><img class=\"icon-lowopacity\" src=\"icons/duplicate.png\" /></a>";
            echo '</td>';
            echo '</tr>';
            echo '</table>';
            echo '</form>';

            // Configuration de ce profil avec un div caché
            echo "<a href=\"#\" id=\"profilConfigurationToggleButton${i}\"><p><b>Configuration</b><img src=\"icons/chevron-circle-down.png\" class=\"icon\"/></p></a>";
            echo "<div id=\"profilConfigurationTbody${i}\" class=\"hide\">";

            echo '<form action="profiles.php" method="post" autocomplete="off">';
            // Il faut transmettre le nom du profil dans le formulaire, donc on ajoute un input caché avec le nom du profil
            echo "<input type=\"hidden\" name=\"profileName\" value=\"${profileName}\" />";
            echo '<table class="table-large background-gray">';
            if ($OS_FAMILY == "Redhat") {
              echo '<tr>';
              echo '<td><b>Repo</b></td>';
              echo '<td></td>';
              echo '</tr>';
            }
            if ($OS_FAMILY == "Debian") {
              echo '<tr>';
              echo '<td><b>Repo</b></td>';
              echo '<td><b>Distribution</b></td>';
              echo '<td><b>Section</b></td>';
              echo '<td></td>';
              echo '</tr>';
            }
            // Scan du répertoire du profil pour récupérer ses fichiers .repo ou .list selon le système
            $profileName_dir = "${PROFILES_MAIN_DIR}/${profileName}";
            $repoConfFiles = scandir($profileName_dir);
            
            // Pour chaque répertoire de profil sur le serveur, on récupère les noms de fichier de conf (.repo ou .list selon l'OS)
            foreach($repoConfFiles as $repoFile) {
              if (($repoFile != "..") AND ($repoFile != ".") AND ($repoFile != "config")){ // Ne pas afficher les répertoires ../ et ./ ni le fichier de conf du profil
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
                  echo "<td>${repoName}</td>";
                  echo "<td class=\"td-fit\"><a href=\"?action=deleteProfileRepo&profileName=${profileName}&repoName=${repoName}\" title=\"Retirer le repo ${repoName}\"><img class=\"icon-lowopacity\" src=\"icons/bin.png\" /></a></td>";
                }
                if ($OS_FAMILY == "Debian") {
                  echo "<td>${repoName}</td>";
                  echo "<td>${repoDist}</td>";
                  echo "<td>${repoSection}</td>";
                  echo "<td class=\"td-fit\"><a href=\"?action=deleteProfileRepo&profileName=${profileName}&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}\" title=\"Retirer la section ${repoSection}\"><img class=\"icon-lowopacity\" src=\"icons/bin.png\" /></a></td>";
                }
                echo "</tr>";
              }                      
              // on en profite pour récupérer la conf du profil contenue dans le fichier "config", ce sera utile pour la suite
              // voir si on peut faire autrement qu'avec une commande exec (voir du côté de php fopen)
              $profileConf_excludeMajor = exec("grep '^EXCLUDE_MAJOR=' ${PROFILES_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
              $profileConf_exclude = exec("grep '^EXCLUDE=' ${PROFILES_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
              $profileConf_needRestart = exec("grep '^NEED_RESTART=' ${PROFILES_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
              $profileConf_keepCron = exec("grep '^KEEP_CRON=' ${PROFILES_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
              $profileConf_allowOverwrite = exec("grep '^ALLOW_OVERWRITE=' ${PROFILES_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
              $profileConf_allowReposFilesOverwrite = exec("grep '^ALLOW_REPOSFILES_OVERWRITE=' ${PROFILES_MAIN_DIR}/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
            }

            if ($OS_FAMILY == "Redhat") {
              echo '<tr>';
              echo '<td><input type="text" name="addProfileRepo" placeholder="Nom du repo" /></td>';
              echo '<td class="td-fit"><button type="submit" class="button-submit-xxsmall-blue"><b>+</b></button></td>';
              echo '</tr>';
            }

            if ($OS_FAMILY == "Debian") {
              echo '<tr>';
              echo '<td><input type="text" name="addProfileRepo" class="input-small" placeholder="Nom du repo" /></td>';
              echo '<td><input type="text" name="addProfileRepoDist" class="input-small" placeholder="Distribution" /></td>';
              echo '<td><input type="text" name="addProfileRepoSection" class="input-small" placeholder="Section" /></td>';
              echo '<td class="td-fit"><button type="submit" class="button-submit-xxsmall-blue"><b>+</b></button></td>';
              echo '</tr>';
            }
            echo '</table>';
            echo '</form>';
            echo '<br>';

            // Si le serveur est configuré pour gérer la conf des serveurs clients alors on affiche la configuration pour chaque profil
            if ($serverConf_manageClientsConf == "yes") {
              echo '<form action="profiles.php" method="post" autocomplete="off">';
              // Il faut transmettre le nom du profil dans le formulaire, donc on ajoute un input caché avec le nom du profil
              echo "<input type=\"hidden\" name=\"profileName\" value=\"${profileName}\" />";
              echo '<table class="table-large background-gray">';
              echo '<tr>';
              echo '<td class="td-fit" title="Paquets à exclure uniquement si sa nouvelle version est majeure">Paquets à exclure en cas de version majeure</td>';
              echo "<td><input type=\"text\" name=\"profileConf_excludeMajor\" value=\"${profileConf_excludeMajor}\" /></td>";
              echo '</tr>';
              echo '<tr>';
              echo '<td class="td-fit" title="Paquets à exclure quelque soit la version proposée">Paquets à exclure (toute version)</td>';
              echo "<td><input type=\"text\" name=\"profileConf_exclude\" value=\"${profileConf_exclude}\" /></td>";
              echo '</tr>';
              echo '<tr>';
              echo '<td class="td-fit" title="Services nécessitant un redémarrage après mise à jour">Services à redémarrer en cas de mise à jour</td>';
              echo "<td><input type=\"text\" name=\"profileConf_needRestart\" value=\"${profileConf_needRestart}\" /></td>";
              echo '</tr>';
              echo '<tr>';
              echo '<td class="td-fit" title="Conserver ou non la tâche cron après exécution de la mise à jour">Conserver la tâche cron</td>';
              echo '<td>';
              echo "<input type=\"radio\" id=\"profileConf_keepCron_${profileName}_yes\" name=\"profileConf_keepCron\" value=\"yes\"";if ($profileConf_keepCron == "yes") { echo 'checked />'; } else { echo ' />'; }
              echo "<label for=\"profileConf_keepCron_${profileName}_yes\">Yes</label>";
              echo "<input type=\"radio\" id=\"profileConf_keepCron_${profileName}_no\" name=\"profileConf_keepCron\" value=\"no\"";if ($profileConf_keepCron == "no") { echo 'checked />'; } else { echo ' />'; }
              echo "<label for=\"profileConf_keepCron_${profileName}_no\">No</label>";
              echo '</td>';
              echo '</tr>';
              echo '<tr>';
              echo '<td class="td-fit" title="Autoriser linux-autoupdate à récupérer et écraser sa conf à chaque exécution">Autoriser la mise à jour auto. de la configuration</td>';
              echo '<td>';
              echo "<input type=\"radio\" id=\"profileConf_allowOverwrite_${profileName}_yes\" name=\"profileConf_allowOverwrite\" value=\"yes\"";if ($profileConf_allowOverwrite == "yes") { echo 'checked />'; } else { echo ' />'; }
              echo "<label for=\"profileConf_allowOverwrite_${profileName}_yes\">Yes</label>";
              echo "<input type=\"radio\" id=\"profileConf_allowOverwrite_${profileName}_no\" name=\"profileConf_allowOverwrite\" value=\"no\"";if ($profileConf_allowOverwrite == "no") { echo 'checked />'; } else { echo ' />'; }
              echo "<label for=\"profileConf_allowOverwrite_${profileName}_no\">No</label>";
              echo '</td>';
              echo '</tr>';
              echo '<tr>';
              echo '<td class="td-fit" title="Autoriser linux-autoupdate à récupérer automatiquement les fichiers .list ou .repo de son profil">Autoriser la mise à jour auto. des fichiers de repo</td>';
              echo '<td>';
              echo "<input type=\"radio\" id=\"profileConf_allowReposFilesOverwrite_${profileName}_yes\" name=\"profileConf_allowReposFilesOverwrite\" value=\"yes\"";if ($profileConf_allowReposFilesOverwrite == "yes") { echo 'checked />'; } else { echo ' />'; }
              echo "<label for=\"profileConf_allowReposFilesOverwrite_${profileName}_yes\">Yes</label>";
              echo "<input type=\"radio\" id=\"profileConf_allowReposFilesOverwrite_${profileName}_no\" name=\"profileConf_allowReposFilesOverwrite\" value=\"no\"";if ($profileConf_allowReposFilesOverwrite == "no") { echo 'checked />'; } else { echo ' />'; }
              echo "<label for=\"profileConf_allowReposFilesOverwrite_${profileName}_no\">No</label>";
              echo '</td>';
              echo '</tr>';
              echo '<tr>';        
              echo '<td colspan="100%"><button type="submit" class="button-submit-large-green">Enregistrer</button></td>';
              echo '</tr>';
              echo '</table>';
              echo '</form>';
              echo '</div>';
              echo '<hr>';
              // Afficher ou masquer la div 'Configuration' :
              echo "<script>";
              echo "$(document).ready(function(){";
                echo "$(\"a#profilConfigurationToggleButton${i}\").click(function(){";
                  echo "$(\"div#profilConfigurationTbody${i}\").slideToggle(150);";
                  echo '$(this).toggleClass("open");';
                echo "});";
              echo "});";
              echo "</script>";
              $i++;
            }
          }
        }?>
    </table>
    <br>
    <p>Ajouter un nouveau profil :</p>
    <form action="profiles.php" method="post" autocomplete="off">
      <table class="table-large">
        <tr>
          <td>
            <input type="text" name="newProfile" />
            <button type="submit" class="button-submit-xsmall-blue">Ajouter</button>
          </td>
        </tr>
      </table>
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