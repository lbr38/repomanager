<?php

namespace Controllers\Service;

use Exception;
use Datetime;

class Planification extends Service
{
    protected $logController;
    private $planController;

    public function __construct()
    {
        $this->logController = new \Controllers\Log\Log();
        $this->planController = new \Controllers\Planification();
    }

    /**
     *  Execute plans if any
     */
    public function planExecute()
    {
        echo 'Executing plans if any...' . PHP_EOL;

        /**
         *  Quit if there was an error while loading general settings
         */
        if (defined('__LOAD_GENERAL_ERROR') and __LOAD_GENERAL_ERROR > 0) {
            $this->logController->log('error', 'Service', 'Cannot execute planification: error while loading general settings');
            return;
        }

        $dateNow = date('Y-m-d');
        $timeNow = date('H:i');
        $minutesNow = date('i');
        $dayNow = strtolower(date('l')); // jour de la semaine (ex : 'monday')
        $planToExec = array();

        /**
         *  Get queued plans
         */
        $plansQueued = $this->planController->listQueue();

        /**
         *  Quit if there is no planification to execute
         */
        if (empty($plansQueued)) {
            return;
        }

        /**
         *  Loop through queued plans
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

            /**
             *  Case where the planification is a one-time planification
             */
            if ($planType == 'plan' and $planDate == $dateNow and $planTime == $timeNow) {
                /**
                 *  Plan Id is added to the array of planifications to execute
                 */
                $planToExec[] = $planId;
            }

            /**
             *  Case where the planification is a regular planification
             */
            if ($planType == 'regular') {
                /**
                 *  Case where the frequency is 'every hour' and the current time is xx:00
                 */
                if ($planFrequency == 'every-hour' and $minutesNow == '00') {
                    /**
                     *  Plan Id is added to the array of planifications to execute
                     */
                    $planToExec[] = $planId;
                }

                /**
                 *  Case where the frequency is 'every day' and the current time is the same as the planification time
                 */
                if ($planFrequency == 'every-day' and $timeNow == $planTime) {
                    /**
                     *  Plan Id is added to the array of planifications to execute
                     */
                    $planToExec[] = $planId;
                }

                /**
                 *  Case where the frequency is 'every week'
                 */
                if ($planFrequency == 'every-week' and !empty($planDay)) {
                    /**
                     *  Loop through the list of days specified by the user
                     */
                    $planDay = explode(',', $planDay);

                    foreach ($planDay as $dayOfWeek) {
                        /**
                         *  If the day and the time correspond then we execute the planification
                         */
                        if ($dayOfWeek == $dayNow and $planTime == $timeNow) {
                            /**
                             *  Plan Id is added to the array of planifications to execute
                             */
                            $planToExec[] = $planId;
                        }
                    }
                }
            }
        }

        /**
         *  Execute planifications
         */
        if (!empty($planToExec)) {
            foreach ($planToExec as $planId) {
                $this->planController->setId($planId);
                try {
                    $this->planController->exec();
                } catch (Exception $e) {
                    $this->logController->log('error', 'Service', 'Error while executing planification: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     *  Send planification reminder
     */
    public function planReminder()
    {
        /**
         *  Exit the function if current time != 00:00
         */
        if (date('H:i') != '00:00') {
            return;
        }

        echo 'Sending plans reminder if any...' . PHP_EOL;

        /**
         *  Quit if there was an error while loading general settings
         */
        if (defined('__LOAD_GENERAL_ERROR') and __LOAD_GENERAL_ERROR > 0) {
            $this->logController->log('error', 'Service', 'Cannot execute planification: error while loading general settings');
            return;
        }

        $dateNow = date('Y-m-d');
        $reminderMessage = '';
        $planToReminder = array();

        /**
         *  Get queued plans
         */
        $plansQueued = $this->planController->listQueue();

        /**
         *  Quit if there is no planification to execute
         */
        if (empty($plansQueued)) {
            return;
        }

        /**
         *  Loop through queued plans
         */
        foreach ($plansQueued as $planQueued) {
            if (!empty($planQueued['Id'])) {
                $planId = $planQueued['Id'];
            }
            if (!empty($planQueued['Type'])) {
                $planType = $planQueued['Type'];
            }
            if (!empty($planQueued['Date'])) {
                $planDate = $planQueued['Date'];
            }
            if (!empty($planQueued['Reminder'])) {
                $planReminder = $planQueued['Reminder'];
            }

            /**
             *  If a planification has a reminder and is a one-time planification
             */
            if (!empty($planReminder) and $planType == 'plan') {
                $planReminder = explode(',', $planReminder);

                /**
                 *  A planification can have 1 or more reminders. For each reminder, we check if its date corresponds to the current date less (-) the number of days of the reminder
                 */
                foreach ($planReminder as $reminder) {
                    $reminderDate = date_create($planDate)->modify("-$reminder days")->format('Y-m-d');

                    if ($reminderDate == $dateNow) {
                        /**
                         *  Plan Id is added to the array of planifications to remind
                         */
                        $planToReminder[] = $planId;
                    }
                }
            }
        }

        /**
         *  Send reminders
         */
        if (!empty($planToReminder)) {
            try {
                foreach ($planToReminder as $planId) {
                    /**
                     *  Generate reminder message
                     */
                    $this->planController->setId($planId);
                    $msg = $this->planController->generateReminders();
                    $reminderMessage .= '<span><b>Planification of the ' . DateTime::createFromFormat('Y-m-d', $this->planController->getDate())->format('d-m-Y') . ' ' . $this->planController->getTime() . ':</b></span><br><span>' . $msg . '</span><br><hr>';
                }

                if (!empty($reminderMessage)) {
                    $mailSubject = '[ Reminder ] Planification(s) to come on ' . WWW_HOSTNAME;
                    $mymail = new \Controllers\Mail($this->planController->getMailRecipient(), $mailSubject, $reminderMessage, 'https://' . WWW_HOSTNAME . '/plans', 'Planifications');
                    $mymail->send();
                }
            } catch (Exception $e) {
                $this->logController->log('error', 'Service', 'Error while sending planification reminder: ' . $e->getMessage());
            }
        }
    }
}
