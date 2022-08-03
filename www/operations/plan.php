<?php

/**
 *  On vérifie qu'un paramètre d'exécution a été passé
 */

if (empty($argv)) {
    exit("Erreur : aucun paramètre n'a été passé");
}

define('ROOT', dirname(__FILE__, 2));

require_once(ROOT . '/controllers/Autoloader.php');
\Controllers\Autoloader::loadFromApi();

/**
 *  Si il y a eu un pb lors du chargement des constantes alors on quitte
 */
if (defined('__LOAD_GENERAL_ERROR') and __LOAD_GENERAL_ERROR > 0) {
    exit('Erreur lors du chargement des constantes');
}

/**
 *  Date et heure actuelle (à laquelle est exécuté ce script)
 */
$dateNow = date('Y-m-d');
$timeNow = date('H:i');
$minutesNow = date('i');
$dayNow = strtolower(date('l')); // jour de la semaine (ex : 'monday')
$reminder_msg = '';
$planToExec = array();
$planToReminder = array();

/**
 *  1. On vérifie la présence d'une ou plusieurs planification dans le pool
 */
$plan = new \Controllers\Planification();
$plansQueued = $plan->listQueue();

/**
 *  Cas où on exécute une planification maintenant
 */
if ($argv[1] == "exec-now" and !empty($argv[2])) {
    if (is_numeric($argv[2])) {
        $planToExec[] = $argv[2];
    }
}

/**
 *  Si il y a des planifications dans le pool (status = 'queued') alors on traite
 */
if (!empty($plansQueued)) {
    /**
     *  On traite chaque planification
     *  On récupère son id, sa date et son heure d'exécution ainsi que les rappels
     */
    foreach ($plansQueued as $planQueued) {
        if (!empty($planQueued['Id'])) {
            $planId = $planQueued['Id'];
        }
        if (!empty($planQueued['Type'])) {
            $planType = $planQueued['Type'];
        }
        if (!empty($planQueued['Frequency'])) {
            $planFrequency = $planQueued['Frequency'];
        }
        if (!empty($planQueued['Day'])) {
            $planDay = $planQueued['Day'];
        }
        if (!empty($planQueued['Date'])) {
            $planDate = $planQueued['Date'];
        }
        if (!empty($planQueued['Time'])) {
            $planTime = $planQueued['Time'];
        }
        if (!empty($planQueued['Reminder'])) {
            $planReminder = $planQueued['Reminder'];
        }

        /**
         *  Exécution
         *  Si il s'agit d'une planification classique (planifiée avec une date et heure) ($planType == 'plan')
         *  Si la date et l'heure de la planification correspond à la date et l'heure d'exécution de ce script ($dateNow et $timeNow) alors on exécute la planification
         */

        /**
         *  Cas où on exécute une planification normale (déclenchée par cron)
         */
        if ($argv[1] == "exec") {
            if ($planType == "plan" and $planDate == $dateNow and $planTime == $timeNow) {

                /**
                 *  On place l'Id de la planification dans l'array des planifications à exécuter
                 */
                $planToExec[] = $planId;
            }

            /**
             *  Exécution
             *  Si il s'agit d'une planification récurrente (toutes les heures, tous les jours...) ($planType == 'regular')
             */
            if ($planType == "regular") {
                /**
                 *  Cas où la fréquence est 'toutes les heures'
                 *  Dans ce cas on exécute la tâche au tout début de l'heure en cours (xx:00 minutes)
                 */
                if ($planFrequency == "every-hour" and $minutesNow == "00") {

                    /**
                     *  On place l'Id de la planification dans l'array des planifications à exécuter
                     */
                    $planToExec[] = $planId;
                }

                /**
                 *  Cas où la fréquence est 'tous les jours'
                 *  Dans ce cas l'utilisateur a également précisé l'heure à laquelle il faut que la planification soit exécutée chaque jour.
                 */
                if ($planFrequency == "every-day" and $timeNow == $planTime) {

                    /**
                     *  On place l'Id de la planification dans l'array des planifications à exécuter
                     */
                    $planToExec[] = $planId;
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
                        if (($dayOfWeek == $dayNow) and ($planTime == $timeNow)) {

                            /**
                             *  On place la planification dans l'array des planifications à exécuter
                             */
                            $planToExec[] = $planId;
                        }
                    }
                }
            }
        }

        /**
         *  Traitement des rappels
         *  Si la date actuelle ($dateNow) correspond à la date de rappel de la planification, alors on envoi un rappel par mail
         */
        if ($argv[1] == "send-reminders" and !empty($planReminder) and $planType == 'plan') {
            $planReminder = explode(",", $planReminder);

            /**
             *  Une planification peut avoir 1 ou plusiers rappels. Pour chaque rappel, on regarde si sa date correspond à la date du jour - le nb de jour du rappel
             */
            foreach ($planReminder as $reminder) {
                $reminderDate = date_create($planDate)->modify("-${reminder} days")->format('Y-m-d');

                if ($reminderDate == $dateNow) {
                    /**
                     *  On place l'Id de la planification dans l'array des planifications à rappeler
                     */
                    $planToReminder[] = $planId;
                }
            }
        }
    }
}

/**
 *  Si il y a des planifications à exécuter
 */
if (!empty($planToExec)) {
    foreach ($planToExec as $planId) {
        /**
         *  Exécution de la planification
         */
        $plan->setId($planId);
        $plan->exec();
    }
}

/**
 *  Si il y a des planifications à rappeler
 */
if (!empty($planToReminder)) {
    foreach ($planToReminder as $planId) {
        /**
         *  Génération du message de rappel
         */
        $plan->setId($planId);
        $msg = $plan->generateReminders();
        $reminder_msg .= '<span><b>Planification du ' . DateTime::createFromFormat('Y-m-d', $plan->getDate())->format('d-m-Y') . ' à ' . $plan->getTime() . ":</b></span><br><span>$msg</span><br><hr>";
    }

    if (!empty($reminder_msg)) {
        /**
         *  Inclu une variable $template contenant le corps du mail avec $reminder_msg :
         */
        include_once(ROOT . "/templates/plan_reminder_mail.inc.php");
        $plan->sendMail("[ RAPPEL ] Planification(s) à venir sur " . WWW_HOSTNAME, $template);
    }
}

exit();
