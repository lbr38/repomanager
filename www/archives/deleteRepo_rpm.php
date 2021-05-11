<?php
function deleteRepo_rpm($repoName, $repoEnv) {
  global $REPOS_LIST;
  global $REPOS_ARCHIVE_LIST;
  global $GROUPS_CONF;
  global $REPOS_DIR;
  global $WWW_DIR;
  
  writeLog("<h5>SUPPRIMER UN REPO</h5>");
  writeLog("<table>");
  writeLog("<tr>
  <td>Nom du repo :</td>
  <td><b>$repoName ($repoEnv)</b></td>
  </tr>");

  // On vérifie que le repo renseigné est bien présent dans le fichier repos.list, si oui alors on peut commencer l'opération
  $checkIfRepoExists = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' ${REPOS_LIST}");
  if (empty($checkIfRepoExists)) {
    $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>le repo $repoName ($repoEnv) n'existe pas</td></tr>";
    writeLog("$msg"); // Enregistre l'erreur dans le log
    echo "$msg";      // Affiche l'erreur à l'utilisateur
    return 1;
  }

  // Récupération de la date du repo
  $repoDate = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' $REPOS_LIST | awk -F ',' '{print $4}' | cut -d'=' -f2 | sed 's/\"//g'");
  if (empty($repoDate)) {
    $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>impossible de récupérer la date du repo</td></tr>";
    writeLog("$msg"); // Enregistre l'erreur dans le log
    echo "$msg";      // Affiche l'erreur à l'utilisateur
    return 1;
  }

  // Suppression du lien symbolique du repo
  if (!unlink("${REPOS_DIR}/${repoName}_${repoEnv}"))  {
    $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>problème lors de la suppression du repo</td></tr>";
    writeLog("$msg"); // Enregistre l'erreur dans le log
    echo "$msg";      // Affiche l'erreur à l'utilisateur
    return 1;
  }

  // On mets à jour les infos dans le fichier repos.list
  $repos_list_content = file_get_contents("$REPOS_LIST");
  $repos_list_content = preg_replace("/Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\",Date=\"${repoDate}\".*/", "", $repos_list_content);
  file_put_contents("$REPOS_LIST", $repos_list_content);

  // Vérifications avant suppression définitive du miroir :
  $checkIfMirrorIsUsed = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\".*\",Date=\"${repoDate}\"' ${REPOS_LIST}");
  // Si la version du repo n'est plus utilisée par un autre env (nom du repo + date du repo n'apparait plus dans le fichier) alors on supprime le répertoire du repo
  if (empty($checkIfMirrorIsUsed)) {
    exec("rm ${REPOS_DIR}/${repoDate}_${repoName}/ -rf");
  }

  // Si il n'y a plus du tout de trace du repo dans le fichier de conf, alors on peut supprimer son fichier de conf repo, et on peut le retirer des groupes où il est présent
  $checkIfRepoExists = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\".*\"' ${REPOS_LIST}");
  if (empty($checkIfRepoExists)) {
    // Suppression du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
    require("${WWW_DIR}/functions/deleteConf.php");
    deleteConf_rpm($repoName);

    // Suppression du repo du fichier de groupes
    deleteRepoFromAllGroup($repoName);
  }
  $msg = '<tr><td colspan="100%"><br>Supprimé <span class="greentext">✔</span></td></tr>';
  writeLog("$msg"); // Enregistre le message dans le log
  echo "$msg";      // Affiche le message à l'utilisateur
}
?>