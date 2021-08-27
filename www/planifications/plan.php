<?php

if (empty($argv)) {
    exit('Erreur : aucun argument passé');
}

$WWW_DIR = dirname(__FILE__, 2);

/**
 *  Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
 */
require_once("${WWW_DIR}/functions/load_common_variables.php");
require_once("${WWW_DIR}/functions/common-functions.php");
require_once("${WWW_DIR}/class/Planification.php");

/**
 *  Date et heure actuelle (à laquelle est exécuté ce script)
 */
$todayDate = date('Y-m-d');
$todayTime = date('H:i');

/**
 *  1. On vérifie la présence d'une ou plusieurs planification dans le pool
 */
$plan = new Planification();
$plansQueued = $plan->listQueue();

if(!empty($plansQueued)) {
    $message_rappel = '';

    /**
     *  On traite chaque planification
     *  On récupère son id, sa date et son heure d'exécution ainsi que les rappels
     */
    foreach($plansQueued as $planQueued) {
        $planId       = $planQueued['Id'];
        $planDate     = $planQueued['Date'];
        $planTime     = $planQueued['Time'];
        $planReminder = $planQueued['Reminder'];

        /**
         *  Exécution
         *  Si la date et l'heure de la planification correspond à la date et l'heure d'exécution de ce script ($todayDate et $todayTime) alors on exécute la planification
         */
        if (($argv[1] == "exec-plans") AND ($planDate == $todayDate) AND ($planTime == $todayTime)) {
            /**
             *  On indique à $plan quel est l'id de la planification et on l'exécute
             */
            $plan->id = $planId;
            $plan->exec();
        }

        /**
         *  Traitement des rappels
         *  Si la date actuelle ($todayDate) correspond à la date de rappel de la planification, alors on envoi un rappel par mail
         */
        if ($argv[1] == "send-reminders" AND !empty($planReminder)) {
            $planReminder = explode(",", $planReminder);

            /**
             *  Une planification peut avoir 1 ou plusiers rappels. Pour chaque rappel, on regarde si sa date correspond à la date du jour - le nb de jour du rappel
             */
            foreach($planReminder as $reminder) {
                $reminderDate = date_create($planDate)->modify("-${reminder} days")->format('Y-m-d');

                if ($reminderDate == $todayDate) {
                    /**
                     *  On indique à $plan quel est l'id de la planification et on génère le message de rappel
                     */
                    $plan->id = $planId;
                    $msg = $plan->generateReminders();
                    $message_rappel = "${message_rappel}<span><b>Planification du $planDate à $planTime :</b></span><br><span>- $msg</span><br><hr>";
                }
            }
        }
    }
    
    /**
     *  2. Si il y a des rappels à envoyer, alors on envoi un mail
     */
    if (!empty($message_rappel)) {
        include_once("${WWW_DIR}/templates/plan_reminder_mail.inc.php"); // inclu une variable $template contenant le corps du mail avec $message_rappel
        $plan->sendMail("[ RAPPEL ] Planification(s) à venir sur $WWW_HOSTNAME", $template);
    }
}

exit();
?>