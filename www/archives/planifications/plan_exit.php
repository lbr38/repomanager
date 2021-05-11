<?php
function plan_exit($planID, $planError, $LOGNAME, $plan_msg_error) {
  global $PLANS_DIR;
  global $PLAN_LOGS_DIR;
  global $EMAIL_DEST;
  global $WWW_DIR;
  global $WWW_HOSTNAME;
  global $planDate;
  global $planTime;

  // Pour les planifications, le PID est égal à $planID (combinaison de date, heure et nombre aléatoire)
  $PID = $planID;

  // Création du fichier de log si il n'existe pas
  $PLAN_LOG = "${PLAN_LOGS_DIR}/plan-${planID}.log";
  if (!file_exists("$PLAN_LOG")) {
    touch("$PLAN_LOG");
  }
  // Si des erreurs on été rencontrées, on affiche le message d'erreur
  if ("$planError" != "0") {
    // Suppression des lignes vides dans le message d'erreur si il y en a
    $plan_msg_error = exec("echo \"$plan_msg_error\" | sed '/^$/d'");
  }

  // Ajout de la tâche planifiée dans le fichier de log avec son état (OK ou en erreur)

  // 1. On crée un fichier temporaire dans lequel on met toutes les infos de la planification exécutée
  file_put_contents("$PLAN_LOG", "[${planID}]".PHP_EOL, FILE_APPEND);

  // 2. On ajoute à la suite l'état de la planification (OK ou en Erreur)
  if ("$planError" == "0") {
    file_put_contents("$PLAN_LOG", 'Status="OK"'.PHP_EOL, FILE_APPEND);
  } else {
    file_put_contents("$PLAN_LOG", 'Status="Error"'.PHP_EOL, FILE_APPEND);
    file_put_contents("$PLAN_LOG", "Error=\"$plan_msg_error\"".PHP_EOL, FILE_APPEND);
  }

  // 3. On ajoute la date et l'heure exacte d'exécution de la planification
  file_put_contents("$PLAN_LOG", "Date=\"$planDate\"".PHP_EOL, FILE_APPEND);
  file_put_contents("$PLAN_LOG", "Time=\"$planTime\"".PHP_EOL, FILE_APPEND);

  // 4. On récupère les paramètres de la planification exécutée :
  exec("cat ${PLANS_DIR}/plan-${planID}.conf | grep -v '\[' | grep -v 'Date=' | grep -v 'Time=' >> $PLAN_LOG");
  
  // 5. On ajoute aussi le nom du ficher de log de cette planification :
  file_put_contents("$PLAN_LOG", "Logfile=\"$LOGNAME\"".PHP_EOL, FILE_APPEND);

  // Puis suppression du fichier de tâche planifiée
  unlink("${PLANS_DIR}/plan-${planID}.conf");

  // cloture du fichier de logs
  closeOperation($PID);
  // suppression du fichier pid
  deletePid($PID);

  // Envoi d'un mail si erreur
  if ("$planError" != "0") {
    // template HTML du mail (à coder)
    include("${WWW_DIR}/templates/plan_error_mail.inc.php");

    // Pour envoyer un mail HTML il faut inclure ces headers
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=iso-8859-1';
    mail($EMAIL_DEST, "[ERREUR] Planification Plan-${planID} sur $WWW_HOSTNAME", $template, implode("\r\n", $headers)); 
  }

  exit();
}
?>