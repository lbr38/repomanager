<?php

if (empty($argv)) {
    exit('Erreur : aucun argument passé');
}

define('ROOT', dirname(__FILE__, 2));

/**
 *  Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
 */
require_once(ROOT.'/models/Autoloader.php');
Autoloader::loadAll();
require_once(ROOT."/functions/common-functions.php");

/**
 *  Si il y a eu un pb lors du chargement des constantes alors on quitte
 */
if (defined('__LOAD_GENERAL_ERROR') AND __LOAD_GENERAL_ERROR > 0) {
    exit('Erreur lors du chargement des constantes');
}

/**
 *  Date et heure actuelle (à laquelle est exécuté ce script)
 */
$dateNow = date('Y-m-d');
$timeNow = date('H:i');
$minutesNow = date('i');
$dayNow = strtolower(date('l')); // jour de la semaine (ex : 'monday')

/**
 *  1. On vérifie la présence d'une ou plusieurs planification dans le pool
 */
$plan = new Planification();
$plansQueued = $plan->listQueue();

/**
 *  Si il y a des planifications dans le pool (status = 'queued') alors on traite
 */
if(!empty($plansQueued)) {
    $message_rappel = '';

    /**
     *  On traite chaque planification
     *  On récupère son id, sa date et son heure d'exécution ainsi que les rappels
     */
    foreach($plansQueued as $planQueued) {
        if (!empty($planQueued['Id']))        $planId        = $planQueued['Id'];
        if (!empty($planQueued['Type']))      $planType      = $planQueued['Type'];
        if (!empty($planQueued['Frequency'])) $planFrequency = $planQueued['Frequency'];
        if (!empty($planQueued['Day']))       $planDay       = $planQueued['Day'];
        if (!empty($planQueued['Date']))      $planDate      = $planQueued['Date'];
        if (!empty($planQueued['Time']))      $planTime      = $planQueued['Time'];
        if (!empty($planQueued['Reminder']))  $planReminder  = $planQueued['Reminder'];

        /**
         *  Exécution
         *  Si il s'agit d'une planification classique (planifiée avec une date et heure) ($planType == 'plan')
         *  Si la date et l'heure de la planification correspond à la date et l'heure d'exécution de ce script ($dateNow et $timeNow) alors on exécute la planification
         */
        if ($argv[1] == "exec-plans") {
            if ($planType == "plan" AND  $planDate == $dateNow AND $planTime == $timeNow) {
                /**
                 *  On indique à $plan quel est l'id de la planification et on l'exécute
                 */
                $plan->setId($planId);
                $plan->exec();
            }

            /**
             *  Exécution
             *  Si il s'agit d'une planification récurrente (toutes les heures, tous les jours...) ($planType == 'regular')
             *  
             */
            if ($planType == "regular") {
                /**
                 *  Cas où la fréquence est 'toutes les heures'
                 *  Dans ce cas on exécute la tâche au tout début de l'heure en cours (xx:00 minutes)
                 */
                if ($planFrequency == "every-hour" AND $minutesNow == "00") {
                    /**
                     *  On indique à $plan quel est l'id de la planification et on l'exécute
                     */
                    $plan->setId($planId);
                    $plan->exec();
                }

                /**
                 *  Cas où la fréquence est 'tous les jours'
                 *  Dans ce cas l'utilisateur a également précisé l'heure à laquelle il faut que la planification soit exécutée chaque jour.
                 */
                if ($planFrequency == "every-day" AND $timeNow == $planTime) {
                    /**
                     *  On indique à $plan quel est l'id de la planification et on l'exécute
                     */
                    $plan->setId($planId);
                    $plan->exec();
                }

                /**
                 *  Cas où la fréquence est 'toutes les semaines'
                 *  Dans ce cas l'utilisateur a également précisé un/des jour(s) et une heure d'éxécution
                 */
                if ($planFrequency == "every-week" and !empty($planDay)) {
                    /**
                     *  On parcout la liste de(s) jour(s) spécifié par l'utilisateur
                     */
                    $planDay = explode(',', $planDay);

                    foreach ($planDay as $dayOfWeek) {
                        /**
                         *  Si le jour et l'heure correspond alors on exécute la planif
                         */
                        if (($dayOfWeek == $dayNow) AND ($planTime == $timeNow)) {
                            /**
                             *  On indique à $plan quel est l'id de la planification et on l'exécute
                             */
                            $plan->setId($planId);
                            $plan->exec();
                        }
                    }
                }
            }
        }

        /**
         *  Traitement des rappels
         *  Si la date actuelle ($dateNow) correspond à la date de rappel de la planification, alors on envoi un rappel par mail
         */
        if ($argv[1] == "send-reminders" AND !empty($planReminder) AND $planType == 'plan') {
            $planReminder = explode(",", $planReminder);

            /**
             *  Une planification peut avoir 1 ou plusiers rappels. Pour chaque rappel, on regarde si sa date correspond à la date du jour - le nb de jour du rappel
             */
            foreach($planReminder as $reminder) {
                $reminderDate = date_create($planDate)->modify("-${reminder} days")->format('Y-m-d');

                if ($reminderDate == $dateNow) {
                    /**
                     *  On indique à $plan quel est l'id de la planification et on génère le message de rappel
                     */
                    $plan->setId($planId);
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
        include_once(ROOT."/templates/plan_reminder_mail.inc.php"); // inclu une variable $template contenant le corps du mail avec $message_rappel
        $plan->sendMail("[ RAPPEL ] Planification(s) à venir sur ".WWW_HOSTNAME, $template);
    }
}

exit();
?>