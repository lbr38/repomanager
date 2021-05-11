<?php
function deleteOldRepo_rpm($repoName, $repoDate) {
  global $REPOS_ARCHIVE_LIST;
  global $REPOS_DIR;

  writeLog("<h5>SUPPRIMER UN REPO ARCHIVÉ</h5>");
  writeLog("<table>");
  writeLog("<tr>
  <td>Nom du repo :</td>
  <td><b>$repoName</b></td>
  </tr>");

  // On vérifie que le repo renseigné est bien présent dans le fichier repos-archive.list, si oui alors on peut commencer l'opération
  $checkIfRepoExists = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Date=\"${repoDate}\"' ${REPOS_ARCHIVE_LIST}");
  if (empty($checkIfRepoExists)) {
    $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>le repo $repoName archivé n'existe pas</td></tr>";
    writeLog("$msg"); // Enregistre l'erreur dans le log
    echo "$msg";      // Affiche l'erreur à l'utilisateur
    return 1;
  }

  // Suppression du repo archivé
  if (file_exists("${REPOS_DIR}/archived_${repoDate}_${repoName}")) {
    exec("rm ${REPOS_DIR}/archived_${repoDate}_${repoName} -rf");
  }

  // Mise à jour des informations dans repos-archive.list
  $repos_archives_content = file_get_contents("$REPOS_ARCHIVE_LIST");
  $repos_archives_content = preg_replace("/Name=\"${repoName}\",Realname=\".*\",Date=\"${repoDate}\".*/", "", $repos_archives_content);
  file_put_contents("$REPOS_ARCHIVE_LIST", $repos_archives_content);

  $msg = '<tr><td colspan="100%"><br>Supprimée <span class="greentext">✔</span></td></tr>';
  writeLog("$msg"); // Enregistre le message dans le log
  echo "$msg";      // Affiche le message à l'utilisateur
}
?>