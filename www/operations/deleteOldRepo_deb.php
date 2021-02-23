<?php
function deleteOldRepo_deb($repoName, $repoDist, $repoSection, $repoDate) {
  global $REPOS_ARCHIVE_LIST;
  global $REPOS_DIR;

  writeLog("<h5>SUPPRIMER UNE SECTION ARCHIVÉE</h5>");
  writeLog("<table>");
  writeLog("<tr>
  <td>Section :</td>
  <td><b>$repoSection ($repoDate)</b></td>
  </tr>
  <tr>
  <td>Nom du repo :</td>
  <td><b>$repoName</b></td>
  </tr>
  <tr>
  <td>Distribution :</td>
  <td><b>$repoDist</b></td>
  </tr>");

  // On vérifie que la section renseignée est bien présente dans le fichier repos-archive.list, si oui alors on peut commencer l'opération
  $checkIfRepoExists = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Date=\"${repoDate}\"' ${REPOS_ARCHIVE_LIST}");
  if (empty($checkIfRepoExists)) {
    $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>la section de repo $repoSection archivée n'existe pas</td></tr>";
    writeLog("$msg"); // Enregistre l'erreur dans le log
    echo "$msg";      // Affiche l'erreur à l'utilisateur
    return 1;
  }

  // Suppression de la section archivée
  if (file_exists("${REPOS_DIR}/${repoName}/${repoDist}/archived_${repoDate}_${repoSection}")) {
    exec("rm ${REPOS_DIR}/${repoName}/${repoDist}/archived_${repoDate}_${repoSection} -rf");
  }

  // Mise à jour des informations dans repos-archive.list
  $repos_archives_content = file_get_contents("$REPOS_ARCHIVE_LIST");
  $repos_archives_content = preg_replace("/Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Date=\"${repoDate}\".*/", "", $repos_archives_content);
  file_put_contents("$REPOS_ARCHIVE_LIST", $repos_archives_content);

  $msg = '<tr><td colspan="100%"><br>Supprimée <span class="greentext">✔</span></td></tr>';
  writeLog("$msg"); // Enregistre le message dans le log
  echo "$msg";      // Affiche le message à l'utilisateur
}
?>