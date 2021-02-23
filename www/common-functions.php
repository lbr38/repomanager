<?php

// Fonction de vérification des données envoyées par formulaire
function validateData($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

// Fonction permettant d'afficher une bulle d'alerte au mileu de la page
function printAlert($message) {
  echo "<div class=\"alert\">";
  echo "<p>${message}</p>";
  echo "</div>";
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

// explosion du tableau contenant tous les détails d'une planification récupérés dans une variable $plan
function planLogExplode($planId) {
  global $PLAN_LOG;
  global $OS_FAMILY;

  if (!file_exists($PLAN_LOG)) {
    return "N/A";
  }

  $i = 1;
  $plan = shell_exec("sed -n '/\[Plan-${planId}\]/,/\[/p' $PLAN_LOG | sed '/^$/d' | grep -v '^\['");
  $plan = explode("\n", $plan);

  $planStatus = str_replace(['Status=', '"'], '', $plan[0]); // on récupère le status en retirant 'Status=""' de l'expression
  if ($planStatus === "Error") {
    $planError = str_replace(['Error=', '"'], '', $plan[1]); // on récupère l'erreur en retirant 'Error=""' de l'expression
    $i++;
  }
  if ($planStatus === "OK") {
    $planError = 'null'; // si on n'a pas eu d'erreur on set la variable à null
  }

  // Récupération de la date, de l'heure et de l'action
  $planDate = str_replace(['Date=', '"'], '', $plan[$i]); // on récupère la date en retirant 'Date=""' de l'expression
  $i++;
  $planTime = str_replace(['Time=', '"'], '', $plan[$i]); // on récupère l'heure en retirant 'Time=""' de l'expression
  $i++;
  $planAction = str_replace(['Action=', '"'], '', $plan[$i]); // on récupère l'action en retirant 'Action=""' de l'expression
  $i++;

  if(substr($plan[$i], 0, 5) == "Group") { // si la ligne suivante commence par Group=
    $planRepoOrGroup = "Group"; // on aura besoin d'indiquer dans le return si c'est un group ou un repo
    $planGroup = str_replace(['Group=', '"'], '', $plan[$i]); // on récupère le groupe en retirant 'Group=""' de l'expression
    $i++;
    $planRepo = 'null'; // comme il s'agit d'un groupe, alors ce n'est pas un repo, on set donc cette variable à null
    if ($OS_FAMILY == "Debian") { // Si Debian alors on set d'autres variables supplémentaires à null
      $planDist = '-';
      $planSection = '-';
    }
  }

  if(substr($plan[$i], 0, 4) == "Repo") { // sinon si la ligne suivante commence par Repo= alors c'est un repo, sinon c'est un groupe
    $planRepoOrGroup = "Repo"; // on aura besoin d'indiquer dans le return si c'est un group ou un repo
    $planRepo = str_replace(['Repo=', '"'], '', $plan[$i]); // on récupère le repo en retirant 'Repo=""' de l'expression
    $i++;
    $planGroup = 'null'; // comme il s'agit d'un repo, alors ce n'est pas un group, on set donc cette variable à null
    if ($OS_FAMILY == "Debian") { // Si Debian, alors on récupère la dist et la section aussi
      $planDist = str_replace(['Dist=', '"'], '', $plan[$i]); // on récupère la distribution en retirant 'Dist=""' de l'expression
      $i++;
      $planSection = str_replace(['Section=', '"'], '', $plan[$i]); // on récupère la section en retirant 'Section=""' de l'expression
      $i++;
    }
  }

  if ($planAction == "update") { // si planAction = 'update' alors il faut récupérer la valeur de GpgCheck
    $planGpgCheck = str_replace(['GpgCheck=', '"'], '', $plan[$i]);
    $i++;
  } else {
    $planGpgCheck = '-';
  }

  if (($OS_FAMILY == "Redhat") AND ($planAction == "update")) { // si planAction = 'update' alors il faut récupérer la valeur de GpgResign
      $planGpgResign = str_replace(['GpgResign=', '"'], '', $plan[$i]);
      $i++;
  } else {
    $planGpgResign = '-';
  }

  $planReminder = str_replace(['Reminder=', '"'], '', $plan[$i]); // on récupère les rappels en retirant 'Reminder=""' de l'expression
  $i++;
  $planLogFile = str_replace(['Logfile=', '"'], '', $plan[$i]); // on récupère les rappels en retirant 'Logfile=""' de l'expression

  // On renvoie un return contenant toutes les valeurs ci-dessus, même celle nulles, ceci afin de s'adapter à toutes les situations et OS
  if ($OS_FAMILY == "Redhat") {
    return array($planStatus, $planError, $planDate, $planTime, $planAction, $planRepoOrGroup, $planGroup, $planRepo, $planGpgCheck, $planGpgResign, $planReminder, $planLogFile);
  }
  if ($OS_FAMILY == "Debian") {
    return array($planStatus, $planError, $planDate, $planTime, $planAction, $planRepoOrGroup, $planGroup, $planRepo, $planDist, $planSection, $planGpgCheck, $planReminder, $planLogFile);
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
  
  echo '<form action="viewlog.php" method="get" class="is-inline-block">';
	echo '<select name="logfile" class="select-xxlarge">';
	echo "<option value=\"$logfiles[0]\">Repomanager : dernier fichier de log</option>";
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
}

function selectPlanlogs() {
  global $MAIN_LOGS_DIR;

  // On récupère la liste des fichiers de logs en les triant 
  $logfiles = scandir("$MAIN_LOGS_DIR/", SCANDIR_SORT_DESCENDING);
  //$logfiles = glob("$MAIN_LOGS_DIR/repomanager_*.log");

  echo '<form action="viewlog.php" method="get" class="is-inline-block">';
	echo '<select name="logfile" class="select-xxlarge">';
	echo "<option value=\"$logfiles[0]\">Planification : dernier fichier de log</option>";
	foreach($logfiles as $logfile) {
    // on ne souhaite pas afficher les répertoires '..' '.' ni le fichier lastlog.log (déjà affiché en premier ci-dessus) et on souhaite uniquement afficher les fichier commencant par repomanager_
		if (($logfile != "..") AND ($logfile != ".") AND ($logfile != "lastlog.log") AND preg_match('/^plan_/',$logfile)) {
      // Formatage du nom du fichier afin d'afficher quelque chose de plus propre dans la liste
      $logfileDate = exec("echo $logfile | awk -F '_' '{print $2}'");
      $logfileDate = DateTime::createFromFormat('Y-m-d', $logfileDate)->format('d-m-Y');
      $logfileTime = exec("echo $logfile | awk -F '_' '{print $3}' | sed 's/.log//g'");
      $logfileTime = DateTime::createFromFormat('H-i-s', $logfileTime)->format('H:i:s');
			echo "<option value=\"${logfile}\">Planification : traitement du $logfileDate à $logfileTime</option>";
		}
	}
	echo '</select>';
	echo '<button type="submit" class="button-submit-xsmall-blue">Afficher</button>';
  echo '</form>';
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
  $repoFile = file_get_contents($REPOS_LIST);
  $rows = explode("\n", $repoFile);
  $lastRepoName="";
  foreach($rows as $row) {
    if(!empty($row) AND $row !== "[REPOS]") { // on ne traite pas les lignes vides ni la ligne [REPOS] (1ère ligne du fichier)
      $rowData = explode(',', $row);
      if ($OS_FAMILY == "Redhat") {
        $repoName = str_replace(['Name=', '"'], '', $rowData[0]);
        $repoEnv = str_replace(['Env=', '"'], '', $rowData[2]);
        $repoDate = str_replace(['Date=', '"'], '', $rowData[3]);
        $repoDescription = str_replace(['Description=', '"'], '', $rowData[4]);
      }
      if ($OS_FAMILY == "Debian") {
        $repoName = str_replace(['Name=', '"'], '', $rowData[0]);
        $repoDist = str_replace(['Dist=', '"'], '', $rowData[2]);
        $repoSection = str_replace(['Section=', '"'], '', $rowData[3]);
        $repoEnv = str_replace(['Env=', '"'], '', $rowData[4]);
        $repoDate = str_replace(['Date=', '"'], '', $rowData[5]);
        $repoDescription = str_replace(['Description=', '"'], '', $rowData[6]);
      }

      if ($repoName !== $lastRepoName) { // Pour ne pas afficher de valeurs en double dans la liste
        if ($OS_FAMILY == "Redhat") {
          echo "<option value=\"${repoName}\">${repoName}</option>";
        }
        if ($OS_FAMILY == "Debian") {
          echo "<option value=\"${repoName}|${repoDist}|${repoSection}\">${repoName} - ${repoDist} - ${repoSection}</option>";
        }
      }
      $lastRepoName = $repoName;
    }
  }
}

// Liste déroulante des groupes
// Avant d'appeler cette fonction il faut prévoir un select car celle-ci n'affiche que les options
function groupsSelectList() {
  global $GROUPS_CONF;

  echo '<option value="">Sélectionnez un groupe...</option>';
  $repoGroupsFile = file_get_contents($GROUPS_CONF); // récupération de tout le contenu du fichier de groupes
  $repoGroups = shell_exec("grep '^\[@.*\]' $GROUPS_CONF"); // récupération de tous les noms de groupes si il y en a 
  if (!empty($repoGroups)) {
    $repoGroups = preg_split('/\s+/', trim($repoGroups)); // on éclate le résultat précédent car tout a été récupéré sur une seule ligne
    foreach($repoGroups as $groupName) {
      $groupName = str_replace(["[", "]"], "", $groupName); // On retire les [ ] autour du nom du groupe
      echo "<option value=\"${groupName}\">${groupName}</option>";
    }
  }
}
?>