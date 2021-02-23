<?php
function cleanArchives() {
  global $ALLOW_AUTODELETE_ARCHIVED_REPOS;
  global $TEMP_DIR;
  global $OS_FAMILY;
  global $REPOS_DIR;
  global $RETENTION;
  global $REPOS_ARCHIVE_LIST;

  // Conversion de RETENTION en nombre entier (base octale)
  $RETENTION = intval($RETENTION, 8);

  // On va utiliser un fichier temporaire pour traiter
  $datesToDelete = "${TEMP_DIR}/repomanager_parsefile.tmp";

  // Si la suppression automatique des repos archivés n'est pas autorisée alors on quitte la fonction
  if ($ALLOW_AUTODELETE_ARCHIVED_REPOS != "yes") {
    //return "La suppression automatique des repos archivés n'est pas autorisée.";
    return false;
  }

  // Si le paramètre retention est vide, alors on quitte la fonction
  if (empty($RETENTION)) {
    return "Erreur CA01 : Le paramètre de retention est vide";
  }

  if (!is_int($RETENTION)) {
    return "Erreur CA02 : Le paramètre de retention doit être un nombre entier";
  }
  if ($RETENTION < 0) {
    return "Erreur CA03 : Le paramètre de retention doit être un nombre entier supérieur ou égal à 0";
  }

  // TRAITEMENT //
  
  // On récupère la liste de tous les repos archivés dans le fichier (on récupère le champ Name uniquement)
  if ($OS_FAMILY == "Redhat") { $reposArchived = shell_exec("cat $REPOS_ARCHIVE_LIST | awk -F',' '{print $1}' | cut -d'=' -f2 | sed 's/\"//g' | sort -u"); }
  if ($OS_FAMILY == "Debian") { $reposArchived = shell_exec("cat $REPOS_ARCHIVE_LIST | cut -d',' -f1,2,3,4 | sort -u"); }
  $reposArchived = explode("\n", $reposArchived);

  // Avec cette liste, on va traiter chaque repo individuellement, en les triant par date puis en supprimant les plus vieux (on conserve X copie du repo, X étant défini par $RETENTION)
  if ($OS_FAMILY == "Redhat") {
    foreach($reposArchived as $repoName) {
      if (!empty($repoName)) {
        // On mets dans un fichier toutes les dates trouvées pour ce repo, et on les trie du + vieux au + recent. Puis on supprime les 2 dates les plus récentes (avec le head)
        exec("grep '^Name=\"${repoName}\"' $REPOS_ARCHIVE_LIST | awk -F',' '{print $3}' | cut -d'=' -f2 | sed 's/\"//g' | sort -t- -k3 -k2 -k1 | head -n -${RETENTION} > $datesToDelete");

        $dates = explode("\n", file_get_contents($datesToDelete));
        // Si le fichier n'est pas vide (contient les dates à supprimer) alors on traite (il pourrait être vide si le nb de vieux repos est inférieur à $RETENTION)
        if (!empty($dates)) {
          foreach($dates as $repoDate) {
            if (!empty($repoDate)) {
              echo "<br>Suppression du repo archivé $repoName en date du $repoDate";

              // Suppression du miroir
              exec("rm '${REPOS_DIR}/archived_${repoDate}_${repoName}' -rf", $output, $return);
              if ($return != 0) {
                echo "<span class=\"redtext\">Erreur lors de la suppression</span>";
                continue; // On traite la date suivante          
              }

              // Nettoyage du fichier de liste
              $repos_list_content = file_get_contents("$REPOS_ARCHIVE_LIST");
              file_put_contents("$REPOS_ARCHIVE_LIST", preg_replace("/Name=\"${repoName}\",Realname=\".*\",Date=\"${repoDate}\",Description=\".*\"/", "", $repos_list_content));
              unset($repos_list_content);
            }
          }
        }
      }
    }
  }

  if ($OS_FAMILY == "Debian") {
    foreach($reposArchived as $repo) {
      if (!empty($repo)) {
        // On mets dans un fichier toutes les dates trouvées pour ce repo, et on les trie du + vieux au + recent. Puis on supprime les 2 dates les plus récentes (avec le head)
        // Ici le grep s'apparent à : grep "Name="repo",Host="host",Dist="dist",Section="section"" car il y a encore toutes ces informations dans $REPOS_ARCHIVED
        exec("grep '$repo' $REPOS_ARCHIVE_LIST | awk -F',' '{print $5}' | cut -d'=' -f2 | sed 's/\"//g' | sort -t- -k3 -k2 -k1 | head -n -${RETENTION} > $datesToDelete");

        $dates = explode("\n", file_get_contents($datesToDelete));
        // Si le fichier n'est pas vide (contient les dates à supprimer) alors on traite (il pourrait être vide si le nb de vieux repos est inférieur à $RETENTION)
        if (!empty($dates)) {
          $repoName = exec("echo $repo | awk -F',' '{print $1}' | cut -d'=' -f2 | sed 's/\"//g'");
          $repoDist = exec("echo $repo | awk -F',' '{print $3}' | cut -d'=' -f2 | sed 's/\"//g'");
          $repoSection = exec("echo $repo | awk -F',' '{print $4}' | cut -d'=' -f2 | sed 's/\"//g'");

          if (empty($repoName) OR empty($repoDist) OR empty($repoSection)) {
            echo "Erreur CA04 : un ou plusieurs paramètre(s) est vide";
            continue;
          }
          
          foreach($dates as $repoDate) {
            if (!empty($repoDate)) {
              echo "<br>Suppression de la section archivée $repoSection du repo $repoName (distribution $repoDist) en date du $repoDate";

              // Suppression du miroir
              exec("rm '${REPOS_DIR}/${repoName}/${repoDist}/archived_${repoDate}_${repoSection}' -rf", $output, $return);
              if ($return != 0) {
                echo "<span class=\"redtext\">Erreur lors de la suppression</span>";
                continue; // On traite la date suivante          
              }

              // Nettoyage du fichier de liste
              $repos_list_content = file_get_contents("$REPOS_ARCHIVE_LIST");
              file_put_contents("$REPOS_ARCHIVE_LIST", preg_replace("/Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Date=\"${repoDate}\",Description=\".*\"/", "", $repos_list_content));
              unset($repos_list_content);
            }
          }
        }
      }
    }
  }

  // Suppression du fichier temporaire
  if (file_exists("${TEMP_DIR}/repomanager_parsefile.tmp")) { unlink("${TEMP_DIR}/repomanager_parsefile.tmp"); }
}
?>