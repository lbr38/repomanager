<?php

if (empty($argv)) {
  return "Erreur : aucun argument passé";
}
/*
if ($argv[1] == "exec") {
  $ACTION_EXEC = '1';
} else if ($argv[1] == "reminders") {
  $ACTION_REMINDER = '1';
} else {
  return "Erreur : argument ".$argv[1]." inconnu";
}*/

$WWW_DIR = dirname(__FILE__, 2);

// Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
require "${WWW_DIR}/functions/load_common_variables.php";
require "${WWW_DIR}/functions/common-functions.php";
require "${WWW_DIR}/planifications/plan_checkAction.php";
require "${WWW_DIR}/planifications/plan_checkIfRepoOrGroup.php";
require "${WWW_DIR}/planifications/plan_exit.php";
require "${WWW_DIR}/planifications/plan_exec.php";
require "${WWW_DIR}/planifications/plan_reminder.php";

// Date et heure actuelle (à laquelle est exécuté ce script)
//$todayDate = date("Y-m-d");
//$todayTime = date("H\hi");
$todayDate = exec("date +%Y-%m-%d");
$todayTime = exec("date +%Hh%M");


$planFiles = shell_exec("ls -A1 $PLANS_DIR/ | egrep '^plan-'");
if(!empty($planFiles)) {
  $message_rappel = '';
  $planFiles = preg_split('/\s+/', trim($planFiles));
  foreach($planFiles as $planFile) {
    $planId = str_replace(['[', ']'], '', exec("egrep '^\[' $PLANS_DIR/$planFile"));
    $planDate = str_replace(['Date=', '"'], '', exec("egrep '^Date=' $PLANS_DIR/$planFile"));
    $planTime = str_replace(['Time=', '"'], '', exec("egrep '^Time=' $PLANS_DIR/$planFile"));
    $planReminder = str_replace(['Reminder=', '"'], '', exec("egrep '^Reminder=' $PLANS_DIR/$planFile"));

    // Si la date et l'heure de la planification correspond à la date et l'heure d'exécution de ce script ($todayDate et $todayTime) alors on exécute la planification
    if (($argv[1] == "exec-plans") AND ($planDate == $todayDate) AND ($planTime == $todayTime)) {
      plan_exec($planId);
    }

    // Si la date actuelle ($todayDate) correspond à la date de rappel de la planification, alors on envoi un rappel par mail
    if ($argv[1] == "send-reminders" AND !empty($planReminder)) {
      $planReminder = explode(",", $planReminder);

      foreach($planReminder as $reminder) {
        $reminderDate = date_create("$planDate")->modify("-${reminder} days")->format('Y-m-d');

        if ($reminderDate == $todayDate) {
          $msg = generateReminders($planId);
          $message_rappel = "${message_rappel}<span><b>Planification du $planDate à $planTime :</b></span><br><span>- $msg</span><br>";
        }
      }
    }
  }
  if (!empty($message_rappel)) {
    sendMail($message_rappel, $EMAIL_DEST);
  }
}
exit();
?>