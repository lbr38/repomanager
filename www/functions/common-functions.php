<?php

// Fonction de vérification des données envoyées par formulaire
function validateData($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

function clearCache($WWW_CACHE) {
  // Suppression du cache serveur
  // 2 cas possibles : 
  // il s'agit d'un répertoire classique sur le disque
  // ou il s'agit d'un lien symbolique vers /dev/smh (en ram)
  if (file_exists("${WWW_CACHE}/repos-list-filter-group.html")) { unlink("${WWW_CACHE}/repos-list-filter-group.html"); }
  if (file_exists("${WWW_CACHE}/repos-list-no-filter.html")) { unlink("${WWW_CACHE}/repos-list-no-filter.html"); }
  if (is_link($WWW_CACHE)) { unlink($WWW_CACHE); }
  if (is_dir($WWW_CACHE)) { rmdir($WWW_CACHE); }

  // Vidage du cache navigateur
  echo "<script>";
  echo "Clear-Site-Data: \"*\";";
  echo "</script>";
}

function cleanConfFiles() {
  global $REPOS_LIST;
  global $REPOS_ARCHIVE_LIST;

  // Nettoie les lignes vides dans les fichiers de listes et tri
  if (file_exists("$REPOS_LIST")) {
    exec("sed -i '/^$/d' $REPOS_LIST");
    exec("sort -o $REPOS_LIST $REPOS_LIST");
  }

  if (file_exists("$REPOS_ARCHIVE_LIST")) {
    exec("sed -i '/^$/d' $REPOS_ARCHIVE_LIST");
    exec("sort -o $REPOS_ARCHIVE_LIST $REPOS_ARCHIVE_LIST");
  }
}

// Fonction permettant d'afficher une bulle d'alerte au mileu de la page
function printAlert($message) {
  echo '<div class="alert">';
  echo "<p>${message}</p>";
  echo '</div>';
  echo '<script type="text/javascript">';
  echo '$(document).ready(function () {';
  echo 'window.setTimeout(function() {';
  echo '$(".alert").fadeTo(1000, 0).slideUp(1000, function(){';
  echo '$(this).remove();';
  echo '});';
  echo '}, 2500);';
  echo '});';
  echo '</script>';
}

// Fonction affichant un message de confirmation avant de supprimer
// $message = le message à afficher
// $url = lien GET vers la page de suppression
// $divID = un id unique du div caché contenant le message et les bouton supprimer ou annuler
// $aID = une class avec un ID unique du bouton cliquable permettant d'afficher/fermer la div caché. Attention le bouton d'affichage doit être avant l'appel de cette fonction.
function deleteConfirm($message, $url, $divID, $aID) {
  echo "<div id=\"${divID}\" class=\"hide deleteAlert\">";
  echo "<p>${message}</p>";
  echo '<br>';
  echo "<a href=\"${url}\" class=\"deleteButton\">Supprimer</a>";
  echo "<span class=\"$aID pointer\">Annuler</span>";
  echo "<script>";
  echo "$(document).ready(function(){";
  echo "$(\".$aID\").click(function(){";
  echo "$(\"div#${divID}\").slideToggle(150);";
  echo '$(this).toggleClass("open");';
  echo "});";
  echo "});";
  echo "</script>";
  echo '</div>';

  unset($message, $url, $divID, $aID);
}

// vérification d'une nouvelle mise à jour github
function checkUpdate() {
  global $BASE_DIR;
  global $VERSION;
  global $GIT_VERSION;

  if (empty($GIT_VERSION)) {
    //echo "version : $GIT_VERSION";
    echo "<p>Erreur lors de la vérification des nouvelles mises à jour</p>";
  } elseif ("$VERSION" !== "$GIT_VERSION") {
    echo "<p>Une nouvelle version est disponible</p>";
  }
}

function operationRunning() {
  global $PID_DIR;

  if (!empty(exec("grep 'repomanager_' ${PID_DIR}/*.pid"))) {
    return true;
  }
  return false;
}

function planificationRunning() {
  global $PID_DIR;

  if (!empty(exec("grep 'plan_' ${PID_DIR}/*.pid"))) {
    return true;
  }
  return false;
}

// parsage du tableau contenant tous les détails d'une planification récupérés dans un array $plan
function planLogExplode($planId) {
  global $PLAN_LOGS_DIR;
  global $OS_FAMILY;

  $plan = file_get_contents("${PLAN_LOGS_DIR}/plan-${planId}.log");
  $array = parse_ini_string($plan);
  
  $planStatus = $array['Status'];
  if ($planStatus === "OK") {
    $planError = 'null'; // si on n'a pas eu d'erreur on set la variable à null
  } else {
    $planError = $array['Error'];
  }
  if (!empty($array['Date'])) { $planDate = $array['Date']; } else { $planDate = ''; }
  if (!empty($array['Time'])) { $planTime = $array['Time']; } else { $planTime = ''; }
  if (!empty($array['Action'])) { $planAction = $array['Action']; } else { $planAction = ''; }
  if (!empty($array['Group'])) { $planGroup = $array['Group']; } else { $planGroup = ''; }
  if (!empty($array['Repo'])) { $planRepo = $array['Repo']; } else { $planRepo = ''; }
  if (!empty($array['Dist'])) { $planDist = $array['Dist']; } else { $planDist = ''; }
  if (!empty($array['Section'])) { $planSection = $array['Section']; } else { $planSection = ''; }
  if (!empty($array['GpgCheck'])) { $planGpgCheck = $array['GpgCheck']; } else { $planGpgCheck = ''; }
  if (!empty($array['GpgResign'])) { $planGpgResign = $array['GpgResign']; } else { $planGpgResign = ''; }
  if (!empty($array['Reminder'])) { $planReminder = $array['Reminder']; } else { $planReminder = ''; }
  if (!empty($array['Logfile'])) { $planLogFile = $array['Logfile']; } else { $planLogFile = ''; }

  // On renvoie un return contenant toutes les valeurs ci-dessus, même celle nulles, ceci afin de s'adapter à toutes les situations et OS
  if ($OS_FAMILY == "Redhat") {
    return array($planStatus, $planError, $planDate, $planTime, $planAction, $planGroup, $planRepo, $planGpgCheck, $planGpgResign, $planReminder, $planLogFile);
  }
  if ($OS_FAMILY == "Debian") {
    return array($planStatus, $planError, $planDate, $planTime, $planAction, $planGroup, $planRepo, $planDist, $planSection, $planGpgCheck, $planReminder, $planLogFile);
  }
}

function selectlogs() {
  global $MAIN_LOGS_DIR;

  // Si un fichier de log est actuellement sélectionné (en GET) alors on récupère son nom afin qu'il soit sélectionné dans la liste déroulante (s'il apparait)
  if (!empty($_GET['logfile'])) {
    $currentLogfile = validateData($_GET['logfile']);
  } else {
    $currentLogfile = '';
  }

  // On récupère la liste des fichiers de logs en les triant 
  $logfiles = scandir("$MAIN_LOGS_DIR/", SCANDIR_SORT_DESCENDING);
  echo '<form action="run.php" method="get" class="is-inline-block">';
	echo '<select name="logfile" class="select-xxlarge">';
  echo "<option value=\"\">Historique de traitements</option>";
	foreach($logfiles as $logfile) {
    // on ne souhaite pas afficher les répertoires '..' '.' ni le fichier lastlog.log (déjà affiché en premier ci-dessus) et on souhaite uniquement afficher les fichier commencant par repomanager_
		if (($logfile != "..") AND ($logfile != ".") AND ($logfile != "lastlog.log") AND preg_match('/^repomanager_/',$logfile)) {
      // Formatage du nom du fichier afin d'afficher quelque chose de plus propre dans la liste
      $logfileDate = exec("echo $logfile | awk -F '_' '{print $2}'");
      $logfileDate = DateTime::createFromFormat('Y-m-d', $logfileDate)->format('d-m-Y');
      $logfileTime = exec("echo $logfile | awk -F '_' '{print $3}' | sed 's/.log//g'");
      $logfileTime = DateTime::createFromFormat('H-i-s', $logfileTime)->format('H:i:s');
      if ($logfile === $currentLogfile) {
        echo "<option value=\"${logfile}\" selected>Repomanager : traitement du $logfileDate à $logfileTime</option>";
      } else {
        echo "<option value=\"${logfile}\">Repomanager : traitement du $logfileDate à $logfileTime</option>";
      }
		}
	}
	echo '</select>';
	echo '<button type="submit" class="button-submit-xsmall-blue">Afficher</button>';
  echo '</form>';

  unset($logfiles, $logfile, $logfileDate, $logfileTime);
}

function selectPlanlogs() {
  global $MAIN_LOGS_DIR;

  // Si un fichier de log est actuellement sélectionné (en GET) alors on récupère son nom afin qu'il soit sélectionné dans la liste déroulante (s'il apparait)
  if (!empty($_GET['logfile'])) {
    $currentLogfile = validateData($_GET['logfile']);
  } else {
    $currentLogfile = '';
  }

  // On récupère la liste des fichiers de logs en les triant 
  $logfiles = scandir("$MAIN_LOGS_DIR/", SCANDIR_SORT_DESCENDING);
  echo '<form action="run.php" method="get" class="is-inline-block">';
	echo '<select name="logfile" class="select-xxlarge">';
	echo "<option value=\"\">Historique de planifications</option>";
	foreach($logfiles as $logfile) {
    // on ne souhaite pas afficher les répertoires '..' '.' ni le fichier lastlog.log (déjà affiché en premier ci-dessus) et on souhaite uniquement afficher les fichier commencant par repomanager_
		if (($logfile != "..") AND ($logfile != ".") AND ($logfile != "lastlog.log") AND preg_match('/^plan_/',$logfile)) {
      // Formatage du nom du fichier afin d'afficher quelque chose de plus propre dans la liste
      $logfileDate = exec("echo $logfile | awk -F '_' '{print $2}'");
      $logfileDate = DateTime::createFromFormat('Y-m-d', $logfileDate)->format('d-m-Y');
      $logfileTime = exec("echo $logfile | awk -F '_' '{print $3}' | sed 's/.log//g'");
      $logfileTime = DateTime::createFromFormat('H-i-s', $logfileTime)->format('H:i:s');
      if ($logfile === $currentLogfile) {
        echo "<option value=\"${logfile}\" selected>Planification : traitement du $logfileDate à $logfileTime</option>";
      } else {
        echo "<option value=\"${logfile}\">Planification : traitement du $logfileDate à $logfileTime</option>";
      }
		}
	}
	echo '</select>';
	echo '<button type="submit" class="button-submit-xsmall-blue">Afficher</button>';
  echo '</form>';

  unset($logfiles, $logfile, $logfileDate, $logfileTime);
}

function reloadPage($actual_uri) {
  header("location: $actual_uri");
}

// Rechargement d'une div en fournissant sa class
function refreshdiv_class($divclass) {
  if (!empty($divclass)) {
    echo '<script>';
    echo "$( \".${divclass}\" ).load(window.location.href + \" .${divclass}\" );";
    echo '</script>';
  }
}

// Affichage d'une div cachée
function showdiv_class($divclass) {
  echo '<script>';
  echo "$(document).ready(function() {";
  echo "$('.${divclass}').show(); })";
  echo '</script>';
}

// Liste déroulante des repos/sections
// Avant d'appeler cette fonction il faut prévoir un select car celle-ci n'affiche que les options
function reposSelectList() {
  global $OS_FAMILY;
  global $REPOS_LIST;

  echo '<option value="">Sélectionnez un repo...</option>';
  $rows = explode("\n", file_get_contents($REPOS_LIST));
  $lastRepoName = '';
  $lastRepoDist = '';
  $lastRepoSection = '';
  foreach($rows as $row) {
    if(!empty($row) AND $row !== "[REPOS]") { // on ne traite pas les lignes vides ni la ligne [REPOS] (1ère ligne du fichier)
      $rowData = explode(',', $row);
      if ($OS_FAMILY == "Redhat") {
        $repoName = strtr($rowData['0'], ['Name=' => '', '"' => '']);
        $repoEnv = strtr($rowData['2'], ['Env=' => '', '"' => '']);
        $repoDate = strtr($rowData['3'], ['Date=' => '', '"' => '']);
        $repoDescription = strtr($rowData['4'], ['Description=' => '', '"' => '']);
      }
      if ($OS_FAMILY == "Debian") {
        $repoName = strtr($rowData['0'], ['Name=' => '', '"' => '']);
        $repoDist = strtr($rowData['2'], ['Dist=' => '', '"' => '']);
        $repoSection = strtr($rowData['3'], ['Section=' => '', '"' => '']);
        $repoEnv = strtr($rowData['4'], ['Env=' => '', '"' => '']);
        $repoDate = strtr($rowData['5'], ['Date=' => '', '"' => '']);
        $repoDescription = strtr($rowData['6'], ['Description=' => '', '"' => '']);
      }
      // Pour ne pas afficher de valeurs en double dans la liste
      if ($OS_FAMILY == "Redhat" AND $repoName !== $lastRepoName) {
        echo "<option value=\"${repoName}\">${repoName}</option>";
      }
      // Pour ne pas afficher de valeurs en double dans la liste
      if ($OS_FAMILY == "Debian" AND ($repoName !== $lastRepoName OR $repoDist !== $lastRepoDist OR $repoSection !== $lastRepoSection)) {
        echo "<option value=\"${repoName}|${repoDist}|${repoSection}\">${repoName} - ${repoDist} - ${repoSection}</option>";
      }
      $lastRepoName = $repoName;
      if ($OS_FAMILY == "Debian") {
        $lastRepoDist = $repoDist;
        $lastRepoSection = $repoSection;
      }
    }
  }
  unset($rows, $rowData, $lastRepoName, $repoName, $repoEnv, $repoDate, $repoDescription);
  if ($OS_FAMILY == "Debian") {
    unset($repoDist, $repoSection);
  }
}

// Liste déroulante des repos/sections. 
// Ici seuls ceux de l'environnement $DEFAULT_ENV sont affichés
// Avant d'appeler cette fonction il faut prévoir un select car celle-ci n'affiche que les options
function reposSelectList_defaultEnv() {
  global $OS_FAMILY;
  global $REPOS_LIST;
  global $DEFAULT_ENV;

  echo '<option value="">Sélectionnez un repo...</option>';
  $rows = explode("\n", shell_exec("grep 'Env=\"${DEFAULT_ENV}\"' $REPOS_LIST"));
  $lastRepoName = '';
  $lastRepoDist = '';
  $lastRepoSection = '';
  foreach($rows as $row) {
    if(!empty($row) AND $row !== "[REPOS]") { // on ne traite pas les lignes vides ni la ligne [REPOS] (1ère ligne du fichier)
      $rowData = explode(',', $row);
      if ($OS_FAMILY == "Redhat") {
        $repoName = strtr($rowData['0'], ['Name=' => '', '"' => '']);
        $repoEnv = strtr($rowData['2'], ['Env=' => '', '"' => '']);
        $repoDate = strtr($rowData['3'], ['Date=' => '', '"' => '']);
        $repoDescription = strtr($rowData['4'], ['Description=' => '', '"' => '']);
      }
      if ($OS_FAMILY == "Debian") {
        $repoName = strtr($rowData['0'], ['Name=' => '', '"' => '']);
        $repoDist = strtr($rowData['2'], ['Dist=' => '', '"' => '']);
        $repoSection = strtr($rowData['3'], ['Section=' => '', '"' => '']);
        $repoEnv = strtr($rowData['4'], ['Env=' => '', '"' => '']);
        $repoDate = strtr($rowData['5'], ['Date=' => '', '"' => '']);
        $repoDescription = strtr($rowData['6'], ['Description=' => '', '"' => '']);
      }
      
      // Pour ne pas afficher de valeurs en double dans la liste
      if ($OS_FAMILY == "Redhat" AND $repoName !== $lastRepoName) {
        echo "<option value=\"${repoName}\">${repoName}</option>";
      }
      // Pour ne pas afficher de valeurs en double dans la liste
      if ($OS_FAMILY == "Debian" AND ($repoName !== $lastRepoName OR $repoDist !== $lastRepoDist OR $repoSection !== $lastRepoSection)) {
        echo "<option value=\"${repoName}|${repoDist}|${repoSection}\">${repoName} - ${repoDist} - ${repoSection}</option>";
      }
      $lastRepoName = $repoName;
      if ($OS_FAMILY == "Debian") {
        $lastRepoDist = $repoDist;
        $lastRepoSection = $repoSection;
      }
    }
  }
  unset($rows, $rowData, $lastRepoName, $lastRepoDist, $lastRepoSection, $repoName, $repoEnv, $repoDate, $repoDescription);
  if ($OS_FAMILY == "Debian") {
    unset($repoDist, $repoSection);
  }
}

// Liste déroulante des groupes
// Avant d'appeler cette fonction il faut prévoir un select car celle-ci n'affiche que les options
function groupsSelectList() {
  global $GROUPS_CONF;

  echo '<option value="">Sélectionnez un groupe...</option>';
  $repoGroups = shell_exec("grep '^\[@.*\]' $GROUPS_CONF"); // récupération de tous les noms de groupes si il y en a 
  if (!empty($repoGroups)) {
    $repoGroups = preg_split('/\s+/', trim($repoGroups)); // on éclate le résultat précédent car tout a été récupéré sur une seule ligne
    foreach($repoGroups as $groupName) {
      $groupName = str_replace(["[", "]"], "", $groupName); // On retire les [ ] autour du nom du groupe
      echo "<option value=\"${groupName}\">${groupName}</option>";
    }
  }
  unset($repoGroups, $groupName);
}

// Créer un nouveau groupe
function newGroup($addGroupName) {
  global $GROUPS_CONF;

  // On vérifie que le groupe n'existe pas déjà :
  $checkIfGroupExists = exec("grep '\[@${addGroupName}\]' $GROUPS_CONF");
  if (!empty($checkIfGroupExists)) {
    printAlert("Le groupe <b>$addGroupName</b> existe déjà");
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
    printAlert("Le groupe <b>$addGroupName</b> a été créé");
  }
  unset($addGroupName, $checkIfGroupExists);
}

// Ajouter un repo à un groupe
function addRepoToGroup($repoName, $groupName) {
  global $REPOS_LIST;
  global $GROUPS_CONF;
  global $OS_FAMILY;
  $error = 0;

  if ($OS_FAMILY == "Redhat") {
    // on vérifie d'abord que le repo à ajouter existe bien
    $checkIfRepoExists = exec("grep '^Name=\"${repoName}\"' $REPOS_LIST");
    if (empty($checkIfRepoExists)) {
      printAlert("Le repo <b>$repoName</b> n'existe pas");
      $error++;
    }
    // On vérifie que le repo n'est pas déjà présent dans le groupe
    $checkIfRepoIsAlreadyInGroup = shell_exec("sed -n '/\[${groupName}\]/,/\[/p' $GROUPS_CONF | sed '/^$/d' | grep '^Name=\"${repoName}\"'");
    if (!empty($checkIfRepoIsAlreadyInGroup)) {
      printAlert("Le repo <b>$repoName</b> est déjà présent dans le groupe <b>$groupName</b>");
      $error++;
    }
    // On traite uniquement si il n'y a pas eu d'erreurs
    if ($error === 0) {
      // on formatte la chaine à insérer à partir des infos récupérées en POST
      $groupNewContent = "Name=\"${repoName}\"";
      // ensuite on commence par récupérer le n° de ligne où sera insérée la nouvelle chaine. Ici la commande sed affiche les numéros de lignes du groupe et tous ses repos actuels jusqu'à rencontrer une 
      // ligne vide (celle qui nous intéresse car on va insérer le nouveau repo à cet endroit), on ne garde donc que le dernier n° de ligne qui s'affiche (tail -n1) :  
      $lineToInsert = exec("sed -n '/\[${groupName}\]/,/^$/=' $GROUPS_CONF | tail -n1");
      // enfin, on insert la nouvelle ligne au numéro de ligne récupéré :
      exec("sed -i '${lineToInsert}i\\${groupNewContent}' $GROUPS_CONF");

      // Affichage d'un message et rechargement de la div
      printAlert("Le repo <b>$repoName</b> a été ajouté au groupe <b>$groupName</b>");
    }
  }

  if ($OS_FAMILY == "Debian") {
    // Pour Debian, la variable $repoName contient le nom du repo, la dist et la section séparés par un | 
    // Du coup on explose $addPlanRepo pour en extraire les 3 valeurs
    $repoNameExplode = explode('|', $repoName);
    $repoName = $repoNameExplode[0];
    $repoDist = $repoNameExplode[1];
    $repoSection = $repoNameExplode[2];
  
    // on vérifie d'abord que la section à ajouter existe bien
    $checkIfSectionExists = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\"' $REPOS_LIST");
    if (empty($checkIfSectionExists)) {
      printAlert("La section <b>$repoSection</b> du repo <b>$repoName</b> n'existe pas");
      $error++;
    }
    // On vérifie que la section de repo n'est pas déjà présente dans le groupe
    $checkIfRepoIsAlreadyInGroup = shell_exec("sed -n '/\[${groupName}\]/,/\[/p' $GROUPS_CONF | sed '/^$/d' | grep '^Name=\"${repoName}\",Dist=\"${repoDist}\",Section=\"${repoSection}\"'");
    if (!empty($checkIfRepoIsAlreadyInGroup)) {
      printAlert("La section <b>$repoSection</b> du repo <b>$repoName</b> est déjà présente dans le groupe <b>$groupName</b>");
      $error++;
    }
    // On traite uniquement si il n'y a pas eu d'erreurs
    if ($error === 0) {
      // on formatte la chaine à insérer à partir des infos récupérées en POST
      $groupNewContent = "Name=\"${repoName}\",Dist=\"${repoDist}\",Section=\"${repoSection}\"";
      // ensuite on commence par récupérer le n° de ligne où sera insérée la nouvelle chaine. Ici la commande sed affiche les numéros de lignes du groupe et tous ses repos actuels jusqu'à rencontrer une 
      // ligne vide (celle qui nous intéresse car on va insérer le nouveau repo à cet endroit), on ne garde donc que le dernier n° de ligne qui s'affiche (tail -n1) :  
      $lineToInsert = exec("sed -n '/\[${groupName}\]/,/^$/=' $GROUPS_CONF | tail -n1");
      // enfin, on insert la nouvelle ligne au numéro de ligne récupéré :
      exec("sed -i '${lineToInsert}i\\${groupNewContent}' $GROUPS_CONF");
      // Affichage d'un message et rechargement de la div
      printAlert("La section <b>$repoSection</b> du repo <b>$repoName</b> a été ajoutée au groupe <b>$groupName</b>");
    }
  }
  unset($checkIfSectionExists, $checkIfRepoIsAlreadyInGroup, $groupNewContent, $lineToInsert, $repoName, $groupName);
}

// Suppression d'un repo d'un groupe en particulier
function deleteRepoFromGroup($repoName, $groupName) {
  global $GROUPS_CONF;
  $error = 0;
  
  // on formatte la chaine à supprimer à partir des infos récupérées en POST
  $groupDelContent = "Name=\"${repoName}\"";
  // on supprime le repo en question, situé entre [@groupName] et la prochaine ligne vide
  exec("sed -i '/^\[${groupName}\]/,/^$/{/^\(^${groupDelContent}$\)/d}' $GROUPS_CONF");
  // Affichage d'un message
  printAlert("Le repo <b>$repoName</b> a été retiré du groupe <b>$groupName</b>");
  
  unset($repoName, $groupDelContent, $groupName);
}
// Suppression d'une section d'un groupe en particulier
function deleteSectionFromGroup($repoName, $groupName) {
  global $GROUPS_CONF;
  $error = 0;

  // Pour Debian, la variable $repoName contient le nom du repo, la dist et la section séparés par un | 
  // Du coup on explose $addPlanRepo pour en extraire les 3 valeurs
  $repoNameExplode = explode('|', $repoName);
  $repoName = $repoNameExplode[0];
  $repoDist = $repoNameExplode[1];
  $repoSection = $repoNameExplode[2];
  // Si la distribution comporte un slash, il faut l'échapper
  //$repoDist = str_replace('/', '\\\/', $repoDist);
  // on formatte la chaine à supprimer à partir des infos récupérées en POST
  $groupDelContent = "Name=\"${repoName}\",Dist=\"${repoDist}\",Section=\"${repoSection}\"";
  // on supprime le repo en question, situé entre [@groupName] et la prochaine ligne vide
  exec("sed -i '/^\[${groupName}\]/,/^$/{/^\(^${groupDelContent}$\)/d}' $GROUPS_CONF");
  // Ré-écrit le contenu dans le fichier de groupes  
  // Affichage d'un message
  printAlert("La section <b>$repoSection</b> du repo <b>$repoName</b> a été retirée du groupe <b>$groupName</b>");

  unset($repoName, $groupDelContent, $groupName);
}

// Suppression d'un repo de tous les groupes où il apparait
function deleteRepoFromAllGroup($repoName) {
  global $GROUPS_CONF;

  // Récupération du contenu du fichier de groupes
  $content = file_get_contents("$GROUPS_CONF");
  $content = preg_replace("/Name=\"${repoName}\".*/", "", $content);
  // Ré-écrit le contenu dans le fichier de groupes  
  file_put_contents("$GROUPS_CONF", $content);
  unset($repoName, $content);
}

// Suppression d'une section de tous les groupes où elle apparait
function deleteSectionFromAllGroup($repoName) {
  global $GROUPS_CONF;

  // Pour Debian, la variable $repoName contient le nom du repo, la dist et la section séparés par un | 
  // Du coup on explose $addPlanRepo pour en extraire les 3 valeurs
  $repoNameExplode = explode('|', $repoName);
  $repoName = $repoNameExplode[0];
  $repoDist = $repoNameExplode[1];
  $repoSection = $repoNameExplode[2];

  // Récupération du contenu du fichier de groupes
  $content = file_get_contents("$GROUPS_CONF");
  $content = preg_replace("/Name=\"${repoName}\",Dist=\"$repoDist\",Section=\"$repoSection\".*/", "", $content);
  // Ré-écrit le contenu dans le fichier de groupes  
  file_put_contents("$GROUPS_CONF", $content);
  unset($repoName, $content);
}

function renameGroup($actualGroupName, $newGroupName) {
  global $GROUPS_CONF;

  // on traite à condition que $actualGroupName != $newGroupName
  if ("$newGroupName" !== "$actualGroupName") { 
    // On vérifie que le nouveau nom de groupe n'existe pas déjà :
    $checkIfGroupExists = exec("grep '\[${newGroupName}\]' $GROUPS_CONF");
    if (!empty($checkIfGroupExists)) {
      printAlert("Le groupe <b>$newGroupName</b> existe déjà");
    } else {
      // Remplacement
      exec("sed -i 's/\[${actualGroupName}\]/\[${newGroupName}\]/g' $GROUPS_CONF");
      // Affichage d'un message
      printAlert("Le repo <b>$actualGroupName</b> a été renommé en <b>$newGroupName</b>");
    }
  }
  unset($actualGroupName, $newGroupName);
}

function deleteGroup($groupName) {
  global $GROUPS_CONF;

  $checkIfGroupExists = exec("grep '\[${groupName}\]' $GROUPS_CONF");
  if (empty($checkIfGroupExists)) {
    printAlert("Le groupe <b>$groupName</b> n'existe pas");
  } else {
    // supprime le nom du groupe entre [ ] ainsi que tout ce qui suit (ses repos) jusqu'à rencontrer une ligne vide (espace entre deux noms de groupes) :
    exec("sed -i '/^\[${groupName}\]/,/^$/{d;}' $GROUPS_CONF");
    // on formate un coup le fichier afin de supprimer les doubles saut de lignes si il y en a :
    exec('sed -i "/^$/N;/^\n$/D" '.$GROUPS_CONF.''); // obligé d'utiliser de simples quotes et de concatenation sinon php évalue le \n et la commande sed ne fonctionne pas
    // Affichage d'un message et rechargement de la div
    printAlert("Le groupe <b>$groupName</b> a été supprimé");
    unset($checkIfGroupExists, $groupName);
  }
}

// Créer un nouveau fichier de log et un PID pour une opération en cours
function createLog() {
  global $MAIN_LOGS_DIR;
  global $PID_DIR;
  $date = exec("date +%Y-%m-%d");
  $heure = exec("date +%H-%M-%S");
  $LOG = "repomanager_${date}_${heure}.log";
  $PID = mt_rand(10001, 99999);

  // Génération du fichier PID
  if (!file_exists("$PID_DIR")) {
    mkdir("$PID_DIR", 0770);
  }
  if (!file_exists("${PID_DIR}/${PID}.pid")) {
    touch("${PID_DIR}/${PID}.pid");
    file_put_contents("${PID_DIR}/${PID}.pid", "PID=\"$PID\"\nLOG=\"$LOG\"");
  }

  // Génération du fichier de log
  if (file_exists("${MAIN_LOGS_DIR}/lastlog.log")) {
    unlink("${MAIN_LOGS_DIR}/lastlog.log");
  }
  exec("ln -s $LOG ${MAIN_LOGS_DIR}/lastlog.log");
  file_put_contents("${MAIN_LOGS_DIR}/${LOG}", "<html><br>
  <span>Opération exécutée le : <b>${date} à ${heure}</b></span><br>
  <span>PID : <b>${PID}.pid</b></span><br><br>");

  // On retourne le PID et le nom du fichier de logs
  return array($PID, $LOG);
}

// Créer un nouveau fichier de log et un PID pour une planification en cours
function createPlanLog($planId) {
  global $MAIN_LOGS_DIR;
  global $PID_DIR;
  $date = exec("date +%Y-%m-%d");
  $heure = exec("date +%H-%M-%S");
  $LOG = "plan_${date}_${heure}_$planId.log";
  $PID = $planId;

  // Génération du fichier PID
  if (!file_exists("$PID_DIR")) {
    mkdir("$PID_DIR", 0770);
  }
  if (!file_exists("${PID_DIR}/${PID}.pid")) {
    touch("${PID_DIR}/${PID}.pid");
    file_put_contents("${PID_DIR}/${PID}.pid", "PID=\"$PID\"\nLOG=\"$LOG\"");
  }

  // Génération du fichier de log
  if (file_exists("${MAIN_LOGS_DIR}/lastlog.log")) {
    unlink("${MAIN_LOGS_DIR}/lastlog.log");
  }
  exec("ln -s $LOG ${MAIN_LOGS_DIR}/lastlog.log");
  file_put_contents("${MAIN_LOGS_DIR}/${LOG}", "<html><br>
  <span>Traitement de la planification <b>'Plan-${planId}'</b> le : <b>${date} à ${heure}</b></span><br>
  <span>PID : <b>${PID}.pid</b></span><br><br>");

  // On retourne le PID et le nom du fichier de logs
  return array($PID, $LOG);
}

// Ecriture dans le fichier de logs
function writeLog($msg) {
  global $MAIN_LOGS_DIR;

  $LOG = "${MAIN_LOGS_DIR}/lastlog.log";
  file_put_contents("$LOG", "$msg", FILE_APPEND);
}

// Suppression du fichier PID
function deletePid($PID) {
  global $PID_DIR;

  if (file_exists("${PID_DIR}/${PID}.pid")) { unlink("${PID_DIR}/${PID}.pid"); }
}

function closeOperation($PID) {
  writeLog("</table>");           // Cloture du tableau du fichier de log
  deletePid($PID);                // Suppression du PID
  cleanConfFiles();               // Nettoyage des fichiers de listes de repos
  refreshdiv_class("list-repos"); // Rafraichissement de la liste des repos
  refreshdiv_class("list-repos-archived");
}

function checkCronReminder() {
  $cronStatus = shell_exec("crontab -l | grep 'planifications/plan.php' | grep -v '#'");
  if (empty($cronStatus)) {
    return 'Off';
  } else {
    return 'On';
  }
}

if (!function_exists('write_ini_file')) {
  /**
   * Write an ini configuration file
   * 
   * @param string $file
   * @param array  $array
   * @return bool
   */ 
  function write_ini_file($file, $array = []) {
      // check first argument is string
      if (!is_string($file)) {
          throw new \InvalidArgumentException('Function argument 1 must be a string.');
      }

      // check second argument is array
      if (!is_array($array)) {
          throw new \InvalidArgumentException('Function argument 2 must be an array.');
      }

      // process array
      $data = array();
      foreach ($array as $key => $val) {
          if (is_array($val)) {
              $data[] = "[$key]";
              foreach ($val as $skey => $sval) {
                  if (is_array($sval)) {
                      foreach ($sval as $_skey => $_sval) {
                          if (is_numeric($_skey)) {
                              $data[] = $skey.'[] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
                          } else {
                              $data[] = $skey.'['.$_skey.'] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
                          }
                      }
                  } else {
                      $data[] = $skey.' = '.(is_numeric($sval) ? $sval : (ctype_upper($sval) ? $sval : '"'.$sval.'"'));
                  }
              }
          } else {
              $data[] = $key.' = '.(is_numeric($val) ? $val : (ctype_upper($val) ? $val : '"'.$val.'"'));
          }
          // empty line
          $data[] = null;
      }

      // open file pointer, init flock options
      $fp = fopen($file, 'w');
      $retries = 0;
      $max_retries = 100;

      if (!$fp) {
          return false;
      }

      // loop until get lock, or reach max retries
      do {
          if ($retries > 0) {
              usleep(rand(1, 5000));
          }
          $retries += 1;
      } while (!flock($fp, LOCK_EX) && $retries <= $max_retries);

      // couldn't get the lock
      if ($retries == $max_retries) {
          return false;
      }

      // got lock, write data
      fwrite($fp, implode(PHP_EOL, $data).PHP_EOL);

      // release lock
      flock($fp, LOCK_UN);
      fclose($fp);

      return true;
  }
}

// Ecrit le contenu de la crontab de $WWW_USER
function enableCron() {
  global $WWW_DIR;
  global $WWW_USER;
  global $TEMP_DIR;
  global $CRON_DAILY_ENABLED;
  global $AUTOMATISATION_ENABLED;
  global $CRON_PLAN_REMINDERS_ENABLED;

  // Récupération du contenu de la crontab actuelle dans un fichier temporaire
  shell_exec("crontab -l > ${TEMP_DIR}/${WWW_USER}_crontab.tmp");

  // On supprime toutes les lignes concernant repomanager dans ce fichier pour refaire propre
  exec("sed -i '/cronjob_daily.php/d' ${TEMP_DIR}/${WWW_USER}_crontab.tmp");
  exec("sed -i '/plan.php/d' ${TEMP_DIR}/${WWW_USER}_crontab.tmp");

  // Puis on ajoute les tâches cron suivantes au fichier temporaire

  // Tâche cron journalière
  if ($CRON_DAILY_ENABLED == "yes") {
    file_put_contents("${TEMP_DIR}/${WWW_USER}_crontab.tmp", "*/5 * * * * php ${WWW_DIR}/operations/cronjob_daily.php".PHP_EOL, FILE_APPEND);
  }

  // si on a activé l'automatisation alors on ajoute la tâche cron d'exécution des planifications
  if ($AUTOMATISATION_ENABLED == "yes") {
    file_put_contents("${TEMP_DIR}/${WWW_USER}_crontab.tmp", "* * * * * php ${WWW_DIR}/planifications/plan.php exec-plans".PHP_EOL, FILE_APPEND);
  }

  // si on a activé l'automatisation et les envois de rappels de planifications alors on ajoute la tâche cron d'envoi des rappels
  if ($AUTOMATISATION_ENABLED == "yes" AND $CRON_PLAN_REMINDERS_ENABLED == "yes") {
    file_put_contents("${TEMP_DIR}/${WWW_USER}_crontab.tmp", "0 0 * * * php ${WWW_DIR}/planifications/plan.php send-reminders".PHP_EOL, FILE_APPEND);
  }

  // Enfin on reimporte le contenu du fichier temporaire
  exec("crontab ${TEMP_DIR}/${WWW_USER}_crontab.tmp");   // on importe le fichier dans la crontab de $WWW_USER
  unlink("${TEMP_DIR}/${WWW_USER}_crontab.tmp");         // puis on supprime le fichier temporaire
}
?>