<?php
/////// GESTION DES FORMULAIRES ET REQUETES GET COMMUNS ////////
// Des formulaires peuvent être communs à plusieurs pages (on retrouve le même formulaire sur plusieurs pages, par exemple pour les groupes), 
// la récupération de leur valeur en POST et leur traitement est donc placé ici, pour éviter le code en doublon


// AFFICHAGE DANS LISTE DES REPOS //

if (!empty($_POST['action']) AND validateData($_POST['action']) == "configureDisplay") {

  // On récupère le contenu actuel de display.ini
  $displayConfiguration = parse_ini_file("$DISPLAY_CONF", true);

  // Liste des repos : choisir d'afficher ou non la taille des repos
  if (!empty($_POST['printRepoSize'])) {
    $printRepoSize = validateData($_POST['printRepoSize']);
    if ($printRepoSize == "on") {
      $displayConfiguration['display']['printRepoSize'] = 'yes';
    } else {
      $displayConfiguration['display']['printRepoSize'] = 'no';
    }
  }

  // Liste des repos : choisir de filtrer ou non par groupe
  if (!empty($_POST['filterByGroups'])) {
    $filterByGroups = validateData($_POST['filterByGroups']);
    if ($filterByGroups == "on") {
      $displayConfiguration['display']['filterByGroups'] = 'yes';
    } else {
      $displayConfiguration['display']['filterByGroups'] = 'no';
    }
  }

  // Liste des repos : choisir ou non la vue simplifiée
  if (!empty($_POST['concatenateReposName'])) {
    $concatenateReposName = validateData($_POST['concatenateReposName']);
    if ($concatenateReposName == "on") {
      $displayConfiguration['display']['concatenateReposName'] = 'yes';
    } else {
      $displayConfiguration['display']['concatenateReposName'] = 'no';
    }
  }

  // Liste des repos : choisir d'afficher ou non une ligne séparatrice entre chaque nom de repo/section
  if (!empty($_POST['dividingLine'])) {
    $dividingLine = validateData($_POST['dividingLine']);
    if ($dividingLine == "on") {
      $displayConfiguration['display']['dividingLine'] = 'yes';
    } else {
      $displayConfiguration['display']['dividingLine'] = 'no';
    }
  }

  // Liste des repos : alterner ou non les couleurs dans la liste
  if (!empty($_POST['alternateColors'])) {
    $alternateColors = validateData($_POST['alternateColors']);
    if ($alternateColors == "on") {
      $displayConfiguration['display']['alternateColors'] = 'yes';
    } else {
      $displayConfiguration['display']['alternateColors'] = 'no';
    }
  }

  // Modification des couleurs, voir comment on peut améliorer car c'est très bricolage
  if (!empty($_POST['alternativeColor1'])) {
    $alternativeColor1 = validateData($_POST['alternativeColor1']);
    $displayConfiguration['display']['alternativeColor1'] = "$alternativeColor1";
  }

  if (!empty($_POST['alternativeColor2'])) {
    $alternativeColor2 = validateData($_POST['alternativeColor2']);
    $displayConfiguration['display']['alternativeColor2'] = "$alternativeColor2";
  }

  // On écrit les modifications dans le fichier display.ini
  write_ini_file("$DISPLAY_CONF", $displayConfiguration);

  clearCache($WWW_CACHE);

  // Puis rechargement de la page pour appliquer les modifications d'affichage
  header("Location: $actual_url");
}

 
//// GROUPES ////
// Traitement des données envoyées par le formulaire de gestion des groupes de repos

// Cas où on souhaite ajouter un nouveau groupe : 
if (!empty($_POST['addGroupName'])) {
  $addGroupName = validateData($_POST['addGroupName']);
  newGroup($addGroupName);
  refreshdiv_class('divGroupsList');
  showdiv_class('divGroupsList');
}

// Cas où on souhaite ajouter un repo à un groupe (cette partie doit être placée avant le "Cas où on souhaite renommer un groupe") :
if (!empty($_POST['actualGroupName']) AND !empty($_POST['groupAddRepoName'])) {
  $actualGroupName = validateData($_POST['actualGroupName']);
  //$groupAddRepoName = validateData($_POST['groupAddRepoName']);

  foreach ($_POST['groupAddRepoName'] as $selectedOption) {
    $groupAddRepoName = validateData($selectedOption);

    // Note pour Debian : le repo, la distribution et la section sont concaténées dans $groupAddRepoName et séparées par un |
    addRepoToGroup($groupAddRepoName, $actualGroupName);
  }
  refreshdiv_class('divGroupsList');
  showdiv_class('divGroupsList');
}

// Cas où on souhaite supprimer un repo/section d'un groupe :
// Cas Redhat :
if ($OS_FAMILY == "Redhat" AND !empty($_GET['action']) AND (validateData($_GET['action']) == "deleteGroupRepo") AND !empty($_GET['groupName']) AND !empty($_GET['repoName'])) {
  $groupName = validateData($_GET['groupName']);
  $groupDelRepoName = validateData($_GET['repoName']);
  deleteRepoFromGroup($groupDelRepoName, $groupName);
  refreshdiv_class('divGroupsList');
  showdiv_class('divGroupsList');
}
// Cas Debian :
if ($OS_FAMILY == "Debian" AND !empty($_GET['action']) AND (validateData($_GET['action']) == "deleteGroupRepo" AND !empty($_GET['groupName']) AND !empty($_GET['repoName']) AND !empty($_GET['repoDist']) AND !empty($_GET['repoSection']))) {
  $groupName = validateData($_GET['groupName']);
  $groupDelRepoName = validateData($_GET['repoName']);
  $groupDelRepoDist = validateData($_GET['repoDist']);
  $groupDelRepoSection = validateData($_GET['repoSection']);
  $groupDelRepoName = "${groupDelRepoName}|${groupDelRepoDist}|${groupDelRepoSection}";
  deleteSectionFromGroup($groupDelRepoName, $groupName);
  refreshdiv_class('divGroupsList');
  showdiv_class('divGroupsList');
}

// Cas où on souhaite renommer un groupe :
if (!empty($_POST['newGroupName']) AND !empty($_POST['actualGroupName'])) {
  $actualGroupName = validateData($_POST['actualGroupName']);
  $newGroupName = validateData($_POST['newGroupName']);
  renameGroup($actualGroupName, $newGroupName);
  refreshdiv_class('divGroupsList');
  showdiv_class('divGroupsList');
}

// Cas où on souhaite supprimer un groupe :
if (!empty($_GET['action']) AND (validateData($_GET['action']) == "deleteGroup") AND !empty($_GET['groupName'])) {
  $groupName = validateData($_GET['groupName']);
  deleteGroup($groupName);
  refreshdiv_class('divGroupsList');
  showdiv_class('divGroupsList');
}


//// REPOS SOURCES ////

// Redhat : on a la possibilité d'ajouter de nouveaux fichiers .repo depuis l'accueil
if ($OS_FAMILY == "Redhat") {
  // Cas où on souhaite ajouter un nouveau fichier de conf :
  if (!empty($_POST['newRepoName']) AND !empty($_POST['newRepoUrlType']) AND !empty($_POST['newRepoUrl'])) {
    $error=0; // un peu de gestion d'erreur
    $newRepoName = validateData($_POST['newRepoName']);
    $newRepoUrlType = validateData($_POST['newRepoUrlType']);
    //$newRepoUrl = validateData($_POST['newRepoUrl']);
    $newRepoUrl = $_POST['newRepoUrl']; // pas de validatedata car transforme certains caractères dans l'url et du coup l'url ne fonctionne plus...

    // On forge le nom du fichier à partir du nom de repo fourni
    $newRepoFile = "${newRepoName}.repo";
    // Si le fichier existe déjà on affiche un alerte
    if (file_exists("${REPOMANAGER_YUM_DIR}/${newRepoFile}")) {
      // Affichage d'un message et rechargement de la div
      printAlert("Le fichier $newRepoFile existe déjà");
      refreshdiv_class('divManageReposSources');
      showdiv_class('divManageReposSources');
      $error++;
    }
    // On récupère la clé gpg, soit une clé existante, soit au format url, soit au format texte à importer. Si les deux sont renseignés on affiche une erreur (c'est l'un ou l'autre)
    if (!empty($_POST['existingRepoGpgKey']) AND !empty($_POST['newRepoGpgKeyURL']) AND !empty($_POST['newRepoGpgKeyText'])) {
      printAlert("Erreur clé GPG : Vous ne pouvez pas renseigner plusieurs clé GPG à la fois");
      $error++;
    } elseif (!empty($_POST['existingRepoGpgKey'])) { // On recupère le nom de la clé existante
       $existingRepoGpgKey = validateData($_POST['existingRepoGpgKey']);
    } elseif (!empty($_POST['newRepoGpgKeyURL'])) { // On recupère l'url de la clé gpg
      $newRepoGpgKeyURL = validateData($_POST['newRepoGpgKeyURL']);
    } elseif (!empty($_POST['newRepoGpgKeyText'])) { // On récupère la clé gpg au format texte
      $newRepoGpgKeyText = validateData($_POST['newRepoGpgKeyText']);
      // on importe la clé gpg au format texte dans le répertoire par défaut où rpm stocke ses clés gpg importées (et dans un sous-répertoire repomanager)
      $newGpgFile = "REPOMANAGER-RPM-GPG-KEY-${newRepoName}";
      if (file_exists("${RPM_GPG_DIR}/${newGpgFile}")) {
        // Affichage d'un message et rechargement de la div
        printAlert("Un fichier GPG du même nom existe déjà dans le trousseau de repomanager"); // on n'incrémente pas error ici car l'import de la clé peut se refaire à part ensuite
        refreshdiv_class('divManageReposSources');
        showdiv_class('divManageReposSources');
      } else {
        file_put_contents("${RPM_GPG_DIR}/${newGpgFile}", $newRepoGpgKeyText, FILE_APPEND | LOCK_EX); // ajout de la clé gpg à l'intérieur du fichier gpg
      }
    }

    // On continue uniquement si il n'y a pas eu d'erreur précedemment
    if ($error === 0) {
      // on génère la conf qu'on va injecter dans le fichier de repo
      $newRepoFileConf = "[${newRepoName}]";
      $newRepoFileConf = "${newRepoFileConf}\nenabled=1";
      $newRepoFileConf = "${newRepoFileConf}\nname=Repo ${newRepoName} sur ${WWW_HOSTNAME}";
      // Forge l'url en fonction de son type (baseurl, mirrorlist...)
      if ($newRepoUrlType == "baseurl") {
        $newRepoFileConf = "${newRepoFileConf}\nbaseurl=${newRepoUrl}";
      }
      if ($newRepoUrlType == "mirrorlist") {
        $newRepoFileConf = "${newRepoFileConf}\nmirrorlist=${newRepoUrl}";
      }
      if ($newRepoUrlType == "metalink") {
        $newRepoFileConf = "${newRepoFileConf}\nmetalink=${newRepoUrl}";
      }
      // Si on a renseigné une clé gpg, on active gpgcheck
      if (!empty($existingRepoGpgKey) OR !empty($newRepoGpgKeyURL) OR !empty($newRepoGpgKeyText)) {
        $newRepoFileConf = "${newRepoFileConf}\ngpgcheck=1";
      }
      // On indique le chemin vers la clé GPG existante
      if (!empty($existingRepoGpgKey)) {
        $newRepoFileConf = "${newRepoFileConf}\ngpgkey=file://${RPM_GPG_DIR}/${existingRepoGpgKey}";
      }
      // On indique l'url vers la clé gpg
      if (!empty($newRepoGpgKeyURL)) {
        $newRepoFileConf = "${newRepoFileConf}\ngpgkey=${newRepoGpgKeyURL}";
      }
      // On indique le chemin vers la clé gpg
      if (!empty($newRepoGpgKeyText)) {
        $newRepoFileConf = "${newRepoFileConf}\ngpgkey=file://${RPM_GPG_DIR}/${newGpgFile}";
      }
      exec("echo '${newRepoFileConf}' > ${REPOMANAGER_YUM_DIR}/${newRepoFile}");
      // Affichage d'un message et rechargement de la div
      printAlert("Le repo source ${newRepoName} a été ajouté. Vous pouvez désormais créer un miroir à partir de ce repo.");
      refreshdiv_class('divManageReposSources');
      showdiv_class('divManageReposSources');
    }    
  }

  // Cas où on souhaite modifier la conf d'un repo source (textarea) :
  if (!empty($_POST['action']) AND validateData($_POST['action']) == "editRepoSourceConf" AND !empty($_POST['repoSourceConf'])) {
    $repoFileName = validateData($_POST['repoFileName']);
    $repoSourceConf = $_POST['repoSourceConf']; // Pas de validatedata ici car ça remplace certains caractères d'url dans la conf
    // On écrit la conf dans le fichier indiqué :
    file_put_contents("${REPOMANAGER_YUM_DIR}/${repoFileName}", $repoSourceConf);
    // Affichage d'un message et rechargement de la div
    printAlert("La configuration a bien été enregistrée");
    refreshdiv_class('divManageReposSources');
    showdiv_class('divManageReposSources');
  }
   
  
  // Cas où on souhaite supprimer un fichier de conf :
  if (isset($_GET['action']) AND (validateData($_GET['action']) == "deleteRepoFile") AND !empty($_GET['repoFileName'])) {
    $repoFileName = validateData($_GET['repoFileName']);
    unlink("${REPOMANAGER_YUM_DIR}/${repoFileName}"); // supprime le fichier
    // Affichage d'un message et rechargement de la div
    printAlert("Le fichier de repo source ${repoFileName} a été supprimé. Vous ne pouvez plus créer de miroir à partir de ce repo");
    refreshdiv_class('divManageReposSources');
    showdiv_class('divManageReposSources');
  }

  // Cas où on souhaite supprimer une clé GPG du trousseau de repomanager
  if (isset($_GET['action']) AND (validateData($_GET['action']) == "deleteGpgKey") AND !empty($_GET['gpgKeyFile'])) {
    $gpgKeyFile = validateData($_GET['gpgKeyFile']);
    unlink("${RPM_GPG_DIR}/${gpgKeyFile}");
    // Affichage d'un message et rechargement de la div
    printAlert("La clé GPG a été supprimée");
    refreshdiv_class('divManageReposSources');
    showdiv_class('divManageReposSources');
  }
}


// Debian : on a la possibilité d'ajouter de nouvelles url hotes depuis l'accueil
if ($OS_FAMILY == "Debian") {
   // Cas où on souhaite ajouter une nouvelle url hôte :
  if (!empty($_POST['newHostName']) AND !empty($_POST['newHostUrl'])) {
    $newHostName = validateData($_POST['newHostName']);
    $newHostUrl = validateData($_POST['newHostUrl']);
    if (!empty($_POST['newHostGpgKey'])) { // on importe la clé si elle a été transmise 
        $newHostGpgKey = validateData($_POST['newHostGpgKey']);
        $gpgTempFile = '/tmp/repomanager_newgpgkey.tmp'; // création d'un fichier temporaire
        file_put_contents($gpgTempFile, $newHostGpgKey, FILE_APPEND | LOCK_EX); // ajout de la clé gpg à l'intérieur d'un fichier temporaire, afin de l'importer
        $output=null; // un peu de gestion d'erreur
        $retval=null;
        exec("gpg --no-default-keyring --keyring ${GPGHOME}/trustedkeys.gpg --import $gpgTempFile", $output, $retval);
        if ($retval !== 0) {
          // Affichage d'un message et rechargement de la div
          printAlert("Erreur lors de l'import de la clé GPG");
          if ($DEBUG_MODE == "yes") { print_r($output); }
        } 
        unlink($gpgTempFile); // suppression du fichier temporaire
    }
    exec("echo 'Name=\"${newHostName}\",Url=\"${newHostUrl}\"' >> $HOSTS_CONF"); // import du nom et de l'url dans le fichier des hôtes
    // Affichage d'un message et rechargement de la div
    printAlert("L'hôte $newHostName a été ajouté. Vous pouvez créer des sections à partir de cet hôte");
    refreshdiv_class('divManageReposSources');
    showdiv_class('divManageReposSources');
  }

  // Cas où on souhaite supprimer une url hôte :
  if (isset($_GET['action']) AND (validateData($_GET['action']) == "deleteHost") AND !empty($_GET['hostName'])) {
    $hostName = validateData($_GET['hostName']);
    exec('sed -i \'/^Name=\"'.$hostName.'\"/d\' '.$HOSTS_CONF);
    // Affichage d'un message et rechargement de la div
    printAlert("L'hôte $hostName a été supprimé. Vous ne pouvez plus créer ou mettre à jour des sections à partir de cet hôte");
    refreshdiv_class('divManageReposSources');
    showdiv_class('divManageReposSources');
  }

  // Cas où on souhaite supprimer un clé gpg du trousseau de repomanager :
  if (isset($_GET['action']) AND (validateData($_GET['action']) == "deleteGpgKey") AND !empty($_GET['gpgKeyID'])) {
    $gpgKeyID = validateData($_GET['gpgKeyID']);
    exec("gpg --no-default-keyring --keyring ${GPGHOME}/trustedkeys.gpg --no-greeting --delete-key --batch --yes $gpgKeyID");
    // Affichage d'un message et rechargement de la div
    printAlert("La clé GPG a été supprimée");
    refreshdiv_class('divManageReposSources');
    showdiv_class('divManageReposSources');
  }
}

// Vérifications de la présence des fichiers de base
if (!file_exists($GROUPS_CONF)) { // Si le fichier de groupes n'existe pas, on le créé
  file_put_contents($GROUPS_CONF, "[GROUPES]\n\n");
}
if (!file_exists($ENV_CONF)) { // Si le fichier de groupes n'existe pas, on le créé
  file_put_contents($ENV_CONF, "[ENVIRONNEMENTS]\n\n");
}
?>