<?php
function restoreOldRepo_rpm($repoName, $repoDate, $repoEnv, $repoDescription) {
    global $REPOS_LIST;
    global $REPOS_ARCHIVE_LIST;
    global $REPOS_DIR;

    writeLog("<h5>RESTAURER UN REPO</h5>");
    writeLog("<table>");

    if ($repoDescription == "nodescription") { 
        $repoDescription = '';
    }

    writeLog("<tr>
    <td>Nom du repo :</td>
    <td><b>$repoName</b></td>
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

    // On récupère le Realname du repo archivé qui va être restauré
    $repoSource = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Date=\"${repoDate}\"' $REPOS_ARCHIVE_LIST | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");

    // On vérifie que le repo renseigné est bien présent dans le fichier repos-archive.list, si oui alors on peut commencer l'opération
    $checkIfRepoExists = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Date=\"${repoDate}\"' $REPOS_ARCHIVE_LIST");
    if (empty($checkIfRepoExists)) {
        $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>aucun repo archivé $repoName n'existe</td></tr>";
        writeLog("$msg"); // Enregistre l'erreur dans le log
        echo "$msg";      // Affiche l'erreur à l'utilisateur
        return 1;
    }
 
    // On récupère des informations du repo du même nom actuellement en place et qui va être remplacé
    $repoActualDate = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' $REPOS_LIST | awk -F ',' '{print $4}' | cut -d'=' -f2 | sed 's/\"//g'");  
    
    // Suppression du lien symbolique du repo actuellement en place sur $repoEnv
    if (file_exists("${REPOS_DIR}/${repoName}_${repoEnv}")) {
        unlink("${REPOS_DIR}/${repoName}_${repoEnv}");
    }

    // Remise en place de l'ancien miroir
    if (!rename("${REPOS_DIR}/archived_${repoDate}_${repoName}", "${REPOS_DIR}/${repoDate}_${repoName}")) {
        $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>impossible de restaurer le miroir du $repoDate</td></tr>";
        writeLog("$msg"); // Enregistre l'erreur dans le log
        echo "$msg";      // Affiche l'erreur à l'utilisateur
        return 1;
    }

    // Création du lien symbolique
    if (!file_exists("${REPOS_DIR}/${repoName}_${repoEnv}")) {
        exec("cd ${REPOS_DIR} && ln -s ${repoDate}_${repoName}/ ${repoName}_${repoEnv}");
    }

    // Archivage de la version du repo (qui vient d'être remplacée par le repo restauré) si elle n'est plus utilisée par d'autres envs
    // On vérifie que la version du repo n'est pas utilisée par d'autres environnements avant de l'archiver
    // Cas 1 : Si la version qui vient d'être remplacée est utilisée par d'autres envs, alors on ne l'archive pas :
    $checkIfStillUsed = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\".*\",Date=\"${repoActualDate}\"' $REPOS_LIST | grep -v 'Env=\"${repoEnv}\"'");
    $checkIfStillUsed_2 = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\",Date=\".*\"' $REPOS_LIST");
    if (!empty($checkIfStillUsed)) {
        $msg = "<tr><td colspan=\"100%\"><br>Le miroir en date du ${repoActualDate} est toujours utilisé par d'autres environnements, il n'a donc pas été archivé</td></tr>";
        writeLog("$msg"); // Enregistre l'erreur dans le log
        echo "$msg";      // Affiche l'erreur à l'utilisateur
        // Mise à jour des informations dans repos.list
        $content = file_get_contents("$REPOS_LIST");
        $content = preg_replace("/Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\",Date=\"${repoActualDate}\",Description=\".*\"/", "", $content);
        file_put_contents("$REPOS_LIST", $content);
        // Puis on rajoute la nouvelle (ya que la date qui change au final)
        file_put_contents("$REPOS_LIST", "Name=\"${repoName}\",Realname=\"${repoSource}\",Env=\"${repoEnv}\",Date=\"${repoDate}\",Description=\"${repoDescription}\"".PHP_EOL , FILE_APPEND | LOCK_EX);
        // Mise à jour des informations dans repos-archive.list
        $content = file_get_contents("$REPOS_ARCHIVE_LIST");
        // Suppression des infos du repo archivé dans repos-archive.list puisqu'on vient de la restaurer
        $content = preg_replace("/Name=\"${repoName}\",Realname=\"${repoSource}\",Date=\"${repoDate}\",Description=\".*\"/", "", $content);
        file_put_contents("$REPOS_ARCHIVE_LIST", "$content");
    } elseif (empty($checkIfStillUsed_2)) {
        // Cas 2 : Si le repo qu'on vient de restaurer n'a remplacé aucun repo (comprendre il n'y avait aucun repo en cours sur $repoEnv), alors on mets à jour les infos dans repos.list. Pas d'archivage de quoi que ce soit.
        // Mise à jour des informations dans repos.list
        file_put_contents("$REPOS_LIST", "Name=\"${repoName}\",Realname=\"${repoSource}\",Env=\"${repoEnv}\",Date=\"${repoDate}\",Description=\"${repoDescription}\"".PHP_EOL , FILE_APPEND | LOCK_EX);
        // Mise à jour des informations dans repos-archive.list
        $content = file_get_contents("$REPOS_ARCHIVE_LIST");
        $content = preg_replace("/Name=\"${repoName}\",Realname=\".*\",Date=\"${repoDate}\",Description=\".*\"/", "", $content);
        file_put_contents("$REPOS_ARCHIVE_LIST", "$content");
    } else {
        // Cas 3 : Si la version remplacée n'est plus utilisée pour quelconque environnement, alors on l'archive
        // On récupère des informations supplémentaires sur le repo qui va être remplacé
        $repoActualRealname = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' $REPOS_LIST | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
        $repoActualDescription = exec("egrep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\",Date=\"${repoActualDate}\"' $REPOS_LIST | awk -F ',' '{print $5}' | cut -d'=' -f2 | sed 's/\"//g'");
        // Archivage du miroir en date du $repoActualDate car il n'est plus utilisé par quelconque environnement
        if (!rename("${REPOS_DIR}/${repoActualDate}_${repoName}", "${REPOS_DIR}/archived_${repoActualDate}_${repoName}")) {
            $msg = "<tr><td colspan=\"100%\"><br><span class=\"redtext\">Erreur : </span>impossible d'archiver le miroir en date du $repoActualDate</td></tr>";
            writeLog("$msg"); // Enregistre l'erreur dans le log
            echo "$msg";      // Affiche l'erreur à l'utilisateur
            return 1;
        }
        // Mise à jour des informations dans repos.list :
        $content = file_get_contents("$REPOS_LIST");
        $content = preg_replace("/Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\",Date=\"${repoActualDate}\",Description=\".*\"/", "", $content);
        file_put_contents("$REPOS_LIST", "$content");
        file_put_contents("$REPOS_LIST", "Name=\"${repoName}\",Realname=\"${repoSource}\",Env=\"${repoEnv}\",Date=\"${repoDate}\",Description=\"${repoDescription}\"".PHP_EOL , FILE_APPEND | LOCK_EX);        
        // Mise à jour des informations dans repos-archive.list :
        $content = file_get_contents("$REPOS_ARCHIVE_LIST");
        // Supprime le repo qu'on a restauré :
        $content = preg_replace("/Name=\"${repoName}\",Realname=\"${repoSource}\",Date=\"${repoDate}\",Description=\".*\"/", "", $content);
        file_put_contents("$REPOS_ARCHIVE_LIST", "$content");
        // Ajoute le repo qui s'est fait remplacer :
        file_put_contents("$REPOS_ARCHIVE_LIST", "Name=\"${repoName}\",Realname=\"${repoActualRealname}\",Date=\"${repoActualDate}\",Description=\"${repoActualDescription}\"".PHP_EOL , FILE_APPEND | LOCK_EX);               
        $msg = "<tr><td colspan=\"100%\"><br>Le miroir en date du $repoActualDate a été archivé car il n'est plus utilisé par quelconque environnement</td></tr>";
        writeLog("$msg"); // Enregistre l'erreur dans le log
        echo "$msg";      // Affiche l'erreur à l'utilisateur
    }

    $msg = "<tr><td colspan=\"100%\"><br>Restauré en <b>$repoEnv</b> <span class=\"greentext\">✔</span></td></tr>";
    writeLog("$msg"); // Enregistre le message dans le log
    echo "$msg";      // Affiche le message à l'utilisateur
}
?>