<?php
function deleteRepo_deb($repoName) {
  global $REPOS_LIST;
  global $REPOS_ARCHIVE_LIST;
  global $GROUPS_CONF;
  global $REPOS_DIR;

  writeLog("<h5>SUPPRIMER UN REPO</h5>");
  writeLog("<table>");
  writeLog("<tr>
  <td>Nom du repo :</td>
  <td><b>$repoName</b></td>
  </tr>");

  // On vérifie que le repo renseigné est bien présent dans le fichier repos.list, si oui alors on peut commencer l'opération
  $checkIfRepoExists = exec("grep '^Name=\"${repoName}\"' ${REPOS_LIST}");
  if (empty($checkIfRepoExists)) {
    $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>le repo $repoName n'existe pas</td></tr>";
    writeLog("$msg"); // Enregistre l'erreur dans le log
    echo "$msg";      // Affiche l'erreur à l'utilisateur
    return 1;
  }

  // Suppression du répertoire du repo
  exec("rm ${REPOS_DIR}/${repoName} -rf");

  // On mets à jour les infos dans le fichier repos.list ainsi que le fichier repos-archives.list en supprimant la ligne du repo
  $repos_list_content = file_get_contents("$REPOS_LIST");
  $repos_archives_content = file_get_contents("$REPOS_ARCHIVE_LIST");
  $repos_list_content = preg_replace("/Name=\"${repoName}\".*/", "", $repos_list_content);
  $repos_archives_content = preg_replace("/Name=\"${repoName}\".*/", "", $repos_archives_content);
  file_put_contents("$REPOS_LIST", $repos_list_content);
  file_put_contents("$REPOS_ARCHIVE_LIST", $repos_archives_content);

  // Comme on a a supprimé tout un repo, on a forcément supprimé toutes ses distributions et sections (sur Debian). On retire donc toutes les occurences du repo dans le fichier de groupes
  // Suppression du repo du fichier de groupes
  deleteRepoFromAllGroup($repoName);

  $msg = '<tr><td colspan="100%"><br>Supprimé <span class="greentext">✔</span></td></tr>';
  writeLog("$msg"); // Enregistre le message dans le log
  echo "$msg";      // Affiche le message à l'utilisateur
}
?>