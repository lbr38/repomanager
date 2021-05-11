<?php

function changeEnv_rpm($repoName, $repoEnv, $repoNewEnv, $repoDescription) {
  global $REPOS_LIST;
  global $REPOS_ARCHIVE_LIST;
  global $GROUPS_CONF;
  global $REPOS_DIR;
  global $DEFAULT_ENV;
  global $LAST_ENV;

  // Si la description est égale à 'nodescription' alors elle doit être laissée vide
  if ($repoDescription == "nodescription") { $repoDescription = ''; }

  writeLog("<h5>NOUVEL ENVIRONNEMENT DE REPO</h5>
  <table>
  <tr>
    <td>Nom du repo :</td>
	  <td><b>$repoName</b></td>
  </tr>
  <tr>
  <td>Environnement source :</td>");
  if ($DEFAULT_ENV === $LAST_ENV) { // Cas où il n'y a qu'un seul env
    writeLog("<td class=\"td-redbackground\"><span>$repoEnv</span></td></tr>");
  } elseif ($repoEnv === $DEFAULT_ENV) { 
    writeLog("<td class=\"td-whitebackground\"><span>$repoEnv</span></td></tr>");
  } elseif ($repoEnv === $LAST_ENV) {
    writeLog("<td class=\"td-redbackground\"><span>$repoEnv</span></td></tr>");
  } else {
    writeLog("<td class=\"td-whitebackground\"><span>$repoEnv</span></td></tr>");
  }
  writeLog("<tr>
	<td>Nouvel environnement :</td>");
  if ($DEFAULT_ENV === $LAST_ENV) { // Cas où il n'y a qu'un seul env
    writeLog("<td class=\"td-redbackground\"><span>$repoNewEnv</span></td></tr>");
  } elseif ($repoNewEnv === $DEFAULT_ENV) { 
    writeLog("<td class=\"td-whitebackground\"><span>$repoNewEnv</span></td></tr>");
  } elseif ($repoNewEnv === $LAST_ENV) {
    writeLog("<td class=\"td-redbackground\"><span>$repoNewEnv</span></td></tr>");
  } else {
    writeLog("<td class=\"td-whitebackground\"><span>$repoNewEnv</span></td></tr>");
  }
  if (!empty($repoDescription)) {
    writeLog("<tr>
    <td>Description :</td>
    <td><b>$repoDescription</b></td>
    </tr>");
  }

  // On vérifie si le repo est présent dans repos.list
  $checkIfRepoExists = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' $REPOS_LIST");
  if (empty($checkIfRepoExists)) {
    $msg = '<tr><td colspan="100%"><br><span class="redtext">Erreur :</span> ce repo n\'existe pas</td></tr>';
    writeLog("$msg"); // Enregistre l'erreur dans le log
    echo "$msg";      // Affiche l'erreur à l'utilisateur
    return false;
  }

	// Récupère la date vers laquelle on va faire pointer le nouvel env
  $repoDate = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' $REPOS_LIST | awk -F ',' '{print $4}' | cut -d'=' -f2 | sed 's/\"//g'");
	// Récupère le nom du repo source
  $repoSource = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' $REPOS_LIST | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
	
	// Si on n'a pas transmis de description, on va conserver celle actuelle si existe. Cependant si il n'y a pas de description ou qu'aucun repo n'existe actuellement dans l'env $repoNewEnv, alors le grep ne renverra rien et la description restera vide
  if (empty($repoDescription)) {
    $repoDescription = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoNewEnv}\"' $REPOS_LIST | awk -F ',' '{print $5}' | cut -d'=' -f2 | sed 's/\"//g'");
  }

	// Dernière vérif : on vérifie que le repo n'est pas déjà dans l'environnement souhaité (par exemple fait par quelqu'un d'autre), dans ce cas on annule l'opération
  $checkIfRepoExists = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoNewEnv}\",Date=\"${repoDate}\"' $REPOS_LIST");
  if (!empty($checkIfRepoExists)) {
    $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur :</span> ce repo est déjà en ${repoNewEnv} au ${repoDate}</td></tr>";
    writeLog("$msg"); // Enregistre l'erreur dans le log
    echo "$msg";      // Affiche l'erreur à l'utilisateur
    return false;
  }


  // TRAITEMENT 

	// Deux cas possibles : 
	// - ce repo n'avait pas de version dans l'environnement cible, on crée simplement un lien symbo
	// - ce repo avait déjà une version dans l'environnement cible, on modifie le lien symbo et on passe la version précédente en archive
  $checkIfRepoExists = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoNewEnv}\"' $REPOS_LIST");
  
  // Cas 1 : pas de version déjà en $repoNewEnv
  if (empty($checkIfRepoExists)) {
    // Suppression du lien symbolique (on sait jamais si il existe)
    if (file_exists("${REPOS_DIR}/${repoName}_${repoNewEnv}")) {
      unlink("${REPOS_DIR}/${repoName}_${repoNewEnv}");
    }

    // Création du lien symbolique
    exec("cd ${REPOS_DIR}/ && ln -s ${repoDate}_${repoName}/ ${repoName}_${repoNewEnv}");

		// Mise à jour des informations dans repos.list :"
		file_put_contents("$REPOS_LIST", "Name=\"${repoName}\",Realname=\"${repoSource}\",Env=\"${repoNewEnv}\",Date=\"${repoDate}\",Description=\"${repoDescription}\"" . PHP_EOL, FILE_APPEND);
  }

	// Cas 2 : Il y a déjà une version en $repoNewEnv qui va donc passer en archive. Modif du lien symbo + passage de la version précédente en archive :
  if (!empty($checkIfRepoExists)) {
    // Suppression du lien symbolique
    if (file_exists("${REPOS_DIR}/${repoName}_${repoNewEnv}")) {
      unlink("${REPOS_DIR}/${repoName}_${repoNewEnv}");
    }

    // Création du lien symbolique
    exec("cd ${REPOS_DIR}/ && ln -s ${repoDate}_${repoName}/ ${repoName}_${repoNewEnv}");

    // Passage de l'ancienne version de $repoNewEnv en archive
    $old_repoDate = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoNewEnv}\"' $REPOS_LIST | awk -F ',' '{print $4}' | cut -d'=' -f2 | sed 's/\"//g'");
    $old_repoDescription = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoNewEnv}\"' $REPOS_LIST | awk -F ',' '{print $5}' | cut -d'=' -f2 | sed 's/\"//g'");
    if (!rename("${REPOS_DIR}/${old_repoDate}_${repoName}", "${REPOS_DIR}/archived_${old_repoDate}_${repoName}")) {
      $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur :</span> un problème est survenu lors du passage de l'ancienne version du $old_repoDate en archive</td></tr>";
      writeLog("$msg"); // Enregistre l'erreur dans le log
      echo "$msg";      // Affiche l'erreur à l'utilisateur
      return false;
    }

    // Mise à jour des informations dans repos.list
    $repos_list_content = file_get_contents("$REPOS_LIST");
    $repos_list_content = preg_replace("/Name=\"${repoName}\",Realname=\"${repoSource}\",Env=\"${repoNewEnv}\",Date=\"${old_repoDate}\",Description=\".*\"/", "", $repos_list_content);
    file_put_contents("$REPOS_LIST", $repos_list_content);
		file_put_contents("$REPOS_LIST", "Name=\"${repoName}\",Realname=\"${repoSource}\",Env=\"${repoNewEnv}\",Date=\"${repoDate}\",Description=\"${repoDescription}\"" . PHP_EOL, FILE_APPEND);

		// Mise à jour des informations dans repos-archive.list :
    file_put_contents("$REPOS_ARCHIVE_LIST", "Name=\"${repoName}\",Realname=\"${repoSource}\",Date=\"${old_repoDate}\",Description=\"${old_repoDescription}\"" . PHP_EOL, FILE_APPEND);

    // Application des droits sur la section archivée
    exec("find ${REPOS_DIR}/archived_${old_repoDate}_${repoName}/ -type f -exec chmod 0660 {} \;");
    exec("find ${REPOS_DIR}/archived_${old_repoDate}_${repoName}/ -type d -exec chmod 0770 {} \;");
  }

	// Application des droits sur la section modifiée
	exec("find ${REPOS_DIR}/${repoDate}_${repoName}/ -type f -exec chmod 0660 {} \;");
  exec("find ${REPOS_DIR}/${repoDate}_${repoName}/ -type d -exec chmod 0770 {} \;");

  $msg = '<tr><td colspan="100%"><br>Terminé <span class="greentext">✔</span></td></tr>';
  writeLog("$msg"); // Enregistre le message dans le log
  echo "$msg";      // Affiche le message à l'utilisateur

  // Appel de la fonction cleanArchives pour supprimer les repos archivés
  ob_start();
  cleanArchives();
  $msg = ob_get_clean();
  writeLog("$msg"); // Enregistre le message dans le log
  
  return 0;
}
?>