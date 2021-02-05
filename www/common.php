<?php
/////// GESTION DES FORMULAIRES ET REQUETES GET COMMUNS ////////
// Des formulaires peuvent être communs à plusieurs pages (on retrouve le même formulaire sur plusieurs pages, par exemple pour les groupes), 
// la récupération de leur valeur en POST et leur traitement est donc placé ici, pour éviter le code en doublon
//print_r();


// AFFICHAGE DANS LISTE DES REPOS //

// Liste des repos : choisir d'afficher ou non la taille des repos
if (isset($_POST['printRepoSize'])) {
  $printRepoSize = validateData($_POST['printRepoSize']);

  if ($printRepoSize == "on") {
    exec("sed -i 's/\$printRepoSize = \"no\"/\$printRepoSize = \"yes\"/g' ${WWW_DIR}/vars/display.vars");
  } else {
    exec("sed -i 's/\$printRepoSize = \"yes\"/\$printRepoSize = \"no\"/g' ${WWW_DIR}/vars/display.vars");
  }
}

// Liste des repos : choisir de filtrer ou non par groupe
if (isset($_POST['filterByGroups'])) {
  $filterByGroups = validateData($_POST['filterByGroups']);

  if ($filterByGroups == "on") {
    exec("sed -i 's/\$filterByGroups = \"no\"/\$filterByGroups = \"yes\"/g' ${WWW_DIR}/vars/display.vars");
  } else {
    exec("sed -i 's/\$filterByGroups = \"yes\"/\$filterByGroups = \"no\"/g' ${WWW_DIR}/vars/display.vars");
  }
}

// Liste des repos : choisir ou non la vue simplifiée
if (isset($_POST['concatenateReposName'])) {
  $concatenateReposName = validateData($_POST['concatenateReposName']);

  if ($concatenateReposName == "on") {
    exec("sed -i 's/\$concatenateReposName = \"no\"/\$concatenateReposName = \"yes\"/g' ${WWW_DIR}/vars/display.vars");
  } else {
    exec("sed -i 's/\$concatenateReposName = \"yes\"/\$concatenateReposName = \"no\"/g' ${WWW_DIR}/vars/display.vars");
  }
}

// Liste des repos : choisir d'afficher ou non une ligne séparatrice entre chaque nom de repo/section
if (isset($_POST['dividingLine'])) {
  $dividingLine = validateData($_POST['dividingLine']);

  if ($dividingLine == "on") {
    exec("sed -i 's/\$dividingLine = \"no\"/\$dividingLine = \"yes\"/g' ${WWW_DIR}/vars/display.vars");
  } else {
    exec("sed -i 's/\$dividingLine = \"yes\"/\$dividingLine = \"no\"/g' ${WWW_DIR}/vars/display.vars");
  }
}

// Liste des repos : alterner ou non les couleurs dans la liste
if (isset($_POST['alternateColors'])) {
  $alternateColors = validateData($_POST['alternateColors']);

  if ($alternateColors == "on") {
    exec("sed -i 's/\$alternateColors = \"no\"/\$alternateColors = \"yes\"/g' ${WWW_DIR}/vars/display.vars");
  } else {
    exec("sed -i 's/\$alternateColors = \"yes\"/\$alternateColors = \"no\"/g' ${WWW_DIR}/vars/display.vars");
  }
}

// Modification des couleurs, voir comment on peut améliorer car c'est très bricolage
if (!empty($_POST['alternativeColor1'])) {
  $alternativeColor1 = validateData($_POST['alternativeColor1']);
  exec("sed -i 's/--color1.*/--color1:${alternativeColor1};/g' styles/vars/colors.css");
}

if (!empty($_POST['alternativeColor2'])) {
  $alternativeColor2 = validateData($_POST['alternativeColor2']);
  exec("sed -i 's/--color2.*/--color2:${alternativeColor2};/g' styles/vars/colors.css");
}

 
//// GROUPES ////
// Traitement des données envoyées par le formulaire de gestion des groupes de repos

// Cas où on souhaite ajouter un nouveau groupe : 
if (!empty($_POST['addGroupName'])) {
  $addGroupName = validateData($_POST['addGroupName']);

  // On vérifie que le groupe n'existe pas déjà :
  $checkIfGroupExists = exec("grep '\[@${addGroupName}\]' $GROUPS_CONF");
  if (!empty($checkIfGroupExists)) {
    printAlert("Le groupe $addGroupName existe déjà");
  } else {
    // on formate pour que le contenu soit ajouté en laissant un saut de ligne vide et entre crochets et avec un @ devant le nom du groupe
    // on laisse aussi deux sauts de lignes après car le dernier groupe du fichier doit être suivi de deux lignes vides, sinon l'ajout de repo dans ce dernier groupe ne fonctionne pas
    // à noter que la suppression des lignes en doubles plus bas n'affecte pas le dernier groupe du fichier (les deux lignes restent toujours bien en place, tant mieux)
    $addGroupNameFormated = "\n\n[@${addGroupName}]\n\n"; 
    // Ecrit le contenu dans le fichier, en utilisant le drapeau
    // FILE_APPEND pour rajouter à la suite du fichier et
    // LOCK_EX pour empêcher quiconque d'autre d'écrire dans le fichier en même temps
    file_put_contents($GROUPS_CONF, $addGroupNameFormated, FILE_APPEND | LOCK_EX);
    // on formate un coup le fichier afin de supprimer les doubles saut de lignes si il y en a :
    exec('sed -i "/^$/N;/^\n$/D" '.$GROUPS_CONF.''); // obligé d'utiliser de simples quotes et de concatenation sinon php évalue le \n et la commande sed ne fonctionne pas
    
    // Affichage d'un message et rechargement de la div
    printAlert("Le groupe $addGroupName a été créé");
    refreshdiv_class('divGroupsList');
    showdiv_class('divGroupsList');
  }
}


// Cas où on souhaite ajouter un repo à un groupe (cette partie doit être placée avant le "Cas où on souhaite renommer un groupe") :
// Cas Redhat :
if ($OS_FAMILY == "Redhat" AND !empty($_POST['actualGroupName']) AND !empty($_POST['groupAddRepoName'])) {
  $actualGroupName = validateData($_POST['actualGroupName']);
  $groupAddRepoName = validateData($_POST['groupAddRepoName']);

  // on vérifie d'abord que le repo à ajouter existe bien
  $checkIfRepoExists = exec("grep '^Name=\"${groupAddRepoName}\"' $REPOS_LIST");
  if (empty($checkIfRepoExists)) {
    printAlert("Le repo $groupAddRepoName n'existe pas");
  } else {
    // on formatte la chaine à insérer à partir des infos récupérées en POST
    $groupNewContent = "Name=\"${groupAddRepoName}\"";
    // ensuite on commence par récupérer le n° de ligne où sera insérée la nouvelle chaine. Ici la commande sed affiche les numéros de lignes du groupe et tous ses repos actuels jusqu'à rencontrer une 
    // ligne vide (celle qui nous intéresse car on va insérer le nouveau repo à cet endroit), on ne garde donc que le dernier n° de ligne qui s'affiche (tail -n1) :  
    $lineToInsert = exec("sed -n '/\[${actualGroupName}\]/,/^$/=' $GROUPS_CONF | tail -n1");
    // enfin, on insert la nouvelle ligne au numéro de ligne récupéré :
    exec("sed -i '${lineToInsert}i\\${groupNewContent}' $GROUPS_CONF");

    // Affichage d'un message et rechargement de la div
    printAlert("Le repo $groupAddRepoName a été ajouté au groupe $actualGroupName");
    refreshdiv_class('divGroupsList');
    showdiv_class('divGroupsList');
  }
}

// Cas Debian :
if ($OS_FAMILY == "Debian" AND !empty($_POST['actualGroupName']) AND !empty($_POST['groupAddRepoName'])) {
  $actualGroupName = validateData($_POST['actualGroupName']);
  $groupAddRepoName = validateData($_POST['groupAddRepoName']);
  // Pour Debian, la fonction reposSelectList() a renvoyé une valeur contenant le nom du repo, la dist et la section séparés par un | (voir fonction reposSelectList())
  // Du coup on explose $addPlanRepo pour en extraire les 3 valeurs

  if ($OS_FAMILY == "Debian") {
    $groupAddRepoNameExplode = explode('|', $groupAddRepoName);
    $groupAddRepoName = $groupAddRepoNameExplode[0];
    $groupAddRepoDist = $groupAddRepoNameExplode[1];
    $groupAddRepoSection = $groupAddRepoNameExplode[2];
  }

  // on vérifie d'abord que la section à ajouter existe bien
  $checkIfSectionExists = exec("grep '^Name=\"${groupAddRepoName}\",Host=\".*\",Dist=\"${groupAddRepoDist}\",Section=\"${groupAddRepoSection}\"' $REPOS_LIST");
  if (empty($checkIfSectionExists)) {
    printAlert("La section $groupAddRepoSection du repo $groupAddRepoName n'existe pas");
  } else {
    // on formatte la chaine à insérer à partir des infos récupérées en POST
    $groupNewContent = "Name=\"${groupAddRepoName}\",Dist=\"${groupAddRepoDist}\",Section=\"${groupAddRepoSection}\"";
    // ensuite on commence par récupérer le n° de ligne où sera insérée la nouvelle chaine. Ici la commande sed affiche les numéros de lignes du groupe et tous ses repos actuels jusqu'à rencontrer une 
    // ligne vide (celle qui nous intéresse car on va insérer le nouveau repo à cet endroit), on ne garde donc que le dernier n° de ligne qui s'affiche (tail -n1) :  
    $lineToInsert = exec("sed -n '/\[${actualGroupName}\]/,/^$/=' $GROUPS_CONF | tail -n1");
    // enfin, on insert la nouvelle ligne au numéro de ligne récupéré :
    exec("sed -i '${lineToInsert}i\\${groupNewContent}' $GROUPS_CONF");
    
    // Affichage d'un message et rechargement de la div
    printAlert("La section $groupAddRepoSection du repo $groupAddRepoName a été ajoutée au groupe $actualGroupName");
    refreshdiv_class('divGroupsList');
    showdiv_class('divGroupsList');
  }
}


// Cas où on souhaite supprimer un repo d'un groupe :
// Cas Redhat :
if ($OS_FAMILY == "Redhat" AND isset($_GET['action']) AND ($_GET['action'] == "deleteGroupRepo") AND !empty($_GET['groupName']) AND !empty($_GET['repoName'])) {
  $groupName = validateData($_GET['groupName']);
  $groupDelRepoName = validateData($_GET['repoName']);

  // on formatte la chaine à supprimer à partir des infos récupérées en POST
  $groupDelContent = "Name=\"${groupDelRepoName}\"";
  // on supprime le repo en question, situé entre [@groupName] et la prochaine ligne vide
  //exec("sed -i '/^\[${groupName}\]/,/^$/{/^\(^${repoName}:${repoDist}:${repoSection}$\)/d}' $GROUPS_CONF");
  exec("sed -i '/^\[${groupName}\]/,/^$/{/^\(^${groupDelContent}$\)/d}' $GROUPS_CONF");
  
  // Affichage d'un message et rechargement de la div
  printAlert("Le repo $groupDelRepoName a été retiré du groupe $groupName");
  refreshdiv_class('divGroupsList');
  showdiv_class('divGroupsList');
}

// Cas Debian :
if ($OS_FAMILY == "Debian" AND isset($_GET['action']) AND ($_GET['action'] == "deleteGroupRepo" AND !empty($_GET['groupName']) AND !empty($_GET['repoName']) AND !empty($_GET['repoDist']) AND !empty($_GET['repoSection']))) {
  $groupName = validateData($_GET['groupName']);
  $groupDelRepoName = validateData($_GET['repoName']);
  $groupDelRepoDist = validateData($_GET['repoDist']);
  $groupDelRepoSection = validateData($_GET['repoSection']);

  // on formatte la chaine à supprimer à partir des infos récupérées en POST
  $groupDelContent = "Name=\"${groupDelRepoName}\",Dist=\"${groupDelRepoDist}\",Section=\"${groupDelRepoSection}\"";
  // on supprime le repo en question, situé entre [@groupName] et la prochaine ligne vide
  //exec("sed -i '/^\[${groupName}\]/,/^$/{/^\(^${repoName}:${repoDist}:${repoSection}$\)/d}' $GROUPS_CONF");
  exec("sed -i '/^\[${groupName}\]/,/^$/{/^\(^${groupDelContent}$\)/d}' $GROUPS_CONF");
  
  // Affichage d'un message et rechargement de la div
  printAlert("La section $groupDelRepoSection a été retiré du groupe $groupName");
  refreshdiv_class('divGroupsList');
  showdiv_class('divGroupsList');
}

// Cas où on souhaite renommer un groupe :
if (!empty($_POST['newGroupName']) AND !empty($_POST['actualGroupName'])) {
  $actualGroupName = validateData($_POST['actualGroupName']);
  $newGroupName = validateData($_POST['newGroupName']);

  if ("$newGroupName" !== "$actualGroupName") { // on traite à condition que $actualGroupName != $newGroupName 
    // On vérifie que le groupe n'existe pas déjà :
    $checkIfGroupExists = exec("grep '\[${newGroupName}\]' $GROUPS_CONF");
    if (!empty($checkIfGroupExists)) {
      printAlert("Le groupe $newGroupName existe déjà");
    } else {
      // il n'existe pas de fonction php permettant de remplacer clairement un pattern dans un fichier, donc on le fait avec un gros sed des familles :
      exec("sed -i 's/\[${actualGroupName}\]/\[${newGroupName}\]/g' $GROUPS_CONF");
      
      // Affichage d'un message et rechargement de la div
      printAlert("Le repo $actualGroupName a été renommé en $newGroupName");
      refreshdiv_class('divGroupsList');
      showdiv_class('divGroupsList');
    }
  }
}

// Cas où on souhaite supprimer un groupe :
if (isset($_GET['action']) AND ($_GET['action'] == "deleteGroup") AND !empty($_GET['groupName'])) {
  $groupName = validateData($_GET['groupName']);
  // supprime le nom du groupe entre [ ] ainsi que tout ce qui suit (ses repos) jusqu'à rencontrer une ligne vide (espace entre deux noms de groupes) :
  exec("sed -i '/^\[${groupName}\]/,/^$/{d;}' $GROUPS_CONF");
  // on formate un coup le fichier afin de supprimer les doubles saut de lignes si il y en a :
  exec('sed -i "/^$/N;/^\n$/D" '.$GROUPS_CONF.''); // obligé d'utiliser de simples quotes et de concatenation sinon php évalue le \n et la commande sed ne fonctionne pas
  
  // Affichage d'un message et rechargement de la div
  printAlert("Le groupe $groupName a été supprimé");
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
  if (isset($_GET['action']) AND ($_GET['action'] == "deleteRepoFile") AND !empty($_GET['repoFileName'])) {
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
    $newHostName = $_POST['newHostName'];
    $newHostUrl = $_POST['newHostUrl'];
    if (!empty($_POST['newHostGpgKey'])) { // on importe la clé si elle a été transmise 
        $newHostGpgKey = $_POST['newHostGpgKey'];
        $gpgTempFile = '/tmp/repomanager_newgpgkey.tmp'; // création d'un fichier temporaire
        file_put_contents($gpgTempFile, $newHostGpgKey, FILE_APPEND | LOCK_EX); // ajout de la clé gpg à l'intérieur d'un fichier temporaire, afin de l'importer
        $output=null; // un peu de gestion d'erreur
        $retval=null;
        exec("gpg --no-default-keyring --keyring ${GPGHOME}/trustedkeys.gpg --import $gpgTempFile", $output, $retval);
        if ($retval !== 0) {
          // Affichage d'un message et rechargement de la div
          printAlert("Erreur lors de l'import de la clé GPG");
          if ($debugMode == "yes") { print_r($output); }
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
  if (isset($_GET['action']) AND ($_GET['action'] == "deleteHost") AND !empty($_GET['hostName'])) {
    $hostName = $_GET['hostName'];
    exec('sed -i \'/^Name=\"'.$hostName.'\"/d\' '.$HOSTS_CONF);
    // Affichage d'un message et rechargement de la div
    printAlert("L'hôte $hostName a été supprimé. Vous ne pouvez plus créer ou mettre à jour des sections à partir de cet hôte");
    refreshdiv_class('divManageReposSources');
    showdiv_class('divManageReposSources');
  }

  // Cas où on souhaite supprimer un clé gpg du trousseau de repomanager :
  if (isset($_GET['action']) AND ($_GET['action'] == "deleteGpgKey") AND !empty($_GET['gpgKeyID'])) {
    $gpgKeyID = validateData($_GET['gpgKeyID']);
    exec("gpg --no-default-keyring --keyring ${GPGHOME}/trustedkeys.gpg --no-greeting --delete-key --batch --yes $gpgKeyID");
    // Affichage d'un message et rechargement de la div
    printAlert("La clé GPG a été supprimée");
    refreshdiv_class('divManageReposSources');
    showdiv_class('divManageReposSources');
  }
}

// Vérifications, présence des fichiers de base
if (!file_exists($PLAN_CONF)) { // Si le fichier de planifications n'existe pas, on le créé
  file_put_contents($PLAN_CONF, "[PLANIFICATIONS]\n\n");
}
if (!file_exists($GROUPS_CONF)) { // Si le fichier de groupes n'existe pas, on le créé
  file_put_contents($GROUPS_CONF, "[GROUPES]\n\n");
}


//// RECHARGEMENT PAGE ////
// Nettoyage du cache navigateur puis rechargement de la page si l'un des paramètres d'affichage ci-dessus a été passé en POST 
if (!empty($printRepoSize) OR !empty($filterByGroups) OR !empty($concatenateReposName) OR !empty($alternateColors) OR !empty($alternativeColor1) OR !empty($alternativeColor2)) {
  // Nettoyage du cache navigateur puis rechargement de la page
  echo "<script>";
  echo "Clear-Site-Data: \"*\";";
  echo "window.location.replace('/index.php');";
  echo "</script>";
}
?>