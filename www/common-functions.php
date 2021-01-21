<?php

// Fonction de vérification des données envoyées par formulaire
function validateData($data){
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

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
function checkUpdate($BASE_DIR, $VERSION) {
  $GIT_VERSION= exec("grep 'GITHUB_VERSION=' ${BASE_DIR}/cron/github.version | awk -F= '{print $2}' | sed 's/\"//g'");
  
  if (empty($GIT_VERSION)) {
    //echo "version : $GIT_VERSION";
    echo "<p>Erreur lors de la vérification des nouvelles mises à jour</p>";
  } elseif ("$VERSION" !== "$GIT_VERSION") {
    echo "<p>Une nouvelle version est disponible</p>";
  }
}

// explosion du tableau contenant tous les détails d'une planification récupérés dans une variable $plan
function planLogExplode($planId, $PLAN_LOG, $OS_FAMILY) {

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

  // On renvoie un return contenant toutes les valeurs ci-dessus, même celle nulles, ceci afin de s'adapter à toutes les situations et OS
  return array($planStatus, $planError, $planDate, $planTime, $planAction, $planRepoOrGroup, $planGroup, $planRepo, $planDist, $planSection, $planGpgCheck, $planGpgResign, $planReminder);
}

?>