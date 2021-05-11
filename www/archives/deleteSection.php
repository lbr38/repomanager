<?php
function deleteSection($repoName, $repoDist, $repoSection, $repoEnv) {
  global $REPOS_LIST;
  global $GROUPS_CONF;
  global $REPOS_DIR;
  global $WWW_DIR;

  writeLog("<h5>SUPPRIMER UNE SECTION DE REPO</h5>");
  writeLog("<table>");
  writeLog("<tr>
		<td>Section de repo :</td>
		<td><b>$repoSection ($repoEnv)</b></td>
	  </tr>
    <tr>
		<td>Nom du repo :</td>
		<td><b>$repoName</b></td>
	  </tr>
    <tr>
		<td>Distribution :</td>
		<td><b>$repoDist</b></td>
	  </tr>");

  // On vérifie que la section renseignée est bien présente dans le fichier repos.list, alors on peut commencer l'opération
  $checkIfSectionExists = exec("egrep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\"' $REPOS_LIST");
  if (empty($checkIfSectionExists)) {
    $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>cette section n'existe pas</td></tr>";
    writeLog("$msg"); // Enregistre l'erreur dans le log
    echo "$msg";      // Affiche l'erreur à l'utilisateur
    return 1;
  }

  // Récupération de la date de la section :
  $repoDate = exec("egrep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\"' ${REPOS_LIST} | awk -F ',' '{print $6}' | cut -d'=' -f2 | sed 's/\"//g'");
  if (empty($repoDate)) {
    $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>impossible de récupérer la date de la section</td></tr>";
    writeLog("$msg"); // Enregistre l'erreur dans le log
    echo "$msg";      // Affiche l'erreur à l'utilisateur
    return 1;
  }

  // Suppression du lien symbolique
  if (file_exists("${REPOS_DIR}/${repoName}/${repoDist}/${repoSection}_${repoEnv}")) {
    unlink("${REPOS_DIR}/${repoName}/${repoDist}/${repoSection}_${repoEnv}");
  }

  // On mets à jour les infos dans le fichier repos.list en supprimant la ligne du repo
  $repos_list_content = file_get_contents("$REPOS_LIST");
  $repos_list_content = preg_replace("/Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\",Date=\"${repoDate}\",Description=\".*\"/", "", $repos_list_content);
  file_put_contents("$REPOS_LIST", $repos_list_content);

  // Vérifications avant suppression définitive du miroir :
  $checkIfMirrorIsStillUsed = exec("egrep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\".*\",Date=\"${repoDate}\"' $REPOS_LIST");
  if (!empty($checkIfMirrorIsStillUsed)) {
    $msg = "<tr><td colspan=\"100%\"><br>La version du miroir de cette section est toujours utilisée pour d'autres environnements. Le miroir du ${repoDate} n'est donc pas supprimé</td></tr>";
    writeLog("$msg"); // Enregistre l'erreur dans le log
    echo "$msg";      // Affiche l'erreur à l'utilisateur
  } else {
    // Suppression
    exec("rm ${REPOS_DIR}/${repoName}/${repoDist}/${repoDate}_${repoSection} -rf");
  }

  // Si il n'y a plus du tout de trace de la section dans le fichier de conf, alors on peut supprimer son fichier de conf repo, et on peut la retirer des groupes où elle est présente
  $checkIfSectionExists = exec("egrep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\".*\"' $REPOS_LIST");
  if (empty($checkIfSectionExists)) {

    // Suppression du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
    require("${WWW_DIR}/functions/deleteConf.php");
    deleteConf_deb($repoName, $repoDist, $repoSection);

    // Suppression de la section si elle est présente dans le fichier de groupes
    $repoName = "${repoName}|${repoDist}|${repoSection}";
    deleteSectionFromAllGroup($repoName);
  }

  $msg = '<tr><td colspan="100%"><br>Supprimée <span class="greentext">✔</span></td></tr>';
  writeLog("$msg"); // Enregistre le message dans le log
  echo "$msg";      // Affiche le message à l'utilisateur
}
?>