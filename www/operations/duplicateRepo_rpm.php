<?php
function duplicateRepo_rpm($repoName, $repoEnv, $repoNewName, $repoGroup, $repoDescription) {
    global $REPOS_LIST;
    global $GROUPS_CONF;
    global $REPOS_DIR;
    global $WWW_DIR;
    global $WWW_USER;
    global $DEFAULT_ENV;
  
    writeLog("<h5>DUPLIQUER UN REPO</h5>");
    writeLog("<table>");

    if (empty($repoName)) {
      $msg = '<tr><td colspan="100%"><br><span class="redtext">Erreur : </span>le nom du repo à dupliquer est vide</td></tr>';
      writeLog("$msg"); // Enregistre l'erreur dans le log
      echo "$msg";      // Affiche l'erreur à l'utilisateur
      return 1;
    }
    if (empty($repoEnv)) { 
      $msg = '<tr><td colspan="100%"><br><span class="redtext">Erreur : </span>l\'environnement du repo à dupliquer est vide</td></tr>';
      writeLog("$msg"); // Enregistre l'erreur dans le log
      echo "$msg";      // Affiche l'erreur à l'utilisateur
      return 1; 
    }
    if (empty($repoNewName)) {
      $msg = '<tr><td colspan="100%"><br><span class="redtext">Erreur : </span>le nouveau nom du repo à dupliquer est vide</td></tr>';
      writeLog("$msg"); // Enregistre l'erreur dans le log
      echo "$msg";      // Affiche l'erreur à l'utilisateur
      return 1; 
    }
    if (empty($repoGroup)) {
      $msg = '<tr><td colspan="100%"><br><span class="redtext">Erreur : </span>le nom du groupe est vide</td></tr>';
      writeLog("$msg"); // Enregistre l'erreur dans le log
      echo "$msg";      // Affiche l'erreur à l'utilisateur
      return 1; 
    }
    if (empty($repoDescription)) {
      $msg = '<tr><td colspan="100%"><br><span class="redtext">Erreur : </span>la description est vide</td></tr>';
      writeLog("$msg"); // Enregistre l'erreur dans le log
      echo "$msg";      // Affiche l'erreur à l'utilisateur
      return 1; 
    }
    // Si le groupe est égale à 'nogroup' alors il doit être laissé vide
    if ($repoGroup == "nogroup") { $repoGroup = ''; }
    // Si la description est égale à 'nodescription' alors elle doit être laissée vide
    if ($repoDescription == "nodescription") { $repoDescription = ''; }
  
    writeLog("<tr>
		<td>Nom du repo :</td>
		<td><b>$repoName</b></td>
	  </tr>
    <tr>
		<td>Nouveau nom du repo :</td>
		<td><b>$repoNewName</b></td>
	  </tr>");
    if (!empty($repoDescription)) {
      writeLog("<tr>
      <td>Description :</td>
      <td><b>$repoDescription</b></td>
      </tr>");
    }
    if (!empty($repoGroup)) {
      writeLog("<tr>
      <td>Ajout à un groupe :</td>
      <td><b>$repoGroup</b></td>
      </tr>");
    }

    // On vérifie que le repo source (celui qui sera copié) existe bien :
    $checkIfRepoExists = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' $REPOS_LIST");
    if (empty($checkIfRepoExists)) {
      $msg = '<tr><td colspan="100%"><br><span class="redtext">Erreur :</span>le repo à dupliquer n\'existe pas</td></tr>';
      writeLog("$msg"); // Enregistre l'erreur dans le log
      echo "$msg";      // Affiche l'erreur à l'utilisateur
      return 1;
    }
   
    // On vérifie qu'un repo du même nom n'existe pas déjà à l'env par défaut $DEFAULT_ENV (car la copie crée forcément une nouvelle section à l'env par défaut)
    $checkIfRepoAlreadyExists = exec("egrep '^Name=\"${repoNewName}\",Realname=\".*\",Env=\"${DEFAULT_ENV}\"' $REPOS_LIST");
    if (!empty($checkIfRepoAlreadyExists)) {
      $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>un repo $repoNewName existe déjà</td></tr>";
      writeLog("$msg"); // Enregistre l'erreur dans le log
      echo "$msg";      // Affiche l'erreur à l'utilisateur
      return 1;
    }
    
    // On récupère la date et le host du repo qu'on va dupliquer :
    $repoDate = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' $REPOS_LIST | awk -F ',' '{print $4}' | cut -d'=' -f2 | sed 's/\"//g'");
    $repoRealname = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' $REPOS_LIST | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
    if (empty($repoDate)) {
      $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>impossible de déterminer la date du repo $repoName</td></tr>";
      writeLog("$msg"); // Enregistre l'erreur dans le log
      echo "$msg";      // Affiche l'erreur à l'utilisateur
      return 1;
    }
    if (empty($repoRealname)) {
      $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>impossible de déterminer le repo source du repo $repoName</td></tr>";
      writeLog("$msg"); // Enregistre l'erreur dans le log
      echo "$msg";      // Affiche l'erreur à l'utilisateur
      return 1;
    }
    
    // Création du nouveau répertoire avec le nouveau nom du repo :
    if (!file_exists("${REPOS_DIR}/${repoDate}_${repoNewName}")) {
      if (!mkdir("${REPOS_DIR}/${repoDate}_${repoNewName}", 0770, true)) {
        $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>impossible de créer le répertoire ${REPOS_DIR}/${repoNewName}</td></tr>";
        writeLog("$msg"); // Enregistre l'erreur dans le log
        echo "$msg";      // Affiche l'erreur à l'utilisateur
        return 1;
      }
    }
    
    // Copie du contenu du repo
    // Anti-slash devant la commande cp pour forcer l'écrasement :
    exec("\cp -r ${REPOS_DIR}/${repoDate}_${repoName} ${REPOS_DIR}/${repoDate}_${repoNewName}");
  
    // Création du lien symbolique :
    exec("cd ${REPOS_DIR}/ && ln -s ${repoDate}_${repoNewName}/ ${repoNewName}_${DEFAULT_ENV}");
    
    // Mise à jour des informations dans repos.list :
    $content = file_get_contents($REPOS_LIST);
    $content = "${content}Name=\"${repoNewName}\",Realname=\"${repoRealname}\",Env=\"${DEFAULT_ENV}\",Date=\"${repoDate}\",Description=\"${repoDescription}\"";
    file_put_contents("$REPOS_LIST", $content . PHP_EOL);
    
    // Application des droits sur le nouveau repo créé :
    exec("find ${REPOS_DIR}/${repoDate}_${repoNewName}/ -type f -exec chmod 0660 {} \;");
    exec("find ${REPOS_DIR}/${repoDate}_${repoNewName}/ -type d -exec chmod 0770 {} \;");
    exec("chown -R ${WWW_USER}:repomanager ${REPOS_DIR}/${repoDate}_${repoNewName}/");
    
    // Ajout de la section à un groupe si un groupe a été renseigné
    if (!empty($repoGroup)) {
      // Appel de la fonction permettant l'ajout à un groupe :
      addRepoToGroup($repoNewName, $repoGroup);
    }

    // Génération du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
    require("${WWW_DIR}/functions/generateConf.php");
    if (generateConf_rpm($repoNewName, 'default') === false) {
      $msg = '<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>impossible de générer le fichier de conf .list sur le serveur, une variable nécéssaire à la génération est vide</td></tr>';
      writeLog("$msg"); // Enregistre le message dans le log
      echo "$msg";      // Affiche le message à l'utilisateur
    }

    $msg = '<tr><td colspan="100%"><br>Dupliqué <span class="greentext">✔</span></td></tr>';
    writeLog("$msg"); // Enregistre le message dans le log
    echo "$msg";      // Affiche le message à l'utilisateur
  }
?>