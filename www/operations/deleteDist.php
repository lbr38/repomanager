<?php
function deleteDist($repoName, $repoDist) {
  global $REPOS_LIST;
  global $REPOS_ARCHIVE_LIST;
  global $GROUPS_CONF;
  global $REPOS_DIR;

  writeLog("<h5>SUPPRIMER UNE DISTRIBUTION</h5>");
  writeLog("<table>");
  writeLog("<tr>
		<td>Nom du repo :</td>
		<td><b>$repoName</b></td>
	  </tr>
    <tr>
		<td>Distribution :</td>
		<td><b>$repoDist</b></td>
	  </tr>");

  // On vérifie que le repo renseigné est bien présent dans le fichier repo_sys/spec.conf, alors on peut commencer l'opération
  $checkIfRepoExists = exec("egrep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\"' ${REPOS_LIST}");
  if (empty($checkIfRepoExists)) {
    $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>le repo $repoName (distribution $repoDist) n'existe pas</td></tr>";
    writeLog("$msg"); // Enregistre l'erreur dans le log
    echo "$msg";      // Affiche l'erreur à l'utilisateur
    return 1;
  }

  // Suppression du répertoire de la distribution. Comme PHP c'est de la merde et qu'il ne sait pas supprimer un répertoire non-vide, obligé d'utiliser une cmd système
  exec("rm ${REPOS_DIR}/${repoName}/${repoDist} -rf");
  
  // On supprime le répertoire parent (repo) si celui-ci est vide après la suppression de la distribution :
  $checkIfDirIsEmpty = exec("ls -A ${REPOS_DIR}/${repoName}/");
  if (empty($checkIfDirIsEmpty)) {
    exec("rm ${REPOS_DIR}/${repoName}/ -rf");
  }

  // On mets à jour les infos dans le fichier repos.list ainsi que le fichier repos-archives.list en supprimant la ligne du repo
  $repos_list_content = file_get_contents("$REPOS_LIST");
  $repos_archives_content = file_get_contents("$REPOS_ARCHIVE_LIST");
  $repos_list_content = preg_replace("/Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\".*/", "", $repos_list_content);
  $repos_archives_content = preg_replace("/Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\".*/", "", $repos_archives_content);
  file_put_contents("$REPOS_LIST", $repos_list_content);
  file_put_contents("$REPOS_ARCHIVE_LIST", $repos_archives_content);

  // Comme on a a supprimé toute une distribution, on a forcément supprimé toutes ses sections. On retire donc toutes les occurences de la distribution dans le fichier de groupes
  $groups_content = file_get_contents("$GROUPS_CONF");
  $groups_content = preg_replace("/Name=\"${repoName}\",Dist=\"${repoDist}\".*/", "", $groups_content);
  file_put_contents("$GROUPS_CONF", $groups_content);

  $msg = '<tr><td colspan="100%"><br>Supprimée <span class="greentext">✔</span></td></tr>';
  writeLog("$msg"); // Enregistre le message dans le log
  echo "$msg";      // Affiche le message à l'utilisateur
}
?>