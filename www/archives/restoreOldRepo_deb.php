<?php
function restoreOldRepo_deb($repoName, $repoDist, $repoSection, $repoDate, $repoEnv, $repoDescription) {
    global $REPOS_LIST;
    global $REPOS_ARCHIVE_LIST;
    global $REPOS_DIR;

    writeLog("<h5>RESTAURER UNE SECTION DE REPO</h5>");
    writeLog("<table>");

    if ($repoDescription == "nodescription") { 
        $repoDescription = '';
    }

    writeLog("<tr>
    <td>Section de repo :</td>
    <td><b>$repoSection</b></td>
    </tr>
    <tr>
    <td>Nom du repo :</td>
    <td><b>$repoName</b></td>
    </tr>
    <tr>
    <td>Distribution :</td>
    <td><b>$repoDist</b></td>
    </tr>
    <tr>
    <td>Environnement cible :</td>
    <td><b>$repoEnv</b></td>
    </tr>");
    if (!empty($repoDescription)) {
      writeLog("<tr>
      <td>Description :</td>
      <td><b>$repoDescription</b></td>
      </tr>");
    }

    // On récupère le Host du repo archivé qui va être restauré
    $repoHost = exec("egrep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Date=\"${repoDate}\"' $REPOS_ARCHIVE_LIST | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");

    // On vérifie que la section renseignée est bien présente dans le fichier repos-archive.list, si oui alors on peut commencer l'opération
    $checkIfSectionExists = exec("egrep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Date=\"${repoDate}\"' $REPOS_ARCHIVE_LIST");
    if (empty($checkIfSectionExists)) {
        $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>aucune section $repoSection archivée n'existe</td></tr>";
        writeLog("$msg"); // Enregistre l'erreur dans le log
        echo "$msg";      // Affiche l'erreur à l'utilisateur
        return 1;
    }

    // On récupère des informations de la section du même nom actuellement en place et qui va être remplacée
    $repoActualDate = exec("egrep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\"' $REPOS_LIST | awk -F ',' '{print $6}' | cut -d'=' -f2 | sed 's/\"//g'");
    
    // Suppression du lien symbolique de la section actuellement en place sur $repoEnv
    if (file_exists("${REPOS_DIR}/${repoName}/${repoDist}/${repoSection}_${repoEnv}")) {
        unlink("${REPOS_DIR}/${repoName}/${repoDist}/${repoSection}_${repoEnv}");
    }

    // Remise en place de l'ancien miroir
    if (!rename("${REPOS_DIR}/${repoName}/${repoDist}/archived_${repoDate}_${repoSection}", "${REPOS_DIR}/${repoName}/${repoDist}/${repoDate}_${repoSection}")) {
        $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>impossible de restaurer le miroir du $repoDate</td></tr>";
        writeLog("$msg"); // Enregistre l'erreur dans le log
        echo "$msg";      // Affiche l'erreur à l'utilisateur
        return 1;
    }

    // Création du lien symbolique
    if (!file_exists("${REPOS_DIR}/${repoName}/${repoDist}/${repoName}_${repoEnv}")) {
        exec("cd ${REPOS_DIR}/${repoName}/${repoDist}/ && ln -s ${repoDate}_${repoSection}/ ${repoSection}_${repoEnv}");
    }

    // Archivage de la version de la section (remplacée par la section restaurée) si elle n'est pas utilisée par d'autres envs
    // On vérifie que la version de la section n'est pas utilisée par d'autres environnements avant de l'archiver
    // Cas 1 : Si la version qui vient d'être remplacée est utilisée par d'autres envs, alors on ne l'archive pas :
    $checkIfStillUsed = exec("egrep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\".*\",Date=\"${repoActualDate}\"' $REPOS_LIST | grep -v 'Env=\"${repoEnv}\"'");
    $checkIfStillUsed_2 = exec("egrep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\",Date=\".*\"' $REPOS_LIST");
    if (!empty($checkIfStillUsed)) {
        $msg = "<tr><td colspan=\"100%\"><br>Le miroir en date du <b>${repoActualDate}</b> est toujours utilisé par d'autres environnements, il n'a donc pas été archivé</td></tr>";
        writeLog("$msg"); // Enregistre l'erreur dans le log
        echo "$msg";      // Affiche l'erreur à l'utilisateur
        // Mise à jour des informations dans repos.list
        $content = file_get_contents("$REPOS_LIST");
        $content = preg_replace("/Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\",Date=\"${repoActualDate}\",Description=\".*\"/", "", $content);
        file_put_contents("$REPOS_LIST", $content);
         // Puis on rajoute la nouvelle (ya que la date qui change au final) (rajout à tester)
        file_put_contents("$REPOS_LIST", "Name=\"${repoName}\",Host=\"${repoHost}\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\",Date=\"${repoDate}\",Description=\"${repoDescription}\"".PHP_EOL , FILE_APPEND | LOCK_EX);
        // Mise à jour des informations dans repos-archive.list
        $content = file_get_contents("$REPOS_ARCHIVE_LIST");
        // Suppression des infos de la section archivée dans repos-archive.list puisqu'on vient de la restaurer
        $content = preg_replace("/Name=\"${repoName}\",Host=\"${repoHost}\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Date=\"${repoDate}\",Description=\".*\"/", "", $content);
        file_put_contents("$REPOS_ARCHIVE_LIST", "$content");
    } elseif (empty($checkIfStillUsed_2)) {
        // Cas 2 : Si la section qu'on vient de restaurer n'a remplacé aucune section (comprendre il n'y avait aucune section en cours sur repoEnv), alors on mets à jour les infos dans repos.list. Pas d'archivage de quoi que ce soit.
        // Mise à jour des informations dans repos.list :
        file_put_contents("$REPOS_LIST", "Name=\"${repoName}\",Host=\"${repoHost}\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\",Date=\"${repoDate}\",Description=\"${repoDescription}\"".PHP_EOL , FILE_APPEND | LOCK_EX);
        // Mise à jour des informations dans repos-archive.list :
        $content = file_get_contents("$REPOS_ARCHIVE_LIST");
        $content = preg_replace("/Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Date=\"${repoDate}\",Description=\".*\"/", "", $content);
        file_put_contents("$REPOS_ARCHIVE_LIST", "$content");
    } else {
        // Cas 3 : Si la version remplacée n'est plus utilisée pour quelconque environnement, alors on l'archive
        // On récupère des informations supplémentaires sur la section qui va être remplacée
        $repoActualHost = exec("egrep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\"' $REPOS_LIST | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
        $repoActualDescription = exec("egrep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\",Date=\"${repoActualDate}\"' $REPOS_LIST | awk -F ',' '{print $7}' | cut -d'=' -f2 | sed 's/\"//g'");
        // Archivage du miroir en date du $repoActualDate car il n'est plus utilisé par quelconque environnement
        if (!rename("${REPOS_DIR}/${repoName}/${repoDist}/${repoActualDate}_${repoSection}", "${REPOS_DIR}/${repoName}/${repoDist}/archived_${repoActualDate}_${repoSection}")) {
            $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>impossible d'archiver le miroir en date du $repoActualDate</td></tr>";
            writeLog("$msg"); // Enregistre l'erreur dans le log
            echo "$msg";      // Affiche l'erreur à l'utilisateur
            return 1;
        }
        // Mise à jour des informations dans repos.list :
        $content = file_get_contents("$REPOS_LIST");
        $content = preg_replace("/Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\",Date=\"${repoActualDate}\",Description=\".*\"/", "", $content);
        file_put_contents("$REPOS_LIST", "$content");
        file_put_contents("$REPOS_LIST", "Name=\"${repoName}\",Host=\"${repoHost}\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\",Date=\"${repoDate}\",Description=\"${repoDescription}\"".PHP_EOL , FILE_APPEND | LOCK_EX);
        // Mise à jour des informations dans repos-archive.list :
        $content = file_get_contents("$REPOS_ARCHIVE_LIST");
        // Supprime la section qu'on a restauré :
        $content = preg_replace("/Name=\"${repoName}\",Host=\"${repoHost}\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Date=\"${repoDate}\",Description=\".*\"/", "", $content);
        file_put_contents("$REPOS_ARCHIVE_LIST", "$content");
        // Ajoute la section qui s'est faite remplacer :
        file_put_contents("$REPOS_ARCHIVE_LIST", "Name=\"${repoName}\",Host=\"${repoActualHost}\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Date=\"${repoActualDate}\",Description=\"${repoActualDescription}\"".PHP_EOL , FILE_APPEND | LOCK_EX);
        $msg = "<tr><td colspan=\"100%\"><br>Le miroir en date du $repoActualDate a été archivé car il n'est plus utilisé par quelconque environnement</td></tr>";
        writeLog("$msg"); // Enregistre l'erreur dans le log
        echo "$msg";      // Affiche l'erreur à l'utilisateur
    }
	
    $msg = "<tr><td colspan=\"100%\"><br>Restaurée en <b>$repoEnv</b> <span class=\"greentext\">✔</span></td></tr>";
    writeLog("$msg"); // Enregistre le message dans le log
    echo "$msg";      // Affiche le message à l'utilisateur
}
?>